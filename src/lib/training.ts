// Virtual classes: course queries, slot rules, booking, and what happens once a booking is paid.
import { env } from 'cloudflare:workers';
import { and, asc, count, eq, gt, inArray, lt, or, sql } from 'drizzle-orm';
import type { TRAINING_PLATFORMS } from '~/db/schema';
import { PLATFORM_LABELS, TRAINING_RULES } from '~/data/courses';
import { newAccessToken } from '~/lib/access';
import { availableStarts, hourSlots, type SlotRules } from '~/lib/availability';
import { constraintFailed, db, runBatch, schema } from '~/lib/db';
import { emailBody, sendEmail } from '~/lib/email';
import { formatLongDate, formatTime, makeReference, nairobiToUtc } from '~/lib/format';
import { cancelEvent, createMeetEvent, googleConfigured, googleConnection, rescheduleEvent } from '~/lib/google';
import { googleCalendarLink, icsEvent } from '~/lib/ics';
import { formatMoney } from '~/lib/money';
import { emailBusinessDirect } from '~/lib/notify-business';

type WaitUntil = (promise: Promise<unknown>) => void;
const t = schema.trainingBookings;
const HOUR = 3_600_000;

export type Course = typeof schema.trainingCourses.$inferSelect;
export type Booking = typeof schema.trainingBookings.$inferSelect;
export type Platform = (typeof TRAINING_PLATFORMS)[number];

export const activeCourses = () =>
  db.select().from(schema.trainingCourses).where(eq(schema.trainingCourses.isActive, true)).orderBy(asc(schema.trainingCourses.sortOrder));

export async function courseBySlug(slug: string) {
  const [course] = await db.select().from(schema.trainingCourses).where(and(eq(schema.trainingCourses.slug, slug), eq(schema.trainingCourses.isActive, true)));
  return course ?? null;
}

/** Bookings that currently hold their hours: paid ones, and unpaid ones still inside their payment hold. */
const holdsTime = (now: number) => or(inArray(t.status, ['confirmed', 'completed']), and(eq(t.status, 'pending_payment'), gt(t.holdExpiresAt, new Date(now))));

/** Everything availableStarts() needs, read fresh from the database. */
export async function slotRules(now = Date.now()): Promise<SlotRules> {
  const [weekly, blackouts, taken] = await db.batch([
    db.select().from(schema.trainingAvailability),
    db.select({ date: schema.trainingBlackouts.date }).from(schema.trainingBlackouts),
    db.select({ slot: schema.trainingBookingHours.slotStart }).from(schema.trainingBookingHours)
      .innerJoin(t, eq(t.id, schema.trainingBookingHours.bookingId))
      .where(and(holdsTime(now), gt(schema.trainingBookingHours.slotStart, new Date(now)))),
  ]);
  return {
    weekly, now,
    blackouts: new Set(blackouts.map((row) => row.date)),
    taken: new Set(taken.map((row) => row.slot.getTime())),
    leadHours: TRAINING_RULES.leadHours,
    horizonDays: TRAINING_RULES.horizonDays,
  };
}

export class BookingError extends Error {
  constructor(message: string, readonly conflict = false) { super(message); }
}

export interface BookingInput {
  course: Course;
  hours: number;
  date: string;
  time: string;
  platform: Platform;
  name: string;
  email: string;
  phone: string;
  skillLevel: string;
  goals: string;
  userId: string | null;
}

/** Statements that release hours held by unpaid bookings whose payment window has passed. */
const releaseExpiredHolds = (now: number) => [
  db.delete(schema.trainingBookingHours).where(inArray(schema.trainingBookingHours.bookingId,
    db.select({ id: t.id }).from(t).where(and(eq(t.status, 'pending_payment'), lt(t.holdExpiresAt, new Date(now)))))),
  db.update(t).set({ status: 'expired' }).where(and(eq(t.status, 'pending_payment'), lt(t.holdExpiresAt, new Date(now)))),
];

/**
 * Creates an unpaid booking that holds its hours for TRAINING_RULES.holdMinutes. One atomic batch: expired holds
 * are released first, then the booking, its hour rows (UNIQUE slot_start rejects any overlap) and its payment row.
 */
export async function createBooking(input: BookingInput) {
  const now = Date.now();
  if (input.hours < 1 || input.hours > TRAINING_RULES.maxHours) throw new BookingError(`Sessions can be 1 to ${TRAINING_RULES.maxHours} hours.`);
  const starts = availableStarts(input.date, input.hours, await slotRules(now));
  if (!starts.includes(input.time)) throw new BookingError('That time is no longer available. Please choose another.', true);

  const startsAt = nairobiToUtc(input.date, input.time);
  const reference = makeReference('YT');
  const access = await newAccessToken();
  const totalCents = input.course.hourlyRateCents * input.hours;
  const bookingId = sql`(select ${t.id} from ${t} where ${t.reference} = ${reference})`;
  try {
    await runBatch([
      ...releaseExpiredHolds(now),
      db.insert(t).values({
        reference, accessTokenHash: access.hash, userId: input.userId, courseId: input.course.id,
        name: input.name, email: input.email, phone: input.phone, startsAt: new Date(startsAt), hours: input.hours,
        rateCents: input.course.hourlyRateCents, totalCents, platform: input.platform, skillLevel: input.skillLevel, goals: input.goals,
        holdExpiresAt: new Date(now + TRAINING_RULES.holdMinutes * 60_000),
      }),
      ...hourSlots(startsAt, input.hours).map((slot) => db.insert(schema.trainingBookingHours).values({ slotStart: new Date(slot), bookingId })),
      db.insert(schema.payments).values({ trainingBookingId: bookingId, method: 'mpesa', amountCents: totalCents }),
    ]);
  } catch (error) {
    if (constraintFailed(error, 'UNIQUE')) throw new BookingError('That time was just booked by someone else. Please choose another.', true);
    throw error;
  }
  return { reference, accessToken: access.token, totalCents, startsAt };
}

export async function bookingWithCourse(reference: string) {
  const [row] = await db.select({ booking: t, course: schema.trainingCourses }).from(t)
    .innerJoin(schema.trainingCourses, eq(schema.trainingCourses.id, t.courseId)).where(eq(t.reference, reference));
  return row ?? null;
}

async function heldHours(bookingId: number) {
  const [row] = await db.select({ n: count() }).from(schema.trainingBookingHours).where(eq(schema.trainingBookingHours.bookingId, bookingId));
  return row?.n ?? 0;
}

/**
 * Called once a class payment is confirmed. If the payment arrived after the hold lapsed, the booking tries to
 * reclaim its hours; if someone else has taken them, the team is alerted to reschedule or refund.
 */
export async function onTrainingPaid(bookingId: number, waitUntil: WaitUntil) {
  const [booking] = await db.select().from(t).where(eq(t.id, bookingId));
  if (!booking || booking.status === 'confirmed' || booking.status === 'completed') return;
  if ((await heldHours(bookingId)) !== booking.hours) {
    try {
      // Expired holds (possibly this booking's own) are released first, so only a live booking can block us.
      await runBatch([
        ...releaseExpiredHolds(Date.now()),
        db.delete(schema.trainingBookingHours).where(eq(schema.trainingBookingHours.bookingId, bookingId)),
        ...hourSlots(booking.startsAt.getTime(), booking.hours).map((slot) => db.insert(schema.trainingBookingHours).values({ slotStart: new Date(slot), bookingId })),
      ]);
    } catch (error) {
      if (!constraintFailed(error, 'UNIQUE')) throw error;
      console.error('Class paid but its time was taken', { reference: booking.reference });
      waitUntil(emailBusinessDirect(`Action needed: paid class ${booking.reference} lost its time slot`, [
        `${booking.name} (${booking.email}, ${booking.phone}) paid ${formatMoney(booking.totalCents)} after their payment window closed, and the time was booked by someone else.`,
        'Please contact them to choose a new time (or refund), then update the booking in admin.',
      ]));
      return;
    }
  }
  await db.update(t).set({ status: 'confirmed', holdExpiresAt: null }).where(eq(t.id, bookingId));
  waitUntil(afterConfirmed(bookingId).catch((error) => console.error('Post-confirmation tasks failed', { bookingId, error: String(error) })));
}

/** Creates the Google Meet link when possible, then emails the customer and the team. */
export async function afterConfirmed(bookingId: number) {
  let data = await bookingById(bookingId);
  if (!data) return;
  if (data.booking.platform === 'google_meet' && !data.booking.meetingUrl && googleConfigured() && (await googleConnection()).connected) {
    try {
      await createMeetingFor(data.booking, data.course);
      data = (await bookingById(bookingId))!;
    } catch (error) {
      console.error('Google Meet creation failed', { reference: data.booking.reference, error: String(error) });
    }
  }
  const { booking, course } = data;
  await Promise.all([
    sendBookingEmail(booking, course, 'confirmed'),
    emailBusinessDirect(`New paid class: ${course.title}, ${formatLongDate(booking.startsAt)} ${formatTime(booking.startsAt)}`, [
      `${booking.name} <${booking.email}>, ${booking.phone}`,
      `${booking.hours} hour(s) on ${PLATFORM_LABELS[booking.platform]} · ${formatMoney(booking.totalCents)} paid · skill level: ${booking.skillLevel}`,
      `Goals: ${booking.goals}`,
      booking.meetingUrl ? `Meeting link: ${booking.meetingUrl}` : `No meeting link yet: add one in admin → Classes (${new URL(`/admin/training/${booking.reference}`, env.SITE_URL)}).`,
    ]),
  ]);
}

async function bookingById(id: number) {
  const [row] = await db.select({ booking: t, course: schema.trainingCourses }).from(t)
    .innerJoin(schema.trainingCourses, eq(schema.trainingCourses.id, t.courseId)).where(eq(t.id, id));
  return row ?? null;
}

/** Creates the Calendar event with a Meet link and saves it on the booking. */
export async function createMeetingFor(booking: Booking, course: Course) {
  const { eventId, meetUrl } = await createMeetEvent({
    requestId: booking.reference,
    summary: `${course.title} · Yucca Homeware Consultant`,
    description: `Virtual class booked on Yucca Homeware (${booking.reference}).\n\nYour goals: ${booking.goals}`,
    start: booking.startsAt,
    end: new Date(booking.startsAt.getTime() + booking.hours * HOUR),
    attendeeEmail: booking.email,
    attendeeName: booking.name,
  });
  await db.update(t).set({ calendarEventId: eventId, meetingUrl: meetUrl ?? booking.meetingUrl }).where(eq(t.id, booking.id));
  return meetUrl;
}

export const bookingUrl = (reference: string) => new URL(`/classes/booking/${reference}`, env.SITE_URL).toString();

/** Confirmation (with a calendar file) or "your meeting link is ready" email. */
export async function sendBookingEmail(booking: Booking, course: Course, kind: 'confirmed' | 'link' | 'rescheduled') {
  const end = new Date(booking.startsAt.getTime() + booking.hours * HOUR);
  const when = `${formatLongDate(booking.startsAt)} at ${formatTime(booking.startsAt)} (Nairobi time, EAT)`;
  const platform = PLATFORM_LABELS[booking.platform];
  const join = booking.meetingUrl
    ? `Join on ${platform}: ${booking.meetingUrl}`
    : booking.platform === 'whatsapp'
      ? `We’ll call you on WhatsApp video at ${booking.phone} at the start time.`
      : `We’ll email your ${platform} link before the session. It will also appear on your booking page.`;
  const subject = {
    confirmed: `Confirmed: ${course.title} on ${formatLongDate(booking.startsAt)}`,
    link: `Your ${platform} link for ${course.title}`,
    rescheduled: `New time: ${course.title} on ${formatLongDate(booking.startsAt)}`,
  }[kind];
  const summary = `${course.title} (Yucca Homeware Consultant)`;
  return sendEmail({
    to: booking.email,
    subject,
    text: emailBody(
      `Hello ${booking.name},`,
      kind === 'link' ? `Your meeting link is ready for your ${course.title} session on ${when}.`
        : kind === 'rescheduled' ? `Your ${course.title} session has moved to ${when}.`
          : `Thank you for booking. Your ${booking.hours}-hour ${course.title} session is confirmed for ${when}.`,
      join,
      `Add it to Google Calendar: ${googleCalendarLink({ start: booking.startsAt, end, summary, details: join, location: booking.meetingUrl ?? platform })}`,
      `Booking ${booking.reference} · ${formatMoney(booking.totalCents)} paid\nYour booking page: ${bookingUrl(booking.reference)}`,
      'Need to reschedule? Reply to this email or contact us as early as you can before the session.',
    ),
    attachments: [{
      disposition: 'attachment', filename: 'yucca-class.ics', type: 'text/calendar',
      content: icsEvent({ uid: `${booking.reference}@yuccahomeware.com`, start: booking.startsAt, end, summary, description: join, location: booking.meetingUrl ?? platform, url: bookingUrl(booking.reference) }),
    }],
  });
}

/**
 * Moves a booking to a new time (staff action). The new hour rows are claimed in the same batch that frees the
 * old ones, so a clash leaves the booking untouched. The Calendar event and the customer are updated.
 */
export async function rescheduleBooking(booking: Booking, course: Course, date: string, time: string) {
  const startsAt = nairobiToUtc(date, time);
  if (Number.isNaN(startsAt)) throw new BookingError('Choose a valid date and time.');
  try {
    await runBatch([
      ...releaseExpiredHolds(Date.now()),
      db.delete(schema.trainingBookingHours).where(eq(schema.trainingBookingHours.bookingId, booking.id)),
      ...hourSlots(startsAt, booking.hours).map((slot) => db.insert(schema.trainingBookingHours).values({ slotStart: new Date(slot), bookingId: booking.id })),
      db.update(t).set({ startsAt: new Date(startsAt) }).where(eq(t.id, booking.id)),
    ]);
  } catch (error) {
    if (constraintFailed(error, 'UNIQUE')) throw new BookingError('That time overlaps another booking.', true);
    throw error;
  }
  const moved = { ...booking, startsAt: new Date(startsAt) };
  if (booking.calendarEventId && googleConfigured()) {
    await rescheduleEvent(booking.calendarEventId, moved.startsAt, new Date(startsAt + booking.hours * HOUR))
      .catch((error) => console.error('Calendar reschedule failed', { reference: booking.reference, error: String(error) }));
  }
  if (booking.status === 'confirmed') await sendBookingEmail(moved, course, 'rescheduled');
}

/** Cancels a booking, frees its time and cancels the Calendar event (Google tells the attendee). */
export async function cancelBooking(booking: Booking) {
  await runBatch([
    db.delete(schema.trainingBookingHours).where(eq(schema.trainingBookingHours.bookingId, booking.id)),
    db.update(t).set({ status: 'cancelled', holdExpiresAt: null }).where(eq(t.id, booking.id)),
  ]);
  if (booking.calendarEventId && googleConfigured()) {
    await cancelEvent(booking.calendarEventId).catch((error) => console.error('Calendar cancel failed', { reference: booking.reference, error: String(error) }));
  }
}

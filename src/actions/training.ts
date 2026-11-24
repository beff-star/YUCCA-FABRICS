import { ActionError, defineAction } from 'astro:actions';
import { z } from 'astro/zod';
import { TRAINING_PLATFORMS } from '~/db/schema';
import { PLATFORM_LABELS, SKILL_LEVELS, TRAINING_RULES } from '~/data/courses';
import { grantAccess } from '~/lib/access';
import { bookableDates } from '~/lib/availability';
import { db, schema } from '~/lib/db';
import { emailBody, sendEmail } from '~/lib/email';
import { formatClock, formatIsoDate, formatLongDate, formatTime } from '~/lib/format';
import { formatMoney } from '~/lib/money';
import { emailBusiness } from '~/lib/notify-business';
import { allow, clientIp } from '~/lib/throttle';
import { BookingError, courseBySlug, createBooking, slotRules } from '~/lib/training';
import { description, email, name, optional, optionalText, phone } from './fields';

const hours = z.coerce.number().int().min(1).max(TRAINING_RULES.maxHours);

export const training = {
  /** Open start times for the next weeks, for a session of the given length. */
  trainingSlots: defineAction({
    input: z.object({ hours }),
    async handler(input) {
      const dates = bookableDates(input.hours, await slotRules());
      return dates.map(({ date, starts }) => ({ date, label: formatIsoDate(date), starts: starts.map((time) => ({ time, label: formatClock(time) })) }));
    },
  }),

  trainingBook: defineAction({
    accept: 'form',
    input: z.object({
      course_slug: z.string().regex(/^[a-z0-9-]{3,120}$/, 'Choose a course.'),
      hours,
      slot: z.string().regex(/^\d{4}-\d{2}-\d{2}T\d{2}:00$/, 'Choose a date and start time.'),
      platform: z.enum(TRAINING_PLATFORMS, 'Choose how you’d like to meet.'),
      name, email, phone,
      skill_level: z.enum(SKILL_LEVELS, 'Tell us your experience level.'),
      goals: description,
      agree: z.literal('on', 'Please confirm you’ve read the booking guidelines.'),
    }),
    async handler(input, context) {
      const course = await courseBySlug(input.course_slug);
      if (!course) throw new ActionError({ code: 'BAD_REQUEST', message: 'That course isn’t available. Please choose another.' });
      if (!(await allow(`book:${clientIp(context.request)}`, 6, 15 * 60))) {
        throw new ActionError({ code: 'TOO_MANY_REQUESTS', message: 'You’ve started several bookings already. Please wait a few minutes and try again.' });
      }
      const [date, time] = input.slot.split('T') as [string, string];
      let booking: Awaited<ReturnType<typeof createBooking>>;
      try {
        booking = await createBooking({
          course, hours: input.hours, date, time, platform: input.platform, name: input.name, email: input.email,
          phone: input.phone, skillLevel: input.skill_level, goals: input.goals, userId: context.locals.user?.id ?? null,
        });
      } catch (error) {
        if (error instanceof BookingError) throw new ActionError({ code: error.conflict ? 'CONFLICT' : 'BAD_REQUEST', message: error.message });
        throw error;
      }
      grantAccess(context.cookies, booking.reference, booking.accessToken);
      const link = new URL(`/classes/booking/${booking.reference}?t=${booking.accessToken}`, context.url).toString();
      const when = new Date(booking.startsAt);
      context.locals.cfContext.waitUntil(sendEmail({
        to: input.email,
        subject: `Complete your booking: ${course.title}`,
        text: emailBody(
          `Hello ${input.name},`,
          `We’re holding ${formatLongDate(when)} at ${formatTime(when)} (Nairobi time) for your ${input.hours}-hour ${course.title} session on ${PLATFORM_LABELS[input.platform]}.`,
          `Pay ${formatMoney(booking.totalCents)} with M-Pesa within ${TRAINING_RULES.holdMinutes} minutes to confirm it: ${link}`,
          'If the time is released before you pay, you can choose another slot on the same page.',
        ),
      }));
      return { message: 'Your time is held. Complete payment to confirm it.', redirect: `/classes/booking/${booking.reference}` };
    },
  }),

  groupTraining: defineAction({
    accept: 'form',
    input: z.object({
      organisation: optionalText(150),
      name, email, phone,
      group_size: z.coerce.number().int().min(2, 'Group training is for two or more people.').max(500),
      course: optionalText(200),
      dates: optionalText(300),
      message: optional(z.string().trim().max(5000)),
    }),
    async handler(input, { request, locals }) {
      if (!(await allow(`form:group:${clientIp(request)}`, 5, 600))) {
        throw new ActionError({ code: 'TOO_MANY_REQUESTS', message: 'You’ve sent a few requests already. Please wait a few minutes and try again.' });
      }
      const details = [
        input.organisation && `Organisation: ${input.organisation}`,
        `Group size: ${input.group_size}`,
        input.course && `Course: ${input.course}`,
        input.dates && `Preferred dates: ${input.dates}`,
        input.message,
      ].filter(Boolean).join('\n');
      await db.insert(schema.contacts).values({ name: input.name, email: input.email, phone: input.phone, subject: `Group training enquiry (${input.group_size} people)`, message: details });
      await emailBusiness(locals, `Group training enquiry: ${input.group_size} people`, [`From: ${input.name} <${input.email}>, ${input.phone}`, details], input.email);
      return { message: 'Thank you! We’ll prepare a quote for your group and get back to you shortly.' };
    },
  }),
};

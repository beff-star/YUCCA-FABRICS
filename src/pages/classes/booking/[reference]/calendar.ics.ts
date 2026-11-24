import type { APIRoute } from 'astro';
import { PLATFORM_LABELS } from '~/data/courses';
import { canAccess } from '~/lib/access';
import { icsEvent } from '~/lib/ics';
import { bookingUrl, bookingWithCourse } from '~/lib/training';

/** Calendar file for a confirmed class (same access rules as the booking page). */
export const GET: APIRoute = async ({ params, locals, cookies }) => {
  const data = await bookingWithCourse(params.reference ?? '');
  if (!data || data.booking.status !== 'confirmed'
    || !(await canAccess({ reference: data.booking.reference, userId: data.booking.userId, accessTokenHash: data.booking.accessTokenHash }, locals, cookies))) {
    return new Response('Not found', { status: 404 });
  }
  const { booking, course } = data;
  const location = booking.meetingUrl ?? PLATFORM_LABELS[booking.platform];
  const body = icsEvent({
    uid: `${booking.reference}@yuccahomeware.com`,
    start: booking.startsAt,
    end: new Date(booking.startsAt.getTime() + booking.hours * 3_600_000),
    summary: `${course.title} (Yucca Homeware Consultant)`,
    description: `Booking ${booking.reference}. ${booking.meetingUrl ? `Join: ${booking.meetingUrl}` : `Platform: ${location}`}`,
    location,
    url: bookingUrl(booking.reference),
  });
  return new Response(body, {
    headers: {
      'Content-Type': 'text/calendar; charset=utf-8',
      'Content-Disposition': `attachment; filename="yucca-class-${booking.reference}.ics"`,
      'Cache-Control': 'private, no-store',
    },
  });
};

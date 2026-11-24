// Minimal iCalendar (RFC 5545) file so customers can add a class to any calendar app.

const stamp = (date: Date) => date.toISOString().replace(/[-:]/g, '').replace(/\.\d{3}/, '');
/** Escapes text values (backslash, comma, semicolon, newline) as RFC 5545 requires. */
const text = (value: string) => value.replace(/\\/g, '\\\\').replace(/[,;]/g, (c) => `\\${c}`).replace(/\r?\n/g, '\\n');
/** Folds lines longer than 75 octets, as the spec requires. */
const fold = (line: string) => line.match(/.{1,73}/g)!.join('\r\n ');

export function icsEvent(input: { uid: string; start: Date; end: Date; summary: string; description: string; location?: string; url?: string }): string {
  const lines = [
    'BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//Yucca Homeware//Classes//EN', 'CALSCALE:GREGORIAN', 'METHOD:PUBLISH',
    'BEGIN:VEVENT',
    `UID:${input.uid}`,
    `DTSTAMP:${stamp(new Date())}`,
    `DTSTART:${stamp(input.start)}`,
    `DTEND:${stamp(input.end)}`,
    `SUMMARY:${text(input.summary)}`,
    `DESCRIPTION:${text(input.description)}`,
    ...(input.location ? [`LOCATION:${text(input.location)}`] : []),
    ...(input.url ? [`URL:${input.url}`] : []),
    'BEGIN:VALARM', 'TRIGGER:-PT30M', 'ACTION:DISPLAY', `DESCRIPTION:${text(input.summary)}`, 'END:VALARM',
    'END:VEVENT', 'END:VCALENDAR',
  ];
  return `${lines.map(fold).join('\r\n')}\r\n`;
}

/** Google Calendar "add event" link (no API needed). */
export function googleCalendarLink(input: { start: Date; end: Date; summary: string; details: string; location?: string }) {
  const params = new URLSearchParams({ action: 'TEMPLATE', text: input.summary, dates: `${stamp(input.start)}/${stamp(input.end)}`, details: input.details, ctz: 'Africa/Nairobi' });
  if (input.location) params.set('location', input.location);
  return `https://calendar.google.com/calendar/render?${params}`;
}

// Pure scheduling rules for virtual classes (no database access, so they are unit-tested directly).
// All wall-clock times are Nairobi time; timestamps are UTC milliseconds.
import { nairobiToUtc, todayInNairobi } from '~/lib/format';

export interface WeeklyHours { weekday: number; startTime: string; endTime: string }

const HOUR = 3_600_000;
const DAY = 24 * HOUR;
const minutes = (hhmm: string) => { const [h = 0, m = 0] = hhmm.split(':').map(Number); return h * 60 + m; };
const clock = (mins: number) => `${String(Math.floor(mins / 60)).padStart(2, '0')}:${String(mins % 60).padStart(2, '0')}`;

/** Weekday (0 = Sunday) of a 'YYYY-MM-DD' calendar date. */
export const weekdayOf = (date: string) => new Date(`${date}T12:00:00Z`).getUTCDay();

/** Adds days to a 'YYYY-MM-DD' date. */
export const addDays = (date: string, days: number) => new Date(Date.parse(`${date}T12:00:00Z`) + days * DAY).toISOString().slice(0, 10);

/** The UTC start of each hour a booking occupies. */
export const hourSlots = (startsAt: number, hours: number) => Array.from({ length: hours }, (_, i) => startsAt + i * HOUR);

export interface SlotRules {
  weekly: WeeklyHours[];
  blackouts: Set<string>;
  /** UTC starts of hours already held by other bookings. */
  taken: Set<number>;
  now: number;
  leadHours: number;
  horizonDays: number;
}

/**
 * Whole-hour start times on `date` where a session of `hours` fits inside the opening hours, every hour is free,
 * and the start is at least `leadHours` from now and within the booking horizon.
 */
export function availableStarts(date: string, hours: number, rules: SlotRules): string[] {
  if (rules.blackouts.has(date)) return [];
  const today = todayInNairobi(rules.now);
  if (date < today || date > addDays(today, rules.horizonDays)) return [];
  const earliest = rules.now + rules.leadHours * HOUR;
  const starts = new Set<string>();
  for (const window of rules.weekly.filter((w) => w.weekday === weekdayOf(date))) {
    const open = Math.ceil(minutes(window.startTime) / 60) * 60; // sessions start on the hour
    for (let start = open; start + hours * 60 <= minutes(window.endTime); start += 60) {
      const startsAt = nairobiToUtc(date, clock(start));
      if (startsAt < earliest) continue;
      if (hourSlots(startsAt, hours).every((slot) => !rules.taken.has(slot))) starts.add(clock(start));
    }
  }
  return [...starts].sort();
}

/** The next dates (up to `limit`) that have at least one free start for a session of `hours`. */
export function bookableDates(hours: number, rules: SlotRules, limit = 21): { date: string; starts: string[] }[] {
  const result: { date: string; starts: string[] }[] = [];
  const today = todayInNairobi(rules.now);
  for (let offset = 0; offset <= rules.horizonDays && result.length < limit; offset++) {
    const date = addDays(today, offset);
    const starts = availableStarts(date, hours, rules);
    if (starts.length > 0) result.push({ date, starts });
  }
  return result;
}

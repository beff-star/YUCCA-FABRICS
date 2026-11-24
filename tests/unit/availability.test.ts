import { describe, expect, it } from 'vitest';
import { addDays, availableStarts, bookableDates, hourSlots, weekdayOf, type SlotRules } from '~/lib/availability';
import { nairobiToUtc } from '~/lib/format';

// Monday 23 Nov 2026, 08:00 in Nairobi.
const NOW = nairobiToUtc('2026-11-23', '08:00');
const rules = (overrides: Partial<SlotRules> = {}): SlotRules => ({
  weekly: [1, 2, 3, 4, 5, 6].map((weekday) => ({ weekday, startTime: '09:00', endTime: '17:00' })),
  blackouts: new Set(),
  taken: new Set(),
  now: NOW,
  leadHours: 24,
  horizonDays: 60,
  ...overrides,
});

describe('calendar helpers', () => {
  it('knows the weekday and adds days', () => {
    expect(weekdayOf('2026-11-23')).toBe(1);
    expect(addDays('2026-11-30', 2)).toBe('2026-12-02');
  });

  it('lists each booked hour', () => {
    const start = nairobiToUtc('2026-11-25', '10:00');
    expect(hourSlots(start, 3)).toEqual([start, start + 3_600_000, start + 7_200_000]);
  });
});

describe('availableStarts', () => {
  it('offers every hour that fits a 1-hour session inside opening hours', () => {
    expect(availableStarts('2026-11-25', 1, rules())).toEqual(['09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00']);
  });

  it('only offers starts where a longer session still ends by closing time', () => {
    expect(availableStarts('2026-11-25', 3, rules())).toEqual(['09:00', '10:00', '11:00', '12:00', '13:00', '14:00']);
  });

  it('respects the 24-hour lead time', () => {
    // Tomorrow before 08:00 is inside the lead time.
    expect(availableStarts('2026-11-24', 1, rules({ now: nairobiToUtc('2026-11-23', '10:30') }))[0]).toBe('11:00');
    expect(availableStarts('2026-11-23', 1, rules())).toEqual([]);
  });

  it('skips Sundays, blackout days and dates beyond the horizon', () => {
    expect(availableStarts('2026-11-29', 1, rules())).toEqual([]);
    expect(availableStarts('2026-11-25', 1, rules({ blackouts: new Set(['2026-11-25']) }))).toEqual([]);
    expect(availableStarts('2027-03-01', 1, rules())).toEqual([]);
  });

  it('never overlaps a taken hour', () => {
    const taken = new Set([nairobiToUtc('2026-11-25', '11:00')]);
    // A 2-hour session can't start at 10:00 (runs into 11:00) or 11:00.
    expect(availableStarts('2026-11-25', 2, rules({ taken }))).toEqual(['09:00', '12:00', '13:00', '14:00', '15:00']);
  });
});

describe('bookableDates', () => {
  it('returns upcoming dates with free starts, skipping closed days', () => {
    const dates = bookableDates(1, rules(), 6).map((d) => d.date);
    expect(dates).toEqual(['2026-11-24', '2026-11-25', '2026-11-26', '2026-11-27', '2026-11-28', '2026-11-30']);
  });
});

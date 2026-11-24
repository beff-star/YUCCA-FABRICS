import { and, asc, eq, gte, lt, sql } from 'drizzle-orm';
import { addDays } from '~/lib/availability';
import { db, schema } from '~/lib/db';
import { todayInNairobi } from '~/lib/format';

/** Reads ?from=&to= (Nairobi dates), defaulting to the start of this month until today. */
export function reportRange(params: URLSearchParams) {
  const today = todayInNairobi();
  const valid = (v: string | null) => (v && /^\d{4}-\d{2}-\d{2}$/.test(v) ? v : null);
  let from = valid(params.get('from')) ?? `${today.slice(0, 8)}01`;
  let to = valid(params.get('to')) ?? today;
  if (from > to) [from, to] = [to, from];
  return { from, to };
}

const day = (column: unknown) => sql<string>`strftime('%Y-%m-%d', ${column} / 1000, 'unixepoch', '+3 hours')`;
const bounds = (from: string, to: string) => [new Date(`${from}T00:00:00+03:00`), new Date(`${addDays(to, 1)}T00:00:00+03:00`)] as const;

/** Per-day orders, paid order revenue, pending orders and paid class revenue for a date range. */
export async function dailyReport(from: string, to: string) {
  const [start, end] = bounds(from, to);
  const o = schema.orders;
  const p = schema.payments;
  const [orders, classes] = await db.batch([
    db.select({
      day: day(o.createdAt), orders: sql<number>`count(*)`,
      paid: sql<number>`coalesce(sum(case when ${o.paymentStatus} = 'paid' then ${o.totalCents} end), 0)`,
      pending: sql<number>`sum(case when ${o.status} = 'pending' then 1 else 0 end)`,
    }).from(o).where(and(gte(o.createdAt, start), lt(o.createdAt, end))).groupBy(day(o.createdAt)).orderBy(asc(day(o.createdAt))),
    db.select({ day: day(p.updatedAt), classes: sql<number>`sum(${p.amountCents})` }).from(p)
      .where(and(eq(p.status, 'paid'), sql`${p.trainingBookingId} is not null`, gte(p.updatedAt, start), lt(p.updatedAt, end)))
      .groupBy(day(p.updatedAt)),
  ]);
  const days = new Map<string, { day: string; orders: number; paid: number; pending: number; classes: number }>();
  for (const row of orders) days.set(row.day, { day: row.day, orders: Number(row.orders), paid: Number(row.paid), pending: Number(row.pending), classes: 0 });
  for (const row of classes) {
    const entry = days.get(row.day) ?? { day: row.day, orders: 0, paid: 0, pending: 0, classes: 0 };
    entry.classes = Number(row.classes);
    days.set(row.day, entry);
  }
  return [...days.values()].sort((a, b) => a.day.localeCompare(b.day));
}

import type { APIRoute } from 'astro';
import { csvResponse } from '~/lib/csv';
import { dailyReport, reportRange } from '~/lib/reports';

/** Daily report as CSV (admin only, enforced by the middleware for /admin/*). */
export const GET: APIRoute = async ({ url }) => {
  const { from, to } = reportRange(url.searchParams);
  const rows = await dailyReport(from, to);
  const kes = (cents: number) => (cents / 100).toFixed(2);
  return csvResponse(`yucca-report-${from}-to-${to}.csv`, ['Date', 'Orders', 'Paid order revenue (KES)', 'Paid class revenue (KES)', 'Pending orders'],
    rows.map((row) => [row.day, row.orders, kes(row.paid), kes(row.classes), row.pending]));
};

import type { APIRoute } from 'astro';
import { asc, eq } from 'drizzle-orm';
import { csvResponse } from '~/lib/csv';
import { db, schema } from '~/lib/db';

/** Active newsletter subscribers (admin only, enforced by the middleware for /admin/*). */
export const GET: APIRoute = async () => {
  const rows = await db.select().from(schema.newsletter).where(eq(schema.newsletter.isActive, true)).orderBy(asc(schema.newsletter.createdAt));
  return csvResponse('yucca-newsletter.csv', ['Email', 'Subscribed'], rows.map((row) => [row.email, row.createdAt.toISOString()]));
};

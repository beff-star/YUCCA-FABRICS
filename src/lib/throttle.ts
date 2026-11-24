import { sql } from 'drizzle-orm';
import { db, schema } from '~/lib/db';

/**
 * Fixed-window rate limit stored in D1 (shared by every Worker instance). Returns true when the action
 * is allowed and counts it. Used for sign-in, password reset, contact and booking forms.
 */
export async function allow(key: string, max: number, windowSeconds: number): Promise<boolean> {
  const now = Date.now();
  const windowStart = now - windowSeconds * 1000;
  const [row] = await db.insert(schema.rateLimit)
    .values({ id: crypto.randomUUID(), key, count: 1, lastRequest: now })
    .onConflictDoUpdate({
      target: schema.rateLimit.key,
      set: {
        // A new window starts once the previous one has expired.
        count: sql`case when ${schema.rateLimit.lastRequest} < ${windowStart} then 1 else ${schema.rateLimit.count} + 1 end`,
        lastRequest: sql`case when ${schema.rateLimit.lastRequest} < ${windowStart} then ${now} else ${schema.rateLimit.lastRequest} end`,
      },
    })
    .returning({ count: schema.rateLimit.count });
  return (row?.count ?? 1) <= max;
}

/** Clears a limit, e.g. after a successful sign-in. */
export async function reset(key: string) {
  await db.delete(schema.rateLimit).where(sql`${schema.rateLimit.key} = ${key}`);
}

export const clientIp = (request: Request) => request.headers.get('cf-connecting-ip') ?? 'local';

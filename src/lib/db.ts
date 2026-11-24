import { env } from 'cloudflare:workers';
import type { BatchItem } from 'drizzle-orm/batch';
import { drizzle } from 'drizzle-orm/d1';
import * as schema from '~/db/schema';

export const db = drizzle(env.DB, { schema });
export type Db = typeof db;
export { schema };

/** True when a query failed on a UNIQUE/PRIMARY KEY/CHECK constraint (D1 reports it in the message or cause). */
export function constraintFailed(error: unknown, kind: 'UNIQUE' | 'CHECK' | 'any' = 'any'): boolean {
  const text = `${String(error)} ${String((error as { cause?: unknown })?.cause ?? '')}`;
  if (kind === 'any') return /constraint failed/i.test(text);
  return new RegExp(`${kind} constraint failed`, 'i').test(text);
}

/**
 * Runs statements atomically: D1 batches succeed or fail as a whole. Accepts a dynamic list
 * (drizzle's batch() wants a non-empty tuple type).
 */
export async function runBatch(statements: BatchItem<'sqlite'>[]) {
  if (statements.length === 0) return [];
  return db.batch(statements as [BatchItem<'sqlite'>, ...BatchItem<'sqlite'>[]]);
}

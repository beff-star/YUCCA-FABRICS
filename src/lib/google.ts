// Google Calendar + Meet for virtual classes. The business connects its Google account once from
// /admin/settings/google (OAuth, scope calendar.events); the refresh token is stored AES-GCM encrypted.
// Plain fetch against the REST API (the Node client libraries don't run on Workers).
import { env } from 'cloudflare:workers';
import { eq } from 'drizzle-orm';
import { decryptSecret, encryptSecret } from '~/lib/crypto';
import { db, schema } from '~/lib/db';

const TOKEN_KEY = 'google_refresh_token';
const SCOPE = 'https://www.googleapis.com/auth/calendar.events';
const CALENDAR = 'https://www.googleapis.com/calendar/v3/calendars/primary/events';

export class GoogleError extends Error {}

export const googleConfigured = () => Boolean(env.GOOGLE_CLIENT_ID && env.GOOGLE_CLIENT_SECRET && env.TOKEN_ENCRYPTION_KEY);
export const googleRedirectUri = () => new URL('/api/google/oauth-callback', env.SITE_URL).toString();

export function googleAuthUrl(state: string) {
  const params = new URLSearchParams({
    client_id: env.GOOGLE_CLIENT_ID, redirect_uri: googleRedirectUri(), response_type: 'code', scope: SCOPE,
    access_type: 'offline', prompt: 'consent', include_granted_scopes: 'true', state,
  });
  return `https://accounts.google.com/o/oauth2/v2/auth?${params}`;
}

async function tokenRequest(body: Record<string, string>) {
  const response = await fetch('https://oauth2.googleapis.com/token', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams({ client_id: env.GOOGLE_CLIENT_ID, client_secret: env.GOOGLE_CLIENT_SECRET, ...body }),
    signal: AbortSignal.timeout(15_000),
  });
  const json = await response.json().catch(() => ({})) as { access_token?: string; refresh_token?: string; error?: string; error_description?: string };
  if (!response.ok) throw new GoogleError(`Google token error: ${json.error ?? response.status} ${json.error_description ?? ''}`.trim());
  return json;
}

/** Finishes the OAuth flow and stores the (encrypted) refresh token. */
export async function connectGoogle(code: string) {
  const tokens = await tokenRequest({ code, grant_type: 'authorization_code', redirect_uri: googleRedirectUri() });
  if (!tokens.refresh_token) throw new GoogleError('Google did not return a refresh token. Remove the app’s access in your Google account and connect again.');
  const valueEncrypted = await encryptSecret(tokens.refresh_token);
  await db.insert(schema.integrations).values({ key: TOKEN_KEY, valueEncrypted })
    .onConflictDoUpdate({ target: schema.integrations.key, set: { valueEncrypted, updatedAt: new Date() } });
}

export async function disconnectGoogle() {
  await db.delete(schema.integrations).where(eq(schema.integrations.key, TOKEN_KEY));
}

export async function googleConnection(): Promise<{ connected: boolean; since: Date | null }> {
  const [row] = await db.select({ updatedAt: schema.integrations.updatedAt }).from(schema.integrations).where(eq(schema.integrations.key, TOKEN_KEY));
  return { connected: Boolean(row), since: row?.updatedAt ?? null };
}

async function accessToken(): Promise<string> {
  const [row] = await db.select().from(schema.integrations).where(eq(schema.integrations.key, TOKEN_KEY));
  if (!row) throw new GoogleError('Google Calendar is not connected.');
  const tokens = await tokenRequest({ grant_type: 'refresh_token', refresh_token: await decryptSecret(row.valueEncrypted) });
  if (!tokens.access_token) throw new GoogleError('Google did not return an access token.');
  return tokens.access_token;
}

interface CalendarEvent {
  id: string;
  hangoutLink?: string;
  conferenceData?: { createRequest?: { status?: { statusCode?: string } }; entryPoints?: { entryPointType: string; uri: string }[] };
}

const meetLink = (event: CalendarEvent) =>
  event.hangoutLink ?? event.conferenceData?.entryPoints?.find((point) => point.entryPointType === 'video')?.uri ?? null;

async function calendarFetch(token: string, url: string, init: RequestInit = {}) {
  const response = await fetch(url, { ...init, headers: { Authorization: `Bearer ${token}`, 'Content-Type': 'application/json' }, signal: AbortSignal.timeout(15_000) });
  if (!response.ok) throw new GoogleError(`Google Calendar error ${response.status}: ${(await response.text()).slice(0, 300)}`);
  return response.status === 204 ? null : await response.json() as CalendarEvent;
}

/**
 * Creates the calendar event with a Google Meet link and invites the customer (Google emails the invitation).
 * Meet links are generated asynchronously, so the event is re-read briefly until the link is ready.
 */
export async function createMeetEvent(input: { requestId: string; summary: string; description: string; start: Date; end: Date; attendeeEmail: string; attendeeName: string }) {
  const token = await accessToken();
  const created = await calendarFetch(token, `${CALENDAR}?conferenceDataVersion=1&sendUpdates=all`, {
    method: 'POST',
    body: JSON.stringify({
      summary: input.summary,
      description: input.description,
      start: { dateTime: input.start.toISOString(), timeZone: 'Africa/Nairobi' },
      end: { dateTime: input.end.toISOString(), timeZone: 'Africa/Nairobi' },
      attendees: [{ email: input.attendeeEmail, displayName: input.attendeeName }],
      conferenceData: { createRequest: { requestId: input.requestId, conferenceSolutionKey: { type: 'hangoutsMeet' } } },
      reminders: { useDefault: true },
    }),
  });
  let event = created!;
  for (let attempt = 0; attempt < 4 && !meetLink(event); attempt++) {
    await new Promise((resolve) => setTimeout(resolve, 1500));
    event = (await calendarFetch(token, `${CALENDAR}/${encodeURIComponent(event.id)}`))!;
  }
  return { eventId: event.id, meetUrl: meetLink(event) };
}

/** Moves an event to a new time (Google re-notifies the attendee). */
export async function rescheduleEvent(eventId: string, start: Date, end: Date) {
  const token = await accessToken();
  await calendarFetch(token, `${CALENDAR}/${encodeURIComponent(eventId)}?sendUpdates=all`, {
    method: 'PATCH',
    body: JSON.stringify({ start: { dateTime: start.toISOString(), timeZone: 'Africa/Nairobi' }, end: { dateTime: end.toISOString(), timeZone: 'Africa/Nairobi' } }),
  });
}

/** Cancels an event and tells the attendee. */
export async function cancelEvent(eventId: string) {
  const token = await accessToken();
  await calendarFetch(token, `${CALENDAR}/${encodeURIComponent(eventId)}?sendUpdates=all`, { method: 'DELETE' });
}

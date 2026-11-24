import { eq } from 'drizzle-orm';
import { db, schema } from '~/lib/db';
import { emailBody, sendEmail } from '~/lib/email';
import { DEFAULT_EMAIL, getSettings, setting } from '~/lib/settings';

type Lines = (string | null | undefined | false)[];
const body = (lines: Lines) => emailBody(...lines.filter((line): line is string => Boolean(line)));

/** Emails the business inbox (the business_email setting) during a request. */
export async function emailBusiness(locals: App.Locals, subject: string, lines: Lines, replyTo?: string) {
  const settings = await getSettings(locals);
  return sendEmail({ to: setting(settings, 'business_email', DEFAULT_EMAIL), subject, text: body(lines), replyTo });
}

/** Same, for background tasks that have no request (e.g. after an M-Pesa callback). */
export async function emailBusinessDirect(subject: string, lines: Lines) {
  const [row] = await db.select({ value: schema.websiteSettings.value }).from(schema.websiteSettings).where(eq(schema.websiteSettings.key, 'business_email'));
  return sendEmail({ to: row?.value.trim() || DEFAULT_EMAIL, subject, text: body(lines) });
}

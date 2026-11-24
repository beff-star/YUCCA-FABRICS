import { env } from 'cloudflare:workers';
import { betterAuth } from 'better-auth';
import { drizzleAdapter } from 'better-auth/adapters/drizzle';
import { db, schema } from '~/lib/db';
import { emailBody, sendEmail } from '~/lib/email';

/**
 * Better Auth handles password hashing, sessions and reset tokens. Every call goes through `auth.api` from our
 * own pages (no public /api/auth routes), so the forms work without JavaScript and share our validation.
 */
export const auth = betterAuth({
  baseURL: env.SITE_URL,
  secret: env.BETTER_AUTH_SECRET,
  database: drizzleAdapter(db, {
    provider: 'sqlite',
    transaction: false, // D1 has no interactive transactions
    schema: { user: schema.user, session: schema.session, account: schema.account, verification: schema.verification },
  }),
  emailAndPassword: {
    enabled: true,
    minPasswordLength: 10,
    maxPasswordLength: 128,
    resetPasswordTokenExpiresIn: 60 * 60,
    revokeSessionsOnPasswordReset: true,
    async sendResetPassword({ user, token }) {
      const link = new URL(`/auth/reset-password?token=${encodeURIComponent(token)}`, env.SITE_URL).toString();
      await sendEmail({
        to: user.email,
        subject: 'Reset your Yucca Homeware password',
        text: emailBody(
          `Hello ${user.name},`,
          `We received a request to reset your password. Use this link within one hour:\n${link}`,
          'If you did not ask for this, you can ignore this email; your password will not change.',
        ),
      });
    },
  },
  user: {
    additionalFields: {
      username: { type: 'string', required: false },
      phone: { type: 'string', required: false },
      role: { type: 'string', required: false, defaultValue: 'customer', input: false },
      status: { type: 'string', required: false, defaultValue: 'active', input: false },
    },
  },
  session: {
    expiresIn: 60 * 60 * 24 * 7,
    updateAge: 60 * 60 * 24,
    // A signed cookie caches the session for 5 minutes, saving a D1 read on most requests.
    cookieCache: { enabled: true, maxAge: 5 * 60 },
  },
  advanced: {
    cookiePrefix: 'yf',
    ipAddress: { ipAddressHeaders: ['cf-connecting-ip'] },
  },
  rateLimit: { enabled: false }, // sign-in and reset are throttled in our handlers (lib/throttle.ts)
  telemetry: { enabled: false },
});

export type SessionUser = {
  id: string;
  name: string;
  email: string;
  phone: string | null;
  username: string | null;
  role: 'customer' | 'admin';
};

/** Copies Better Auth's Set-Cookie headers onto a redirect, e.g. after sign-in or sign-out. */
export function redirectWithHeaders(location: string, headers: Headers | null | undefined, status = 303): Response {
  const response = new Response(null, { status, headers: { Location: location } });
  for (const cookie of headers?.getSetCookie() ?? []) response.headers.append('Set-Cookie', cookie);
  return response;
}

/** Password rule shared by registration and reset: 10+ characters with upper and lower case and a number. */
export function passwordProblem(password: string): string | null {
  if (password.length < 10 || password.length > 128) return 'Use a password of at least 10 characters.';
  if (!/[a-z]/.test(password) || !/[A-Z]/.test(password) || !/\d/.test(password)) {
    return 'Use at least one uppercase letter, one lowercase letter and one number in your password.';
  }
  return null;
}

/** Only allow same-site relative redirects after sign-in. */
export const safeNext = (next: string | null | undefined, fallback: string) =>
  next && next.startsWith('/') && !next.startsWith('//') ? next : fallback;

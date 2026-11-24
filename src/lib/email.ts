import { env } from 'cloudflare:workers';

export interface OutgoingEmail {
  to: string;
  subject: string;
  text: string;
  html?: string;
  replyTo?: string;
  attachments?: EmailAttachment[];
}

/**
 * Sends a transactional email through Cloudflare Email Service. Failures are logged and reported as `false`
 * rather than thrown: an order or booking must never fail because an email could not be sent.
 * Swap the body of this function to change provider.
 */
export async function sendEmail(message: OutgoingEmail): Promise<boolean> {
  try {
    await env.EMAIL.send({
      from: { name: 'Yucca Homeware', email: env.EMAIL_FROM },
      to: message.to,
      subject: message.subject,
      text: message.text,
      html: message.html,
      replyTo: message.replyTo,
      attachments: message.attachments,
    });
    return true;
  } catch (error) {
    console.error('Email failed', { to: message.to, subject: message.subject, error: String(error) });
    return false;
  }
}

/** Plain-text email body with the business sign-off. */
export const emailBody = (...paragraphs: string[]) => `${paragraphs.join('\n\n')}\n\nWarm regards,\nYucca Homeware\n${env.SITE_URL}`;

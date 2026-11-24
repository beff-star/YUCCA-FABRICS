import { and, count, countDistinct, eq, isNotNull } from 'drizzle-orm';
import { db, schema } from '~/lib/db';

export type Settings = Record<string, string>;

/** Loads all website settings once per request (cached on locals). */
export async function getSettings(locals: App.Locals): Promise<Settings> {
  if (!locals.settings) {
    const rows = await db.select().from(schema.websiteSettings);
    locals.settings = Object.fromEntries(rows.map((row) => [row.key, row.value]));
  }
  return locals.settings;
}

export const setting = (settings: Settings, key: string, fallback = '') => settings[key]?.trim() || fallback;

export const DEFAULT_PHONE = '+254 700 000 000';
export const DEFAULT_EMAIL = 'hello@yuccahomeware.com';
export const DEFAULT_ADDRESS = 'Nairobi, Kenya';

/** "tel:+254700000000" */
export const telHref = (phone: string) => `tel:${phone.replace(/[^0-9+]/g, '')}`;

/** The admin's WhatsApp URL, or wa.me with the business phone in international format. */
export function whatsappUrl(settings: Settings, text?: string): string {
  const configured = setting(settings, 'whatsapp_url');
  const base = configured || `https://wa.me/${setting(settings, 'business_phone', DEFAULT_PHONE).replace(/\D/g, '').replace(/^0/, '254')}`;
  return text ? `${base}${base.includes('?') ? '&' : '?'}text=${encodeURIComponent(text)}` : base;
}

export interface SocialLink { label: string; icon: string; url: string }

/** Blank settings fall back to each platform's home page as a placeholder. */
export function socialLinks(settings: Settings): SocialLink[] {
  return [
    { label: 'Instagram', icon: 'fa6-brands:instagram', url: setting(settings, 'instagram_url', 'https://www.instagram.com/') },
    { label: 'Facebook', icon: 'fa6-brands:facebook-f', url: setting(settings, 'facebook_url', 'https://www.facebook.com/') },
    { label: 'TikTok', icon: 'fa6-brands:tiktok', url: setting(settings, 'tiktok_url', 'https://www.tiktok.com/') },
    { label: 'WhatsApp', icon: 'fa6-brands:whatsapp', url: whatsappUrl(settings) },
  ];
}

export interface SiteNumber { value: string; label: string }

/**
 * Honest statistics: live counts from the database plus milestones only when an admin has entered them.
 * Never hard-code figures here.
 */
export async function siteNumbers(settings: Settings): Promise<SiteNumber[]> {
  const [[fabrics], [materials], [services]] = await db.batch([
    db.select({ n: count() }).from(schema.products).where(eq(schema.products.isActive, true)),
    db.select({ n: countDistinct(schema.products.material) }).from(schema.products)
      .where(and(eq(schema.products.isActive, true), isNotNull(schema.products.material))),
    db.select({ n: count() }).from(schema.services).where(eq(schema.services.isActive, true)),
  ]);
  const milestone = (key: string) => (/^\d+$/.test(setting(settings, key)) ? setting(settings, key) : '');
  return [
    { value: milestone('years_experience') && `${milestone('years_experience')}+`, label: 'Years of experience' },
    { value: milestone('completed_projects') && `${milestone('completed_projects')}+`, label: 'Projects completed' },
    { value: milestone('verified_customers') && `${milestone('verified_customers')}+`, label: 'Happy customers' },
    { value: fabrics?.n ? String(fabrics.n) : '', label: 'Fabrics in the collection' },
    { value: materials?.n ? String(materials.n) : '', label: 'Fabric types' },
    { value: services?.n ? String(services.n) : '', label: 'Studio services' },
  ].filter((item) => item.value !== '');
}

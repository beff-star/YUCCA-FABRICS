import { env } from 'cloudflare:workers';
import { DEFAULT_ADDRESS, DEFAULT_PHONE, setting, type Settings } from '~/lib/settings';

export const BUSINESS_NAME = 'Yucca Homeware';
/** Other names people search for: the former shop name and the training brand. */
export const BUSINESS_ALT_NAMES = ['Yucca Fabrics', 'Yucca Homeware Consultant'];
export const BUSINESS_SUMMARY =
  'Upholstery fabrics by the metre, furniture reupholstery, interior design consultations and virtual classes in Nairobi, Kenya.';

/** The canonical origin: the production domain, or the request origin when SITE_URL is unset. */
export const siteOrigin = (requestUrl: URL) => env.SITE_URL || requestUrl.origin;
export const absoluteUrl = (path: string, origin: string) => new URL(path, origin).toString();
export const businessId = (origin: string) => absoluteUrl('/#business', origin);

/** Shortens text to search-snippet length, cutting at a word boundary. */
export function metaDescription(text: string, max = 158): string {
  const clean = text.replace(/\s+/g, ' ').trim();
  if (clean.length <= max) return clean;
  const cut = clean.slice(0, max - 1);
  const lastSpace = cut.lastIndexOf(' ');
  return `${(lastSpace > max / 2 ? cut.slice(0, lastSpace) : cut).replace(/[\s,.;:–-]+$/, '')}…`;
}

/**
 * LocalBusiness JSON-LD for the shop in Nairobi County. Phone, street address and social profiles are only
 * included once an admin has replaced the placeholders, so search engines never receive made-up details.
 */
export function businessJsonLd(settings: Settings, origin: string) {
  const phone = setting(settings, 'business_phone');
  const email = setting(settings, 'business_email');
  const address = setting(settings, 'business_address');
  const sameAs = ['instagram_url', 'facebook_url', 'tiktok_url', 'whatsapp_url'].map((key) => setting(settings, key)).filter(Boolean);
  return {
    '@context': 'https://schema.org',
    '@type': ['HomeGoodsStore', 'HomeAndConstructionBusiness'],
    '@id': businessId(origin),
    name: BUSINESS_NAME,
    alternateName: BUSINESS_ALT_NAMES,
    description: BUSINESS_SUMMARY,
    url: absoluteUrl('/', origin),
    logo: absoluteUrl('/images/yucca-homeware-logo.jpeg', origin),
    image: absoluteUrl('/images/fabrics/santa-boucle.jpeg', origin),
    telephone: phone && phone !== DEFAULT_PHONE ? phone : undefined,
    email: email || undefined,
    address: {
      '@type': 'PostalAddress',
      streetAddress: address && address !== DEFAULT_ADDRESS ? address : undefined,
      addressLocality: 'Nairobi',
      addressRegion: 'Nairobi County',
      addressCountry: 'KE',
    },
    areaServed: [{ '@type': 'City', name: 'Nairobi' }, { '@type': 'Country', name: 'Kenya' }],
    currenciesAccepted: 'KES',
    paymentAccepted: 'M-Pesa, Cash, Bank transfer',
    sameAs: sameAs.length ? sameAs : undefined,
  };
}

export const websiteJsonLd = (origin: string) => ({
  '@context': 'https://schema.org', '@type': 'WebSite', '@id': absoluteUrl('/#website', origin),
  name: BUSINESS_NAME, alternateName: BUSINESS_ALT_NAMES, url: absoluteUrl('/', origin), inLanguage: 'en-KE',
  publisher: { '@id': businessId(origin) },
});

/** BreadcrumbList from [label, path] pairs, starting after "Home". */
export function breadcrumbJsonLd(origin: string, trail: [name: string, path: string][]) {
  return {
    '@context': 'https://schema.org', '@type': 'BreadcrumbList',
    itemListElement: [['Home', '/'] as const, ...trail].map(([name, path], index) => ({
      '@type': 'ListItem', position: index + 1, name, item: absoluteUrl(path, origin),
    })),
  };
}

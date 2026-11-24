// Presentation for the services stored in the `services` table, keyed by slug. The database keeps the
// admin-editable name, price, duration and short description; this file adds the look and longer copy.
// A service added in admin without an entry here falls back to `default`. Images: public/images/services/.
// Themes pair a background with a text colour that passes WCAG AA (mustard uses dark text).
export interface ServiceDetails {
  theme: { bg: string; text: 'light' | 'dark' };
  icon: string;
  image: string;
  imageAlt: string;
  tagline: string;
  story: string;
  includes: string[];
  ideal: string;
  /** Pre-selects this option on the consultation form for consultation services. */
  consultationType?: string;
}

const details: Record<string, ServiceDetails> = {
  'furniture-reupholstery': {
    theme: { bg: '#1F5B43', text: 'light' },
    icon: 'fa6-solid:couch',
    image: 'reupholstery-tufted-corduroy.jpg',
    imageAlt: 'Close-up of a green corduroy seat with tufted buttons',
    tagline: 'Keep the frame you love. Change everything else.',
    story: 'Good furniture is often worth more than it looks. We take a tired sofa, armchair or dining chair back to its frame, put right what has worn out underneath, and dress it in a fabric chosen for your room and the way you use it.',
    includes: ['Help choosing from our bouclé, linen, velvet and leather collection', 'Old covers stripped and the frame checked', 'Foam, webbing and padding renewed where needed', 'Neat finishing: piping, buttons and trims'],
    ideal: 'Sofas, armchairs, dining and accent chairs, headboards and ottomans.',
  },
  'cushion-renewal': {
    theme: { bg: '#E3A82B', text: 'dark' },
    icon: 'fa6-solid:mattress-pillow',
    image: 'cushion-renewal-mustard-cushion.jpg',
    imageAlt: 'A mustard cushion resting on an upholstered armchair',
    tagline: 'Plump, fresh and comfortable again.',
    story: 'Flat seats and faded covers make a whole sofa look older than it is. New covers and fresh inners bring back the shape and comfort, often for far less than replacing the piece.',
    includes: ['New covers in a fabric of your choice', 'Flattened foam and inners replaced', 'Zips, piping and tailored corners', 'Matching scatter cushions to pull a room together'],
    ideal: 'Sofa seat and back cushions, scatter cushions, window seats and bench pads.',
  },
  'fabric-colour-consultation': {
    theme: { bg: '#16646E', text: 'light' },
    icon: 'fa6-solid:swatchbook',
    image: 'fabric-consultation-samples.jpg',
    imageAlt: 'Hands comparing colourful fabric samples on a table',
    tagline: 'Find the fabric that fits your life.',
    story: 'Choosing from a small square on a screen is hard. We bring the samples, talk through how each one feels, wears and cleans, and help you match colour and texture to the room you already have.',
    includes: ['Compare textures and weaves side by side', 'Colours coordinated with your walls, floors and furniture', 'Practical advice for children, pets and busy rooms', 'A shortlist you can order from with confidence'],
    ideal: 'Anyone choosing fabric for a sofa, curtains, cushions or a full room.',
    consultationType: 'Fabric and colour selection',
  },
  'room-styling-consultation': {
    theme: { bg: '#A9461E', text: 'light' },
    icon: 'fa6-solid:compass-drafting',
    image: 'room-styling-swatches-plans.jpg',
    imageAlt: 'A hand holding colour swatches over room plans',
    tagline: 'A clear plan for a room that works.',
    story: 'Sometimes the furniture is fine but the room still doesn’t feel right. In one focused session we look at layout, colour, fabric and the finishing touches, and leave you with practical next steps.',
    includes: ['Furniture layout ideas for flow and comfort', 'A colour palette with fabric pairings', 'Curtains, cushions, rugs and lighting suggestions', 'Priorities to suit your budget and timeline'],
    ideal: 'Living rooms, bedrooms, home offices and small commercial spaces.',
    consultationType: 'Residential interior design',
  },
};

const fallback: ServiceDetails = {
  theme: { bg: '#3F5320', text: 'light' },
  icon: 'fa6-solid:wand-magic-sparkles',
  image: 'upholstery-workshop-chair.jpg',
  imageAlt: 'An upholstered chair in a workshop',
  tagline: '',
  story: '',
  includes: [],
  ideal: '',
};

export const serviceDetails = (slug: string): ServiceDetails => details[slug] ?? fallback;

export const serviceImage = (file: string) => `/images/services/${file}`;

/** Services in these categories are booked through the consultation form rather than quoted. */
export const CONSULTATION_CATEGORY_SLUGS = ['fabric-sourcing', 'interior-styling'];

export const CONSULTATION_TYPES = [
  'Residential interior design',
  'Commercial design',
  'Living room styling',
  'Bedroom styling',
  'Office design',
  'Fabric and colour selection',
] as const;

export const PROPERTY_TYPES = ['Home', 'Apartment', 'Office', 'Hospitality / retail', 'Other'] as const;

/** Consultation start times, every 30 minutes from 9:00 AM to 5:00 PM (Nairobi time). */
export const CONSULTATION_TIMES = Array.from({ length: 17 }, (_, i) =>
  `${String(9 + Math.floor(i / 2)).padStart(2, '0')}:${i % 2 ? '30' : '00'}`);

export const BUDGET_RANGES =['Under KES 50,000', 'KES 50,000–150,000', 'KES 150,000–500,000', 'KES 500,000+'] as const;

// Builds seed/seed.sql: categories, the 18-fabric catalogue (placeholder prices), services, the starter journal,
// the 7 virtual training courses and default settings. Every statement is INSERT OR IGNORE keyed on a unique
// slug/key, so re-running never overwrites edits made in admin.
// Usage: pnpm db:seed:local   (or: node scripts/seed.ts && wrangler d1 execute yucca-db --remote --file=seed/seed.sql)
import { mkdirSync, writeFileSync } from 'node:fs';
import { courseSeeds } from '../src/data/courses.ts';
import { fabricCollection, fabricImage } from '../src/data/fabric-collection.ts';
import { journalCategories, journalPosts } from './journal-posts.ts';

type SqlValue = string | number | boolean | null;
const q = (value: SqlValue): string => {
  if (value === null) return 'NULL';
  if (typeof value === 'boolean') return value ? '1' : '0';
  if (typeof value === 'number') return String(value);
  return `'${value.replaceAll("'", "''")}'`;
};
const insert = (table: string, row: Record<string, SqlValue | { sql: string }>) => {
  const columns = Object.keys(row).join(', ');
  const values = Object.values(row).map((v) => (v !== null && typeof v === 'object' ? v.sql : q(v))).join(', ');
  return `INSERT OR IGNORE INTO ${table} (${columns}) VALUES (${values});`;
};
const cents = (kes: number) => Math.round(kes * 100);

const statements: string[] = [];

/* Shop categories */
const shopCategories = [
  ['Upholstery Fabrics', 'upholstery-fabrics', 'Hard-wearing textiles for the furniture you love.'],
  ['Curtain Fabrics', 'curtain-fabrics', 'Beautiful drapery fabrics to soften the light.'],
  ['Cushions & Accessories', 'cushions-accessories', 'Finishing touches for a considered room.'],
  ['Outdoor Fabrics', 'outdoor-fabrics', 'Durable, colourfast fabrics for outdoor living.'],
  ['Home Décor', 'home-decor', 'Objects and details that make a space yours.'],
];
for (const [name, slug, description] of shopCategories) statements.push(insert('categories', { name, slug, description }));

/* Fabric catalogue. Placeholder prices (KES per metre) by type: replace them in Admin → Products. */
const placeholderPrices: Record<string, number> = { Linen: 1850, 'Bouclé': 2400, Velvet: 2600, 'Linen & velvet': 2600, Fur: 2800, 'Inverted leather': 3200 };
const featured = new Set(['santa-boucle.jpeg', 'bella-fur.jpeg', 'coco-boucle.jpeg', 'harmony-linen.jpeg']);
const fabricDetails: Record<string, [colour: string, description: string]> = {
  'santa-boucle.jpeg': ['Charcoal & ivory', 'A herringbone bouclé in charcoal and ivory with a soft, nubby handle. Pictured on a sectional sofa and lounge chair; a striking choice for statement chairs, sofas and cushions.'],
  'bella-fur.jpeg': ['Forest green', 'A deep forest-green faux fur with a ribbed, channelled pile. Shown on a lounge chair and sofa for a plush, tactile look.'],
  'costa-linen.jpeg': ['Charcoal', 'A salt-and-pepper charcoal linen with a slubby, tweed-like texture. Pictured on a deep sofa with matching cushions.'],
  'new-cubra-linen.jpeg': ['Slate blue', 'A slate-blue linen with a fine, even weave, shown on a sofa and a curved tub chair.'],
  'coco-boucle.jpeg': ['Mustard', 'A warm mustard bouclé with a crinkled, cloud-like surface, pictured on a modular sofa, armchair and throw.'],
  'dona-linen.jpeg': ['Graphite', 'A deep graphite linen with a soft, matte finish, shown on a low modular sofa.'],
  'liza-boucle.jpeg': ['Rust', 'A rust-orange bouclé with a dense, curly texture, pictured on a curved sofa and armchairs.'],
  'cito-linen.jpeg': ['Crimson', 'A rich crimson linen with a textured weave, shown on a sectional sofa and cushions.'],
  'prox-velvet.jpeg': ['Denim blue', 'A denim-blue velvet with a crushed, textured pile, pictured on a channel-stitched sofa.'],
  'karla-linen.jpeg': ['Sand', 'A sand-beige linen with a nubby, natural-looking weave, shown on rounded sofas and cushions.'],
  'lenen-linen.jpeg': ['Burnt orange', 'A burnt-orange linen with a soft, pebbled texture, pictured on a sofa and a timber-framed armchair.'],
  'rock-velvet.jpeg': ['Camel', 'A camel-toned velvet with a gentle sheen, shown on a deep sofa and throw.'],
  'soho-inverted-leather.jpeg': ['Stone taupe', 'A stone-taupe inverted leather with a crackled, embossed surface, pictured on dining chairs and a sofa.'],
  'harmony-linen.jpeg': ['Navy blue', 'A navy linen with a looped, bouclé-like texture, shown on a chaise sofa and swivel armchair.'],
  'ferrari-linen-velvet.jpeg': ['Chocolate', 'A chocolate-brown linen and velvet fabric with a soft, brushed finish, pictured on a modular sofa.'],
  'new-tokyo-linen.jpeg': ['Mocha', 'A mocha linen with a fine, flecked weave, shown on a corner sofa and cushions.'],
  'verola-linen.jpeg': ['Caramel', 'A caramel linen with an open basket weave, pictured on a sofa and cushions.'],
  'koya-linen.jpeg': ['Dusty rose', 'A dusty-rose linen with a two-tone woven texture, shown on a corner sofa and armchair.'],
};
const upholsteryCategory = { sql: "(SELECT id FROM categories WHERE slug = 'upholstery-fabrics')" };
// Like PHP's ucwords: only letters after the start or a space (\b is not Unicode-aware, so "Bouclé" → "BouclÉ").
const titleCase = (s: string) => s.replace(/(^|\s)(\p{L})/gu, (_, space: string, letter: string) => space + letter.toUpperCase());
fabricCollection.forEach((fabric, index) => {
  const details = fabricDetails[fabric.image];
  const price = placeholderPrices[fabric.type];
  if (!details || price === undefined) throw new Error(`No product details for ${fabric.image}`);
  statements.push(insert('products', {
    category_id: upholsteryCategory,
    name: `${fabric.name} ${titleCase(fabric.type)}`,
    slug: fabric.image.replace(/\.[a-z]+$/, ''),
    sku: `YF-${2001 + index}`,
    description: `${details[1]} Sold by the metre; ask us for a swatch or a quote to upholster your piece.`,
    price_cents: cents(price),
    stock: 40,
    unit: 'metre',
    colour: details[0],
    material: fabric.type,
    image: fabricImage(fabric.image),
    is_featured: featured.has(fabric.image),
  }));
});
statements.push(insert('products', {
  category_id: upholsteryCategory, name: 'Terracotta Weave', slug: 'terracotta-weave', sku: 'YF-1002',
  description: 'A richly textured woven upholstery cloth that brings warmth to a favourite armchair.',
  price_cents: cents(2200), stock: 68, unit: 'metre', colour: 'Terracotta', material: 'Cotton blend',
  image: fabricImage('rust-tweed-texture.jpeg'), is_featured: true,
}));

/* Services */
const serviceCategories = [
  ['Upholstery & Restoration', 'upholstery-restoration'],
  ['Fabric Sourcing', 'fabric-sourcing'],
  ['Interior Styling', 'interior-styling'],
  ['Commercial Projects', 'commercial-projects'],
];
for (const [name, slug] of serviceCategories) statements.push(insert('service_categories', { name, slug }));
const serviceRows: [category: string, name: string, slug: string, description: string, priceKes: number | null, duration: string][] = [
  ['upholstery-restoration', 'Furniture reupholstery', 'furniture-reupholstery', 'Give a much-loved sofa or chair a new life with thoughtful fabric selection and skilled upholstery.', null, 'Quoted per project'],
  ['upholstery-restoration', 'Cushion renewal', 'cushion-renewal', 'Refresh cushion covers and inner foam for a more comfortable, considered finish.', null, '3–7 working days'],
  ['fabric-sourcing', 'Fabric & colour consultation', 'fabric-colour-consultation', 'Get help finding the right texture, colour and performance for the way you live.', 2500, '60 minutes'],
  ['interior-styling', 'Room styling consultation', 'room-styling-consultation', 'A practical design session for furniture layout, fabrics, colour and finishing touches.', 5000, '90 minutes'],
];
for (const [category, name, slug, description, price, duration] of serviceRows) {
  statements.push(insert('services', {
    category_id: { sql: `(SELECT id FROM service_categories WHERE slug = ${q(category)})` },
    name, slug, description, starting_price_cents: price === null ? null : cents(price), duration,
  }));
}

/* Journal and gallery categories */
for (const [slug, name] of Object.entries(journalCategories)) statements.push(insert('blog_categories', { name, slug }));
for (const [name, slug] of [['Upholstery', 'upholstery'], ['Interiors', 'interiors'], ['Fabrics', 'fabrics']]) {
  statements.push(insert('gallery_categories', { name, slug }));
}
// Earlier entries get later timestamps, so the first post is the featured (newest) one.
journalPosts.forEach((post, index) => {
  statements.push(insert('blog_posts', {
    category_id: { sql: `(SELECT id FROM blog_categories WHERE slug = ${q(post.category)})` },
    title: post.title, slug: post.slug, excerpt: post.excerpt, body: post.body, image: post.image,
    status: 'published',
    published_at: { sql: `(cast(unixepoch('subsecond') * 1000 as integer) - ${index * 60_000})` },
  }));
});

/* Virtual training courses and default weekly hours (Mon–Sat, 9 AM–5 PM Nairobi time) */
courseSeeds.forEach((course, index) => {
  statements.push(insert('training_courses', {
    slug: course.slug, title: course.title, track: course.track, level: course.level,
    hourly_rate_cents: cents(course.hourlyRate), summary: course.summary,
    outcomes: JSON.stringify(course.outcomes), who_for: course.whoFor, sort_order: index + 1,
  }));
});
statements.push("WITH RECURSIVE days(d) AS (SELECT 1 UNION ALL SELECT d + 1 FROM days WHERE d < 6) INSERT INTO training_availability (weekday, start_time, end_time) SELECT d, '09:00', '17:00' FROM days WHERE NOT EXISTS (SELECT 1 FROM training_availability);");

/* Settings. Milestone numbers stay blank until the owner verifies them in admin. */
const settings: Record<string, string> = {
  business_address: 'Nairobi, Kenya',
  business_phone: '+254 700 000 000',
  business_email: 'hello@yuccahomeware.com',
  business_hours: 'Monday–Saturday, 9:00 AM–5:00 PM',
  bank_details: '',
  years_experience: '',
  verified_customers: '',
  completed_projects: '',
};
for (const [key, value] of Object.entries(settings)) statements.push(insert('website_settings', { setting_key: key, setting_value: value }));

mkdirSync('seed', { recursive: true });
writeFileSync('seed/seed.sql', `${statements.join('\n')}\n`);
console.log(`Wrote seed/seed.sql (${statements.length} statements).`);

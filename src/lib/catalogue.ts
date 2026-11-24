import { and, asc, desc, eq, gt, isNotNull, ne, notInArray, sql, type SQL, type SQLWrapper } from 'drizzle-orm';
import { db, schema } from '~/lib/db';

const p = schema.products;
const c = schema.categories;

/** Average approved rating, or null when a product has no reviews yet. */
const averageRating = sql<number | null>`(select avg(r.rating) from product_reviews r where r.product_id = ${p.id} and r.status = 'approved')`;
const unitsSold = sql<number>`(select coalesce(sum(oi.quantity), 0) from order_items oi join orders o on o.id = oi.order_id where oi.product_id = ${p.id} and o.status <> 'cancelled')`;

/** Everything a product card needs. */
const cardFields = {
  id: p.id, slug: p.slug, name: p.name, image: p.image, priceCents: p.priceCents, salePriceCents: p.salePriceCents,
  stock: p.stock, unit: p.unit, colour: p.colour, material: p.material, categoryName: c.name, averageRating,
};
export type ProductCardData = {
  id: number; slug: string; name: string; image: string | null; priceCents: number; salePriceCents: number | null;
  stock: number; unit: string; colour: string | null; material: string | null; categoryName: string | null;
  averageRating: number | null;
};

const cards = () => db.select(cardFields).from(p).leftJoin(c, eq(c.id, p.categoryId));
const active = eq(p.isActive, true);

/** SQL LIKE '%term%' with the wildcards in the term escaped. */
export const contains = (column: SQLWrapper, term: string) =>
  sql`${column} like ${`%${term.replace(/[\\%_]/g, '\\$&')}%`} escape '\\'`;

export const featuredProducts = (limit = 4) =>
  cards().where(and(active, eq(p.isFeatured, true))).orderBy(desc(p.createdAt), desc(p.id)).limit(limit);

export const latestProducts = (excludeIds: number[], limit = 4) =>
  cards().where(and(active, excludeIds.length ? notInArray(p.id, excludeIds) : undefined)).orderBy(desc(p.createdAt), desc(p.id)).limit(limit);

export const bestSellers = (limit = 4) =>
  cards().where(and(active, gt(unitsSold, 0))).orderBy(desc(unitsSold)).limit(limit);

export const relatedProducts = (categoryId: number | null, productId: number, limit = 4) =>
  categoryId === null ? Promise.resolve([]) : cards().where(and(active, eq(p.categoryId, categoryId), ne(p.id, productId))).limit(limit);

export async function productBySlug(slug: string) {
  const [product] = await db.select({ ...cardFields, sku: p.sku, description: p.description, categoryId: p.categoryId })
    .from(p).leftJoin(c, eq(c.id, p.categoryId)).where(and(active, eq(p.slug, slug)));
  return product ?? null;
}

export const activeCategories = () =>
  db.select({ id: c.id, name: c.name, slug: c.slug, image: c.image }).from(c).where(eq(c.isActive, true)).orderBy(asc(c.name));

export const SHOP_SORTS = { newest: 'Newest', popularity: 'Popularity', rating: 'Rating', name: 'Name' } as const;
export type ShopSort = keyof typeof SHOP_SORTS;

export interface ShopFilters {
  q: string;
  category: number;
  colour: string;
  material: string;
  availability: '' | 'in_stock' | 'out_of_stock';
  rating: number;
  sort: ShopSort;
  page: number;
}

/** Reads and normalises the shop's query string; anything unexpected falls back to "no filter". */
export function parseShopFilters(params: URLSearchParams): ShopFilters {
  const int = (name: string) => { const n = Number.parseInt(params.get(name) ?? '', 10); return Number.isFinite(n) ? n : 0; };
  const sort = params.get('sort') ?? 'newest';
  const availability = params.get('availability') ?? '';
  const rating = int('rating');
  return {
    q: (params.get('q') ?? '').trim().slice(0, 80),
    category: Math.max(0, int('category')),
    colour: (params.get('colour') ?? '').trim(),
    material: (params.get('material') ?? '').trim(),
    availability: availability === 'in_stock' || availability === 'out_of_stock' ? availability : '',
    rating: rating >= 1 && rating <= 5 ? rating : 0,
    sort: sort in SHOP_SORTS ? (sort as ShopSort) : 'newest',
    page: Math.max(1, int('page')),
  };
}

export const SHOP_PAGE_SIZE = 12;

export async function searchShop(filters: ShopFilters) {
  const conditions: (SQL | undefined)[] = [active];
  if (filters.q) conditions.push(sql`(${contains(p.name, filters.q)} or ${contains(p.description, filters.q)} or ${contains(p.sku, filters.q)})`);
  if (filters.category) conditions.push(eq(p.categoryId, filters.category));
  if (filters.colour) conditions.push(eq(p.colour, filters.colour));
  if (filters.material) conditions.push(eq(p.material, filters.material));
  if (filters.availability === 'in_stock') conditions.push(gt(p.stock, 0));
  if (filters.availability === 'out_of_stock') conditions.push(eq(p.stock, 0));
  if (filters.rating) conditions.push(sql`${averageRating} >= ${filters.rating}`);
  const where = and(...conditions);

  const [{ total } = { total: 0 }] = await db.select({ total: sql<number>`count(*)` }).from(p).where(where);
  const pages = Math.max(1, Math.ceil(total / SHOP_PAGE_SIZE));
  const page = Math.min(filters.page, pages);
  const order = {
    newest: [desc(p.createdAt), desc(p.id)],
    name: [asc(p.name)],
    popularity: [desc(unitsSold), asc(p.name)],
    rating: [sql`${averageRating} desc nulls last`, asc(p.name)],
  }[filters.sort];
  const products = await cards().where(where).orderBy(...order).limit(SHOP_PAGE_SIZE).offset((page - 1) * SHOP_PAGE_SIZE);
  return { products, total, page, pages };
}

/** Distinct colours and materials of active products, for the shop filters. */
export async function filterOptions() {
  const [colours, materials] = await db.batch([
    db.selectDistinct({ value: p.colour }).from(p).where(and(active, isNotNull(p.colour), ne(p.colour, ''))).orderBy(asc(p.colour)),
    db.selectDistinct({ value: p.material }).from(p).where(and(active, isNotNull(p.material), ne(p.material, ''))).orderBy(asc(p.material)),
  ]);
  return { colours: colours.map((row) => row.value!), materials: materials.map((row) => row.value!) };
}

/** Live search suggestions: up to 6 active products by name, colour or SKU, featured first. */
export const suggestProducts = (q: string) =>
  db.select({ name: p.name, slug: p.slug, colour: p.colour }).from(p)
    .where(and(active, sql`(${contains(p.name, q)} or ${contains(p.colour, q)} or ${contains(p.sku, q)})`))
    .orderBy(desc(p.isFeatured), asc(p.name)).limit(6);

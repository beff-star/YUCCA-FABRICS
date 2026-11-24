import type { AstroCookies } from 'astro';
import { and, eq, sum } from 'drizzle-orm';
import { db, runBatch, schema } from '~/lib/db';

const COOKIE = 'yf_cart';
const UUID = /^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/;

interface CartContext { cookies: AstroCookies; locals: App.Locals }

/** The visitor's cart id: the signed-in user's cart, or the guest cart in the `yf_cart` cookie. */
export async function findCartId({ cookies, locals }: CartContext): Promise<string | null> {
  if (locals.user) {
    const [row] = await db.select({ id: schema.carts.id }).from(schema.carts).where(eq(schema.carts.userId, locals.user.id));
    return row?.id ?? null;
  }
  const id = cookies.get(COOKIE)?.value;
  return id && UUID.test(id) ? id : null;
}

export async function ensureCartId(context: CartContext): Promise<string> {
  const existing = await findCartId(context);
  if (existing) {
    // A guest cookie can outlive its row (e.g. after the cart was merged elsewhere); recreate it.
    await db.insert(schema.carts).values({ id: existing, userId: context.locals.user?.id ?? null }).onConflictDoNothing();
    return existing;
  }
  const id = crypto.randomUUID();
  await db.insert(schema.carts).values({ id, userId: context.locals.user?.id ?? null });
  if (!context.locals.user) {
    context.cookies.set(COOKIE, id, { path: '/', httpOnly: true, sameSite: 'lax', secure: true, maxAge: 60 * 60 * 24 * 30 });
  }
  return id;
}

export async function cartCount(context: CartContext): Promise<number> {
  const cartId = await findCartId(context);
  if (!cartId) return 0;
  const [row] = await db.select({ n: sum(schema.cartItems.quantity) }).from(schema.cartItems).where(eq(schema.cartItems.cartId, cartId));
  return Number(row?.n ?? 0);
}

export interface CartLine {
  productId: number;
  name: string;
  slug: string;
  sku: string;
  image: string | null;
  unit: string;
  colour: string | null;
  material: string | null;
  stock: number;
  quantity: number;
  unitPriceCents: number;
  lineTotalCents: number;
}

/** Lines for active products only; quantities are clamped to current stock and sale prices apply. */
export async function cartLines(cartId: string | null): Promise<CartLine[]> {
  if (!cartId) return [];
  const p = schema.products;
  const rows = await db
    .select({
      productId: p.id, name: p.name, slug: p.slug, sku: p.sku, image: p.image, unit: p.unit, colour: p.colour,
      material: p.material, stock: p.stock, priceCents: p.priceCents, salePriceCents: p.salePriceCents,
      quantity: schema.cartItems.quantity,
    })
    .from(schema.cartItems)
    .innerJoin(p, eq(p.id, schema.cartItems.productId))
    .where(and(eq(schema.cartItems.cartId, cartId), eq(p.isActive, true)))
    .orderBy(p.name);
  return rows.filter((row) => row.stock > 0).map((row) => {
    const quantity = Math.max(1, Math.min(row.quantity, row.stock));
    const unitPriceCents = row.salePriceCents ?? row.priceCents;
    return { ...row, quantity, unitPriceCents, lineTotalCents: unitPriceCents * quantity };
  });
}

export const subtotalOf = (lines: CartLine[]) => lines.reduce((total, line) => total + line.lineTotalCents, 0);

export class CartError extends Error {
  constructor(message: string, readonly status = 422) { super(message); }
}

async function activeProduct(productId: number) {
  const [product] = await db.select({ id: schema.products.id, name: schema.products.name, stock: schema.products.stock })
    .from(schema.products).where(and(eq(schema.products.id, productId), eq(schema.products.isActive, true)));
  if (!product) throw new CartError('That fabric is no longer available.', 404);
  return product;
}

/** Adds to the existing quantity, refusing to go past the stock on hand. */
export async function addToCart(context: CartContext, productId: number, quantity: number) {
  const product = await activeProduct(productId);
  const cartId = await ensureCartId(context);
  const [current] = await db.select({ quantity: schema.cartItems.quantity }).from(schema.cartItems)
    .where(and(eq(schema.cartItems.cartId, cartId), eq(schema.cartItems.productId, productId)));
  const next = (current?.quantity ?? 0) + quantity;
  if (next > product.stock) {
    throw new CartError(product.stock > 0 ? `Only ${product.stock} available for ${product.name}.` : `${product.name} is sold out.`, 409);
  }
  await upsertItem(cartId, productId, next);
  return product;
}

/** Sets an exact quantity (bag page); 0 removes the line. Quantities above stock are lowered to the stock. */
export async function setQuantity(context: CartContext, productId: number, quantity: number) {
  const cartId = await ensureCartId(context);
  if (quantity <= 0) {
    await db.delete(schema.cartItems).where(and(eq(schema.cartItems.cartId, cartId), eq(schema.cartItems.productId, productId)));
    return;
  }
  const product = await activeProduct(productId);
  if (product.stock < 1) throw new CartError(`${product.name} is sold out.`, 409);
  await upsertItem(cartId, productId, Math.min(quantity, product.stock));
}

async function upsertItem(cartId: string, productId: number, quantity: number) {
  await db.batch([
    db.insert(schema.cartItems).values({ cartId, productId, quantity })
      .onConflictDoUpdate({ target: [schema.cartItems.cartId, schema.cartItems.productId], set: { quantity } }),
    db.update(schema.carts).set({ updatedAt: new Date() }).where(eq(schema.carts.id, cartId)),
  ]);
}

export async function setCartCoupon(cartId: string, code: string | null) {
  await db.update(schema.carts).set({ couponCode: code }).where(eq(schema.carts.id, cartId));
}

export async function cartCoupon(cartId: string | null): Promise<string | null> {
  if (!cartId) return null;
  const [row] = await db.select({ code: schema.carts.couponCode }).from(schema.carts).where(eq(schema.carts.id, cartId));
  return row?.code ?? null;
}

/** Empties a cart after checkout (keeps the row so the guest cookie stays valid). */
export async function clearCart(cartId: string) {
  await db.batch([
    db.delete(schema.cartItems).where(eq(schema.cartItems.cartId, cartId)),
    db.update(schema.carts).set({ couponCode: null }).where(eq(schema.carts.id, cartId)),
  ]);
}

/**
 * At sign-in, folds the guest cart into the user's saved cart. Guest quantities win for products in both,
 * matching the original site; the guest cart is then deleted and its cookie cleared.
 */
export async function mergeGuestCart(cookies: AstroCookies, userId: string) {
  const guestId = cookies.get(COOKIE)?.value;
  cookies.delete(COOKIE, { path: '/' });
  if (!guestId || !UUID.test(guestId)) return;
  const [guest] = await db.select().from(schema.carts).where(eq(schema.carts.id, guestId));
  if (!guest || (guest.userId && guest.userId !== userId)) return;
  const [saved] = await db.select({ id: schema.carts.id }).from(schema.carts).where(eq(schema.carts.userId, userId));
  if (!saved) {
    await db.update(schema.carts).set({ userId }).where(eq(schema.carts.id, guestId));
    return;
  }
  const items = await db.select().from(schema.cartItems).where(eq(schema.cartItems.cartId, guestId));
  // Copy the items before deleting the guest cart (which cascades to its items).
  await runBatch([
    ...items.map((item) => db.insert(schema.cartItems).values({ ...item, cartId: saved.id })
      .onConflictDoUpdate({ target: [schema.cartItems.cartId, schema.cartItems.productId], set: { quantity: item.quantity } })),
    ...(guest.couponCode ? [db.update(schema.carts).set({ couponCode: guest.couponCode }).where(eq(schema.carts.id, saved.id))] : []),
    db.delete(schema.carts).where(eq(schema.carts.id, guestId)),
  ]);
}

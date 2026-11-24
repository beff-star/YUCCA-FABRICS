import { asc, eq } from 'drizzle-orm';
import { db, schema } from '~/lib/db';

/** An order with its line items (product slug and image joined when the product still exists). */
export async function orderWithItems(orderNumber: string) {
  const [order] = await db.select().from(schema.orders).where(eq(schema.orders.orderNumber, orderNumber));
  if (!order) return null;
  const items = await db.select({
    productName: schema.orderItems.productName, sku: schema.orderItems.sku, quantity: schema.orderItems.quantity,
    unitPriceCents: schema.orderItems.unitPriceCents, lineTotalCents: schema.orderItems.lineTotalCents,
    slug: schema.products.slug, image: schema.products.image,
  }).from(schema.orderItems).leftJoin(schema.products, eq(schema.products.id, schema.orderItems.productId))
    .where(eq(schema.orderItems.orderId, order.id)).orderBy(asc(schema.orderItems.id));
  return { order, items };
}

export type OrderWithItems = NonNullable<Awaited<ReturnType<typeof orderWithItems>>>;

export const ORDER_PROGRESS = ['pending', 'confirmed', 'processing', 'shipped', 'delivered'] as const;

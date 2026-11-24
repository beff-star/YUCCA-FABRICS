import { env } from 'cloudflare:workers';
import { taxOn } from '~/lib/money';

export const taxRate = () => Number(env.TAX_RATE ?? '0.16');
export const deliveryFeeCents = () => Math.round(Number(env.DELIVERY_FEE ?? '350') * 100);

export interface Totals { subtotalCents: number; discountCents: number; taxCents: number; deliveryFeeCents: number; totalCents: number }

/** VAT is added on top of the discounted subtotal; delivery is a flat fee (as on the original site). */
export function orderTotals(subtotalCents: number, discountCents: number, rate: number, deliveryCents: number): Totals {
  const taxCents = taxOn(subtotalCents - discountCents, rate);
  return { subtotalCents, discountCents, taxCents, deliveryFeeCents: deliveryCents, totalCents: subtotalCents - discountCents + taxCents + deliveryCents };
}

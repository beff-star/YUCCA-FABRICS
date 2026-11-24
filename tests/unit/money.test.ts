import { describe, expect, it } from 'vitest';
import { couponDiscount } from '~/lib/coupons';
import { centsToInput, formatMoney, formatMoneyWhole, parseKes, taxOn } from '~/lib/money';
import { orderTotals } from '~/lib/pricing';

describe('money', () => {
  it('formats cents as KES', () => {
    expect(formatMoney(185000)).toBe('KES 1,850.00');
    expect(formatMoney(69120)).toBe('KES 691.20');
    expect(formatMoneyWhole(250000)).toBe('KES 2,500');
  });

  it('parses typed amounts into cents and rejects junk', () => {
    expect(parseKes('1,850')).toBe(185000);
    expect(parseKes('1850.5')).toBe(185050);
    expect(parseKes('12.345')).toBeNull();
    expect(parseKes('-5')).toBeNull();
    expect(parseKes('abc')).toBeNull();
  });

  it('round-trips cents to form values', () => {
    expect(centsToInput(185000)).toBe('1850');
    expect(centsToInput(185050)).toBe('1850.50');
    expect(centsToInput(null)).toBe('');
  });

  it('rounds VAT to the cent', () => {
    expect(taxOn(432000, 0.16)).toBe(69120);
    expect(taxOn(333, 0.16)).toBe(53);
  });
});

describe('order totals', () => {
  it('adds VAT on the discounted subtotal plus a flat delivery fee', () => {
    // 2 × KES 2,400 with 10% off, 16% VAT, KES 350 delivery (matches the checkout test order).
    expect(orderTotals(480000, 48000, 0.16, 35000)).toEqual({
      subtotalCents: 480000, discountCents: 48000, taxCents: 69120, deliveryFeeCents: 35000, totalCents: 536120,
    });
  });
});

describe('coupon discount', () => {
  it('takes a percentage of the subtotal', () => {
    expect(couponDiscount({ discountType: 'percent', discountValue: 10 }, 480000)).toBe(48000);
  });

  it('never discounts more than the subtotal', () => {
    expect(couponDiscount({ discountType: 'fixed', discountValue: 500000 }, 480000)).toBe(480000);
    expect(couponDiscount({ discountType: 'percent', discountValue: 150 }, 1000)).toBe(1000);
  });
});

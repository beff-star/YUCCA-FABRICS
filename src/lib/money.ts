// All amounts are integer cents (KES × 100). Convert only at the edges: form input and display.

const twoDecimals = new Intl.NumberFormat('en-KE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const whole = new Intl.NumberFormat('en-KE', { maximumFractionDigits: 0 });

/** "KES 1,850.00" */
export const formatMoney = (cents: number) => `KES ${twoDecimals.format(cents / 100)}`;

/** "KES 1,850" (rounded to the shilling; for prices that are whole by nature). */
export const formatMoneyWhole = (cents: number) => `KES ${whole.format(Math.round(cents / 100))}`;

/** Parses a KES amount typed into a form ("1,850.50") into cents, or null if it isn't a valid amount. */
export function parseKes(input: string): number | null {
  const cleaned = input.replace(/[,\s]/g, '');
  if (!/^\d+(\.\d{1,2})?$/.test(cleaned)) return null;
  return Math.round(Number(cleaned) * 100);
}

/** Cents → KES string for form inputs ("1850.5" → "1850.50"; whole amounts drop the decimals). */
export const centsToInput = (cents: number | null | undefined) =>
  cents == null ? '' : cents % 100 === 0 ? String(cents / 100) : (cents / 100).toFixed(2);

/** Tax is added on top of the discounted subtotal and rounded to the cent, as in the original checkout. */
export const taxOn = (amountCents: number, rate: number) => Math.round(amountCents * rate);

export const paymentMethodLabel = (method: string) =>
  ({ cod: 'Cash on delivery', mpesa: 'M-Pesa', bank_transfer: 'Bank transfer' })[method] ?? method.replace(/_/g, ' ');

const special: Record<string, string> = { pending_payment: 'Awaiting payment', in_progress: 'In progress', in_person: 'In person' };

/** "in_progress" → "In progress" */
export const statusLabel = (status: string) =>
  special[status] ?? status.charAt(0).toUpperCase() + status.slice(1).replace(/_/g, ' ');

/** Dark text on a light tint for each status family (all pass WCAG AA). */
export function statusTone(status: string): string {
  if (['pending', 'new', 'unpaid', 'sent', 'pending_payment'].includes(status)) return 'bg-[#FBF0D9] text-[#6B4A07]';
  if (['confirmed', 'processing', 'shipped', 'quoted', 'in_progress', 'approved', 'accepted', 'read'].includes(status)) return 'bg-[#DDEFF1] text-[#0F4A52]';
  if (['delivered', 'paid', 'completed', 'replied', 'published', 'active'].includes(status)) return 'bg-[#DFF0E7] text-[#17442F]';
  if (['cancelled', 'failed', 'declined', 'rejected', 'expired', 'refunded', 'suspended'].includes(status)) return 'bg-[#F6E1DE] text-[#7A2E22]';
  return 'bg-gray-100 text-gray-700';
}

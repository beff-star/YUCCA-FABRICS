// Small UI helpers shared by page scripts.
import { isInputError } from 'astro:actions';

export function notify(message: string, isError = false) {
  const toast = document.createElement('div');
  toast.className = isError ? 'toast is-error' : 'toast';
  toast.setAttribute('role', isError ? 'alert' : 'status');
  toast.textContent = message;
  document.body.appendChild(toast);
  setTimeout(() => toast.remove(), 4000);
}

export function updateCartCount(count: number) {
  const badge = document.getElementById('cart-count');
  if (badge) badge.textContent = String(count);
  badge?.parentElement?.setAttribute('aria-label', `Shopping bag, ${count} ${count === 1 ? 'item' : 'items'}`);
}

/** First readable message from an action error (field errors first). */
export function actionErrorMessage(error: unknown): string {
  if (isInputError(error)) {
    const first = Object.values(error.fields).flat()[0];
    if (first) return first;
  }
  if (error instanceof Error && error.message) return error.message;
  return 'Something went wrong. Please try again.';
}

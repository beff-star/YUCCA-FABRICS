/// <reference types="astro/client" />

declare namespace App {
  interface Locals {
    user: import('~/lib/auth').SessionUser | null;
    flash: import('~/lib/flash').Flash | null;
    /** Filled on first use by getSettings(). */
    settings?: Record<string, string>;
  }
}

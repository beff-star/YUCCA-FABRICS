// @ts-check
import { readdirSync, readFileSync } from 'node:fs';
import { join } from 'node:path';
import { defineConfig } from 'astro/config';
import cloudflare from '@astrojs/cloudflare';
import tailwindcss from '@tailwindcss/vite';
import icon from 'astro-icon';

/**
 * Bundles only the Font Awesome icons the code uses (all three sets in full add ~10 MB to the Worker).
 * Icon names must therefore appear literally in src/, e.g. 'fa6-solid:couch'.
 */
function usedIcons(dir = 'src') {
  /** @type {Record<string, Set<string>>} */
  const sets = { 'fa6-solid': new Set(), 'fa6-regular': new Set(), 'fa6-brands': new Set() };
  const walk = (/** @type {string} */ path) => {
    for (const entry of readdirSync(path, { withFileTypes: true })) {
      const full = join(path, entry.name);
      if (entry.isDirectory()) walk(full);
      else if (/\.(astro|ts)$/.test(entry.name)) {
        for (const [, set, name] of readFileSync(full, 'utf8').matchAll(/(fa6-(?:solid|regular|brands)):([a-z0-9-]+)/g)) sets[set]?.add(name);
      }
    }
  };
  walk(dir);
  return Object.fromEntries(Object.entries(sets).map(([set, names]) => [set, [...names]]));
}

export default defineConfig({
  output: 'server',
  // Site images are optimised once at build time; uploads in R2 are served as stored.
  adapter: cloudflare({ imageService: 'compile' }),
  integrations: [icon({ include: usedIcons() })],
  // Rejects cross-site form posts (CSRF) by checking the Origin header on every non-GET request.
  security: { checkOrigin: true },
  devToolbar: { enabled: false },
  vite: { plugins: [tailwindcss()] },
});

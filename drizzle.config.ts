import { defineConfig } from 'drizzle-kit';

// Only generates SQL migration files; wrangler applies them to D1 (local or remote).
export default defineConfig({
  dialect: 'sqlite',
  schema: './src/db/schema.ts',
  out: './migrations',
});

import { env } from 'cloudflare:workers';
import type { APIRoute } from 'astro';
import { and, eq, isNotNull, lte } from 'drizzle-orm';
import { db, schema } from '~/lib/db';

const STATIC_PAGES = ['', 'shop', 'classes', 'services', 'consultations', 'gallery', 'journal', 'about', 'contact', 'privacy', 'terms'];
const escapeXml = (text: string) => text.replace(/[<>&'"]/g, (c) => ({ '<': '&lt;', '>': '&gt;', '&': '&amp;', "'": '&apos;', '"': '&quot;' })[c]!);

export const GET: APIRoute = async ({ url }) => {
  const base = env.SITE_URL || url.origin;
  const [products, posts, courses] = await db.batch([
    db.select({ slug: schema.products.slug, updatedAt: schema.products.updatedAt }).from(schema.products).where(eq(schema.products.isActive, true)),
    db.select({ slug: schema.blogPosts.slug, publishedAt: schema.blogPosts.publishedAt }).from(schema.blogPosts)
      .where(and(eq(schema.blogPosts.status, 'published'), isNotNull(schema.blogPosts.publishedAt), lte(schema.blogPosts.publishedAt, new Date()))),
    db.select({ slug: schema.trainingCourses.slug }).from(schema.trainingCourses).where(eq(schema.trainingCourses.isActive, true)),
  ]);
  const entry = (path: string, lastmod?: Date | null) =>
    `<url><loc>${escapeXml(new URL(path, base).toString())}</loc>${lastmod ? `<lastmod>${lastmod.toISOString().slice(0, 10)}</lastmod>` : ''}</url>`;
  const body = [
    '<?xml version="1.0" encoding="UTF-8"?>',
    '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">',
    ...STATIC_PAGES.map((page) => entry(`/${page}`)),
    ...courses.map((course) => entry(`/classes/${course.slug}`)),
    ...products.map((product) => entry(`/products/${product.slug}`, product.updatedAt)),
    ...posts.map((post) => entry(`/journal/${post.slug}`, post.publishedAt)),
    '</urlset>',
  ].join('\n');
  return new Response(body, { headers: { 'Content-Type': 'application/xml; charset=utf-8', 'Cache-Control': 'public, max-age=3600' } });
};

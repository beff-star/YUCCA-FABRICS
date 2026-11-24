import { and, desc, eq, lte, ne, sql, type SQL } from 'drizzle-orm';
import { fabricImage } from '~/data/fabric-collection';
import { contains } from '~/lib/catalogue';
import { db, schema } from '~/lib/db';

const escapeHtml = (text: string) =>
  text.replace(/[&<>"']/g, (ch) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[ch]!);

/**
 * Journal article text to HTML. Everything is escaped first, then a tiny, safe subset of Markdown is applied:
 * blank lines separate paragraphs, "## " starts a heading and "- " starts a bullet. Never output post bodies
 * any other way.
 */
export function journalBodyHtml(body: string): string {
  let html = '';
  let paragraph: string[] = [];
  let list: string[] = [];
  const flush = () => {
    if (paragraph.length) html += `<p>${paragraph.map(escapeHtml).join('<br>')}</p>`;
    if (list.length) html += `<ul>${list.map((item) => `<li>${escapeHtml(item)}</li>`).join('')}</ul>`;
    paragraph = [];
    list = [];
  };
  // Line by line, so a heading, its paragraph and a list can follow each other without blank lines.
  for (const raw of body.replace(/\r\n/g, '\n').trim().split('\n')) {
    const line = raw.trim();
    if (line === '') flush();
    else if (line.startsWith('## ')) { flush(); html += `<h2>${escapeHtml(line.slice(3))}</h2>`; }
    else if (line.startsWith('- ')) { if (paragraph.length) flush(); list.push(line.slice(2)); }
    else { if (list.length) flush(); paragraph.push(line); }
  }
  flush();
  return html;
}

export const journalImage = (image: string | null) => image ?? fabricImage('soho-inverted-leather.jpeg');

const b = schema.blogPosts;
const bc = schema.blogCategories;
const postFields = {
  id: b.id, slug: b.slug, title: b.title, excerpt: b.excerpt, body: b.body, image: b.image, publishedAt: b.publishedAt,
  categoryName: bc.name, categorySlug: bc.slug,
};
export type JournalPost = {
  id: number; slug: string; title: string; excerpt: string | null; body: string; image: string | null;
  publishedAt: Date | null; categoryName: string | null; categorySlug: string | null;
};

const published = () => and(eq(b.status, 'published'), lte(b.publishedAt, new Date()));
const posts = () => db.select(postFields).from(b).leftJoin(bc, eq(bc.id, b.categoryId));

export const latestPosts = (limit: number, excludeId = 0) =>
  posts().where(and(published(), ne(b.id, excludeId))).orderBy(desc(b.publishedAt)).limit(limit);

export async function postBySlug(slug: string) {
  const [post] = await posts().where(and(published(), eq(b.slug, slug)));
  return post ?? null;
}

export const JOURNAL_PAGE_SIZE = 9;

export async function listPosts({ q, category, page }: { q: string; category: string; page: number }) {
  const conditions: (SQL | undefined)[] = [published()];
  if (q) conditions.push(sql`(${contains(b.title, q)} or ${contains(b.excerpt, q)} or ${contains(b.body, q)})`);
  if (category) conditions.push(eq(bc.slug, category));
  const where = and(...conditions);
  const [{ total } = { total: 0 }] = await db.select({ total: sql<number>`count(*)` }).from(b).leftJoin(bc, eq(bc.id, b.categoryId)).where(where);
  const pages = Math.max(1, Math.ceil(total / JOURNAL_PAGE_SIZE));
  const current = Math.min(Math.max(1, page), pages);
  const rows = await posts().where(where).orderBy(desc(b.publishedAt)).limit(JOURNAL_PAGE_SIZE).offset((current - 1) * JOURNAL_PAGE_SIZE);
  return { posts: rows, total, page: current, pages };
}

/** Categories that have at least one published post. */
export const journalCategories = () =>
  db.selectDistinct({ name: bc.name, slug: bc.slug }).from(bc).innerJoin(b, eq(b.categoryId, bc.id)).where(published()).orderBy(bc.name);

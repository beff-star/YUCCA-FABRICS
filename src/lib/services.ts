import { and, asc, eq, inArray } from 'drizzle-orm';
import { CONSULTATION_CATEGORY_SLUGS } from '~/data/service-details';
import { db, schema } from '~/lib/db';

const s = schema.services;
const sc = schema.serviceCategories;

export type ServiceRow = {
  id: number; slug: string; name: string; description: string; startingPriceCents: number | null;
  duration: string | null; categoryName: string | null; categorySlug: string | null;
};

/** Active services with their category; optionally only the ones booked as consultations. */
export const activeServices = (consultationsOnly = false): Promise<ServiceRow[]> =>
  db.select({
    id: s.id, slug: s.slug, name: s.name, description: s.description, startingPriceCents: s.startingPriceCents,
    duration: s.duration, categoryName: sc.name, categorySlug: sc.slug,
  }).from(s).leftJoin(sc, eq(sc.id, s.categoryId))
    .where(and(eq(s.isActive, true), consultationsOnly ? inArray(sc.slug, CONSULTATION_CATEGORY_SLUGS) : undefined))
    .orderBy(asc(s.id));

export const isConsultationService = (service: Pick<ServiceRow, 'categorySlug'>) =>
  CONSULTATION_CATEGORY_SLUGS.includes(service.categorySlug ?? '');

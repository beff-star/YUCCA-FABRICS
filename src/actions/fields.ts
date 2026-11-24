import { z } from 'astro/zod';

/** Astro passes missing form fields as null and empty inputs as ''; both mean "not given" for optional fields. */
export const optional = <T extends z.ZodType>(schema: T) =>
  z.preprocess((value) => (value === '' || value === null ? undefined : value), schema.optional());

export const name = z.string().trim().min(1, 'Please enter your name.').max(150);
export const email = z.email('Enter a valid email address.').max(190).transform((v) => v.toLowerCase());
export const phone = z.string().trim().min(7, 'Enter a phone number we can reach you on.').max(40);
export const description = z.string().trim().min(10, 'Please tell us a little more (at least 10 characters).').max(5000);
export const optionalText = (max: number) => optional(z.string().trim().max(max));
export const isoDate = z.string().regex(/^\d{4}-\d{2}-\d{2}$/, 'Choose a valid date.');
export const id = z.coerce.number().int().positive();
/** An optional file input: an empty file (nothing chosen) counts as missing. */
export const image = z.preprocess((value) => (value instanceof File && value.size > 0 ? value : undefined), z.instanceof(File).optional());

import { ActionError, defineAction } from 'astro:actions';
import { z } from 'astro/zod';
import { description, email, id, image, isoDate, name, optional, optionalText, phone } from './fields';
import { and, eq, sql } from 'drizzle-orm';
import { BUDGET_RANGES, CONSULTATION_TIMES, CONSULTATION_TYPES, PROPERTY_TYPES } from '~/data/service-details';
import { constraintFailed, db, runBatch, schema } from '~/lib/db';
import { emailBody, sendEmail } from '~/lib/email';
import { EAT_OFFSET_MS, formatClock, formatIsoDate, todayInNairobi } from '~/lib/format';
import { emailBusiness } from '~/lib/notify-business';
import { allow, clientIp } from '~/lib/throttle';
import { deleteUpload, saveUploadedImage, UploadError } from '~/lib/uploads';

async function throttle(request: Request, form: string) {
  if (!(await allow(`form:${form}:${clientIp(request)}`, 5, 600))) {
    throw new ActionError({ code: 'TOO_MANY_REQUESTS', message: 'You’ve sent a few requests already. Please wait a few minutes and try again.' });
  }
}

async function upload(file: File | undefined, folder: 'requests' | 'consultations') {
  try {
    return await saveUploadedImage(file, folder);
  } catch (error) {
    if (error instanceof UploadError) throw new ActionError({ code: 'BAD_REQUEST', message: error.message });
    throw error;
  }
}

export const forms = {
  newsletter: defineAction({
    accept: 'form',
    input: z.object({ email }),
    async handler({ email }) {
      await db.insert(schema.newsletter).values({ email })
        .onConflictDoUpdate({ target: schema.newsletter.email, set: { isActive: true } });
      return { message: 'Thank you for subscribing. Look out for notes from the studio.' };
    },
  }),

  contact: defineAction({
    accept: 'form',
    input: z.object({
      name, email, phone: optionalText(40),
      subject: z.string().trim().min(1, 'Please add a subject.').max(180),
      message: description,
      website: optional(z.string()), // honeypot: real visitors never see or fill this field
    }),
    async handler(input, { request, locals }) {
      if (input.website) return { message: 'Thank you. We’ll be in touch soon.' };
      await throttle(request, 'contact');
      await db.insert(schema.contacts).values({ name: input.name, email: input.email, phone: input.phone, subject: input.subject, message: input.message });
      await emailBusiness(locals, `New enquiry: ${input.subject}`, [
        `From: ${input.name} <${input.email}>${input.phone ? `, ${input.phone}` : ''}`, input.message,
      ], input.email);
      return { message: 'Thank you. We’ll be in touch soon.' };
    },
  }),

  serviceRequest: defineAction({
    accept: 'form',
    input: z.object({
      service_id: optional(id),
      name, email, phone,
      location: z.string().trim().min(1, 'Where is the project?').max(255),
      preferred_date: optional(isoDate),
      description,
      reference_image: image,
    }),
    async handler(input, { request, locals }) {
      if (input.preferred_date && input.preferred_date < todayInNairobi()) {
        throw new ActionError({ code: 'BAD_REQUEST', message: 'Please choose today or a later date.' });
      }
      let service: { id: number; name: string } | undefined;
      if (input.service_id) {
        [service] = await db.select({ id: schema.services.id, name: schema.services.name }).from(schema.services)
          .where(and(eq(schema.services.id, input.service_id), eq(schema.services.isActive, true)));
        if (!service) throw new ActionError({ code: 'BAD_REQUEST', message: 'Please choose a service from the list.' });
      }
      await throttle(request, 'service-request');
      const imageUrl = await upload(input.reference_image, 'requests');
      try {
        await runBatch([
          db.insert(schema.serviceRequests).values({
            userId: locals.user?.id ?? null, serviceId: service?.id ?? null, name: input.name, email: input.email, phone: input.phone,
            location: input.location, preferredDate: input.preferred_date ?? null, description: input.description,
          }),
          ...(imageUrl ? [db.insert(schema.serviceRequestImages).values({ requestId: sql`last_insert_rowid()`, image: imageUrl })] : []),
        ]);
      } catch (error) {
        await deleteUpload(imageUrl);
        throw error;
      }
      await emailBusiness(locals, `New service request${service ? `: ${service.name}` : ''}`, [
        `From: ${input.name} <${input.email}>, ${input.phone}`, `Location: ${input.location}`,
        input.preferred_date && `Preferred date: ${formatIsoDate(input.preferred_date)}`, input.description,
        imageUrl && `Photo: ${new URL(imageUrl, request.url).toString()}`,
      ], input.email);
      return { message: 'Thank you! We’ve received your request and will be in touch to discuss your project.' };
    },
  }),

  consultation: defineAction({
    accept: 'form',
    input: z.object({
      name, email, phone,
      consultation_type: z.enum(CONSULTATION_TYPES, 'Choose a consultation type.'),
      property_type: z.enum(PROPERTY_TYPES, 'Choose a property type.'),
      location: z.string().trim().min(1, 'Where is the property?').max(255),
      preferred_date: isoDate,
      preferred_time: z.enum(CONSULTATION_TIMES as [string, ...string[]], 'Choose a time between 9:00 AM and 5:00 PM.'),
      budget: optional(z.enum(BUDGET_RANGES)),
      method: z.enum(['online', 'in_person']),
      description,
      reference_image: image,
    }),
    async handler(input, { request, locals }) {
      const today = todayInNairobi();
      const nowClock = new Date(Date.now() + EAT_OFFSET_MS).toISOString().slice(11, 16);
      if (input.preferred_date < today || (input.preferred_date === today && input.preferred_time <= nowClock)) {
        throw new ActionError({ code: 'BAD_REQUEST', message: 'Please choose a time in the future.' });
      }
      await throttle(request, 'consultation');
      const imageUrl = await upload(input.reference_image, 'consultations');
      const slot = and(eq(schema.consultationSlots.date, input.preferred_date), eq(schema.consultationSlots.time, input.preferred_time));
      try {
        // One batch: the slot's primary key rejects a double booking and rolls back the consultation too.
        await runBatch([
          db.insert(schema.consultations).values({
            userId: locals.user?.id ?? null, name: input.name, email: input.email, phone: input.phone,
            consultationType: input.consultation_type, propertyType: input.property_type, location: input.location,
            preferredDate: input.preferred_date, preferredTime: input.preferred_time, budget: input.budget ?? null,
            method: input.method, description: input.description,
          }),
          db.insert(schema.consultationSlots).values({ date: input.preferred_date, time: input.preferred_time, consultationId: sql`last_insert_rowid()` }),
          ...(imageUrl ? [db.insert(schema.consultationImages).values({
            consultationId: sql`(select ${schema.consultationSlots.consultationId} from ${schema.consultationSlots} where ${slot})`, image: imageUrl,
          })] : []),
        ]);
      } catch (error) {
        await deleteUpload(imageUrl);
        if (constraintFailed(error, 'UNIQUE')) {
          throw new ActionError({ code: 'CONFLICT', message: 'That time was just booked. Please choose another slot.' });
        }
        throw error;
      }
      const when = `${formatIsoDate(input.preferred_date)} at ${formatClock(input.preferred_time)}`;
      const method = input.method === 'online' ? 'online' : 'in person';
      await Promise.all([
        sendEmail({
          to: input.email,
          subject: 'We’ve received your consultation request',
          text: emailBody(`Hello ${input.name},`,
            `Thank you for requesting a ${input.consultation_type.toLowerCase()} consultation (${method}) on ${when}.`,
            'This is a request, not yet a confirmed appointment. We’ll check the slot and confirm it with you shortly.'),
        }),
        emailBusiness(locals, `Consultation request: ${when}`, [
          `${input.name} <${input.email}>, ${input.phone}`, `${input.consultation_type} · ${input.property_type} · ${method}`,
          `Location: ${input.location}`, input.budget && `Budget: ${input.budget}`, input.description,
          imageUrl && `Photo: ${new URL(imageUrl, request.url).toString()}`,
        ], input.email),
      ]);
      return { message: `Thank you! We’ve received your request for ${when} and will confirm it with you shortly.` };
    },
  }),
};

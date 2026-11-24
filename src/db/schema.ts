/**
 * D1 (SQLite) schema. Conventions:
 * - Money is integer cents (KES × 100) so 16% VAT never produces float rounding errors.
 * - Timestamps are integer milliseconds since the epoch (UTC); calendar-only dates are 'YYYY-MM-DD' text.
 * - D1 has no interactive transactions, so invariants that must hold under concurrency are CHECK/UNIQUE
 *   constraints: a db.batch() that violates one fails as a whole (see lib/orders.ts, lib/training.ts).
 */
import { sql } from 'drizzle-orm';
import {
  check, index, integer, primaryKey, sqliteTable, text, uniqueIndex,
} from 'drizzle-orm/sqlite-core';

const now = sql`(cast(unixepoch('subsecond') * 1000 as integer))`;
const createdAt = () => integer('created_at', { mode: 'timestamp_ms' }).notNull().default(now);
const updatedAt = () => integer('updated_at', { mode: 'timestamp_ms' }).notNull().default(now).$onUpdate(() => new Date());
const id = () => integer('id').primaryKey({ autoIncrement: true });
const flag = (name: string, value: boolean) => integer(name, { mode: 'boolean' }).notNull().default(value);

/* ---------- Accounts (Better Auth tables; property names are the ones Better Auth expects) ---------- */

export const user = sqliteTable('user', {
  id: text('id').primaryKey(),
  name: text('name').notNull(),
  email: text('email').notNull().unique(),
  emailVerified: integer('email_verified', { mode: 'boolean' }).notNull().default(false),
  image: text('image'),
  username: text('username').unique(),
  phone: text('phone'),
  role: text('role', { enum: ['customer', 'admin'] }).notNull().default('customer'),
  status: text('status', { enum: ['active', 'suspended'] }).notNull().default('active'),
  createdAt: createdAt(),
  updatedAt: updatedAt(),
}, (t) => [index('idx_user_role_status').on(t.role, t.status)]);

export const session = sqliteTable('session', {
  id: text('id').primaryKey(),
  expiresAt: integer('expires_at', { mode: 'timestamp_ms' }).notNull(),
  token: text('token').notNull().unique(),
  createdAt: createdAt(),
  updatedAt: updatedAt(),
  ipAddress: text('ip_address'),
  userAgent: text('user_agent'),
  userId: text('user_id').notNull().references(() => user.id, { onDelete: 'cascade' }),
}, (t) => [index('idx_session_user').on(t.userId)]);

export const account = sqliteTable('account', {
  id: text('id').primaryKey(),
  accountId: text('account_id').notNull(),
  providerId: text('provider_id').notNull(),
  userId: text('user_id').notNull().references(() => user.id, { onDelete: 'cascade' }),
  accessToken: text('access_token'),
  refreshToken: text('refresh_token'),
  idToken: text('id_token'),
  accessTokenExpiresAt: integer('access_token_expires_at', { mode: 'timestamp_ms' }),
  refreshTokenExpiresAt: integer('refresh_token_expires_at', { mode: 'timestamp_ms' }),
  scope: text('scope'),
  password: text('password'),
  createdAt: createdAt(),
  updatedAt: updatedAt(),
}, (t) => [index('idx_account_user').on(t.userId)]);

export const verification = sqliteTable('verification', {
  id: text('id').primaryKey(),
  identifier: text('identifier').notNull(),
  value: text('value').notNull(),
  expiresAt: integer('expires_at', { mode: 'timestamp_ms' }).notNull(),
  createdAt: createdAt(),
  updatedAt: updatedAt(),
}, (t) => [index('idx_verification_identifier').on(t.identifier)]);

export const rateLimit = sqliteTable('rate_limit', {
  id: text('id').primaryKey(),
  key: text('key').notNull().unique(),
  count: integer('count').notNull(),
  lastRequest: integer('last_request').notNull(),
});

/* ---------- Catalogue ---------- */

export const categories = sqliteTable('categories', {
  id: id(),
  name: text('name').notNull().unique(),
  slug: text('slug').notNull().unique(),
  description: text('description'),
  image: text('image'),
  isActive: flag('is_active', true),
  createdAt: createdAt(),
});

export const products = sqliteTable('products', {
  id: id(),
  categoryId: integer('category_id').references(() => categories.id, { onDelete: 'set null' }),
  name: text('name').notNull(),
  slug: text('slug').notNull().unique(),
  sku: text('sku').notNull().unique(),
  description: text('description').notNull(),
  priceCents: integer('price_cents').notNull(),
  salePriceCents: integer('sale_price_cents'),
  stock: integer('stock').notNull().default(0),
  unit: text('unit').notNull().default('metre'),
  colour: text('colour'),
  material: text('material'),
  /** Root-relative URL: '/images/…' for site images or '/media/…' for R2 uploads. */
  image: text('image'),
  isFeatured: flag('is_featured', false),
  isActive: flag('is_active', true),
  createdAt: createdAt(),
  updatedAt: updatedAt(),
}, (t) => [
  check('products_stock_non_negative', sql`${t.stock} >= 0`),
  index('idx_products_category_active').on(t.categoryId, t.isActive),
  index('idx_products_featured').on(t.isFeatured, t.isActive),
  index('idx_products_created').on(t.createdAt),
]);

export const productImages = sqliteTable('product_images', {
  id: id(),
  productId: integer('product_id').notNull().references(() => products.id, { onDelete: 'cascade' }),
  image: text('image').notNull(),
  altText: text('alt_text'),
  sortOrder: integer('sort_order').notNull().default(0),
}, (t) => [index('idx_product_images_product').on(t.productId)]);

export const wishlistItems = sqliteTable('wishlist_items', {
  userId: text('user_id').notNull().references(() => user.id, { onDelete: 'cascade' }),
  productId: integer('product_id').notNull().references(() => products.id, { onDelete: 'cascade' }),
  createdAt: createdAt(),
}, (t) => [primaryKey({ columns: [t.userId, t.productId] })]);

/** Guests get a cart row keyed by a random id in the `yf_cart` cookie; it is linked to the user at sign-in. */
export const carts = sqliteTable('carts', {
  id: text('id').primaryKey(),
  userId: text('user_id').unique().references(() => user.id, { onDelete: 'cascade' }),
  couponCode: text('coupon_code'),
  updatedAt: updatedAt(),
});

export const cartItems = sqliteTable('cart_items', {
  cartId: text('cart_id').notNull().references(() => carts.id, { onDelete: 'cascade' }),
  productId: integer('product_id').notNull().references(() => products.id, { onDelete: 'cascade' }),
  quantity: integer('quantity').notNull(),
}, (t) => [primaryKey({ columns: [t.cartId, t.productId] }), check('cart_items_quantity_positive', sql`${t.quantity} > 0`)]);

/* ---------- Orders and payments ---------- */

export const coupons = sqliteTable('coupons', {
  id: id(),
  code: text('code').notNull().unique(),
  discountType: text('discount_type', { enum: ['fixed', 'percent'] }).notNull(),
  /** Cents when fixed, whole percent (1–100) when percent. */
  discountValue: integer('discount_value').notNull(),
  minimumOrderCents: integer('minimum_order_cents').notNull().default(0),
  startsAt: integer('starts_at', { mode: 'timestamp_ms' }),
  expiresAt: integer('expires_at', { mode: 'timestamp_ms' }),
  usageLimit: integer('usage_limit'),
  isActive: flag('is_active', true),
});

export const ORDER_STATUSES = ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled'] as const;
export const PAYMENT_STATUSES = ['unpaid', 'pending', 'paid', 'failed', 'refunded'] as const;
export const PAYMENT_METHODS = ['mpesa', 'bank_transfer', 'cod'] as const;

export const orders = sqliteTable('orders', {
  id: id(),
  userId: text('user_id').references(() => user.id, { onDelete: 'set null' }),
  orderNumber: text('order_number').notNull().unique(),
  /** SHA-256 of the token in the guest's `yf_order` cookie, so guests can reopen their confirmation page. */
  accessTokenHash: text('access_token_hash').notNull(),
  customerName: text('customer_name').notNull(),
  email: text('email').notNull(),
  phone: text('phone').notNull(),
  addressLine: text('address_line').notNull(),
  city: text('city').notNull(),
  county: text('county'),
  postalCode: text('postal_code'),
  deliveryInstructions: text('delivery_instructions'),
  subtotalCents: integer('subtotal_cents').notNull(),
  discountCents: integer('discount_cents').notNull().default(0),
  taxCents: integer('tax_cents').notNull(),
  deliveryFeeCents: integer('delivery_fee_cents').notNull(),
  totalCents: integer('total_cents').notNull(),
  paymentMethod: text('payment_method', { enum: PAYMENT_METHODS }).notNull(),
  status: text('status', { enum: ORDER_STATUSES }).notNull().default('pending'),
  paymentStatus: text('payment_status', { enum: PAYMENT_STATUSES }).notNull().default('pending'),
  createdAt: createdAt(),
  updatedAt: updatedAt(),
}, (t) => [
  index('idx_orders_status_created').on(t.status, t.createdAt),
  index('idx_orders_email').on(t.email),
  index('idx_orders_user').on(t.userId),
]);

export const orderItems = sqliteTable('order_items', {
  id: id(),
  orderId: integer('order_id').notNull().references(() => orders.id, { onDelete: 'cascade' }),
  productId: integer('product_id').references(() => products.id, { onDelete: 'set null' }),
  productName: text('product_name').notNull(),
  sku: text('sku').notNull(),
  unitPriceCents: integer('unit_price_cents').notNull(),
  quantity: integer('quantity').notNull(),
  lineTotalCents: integer('line_total_cents').notNull(),
}, (t) => [index('idx_order_items_order').on(t.orderId), index('idx_order_items_product').on(t.productId)]);

export const couponUsage = sqliteTable('coupon_usage', {
  couponId: integer('coupon_id').notNull().references(() => coupons.id, { onDelete: 'restrict' }),
  orderId: integer('order_id').notNull().references(() => orders.id, { onDelete: 'cascade' }),
  /** NULL for guests; SQLite UNIQUE ignores NULLs, so only signed-in customers are limited to one use. */
  userId: text('user_id').references(() => user.id, { onDelete: 'set null' }),
  createdAt: createdAt(),
}, (t) => [primaryKey({ columns: [t.couponId, t.orderId] }), uniqueIndex('uq_coupon_usage_user').on(t.couponId, t.userId)]);

/* ---------- Services and consultations ---------- */

export const serviceCategories = sqliteTable('service_categories', {
  id: id(),
  name: text('name').notNull().unique(),
  slug: text('slug').notNull().unique(),
});

export const services = sqliteTable('services', {
  id: id(),
  categoryId: integer('category_id').references(() => serviceCategories.id, { onDelete: 'set null' }),
  name: text('name').notNull(),
  slug: text('slug').notNull().unique(),
  description: text('description').notNull(),
  startingPriceCents: integer('starting_price_cents'),
  duration: text('duration'),
  image: text('image'),
  isActive: flag('is_active', true),
  createdAt: createdAt(),
});

export const REQUEST_STATUSES = ['new', 'quoted', 'accepted', 'in_progress', 'completed', 'declined'] as const;

export const serviceRequests = sqliteTable('service_requests', {
  id: id(),
  userId: text('user_id').references(() => user.id, { onDelete: 'set null' }),
  serviceId: integer('service_id').references(() => services.id, { onDelete: 'set null' }),
  name: text('name').notNull(),
  email: text('email').notNull(),
  phone: text('phone').notNull(),
  location: text('location').notNull(),
  preferredDate: text('preferred_date'),
  description: text('description').notNull(),
  status: text('status', { enum: REQUEST_STATUSES }).notNull().default('new'),
  adminResponse: text('admin_response'),
  createdAt: createdAt(),
}, (t) => [index('idx_request_status_created').on(t.status, t.createdAt), index('idx_request_user').on(t.userId)]);

export const serviceRequestImages = sqliteTable('service_request_images', {
  id: id(),
  requestId: integer('request_id').notNull().references(() => serviceRequests.id, { onDelete: 'cascade' }),
  image: text('image').notNull(),
});

export const quotations = sqliteTable('quotations', {
  id: id(),
  requestId: integer('request_id').notNull().references(() => serviceRequests.id, { onDelete: 'cascade' }),
  amountCents: integer('amount_cents').notNull(),
  details: text('details').notNull(),
  status: text('status', { enum: ['sent', 'accepted', 'declined', 'expired'] }).notNull().default('sent'),
  validUntil: text('valid_until'),
  createdAt: createdAt(),
});

export const CONSULTATION_STATUSES = ['pending', 'approved', 'rejected', 'completed', 'cancelled'] as const;

export const consultations = sqliteTable('consultations', {
  id: id(),
  userId: text('user_id').references(() => user.id, { onDelete: 'set null' }),
  name: text('name').notNull(),
  email: text('email').notNull(),
  phone: text('phone').notNull(),
  consultationType: text('consultation_type').notNull(),
  propertyType: text('property_type').notNull(),
  location: text('location').notNull(),
  preferredDate: text('preferred_date').notNull(),
  preferredTime: text('preferred_time').notNull(),
  budget: text('budget'),
  method: text('method', { enum: ['online', 'in_person'] }).notNull(),
  description: text('description').notNull(),
  status: text('status', { enum: CONSULTATION_STATUSES }).notNull().default('pending'),
  adminNotes: text('admin_notes'),
  createdAt: createdAt(),
}, (t) => [index('idx_consult_slot').on(t.preferredDate, t.preferredTime, t.status), index('idx_consult_user').on(t.userId)]);

/** One row per booked date/time; the primary key makes a double booking fail. */
export const consultationSlots = sqliteTable('consultation_slots', {
  date: text('date').notNull(),
  time: text('time').notNull(),
  consultationId: integer('consultation_id').notNull().unique().references(() => consultations.id, { onDelete: 'cascade' }),
}, (t) => [primaryKey({ columns: [t.date, t.time] })]);

export const consultationImages = sqliteTable('consultation_images', {
  id: id(),
  consultationId: integer('consultation_id').notNull().references(() => consultations.id, { onDelete: 'cascade' }),
  image: text('image').notNull(),
});

/* ---------- Virtual training (Yucca Homeware Consultant) ---------- */

export const TRAINING_TRACKS = ['furniture', 'upholstery', 'interior'] as const;
export const TRAINING_PLATFORMS = ['google_meet', 'zoom', 'whatsapp'] as const;
export const TRAINING_STATUSES = ['pending_payment', 'confirmed', 'completed', 'cancelled', 'expired'] as const;

export const trainingCourses = sqliteTable('training_courses', {
  id: id(),
  slug: text('slug').notNull().unique(),
  title: text('title').notNull(),
  track: text('track', { enum: TRAINING_TRACKS }).notNull(),
  level: text('level', { enum: ['beginner', 'all levels', 'advanced'] }).notNull(),
  hourlyRateCents: integer('hourly_rate_cents').notNull(),
  summary: text('summary').notNull(),
  /** JSON array of short "you will learn" points. */
  outcomes: text('outcomes', { mode: 'json' }).$type<string[]>().notNull(),
  whoFor: text('who_for').notNull(),
  sortOrder: integer('sort_order').notNull().default(0),
  isActive: flag('is_active', true),
  createdAt: createdAt(),
});

/** Weekly opening hours for sessions, in Nairobi time (weekday 0 = Sunday). */
export const trainingAvailability = sqliteTable('training_availability', {
  id: id(),
  weekday: integer('weekday').notNull(),
  startTime: text('start_time').notNull(),
  endTime: text('end_time').notNull(),
}, (t) => [check('training_availability_weekday', sql`${t.weekday} between 0 and 6`)]);

export const trainingBlackouts = sqliteTable('training_blackouts', {
  date: text('date').primaryKey(),
  reason: text('reason'),
});

export const trainingBookings = sqliteTable('training_bookings', {
  id: id(),
  reference: text('reference').notNull().unique(),
  accessTokenHash: text('access_token_hash').notNull(),
  userId: text('user_id').references(() => user.id, { onDelete: 'set null' }),
  courseId: integer('course_id').notNull().references(() => trainingCourses.id, { onDelete: 'restrict' }),
  name: text('name').notNull(),
  email: text('email').notNull(),
  phone: text('phone').notNull(),
  startsAt: integer('starts_at', { mode: 'timestamp_ms' }).notNull(),
  hours: integer('hours').notNull(),
  rateCents: integer('rate_cents').notNull(),
  totalCents: integer('total_cents').notNull(),
  platform: text('platform', { enum: TRAINING_PLATFORMS }).notNull(),
  skillLevel: text('skill_level').notNull(),
  goals: text('goals').notNull(),
  status: text('status', { enum: TRAINING_STATUSES }).notNull().default('pending_payment'),
  holdExpiresAt: integer('hold_expires_at', { mode: 'timestamp_ms' }),
  meetingUrl: text('meeting_url'),
  calendarEventId: text('calendar_event_id'),
  adminNotes: text('admin_notes'),
  createdAt: createdAt(),
  updatedAt: updatedAt(),
}, (t) => [
  check('training_bookings_hours', sql`${t.hours} between 1 and 8`),
  index('idx_training_status_start').on(t.status, t.startsAt),
  index('idx_training_user').on(t.userId),
]);

/** One row per booked hour; the UNIQUE slot_start makes overlapping bookings fail inside a batch. */
export const trainingBookingHours = sqliteTable('training_booking_hours', {
  slotStart: integer('slot_start', { mode: 'timestamp_ms' }).primaryKey(),
  bookingId: integer('booking_id').notNull().references(() => trainingBookings.id, { onDelete: 'cascade' }),
}, (t) => [index('idx_training_hours_booking').on(t.bookingId)]);

/* Defined after its referenced tables; exactly one of order_id / training_booking_id is set. */
export const payments = sqliteTable('payments', {
  id: id(),
  orderId: integer('order_id').references(() => orders.id, { onDelete: 'cascade' }),
  trainingBookingId: integer('training_booking_id').references(() => trainingBookings.id, { onDelete: 'cascade' }),
  method: text('method', { enum: PAYMENT_METHODS }).notNull(),
  amountCents: integer('amount_cents').notNull(),
  /** M-Pesa receipt number or the customer's bank-transfer reference. */
  reference: text('reference').unique(),
  status: text('status', { enum: ['pending', 'paid', 'failed', 'refunded'] }).notNull().default('pending'),
  checkoutRequestId: text('checkout_request_id').unique(),
  providerPayload: text('provider_payload', { mode: 'json' }).$type<Record<string, unknown>>(),
  createdAt: createdAt(),
  updatedAt: updatedAt(),
}, (t) => [
  check('payments_one_parent', sql`(${t.orderId} is null) != (${t.trainingBookingId} is null)`),
  index('idx_payments_status').on(t.status),
  index('idx_payments_order').on(t.orderId),
  index('idx_payments_booking').on(t.trainingBookingId),
]);

/* ---------- Reviews and content ---------- */

export const productReviews = sqliteTable('product_reviews', {
  id: id(),
  productId: integer('product_id').notNull().references(() => products.id, { onDelete: 'cascade' }),
  userId: text('user_id').notNull().references(() => user.id, { onDelete: 'cascade' }),
  orderId: integer('order_id').notNull().references(() => orders.id, { onDelete: 'cascade' }),
  rating: integer('rating').notNull(),
  title: text('title'),
  review: text('review').notNull(),
  status: text('status', { enum: ['pending', 'approved', 'rejected'] }).notNull().default('pending'),
  createdAt: createdAt(),
}, (t) => [
  uniqueIndex('uq_review_purchase').on(t.productId, t.userId, t.orderId),
  index('idx_reviews_approved').on(t.productId, t.status),
  check('product_reviews_rating', sql`${t.rating} between 1 and 5`),
]);

export const blogCategories = sqliteTable('blog_categories', {
  id: id(),
  name: text('name').notNull().unique(),
  slug: text('slug').notNull().unique(),
});

export const blogPosts = sqliteTable('blog_posts', {
  id: id(),
  categoryId: integer('category_id').references(() => blogCategories.id, { onDelete: 'set null' }),
  authorId: text('author_id').references(() => user.id, { onDelete: 'set null' }),
  title: text('title').notNull(),
  slug: text('slug').notNull().unique(),
  excerpt: text('excerpt'),
  body: text('body').notNull(),
  image: text('image'),
  status: text('status', { enum: ['draft', 'published'] }).notNull().default('draft'),
  publishedAt: integer('published_at', { mode: 'timestamp_ms' }),
  createdAt: createdAt(),
}, (t) => [index('idx_blog_published').on(t.status, t.publishedAt)]);

export const galleryCategories = sqliteTable('gallery_categories', {
  id: id(),
  name: text('name').notNull().unique(),
  slug: text('slug').notNull().unique(),
});

export const gallery = sqliteTable('gallery', {
  id: id(),
  categoryId: integer('category_id').references(() => galleryCategories.id, { onDelete: 'set null' }),
  title: text('title').notNull(),
  description: text('description'),
  image: text('image').notNull(),
  isActive: flag('is_active', true),
  createdAt: createdAt(),
});

export const testimonials = sqliteTable('testimonials', {
  id: id(),
  customerName: text('customer_name').notNull(),
  location: text('location'),
  quote: text('quote').notNull(),
  rating: integer('rating').notNull().default(5),
  isActive: flag('is_active', false),
  createdAt: createdAt(),
});

export const faqs = sqliteTable('faqs', {
  id: id(),
  question: text('question').notNull(),
  answer: text('answer').notNull(),
  sortOrder: integer('sort_order').notNull().default(0),
  isActive: flag('is_active', true),
});

export const contacts = sqliteTable('contacts', {
  id: id(),
  name: text('name').notNull(),
  email: text('email').notNull(),
  phone: text('phone'),
  subject: text('subject').notNull(),
  message: text('message').notNull(),
  status: text('status', { enum: ['new', 'read', 'replied'] }).notNull().default('new'),
  createdAt: createdAt(),
}, (t) => [index('idx_contacts_status_created').on(t.status, t.createdAt)]);

export const newsletter = sqliteTable('newsletter', {
  id: id(),
  email: text('email').notNull().unique(),
  isActive: flag('is_active', true),
  createdAt: createdAt(),
});

export const websiteSettings = sqliteTable('website_settings', {
  key: text('setting_key').primaryKey(),
  value: text('setting_value').notNull(),
  updatedAt: updatedAt(),
});

/** Third-party credentials connected from admin (e.g. the Google refresh token), AES-GCM encrypted. */
export const integrations = sqliteTable('integrations', {
  key: text('key').primaryKey(),
  valueEncrypted: text('value_encrypted').notNull(),
  updatedAt: updatedAt(),
});

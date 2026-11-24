// Minimal stand-in for the Workers runtime module in unit tests. Bindings are empty objects: tests only
// exercise pure functions, never the database or R2.
export const env = {
  DB: {},
  UPLOADS: {},
  EMAIL: {},
  SITE_URL: 'https://yucca.test',
  TAX_RATE: '0.16',
  DELIVERY_FEE: '350',
  MPESA_ENV: 'sandbox',
  MPESA_SHORTCODE: '174379',
  MPESA_PASSKEY: 'passkey',
  MPESA_CALLBACK_TOKEN: 'token',
  EMAIL_FROM: 'hello@yucca.test',
};

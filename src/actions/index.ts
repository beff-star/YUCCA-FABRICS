// Astro Actions: typed server functions used by forms and the JS in src/scripts/. Each form posts with
// `action={actions.name}` (works without JS) and is enhanced by `data-ajax-form="name"`.
import { forms } from './forms';
import { payments } from './payments';
import { shop } from './shop';
import { training } from './training';

export const server = {
  ...forms,
  ...shop,
  ...payments,
  ...training,
};

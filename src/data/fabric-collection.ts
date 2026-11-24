// Fabric lookbook cards shown on the homepage, services page and gallery. Files live in public/images/fabrics/.
// Names and types are copied from the label on each card; keep them in sync when adding images.
// Order matters: the homepage shows entries 0–5 and the services page entries 6–11.
export interface Fabric {
  name: string;
  type: string;
  image: string;
}

export const fabricCollection: Fabric[] = [
  { name: 'Santa', type: 'Bouclé', image: 'santa-boucle.jpeg' },
  { name: 'Bella', type: 'Fur', image: 'bella-fur.jpeg' },
  { name: 'Costa', type: 'Linen', image: 'costa-linen.jpeg' },
  { name: 'New Cubra', type: 'Linen', image: 'new-cubra-linen.jpeg' },
  { name: 'Coco', type: 'Bouclé', image: 'coco-boucle.jpeg' },
  { name: 'Dona', type: 'Linen', image: 'dona-linen.jpeg' },
  { name: 'Liza', type: 'Bouclé', image: 'liza-boucle.jpeg' },
  { name: 'Cito', type: 'Linen', image: 'cito-linen.jpeg' },
  { name: 'Prox', type: 'Velvet', image: 'prox-velvet.jpeg' },
  { name: 'Karla', type: 'Linen', image: 'karla-linen.jpeg' },
  { name: 'Lenen', type: 'Linen', image: 'lenen-linen.jpeg' },
  { name: 'Rock', type: 'Velvet', image: 'rock-velvet.jpeg' },
  { name: 'Soho', type: 'Inverted leather', image: 'soho-inverted-leather.jpeg' },
  { name: 'Harmony', type: 'Linen', image: 'harmony-linen.jpeg' },
  { name: 'Ferrari', type: 'Linen & velvet', image: 'ferrari-linen-velvet.jpeg' },
  { name: 'New Tokyo', type: 'Linen', image: 'new-tokyo-linen.jpeg' },
  { name: 'Verola', type: 'Linen', image: 'verola-linen.jpeg' },
  { name: 'Koya', type: 'Linen', image: 'koya-linen.jpeg' },
];

export const fabricImage = (file: string) => `/images/fabrics/${file}`;

/** Fallback for products without a photo. */
export const PRODUCT_PLACEHOLDER_IMAGE = fabricImage('piki-17-fabric-samples.jpeg');

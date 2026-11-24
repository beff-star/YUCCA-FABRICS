// Virtual training courses from yucca_homeware_hourly_training_rates.pdf (docs/). The seed copies the titles
// and hourly rates into `training_courses`, where admin can edit them; this file adds the look of each card.
// The outlines (summary, outcomes, who it's for) are a first draft for the owner to review.
import type { TRAINING_TRACKS } from '../db/schema.ts';

export type TrainingTrack = (typeof TRAINING_TRACKS)[number];

export interface CourseSeed {
  slug: string;
  title: string;
  track: TrainingTrack;
  level: 'beginner' | 'all levels' | 'advanced';
  /** KES per hour, from the PDF. */
  hourlyRate: number;
  summary: string;
  outcomes: string[];
  whoFor: string;
}

export const courseSeeds: CourseSeed[] = [
  {
    slug: 'furniture-education-quality-assessment',
    title: 'Furniture education & quality assessment',
    track: 'furniture',
    level: 'beginner',
    hourlyRate: 3500,
    summary: 'Learn how good furniture is built and how to judge a piece before you buy, restore or reupholster it.',
    outcomes: [
      'Tell a solid hardwood frame from a weak one',
      'Check joints, springs, webbing and foam',
      'Spot finishing shortcuts and hidden damage',
      'Use a simple checklist to assess any piece yourself',
    ],
    whoFor: 'Homeowners, first-time buyers and anyone looking at second-hand furniture.',
  },
  {
    slug: 'furniture-construction-making',
    title: 'Furniture construction & making',
    track: 'furniture',
    level: 'all levels',
    hourlyRate: 4000,
    summary: 'Understand how furniture goes from timber to finished piece, and how to plan and build your own.',
    outcomes: [
      'Choose timber and board materials for each part',
      'Understand common joints and when to use them',
      'Follow the build sequence from frame to finish',
      'Pick starter tools and work safely',
    ],
    whoFor: 'Aspiring makers, carpenters growing their skills and confident DIYers.',
  },
  {
    slug: 'upholstery-fabric-education',
    title: 'Upholstery & fabric education',
    track: 'upholstery',
    level: 'beginner',
    hourlyRate: 3500,
    summary: 'Get to know upholstery from the inside out: the layers under the fabric and how each fabric wears.',
    outcomes: [
      'Compare bouclé, linen, velvet and leather for real homes',
      'Match fabric to children, pets and strong sunlight',
      'Understand webbing, springs, foam and wadding',
      'Estimate how much fabric a piece needs',
    ],
    whoFor: 'Homeowners choosing fabric, new upholsterers and interior design students.',
  },
  {
    slug: 'interior-design-education',
    title: 'Interior design education',
    track: 'interior',
    level: 'all levels',
    hourlyRate: 4000,
    summary: 'Learn the principles designers use to make rooms feel balanced, comfortable and personal.',
    outcomes: [
      'Use scale, proportion and balance in a room',
      'Build a colour palette that holds together',
      'Plan layouts around how people move and sit',
      'Layer lighting, texture and pattern',
    ],
    whoFor: 'Design enthusiasts, students and homeowners planning their own rooms.',
  },
  {
    slug: 'personalised-interior-design-consultation',
    title: 'Personalised interior design consultation',
    track: 'interior',
    level: 'all levels',
    hourlyRate: 4500,
    summary: 'A live working session on your own space, using your photos and measurements.',
    outcomes: [
      'Layout ideas for the room you actually have',
      'A colour and fabric direction to work from',
      'Advice on what to keep, move or replace',
      'A prioritised list of next steps',
    ],
    whoFor: 'Anyone with a specific room, home or small commercial space to improve.',
  },
  {
    slug: 'furniture-buying-advisory',
    title: 'Furniture buying advisory',
    track: 'furniture',
    level: 'all levels',
    hourlyRate: 4000,
    summary: 'Independent advice before you spend, so the furniture you buy fits your room, budget and life.',
    outcomes: [
      'Size and scale furniture for your room',
      'Know where to spend and where to save',
      'Quality checks to do in the showroom',
      'Questions to ask sellers and makers',
    ],
    whoFor: 'Anyone furnishing a new home, office or rental, or replacing a major piece.',
  },
  {
    slug: 'advanced-furniture-interior-design-training',
    title: 'Advanced furniture & interior design training',
    track: 'interior',
    level: 'advanced',
    hourlyRate: 5000,
    summary: 'In-depth training for people who design, make or specify furniture and interiors for others.',
    outcomes: [
      'Take a project from client brief to installation',
      'Specify furniture, fabrics and finishes clearly',
      'Work well with makers and upholsterers',
      'Cost and present a design scheme',
    ],
    whoFor: 'Design students, practising designers and makers growing their business.',
  },
];

export interface TrackDetails {
  label: string;
  icon: string;
  blurb: string;
  /** Card background; white text passes WCAG AA on each. */
  bg: string;
}

export const tracks: Record<TrainingTrack, TrackDetails> = {
  furniture: {
    label: 'Furniture',
    icon: 'fa6-solid:chair',
    blurb: 'How furniture is made, how to judge its quality and how to buy well.',
    bg: '#1F5B43',
  },
  upholstery: {
    label: 'Upholstery & fabric',
    icon: 'fa6-solid:swatchbook',
    blurb: 'The layers under the fabric, and the fabric that suits your home.',
    bg: '#16646E',
  },
  interior: {
    label: 'Interior design',
    icon: 'fa6-solid:compass-drafting',
    blurb: 'Colour, layout and styling, from first principles to your own room.',
    bg: '#A9461E',
  },
};

interface CourseLook {
  icon: string;
  image: string;
  imageAlt: string;
}

const looks: Record<string, CourseLook> = {
  'furniture-education-quality-assessment': { icon: 'fa6-solid:magnifying-glass', image: 'reupholstery-tufted-corduroy.jpg', imageAlt: 'Close-up of a tufted corduroy seat showing its stitching and buttons' },
  'furniture-construction-making': { icon: 'fa6-solid:hammer', image: 'upholstery-workshop-chair.jpg', imageAlt: 'A chair being worked on in a furniture workshop' },
  'upholstery-fabric-education': { icon: 'fa6-solid:swatchbook', image: 'fabric-consultation-samples.jpg', imageAlt: 'Hands comparing colourful fabric samples on a table' },
  'interior-design-education': { icon: 'fa6-solid:palette', image: 'design-desk-swatches.jpg', imageAlt: 'A designer’s desk with colour swatches and fabric samples' },
  'personalised-interior-design-consultation': { icon: 'fa6-solid:house-chimney', image: 'room-styling-swatches-plans.jpg', imageAlt: 'A hand holding colour swatches over room plans' },
  'furniture-buying-advisory': { icon: 'fa6-solid:tags', image: 'green-sofa-cushions.jpg', imageAlt: 'A green sofa with scatter cushions' },
  'advanced-furniture-interior-design-training': { icon: 'fa6-solid:graduation-cap', image: 'consultation-material-samples.jpg', imageAlt: 'Material and finish samples laid out for a design presentation' },
};

export const courseLook = (slug: string): CourseLook =>
  looks[slug] ?? { icon: 'fa6-solid:chalkboard-user', image: 'design-desk-swatches.jpg', imageAlt: 'A designer’s desk with colour swatches' };

export const PLATFORM_LABELS = {
  google_meet: 'Google Meet',
  zoom: 'Zoom',
  whatsapp: 'WhatsApp video',
} as const;

/** The five guidelines from the rates PDF, word for word. */
export const BOOKING_GUIDELINES = [
  'Minimum booking: 1 hour.',
  'Sessions are conducted virtually via Google Meet, Zoom or WhatsApp video.',
  'Additional time is charged at the applicable hourly rate.',
  'Payment is required before the session.',
  'Group training is quoted separately.',
];

export const TRAINING_RULES = {
  maxHours: 4,
  /** Earliest bookable start, in hours from now. */
  leadHours: 24,
  /** How far ahead customers can book, in days. */
  horizonDays: 60,
  /** How long an unpaid booking holds its slot. */
  holdMinutes: 15,
};

export const SKILL_LEVELS = ['Complete beginner', 'Some experience', 'Working professional'] as const;

export const PLATFORM_DETAILS = {
  google_meet: { icon: 'fa6-solid:video', note: 'A Meet link and calendar invite are emailed to you once you’ve paid. Works in your browser.' },
  zoom: { icon: 'fa6-solid:display', note: 'We email your Zoom link before the session. Use the Zoom app or your browser.' },
  whatsapp: { icon: 'fa6-brands:whatsapp', note: 'We video-call the phone number you give us at the start time.' },
} as const;

export const LEVEL_LABELS = { beginner: 'Beginner', 'all levels': 'All levels', advanced: 'Advanced' } as const;

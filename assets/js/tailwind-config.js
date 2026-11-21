// Shared Tailwind theme for the storefront and admin. Colours are taken from the Yucca logo
// (olive leaves, wooden pot, cream ground); every text colour passes WCAG AA on white and sand.
tailwind.config = {
  theme: {
    extend: {
      colors: {
        forest: '#3F5320', // deep olive: primary buttons, headings accents
        olive: '#5B7029', // logo leaf green: hovers, links
        sage: '#576829', // small labels; 5.1:1 even on cream
        clay: '#8C5530', // logo wood brown: secondary accent and CTAs
        wheat: '#E4C59A', // warm accent on dark backgrounds; 5.2:1 on forest
        sand: '#F7F4EE', // page background
        cream: '#EFEADF', // alternate section background
        ink: '#2A2B25', // body text
        night: '#252E16', // footer and top bar
        // Warm greys to sit with the cream palette; gray-500 and darker pass AA on white, sand and cream.
        gray: { 50: '#F7F6F2', 100: '#EFEEE8', 200: '#E2E0D8', 300: '#CBC9BF', 400: '#8C8D83', 500: '#5E5F56', 600: '#4B4C44', 700: '#3A3B34', 800: '#2A2B25', 900: '#1D1E19' },
      },
      fontFamily: {
        sans: ['Inter', 'sans-serif'],
        serif: ['Poppins', 'sans-serif'],
      },
    },
  },
};

module.exports = {
  content: [
    '**/*.twig',
    '**/*.pcss'
  ],
  theme: {
    extend: {
      fontFamily: {
        sans: ['Lato', 'sans-serif'],
        headline: ['Chronicle SSm A', 'Chronicle SSm B', 'serif'],
      },
      colors: {
        'blue': '#00B3DF',
        'orange': '#FCB316',
        'pink': '#CE539E',
        'green': '#AAC02C',
      },
    },
  },
  variants: {
    extend: {},
  },
  plugins: [
    require('@tailwindcss/typography'),
  ],
};

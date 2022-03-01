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
        iiedblue: {
          "50": "#32e5ff",
          "100": "#28dbff",
          "200": "#1ed1fd",
          "300": "#14c7f3",
          "400": "#0abde9",
          "500": "#00b3df",
          "600": "#00a9d5",
          "700": "#009fcb",
          "800": "#0095c1",
          "900": "#008bb7"
        }, 
        iiedorange: {
          "50": "#ffe548",
          "100": "#ffdb3e",
          "200": "#ffd134",
          "300": "#ffc72a",
          "400": "#ffbd20",
          "500": "#fcb316",
          "600": "#f2a90c",
          "700": "#e89f02",
          "800": "#de9500",
          "900": "#d48b00"
        }, 
        iiedpink: {
          "50": "#ff85d0",
          "100": "#f67bc6",
          "200": "#ec71bc",
          "300": "#e267b2",
          "400": "#d85da8",
          "500": "#ce539e",
          "600": "#c44994",
          "700": "#ba3f8a",
          "800": "#b03580",
          "900": "#a62b76"
        },
        iiedgreen: {
          "50": "#dcf25e",
          "100": "#d2e854",
          "200": "#c8de4a",
          "300": "#bed440",
          "400": "#b4ca36",
          "500": "#aac02c",
          "600": "#a0b622",
          "700": "#96ac18",
          "800": "#8ca20e",
          "900": "#829804"
        }, 
        link: {
          "text": "#1173B3",
          "hover": "#0E6196",
          "visited": "#51758B"
        }
      },
      typography: (theme) => ({
        DEFAULT: {
          css: {
            a: {
              borderBottomWidth: '1px',
              borderBottomColor: theme('colors.iiedblue.200'),
              color: theme('colors.link[text]'),
              fontWeight: '400',
              textDecoration: 'none',
              '&:hover': {
                color: theme('colors.link[hover]'),
                borderBottomWidth: '2px',
                borderBottomColor: theme('colors.iiedblue.500'),
                textDecoration: 'underline',
              },
            },
          },
        },
      })
    },
  },
  variants: {
    extend: {},
  },
  plugins: [
    require('@tailwindcss/typography'),
  ],
};

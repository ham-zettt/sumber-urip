/** @type {import('tailwindcss').Config} */
module.exports = {
  content: ['./*.php', './includes/**/*.php', './actions/**/*.php', './assets/js/**/*.js'],
  theme: {
    extend: {
      colors: {
        brand: {
          50:  '#F2F7FD',
          100: '#E3EEFB',
          200: '#C7DCF7',
          300: '#9CC1F0',
          400: '#669FE6',
          500: '#3F82DA',
          600: '#2766C0', // primary
          700: '#1F52A0', // primary hover
          800: '#1C4482',
          900: '#1B3A6B',
          950: '#112445',
        },
        ink: {
          DEFAULT: '#0F1B2D', // primary text
          soft:    '#33425A', // secondary text
          muted:   '#5B6B82', // tertiary text, labels
          faint:   '#8795AA', // placeholders, disabled text
        },
        line: {
          DEFAULT: '#E4EAF2', // hairline borders, dividers
          strong:  '#CBD5E3', // input borders
        },
        surface: {
          DEFAULT: '#F7F9FC', // subtle section / table header background
          sunken:  '#EEF2F8', // pressed / hover on neutral items
        },
        success: { 50: '#EAF6F0', 600: '#1F8A5B', 700: '#176B47' },
        danger:  { 50: '#FBEDEC', 600: '#C2403A', 700: '#9E322D' },
        warning: { 50: '#FBF3E3', 600: '#B7791F', 700: '#8F5E17' },
      },
      fontFamily: {
        sans: ['"Plus Jakarta Sans"', 'ui-sans-serif', 'system-ui', 'sans-serif'],
      },
      fontSize: {
        // [size, {lineHeight, letterSpacing, fontWeight}]
        'display': ['2.25rem', { lineHeight: '2.5rem', letterSpacing: '-0.02em', fontWeight: '700' }],
        'title':   ['1.5rem',  { lineHeight: '2rem',   letterSpacing: '-0.015em', fontWeight: '700' }],
        'heading': ['1.125rem',{ lineHeight: '1.75rem',letterSpacing: '-0.01em', fontWeight: '600' }],
        'body':    ['0.9375rem',{ lineHeight: '1.5rem' }],
        'small':   ['0.8125rem',{ lineHeight: '1.25rem' }],
        'micro':   ['0.6875rem',{ lineHeight: '1rem', letterSpacing: '0.06em', fontWeight: '600' }],
      },
      borderRadius: {
        DEFAULT: '0.5rem',  // 8px: buttons, inputs
        lg: '0.625rem',     // 10px: cards
        xl: '0.875rem',     // 14px: large panels, modals (maximum)
      },
      boxShadow: {
        card:  '0 1px 2px rgba(15, 27, 45, 0.04)',
        pop:   '0 8px 24px -8px rgba(15, 27, 45, 0.16)', // modals, dropdowns only
      },
      ringColor: { DEFAULT: '#2766C0' },
    },
  },
  plugins: [],
};

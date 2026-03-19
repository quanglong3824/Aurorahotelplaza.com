/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./**/*.php",
    "./assets/js/**/*.js"
  ],
  theme: {
    extend: {
      colors: {
        accent: '#d4af37', // Gold color
      },
      fontFamily: {
        sans: ['Inter', 'system-ui', 'sans-serif'],
      },
    },
  },
  plugins: [],
}

/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./public/**/*.php",
    "./public/**/*.html", 
    "./src/**/*.js",
    "./components/**/*.php",
    "./**/*.php", // Incluye todos los archivos PHP
    "./beneficiarios.php" // Añade específicamente este archivo
  ],
  theme: {
    extend: {},
  },
  plugins: [],
}
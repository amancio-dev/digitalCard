import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],
    theme: {
        borderRadius: { none: '0', sm: '4px', DEFAULT: '6px', md: '6px', lg: '8px', xl: '10px', '2xl': '10px', '3xl': '12px', full: '9999px' },
        extend: {
            colors: {
                paper: '#F4F1EA', surface: '#FBF9F4', ink: '#14110F', line: '#DDD6C8',
                slate: { 50: '#FBF9F4', 100: '#F4F1EA', 200: '#DDD6C8', 300: '#C9C1B1', 400: '#9A9285', 500: '#6B655C', 600: '#55504A', 700: '#3D3935', 800: '#26231F', 900: '#14110F', 950: '#0C0A09' },
                accent: { DEFAULT: '#D9480F', 50: '#FFF3EB', 100: '#FFE4D2', 200: '#FFC7A3', 300: '#F59A63', 400: '#E8671F', 500: '#D9480F', 600: '#B93A08', 700: '#922E07', 800: '#6E2306', 900: '#4A1804' },
                money: { DEFAULT: '#1F7A4D', 50: '#EDF7F1', 100: '#D5EDDF', 200: '#A9D8BF', 300: '#78BF9A', 400: '#44A276', 500: '#2A8A5D', 600: '#1F7A4D', 700: '#19623E', 800: '#154F33', 900: '#103C27' },
                danger: { DEFAULT: '#B42318', 50: '#FDF1EF', 100: '#FADDD8', 200: '#F3B7AE', 300: '#E98A7C', 400: '#D8584A', 500: '#C93B2C', 600: '#B42318', 700: '#912018', 800: '#6F1A14', 900: '#4B120E' },
            },
            fontFamily: {
                sans: ['"Instrument Sans Variable"', ...defaultTheme.fontFamily.sans],
                serif: ['"Instrument Serif"', ...defaultTheme.fontFamily.serif],
                mono: ['"Geist Mono Variable"', ...defaultTheme.fontFamily.mono],
            },
            boxShadow: { card: '0 0 0 1px #DDD6C8', 'card-hover': '0 0 0 1px #14110F', float: '0 12px 32px -12px rgb(20 17 15 / 0.28)' },
        },
    },
    plugins: [forms],
};

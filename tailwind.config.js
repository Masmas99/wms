import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    darkMode: 'class',

    theme: {
        extend: {
            colors: {
                brand: {
                    DEFAULT: '#183E8C',
                    50: '#F4F7FE',
                    100: '#E4ECFB',
                    200: '#C6D6F6',
                    300: '#9CB7EC',
                    400: '#608BE1',
                    500: '#2158CA',
                    600: '#183E8C',
                    700: '#16336F',
                    800: '#142A57',
                    900: '#12213F',
                    950: '#0F172A',
                },
            },
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            borderRadius: {
                control: '0.75rem',
                card: '1rem',
            },
            boxShadow: {
                card: '0 1px 2px 0 rgb(24 62 140 / 0.05)',
                pop: '0 20px 40px -12px rgb(24 62 140 / 0.25)',
            },
        },
    },

    plugins: [forms],
};

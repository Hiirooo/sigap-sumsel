import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.jsx',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                serif: ['"Playfair Display"', 'serif'],
            },
            colors: {
                primary: {
                    DEFAULT: '#0c2d5e',
                    light: '#1a4d8f',
                    dark: '#0a1628',
                },
                gold: {
                    DEFAULT: '#c9a84c',
                    light: '#e8c96d',
                    dark: '#b39038',
                }
            }
        },
    },

    plugins: [forms],
};

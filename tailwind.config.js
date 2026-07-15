import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                ocean: {
                    50: '#eefbfc',
                    100: '#d5f3f6',
                    200: '#aee6ec',
                    300: '#78d3dd',
                    400: '#3fb8c7',
                    500: '#249cac',
                    600: '#1f7e91',
                    700: '#1f6676',
                    800: '#215462',
                    900: '#204654',
                    950: '#102c38',
                },
            },
        },
    },

    plugins: [forms],
};

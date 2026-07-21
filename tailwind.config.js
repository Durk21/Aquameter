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
                display: ['"Space Grotesk"', ...defaultTheme.fontFamily.sans],
                mono: ['"JetBrains Mono"', ...defaultTheme.fontFamily.mono],
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
            boxShadow: {
                soft: '0 2px 8px -2px rgb(16 44 56 / 0.08), 0 1px 2px -1px rgb(16 44 56 / 0.06)',
                elevated: '0 8px 24px -6px rgb(16 44 56 / 0.14), 0 2px 6px -2px rgb(16 44 56 / 0.08)',
                glow: '0 0 0 1px rgb(63 184 199 / 0.15), 0 8px 30px -8px rgb(36 156 172 / 0.35)',
            },
            backgroundImage: {
                'gradient-ocean': 'linear-gradient(135deg, #249cac 0%, #1f6676 100%)',
                'gradient-sunrise': 'linear-gradient(135deg, #3fb8c7 0%, #f59e0b 100%)',
                'gradient-radial': 'radial-gradient(circle at top left, var(--tw-gradient-stops))',
            },
        },
    },

    plugins: [forms],
};

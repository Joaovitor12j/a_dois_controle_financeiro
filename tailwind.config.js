import forms from '@tailwindcss/forms';
import defaultTheme from 'tailwindcss/defaultTheme';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.tsx',
    ],

    theme: {
        extend: {
            colors: {
                tinta: {
                    DEFAULT: '#14202E',
                    claro: '#3A4B5F',
                },
                papel: {
                    DEFAULT: '#F7F3EC',
                    sombra: '#EDE6DA',
                },
                verde: {
                    DEFAULT: '#2F6F5E',
                    escuro: '#1E4A3E',
                },
                vinho: {
                    DEFAULT: '#7B3F55',
                    escuro: '#4E2836',
                },
                ouro: {
                    DEFAULT: '#D9A441',
                    suave: '#EBCB89',
                },
            },
            fontFamily: {
                sans: ['Instrument Sans', ...defaultTheme.fontFamily.sans],
                display: ['Fraunces', ...defaultTheme.fontFamily.serif],
            },
        },
    },

    plugins: [forms],
};

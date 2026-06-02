import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';

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
                sans:    ['Figtree',   ...defaultTheme.fontFamily.sans],
                display: ['Rajdhani',  ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // Identidad Visual rApanel
                'rapanel-navy': {
                    50:  '#f8fafc',  // Background Claro
                    100: '#e2e8f0',  // Bordes Claros
                    600: '#475569',  // Borde Dark (hover)
                    700: '#334155',  // Superficie Dark Media (botones)
                    800: '#1e293b',  // Superficie Dark (Cards)
                    900: '#0f172a',  // Background Dark
                },
                'rapanel-text': {
                    light: '#1d283a', // Tema Claro
                    dark: '#E2E8F0',  // Tema Oscuro
                },
                'rapanel-blue':   { DEFAULT: '#4A90E2', dark: '#1e3a5f' }, // Kafra Blue (Primario)

                // ESTADOS Y ALERTAS
                'rapanel-success': { DEFAULT: '#2ECC71', dark: '#14532d' }, // Heal Green
                'rapanel-danger':  { DEFAULT: '#E74C3C', dark: '#7f1d1d' }, // MVP Red
                'rapanel-gold':    { DEFAULT: '#F1C40F', dark: '#78350f' }, // Emperium Gold
                'rapanel-purple':  { DEFAULT: '#a855f7', dark: '#581c87' }, // Mystic Purple (VIP / Gender)
            },
        },
    },

    plugins: [forms, typography],
};

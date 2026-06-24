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
                // Identidad Visual rApanel — tokens como CSS custom properties (theming en runtime).
                // Formato: rgb(var(--token-rgb, <fallback R G B>) / <alpha-value>).
                //   · El fallback = valor hex original (en tripleta RGB) → si no hay variable
                //     inyectada (app.blade.php / runtime), se ve idéntico a antes (cero regresión, cero FOUC).
                //   · La tripleta `R G B` + `/ <alpha-value>` preserva la opacidad de Tailwind
                //     (ej. bg-rapanel-blue/30, dark:bg-rapanel-blue/10) — ver Fase 1 del plan.
                'rapanel-navy': {
                    50:  'rgb(var(--rapanel-navy-50-rgb,  248 250 252) / <alpha-value>)', // Background Claro
                    100: 'rgb(var(--rapanel-navy-100-rgb, 226 232 240) / <alpha-value>)', // Bordes Claros
                    600: 'rgb(var(--rapanel-navy-600-rgb,  71  85 105) / <alpha-value>)', // Borde Dark (hover)
                    700: 'rgb(var(--rapanel-navy-700-rgb,  51  65  85) / <alpha-value>)', // Superficie Dark Media (botones)
                    800: 'rgb(var(--rapanel-navy-800-rgb,  30  41  59) / <alpha-value>)', // Superficie Dark (Cards)
                    900: 'rgb(var(--rapanel-navy-900-rgb,  15  23  42) / <alpha-value>)', // Background Dark
                },
                'rapanel-text': {
                    light: 'rgb(var(--rapanel-text-light-rgb, 29 40 58)  / <alpha-value>)', // Tema Claro
                    dark:  'rgb(var(--rapanel-text-dark-rgb, 226 232 240) / <alpha-value>)', // Tema Oscuro
                },
                'rapanel-blue': {
                    DEFAULT: 'rgb(var(--rapanel-blue-rgb,      74 144 226) / <alpha-value>)', // Kafra Blue (Primario)
                    dark:    'rgb(var(--rapanel-blue-dark-rgb, 30  58  95) / <alpha-value>)',
                },

                // ESTADOS Y ALERTAS
                'rapanel-success': {
                    DEFAULT: 'rgb(var(--rapanel-success-rgb,      46 204 113) / <alpha-value>)', // Heal Green
                    dark:    'rgb(var(--rapanel-success-dark-rgb, 20  83  45) / <alpha-value>)',
                },
                'rapanel-danger': {
                    DEFAULT: 'rgb(var(--rapanel-danger-rgb,      231 76 60) / <alpha-value>)', // MVP Red
                    dark:    'rgb(var(--rapanel-danger-dark-rgb, 127 29 29) / <alpha-value>)',
                },
                'rapanel-gold': {
                    DEFAULT: 'rgb(var(--rapanel-gold-rgb,      241 196 15) / <alpha-value>)', // Emperium Gold
                    dark:    'rgb(var(--rapanel-gold-dark-rgb, 120  53 15) / <alpha-value>)',
                },
                'rapanel-purple': {
                    DEFAULT: 'rgb(var(--rapanel-purple-rgb,      168 85 247) / <alpha-value>)', // Mystic Purple (VIP / Gender)
                    dark:    'rgb(var(--rapanel-purple-dark-rgb,  88 28 135) / <alpha-value>)',
                },

                // Dark mode surface tokens
                'rapanel-surface':      'rgb(var(--rapanel-surface-rgb,      15 24 41) / <alpha-value>)', // dark card/panel background
                'rapanel-surface-deep': 'rgb(var(--rapanel-surface-deep-rgb, 11 17 32) / <alpha-value>)', // dark sidebar/topbar background
                'rapanel-base-dark':    'rgb(var(--rapanel-base-dark-rgb,      8 13 20) / <alpha-value>)', // dark page base background
            },
        },
    },

    plugins: [forms, typography],
};

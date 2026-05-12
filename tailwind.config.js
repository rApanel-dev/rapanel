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
                // Identidad Visual rApanel
                'rapanel-navy': {
                    50: '#f8fafc',  // Background Claro
                    100: '#e2e8f0', // Bordes Claros
                    800: '#1e293b', // Superficie Dark (Cards)
                    900: '#0f172a', // Background Dark
                },
                'rapanel-text': {
                    light: '#1d283a', // Texto Claro
                    dark: '#E2E8F0',  // Texto Oscuro
                },
                'rapanel-blue': '#4A90E2', // Kafra Blue (Primario)
                'rapanel-gold': '#F1C40F', // Emperium Gold (Acento)
                
                // ESTADOS Y ALERTAS
                'rapanel-success': '#2ECC71', // Heal Green (Éxito / Online)
                'rapanel-danger': '#E74C3C',  // MVP Red (Error / Eliminar)
            },
        },
    },

    plugins: [forms],
};

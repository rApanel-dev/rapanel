<?php

/*
|--------------------------------------------------------------------------
| Theming / Personalización (Fase 2 del plan PLAN_PERSONALIZACION.md)
|--------------------------------------------------------------------------
| Defaults del tema editable por el admin. Los valores hex de aquí coinciden
| EXACTAMENTE con los fallbacks RGB de tailwind.config.js (Fase 1) → si no hay
| un registro `theme` en ra_site_settings, el sitio se ve idéntico (cero
| regresión). Sirven además como fuente única para el botón "Reset".
|
| Modelo (ajustado a cómo funciona ActionButton.vue, que usa un único acento +
| opacidad por modo, NO colores sólidos distintos claro/oscuro):
|   - buttons.*  → un color por variante, compartido en claro y oscuro.
|   - light.*    → fondo / texto del tema claro.
|   - dark.*     → fondo / superficie / texto del tema oscuro.
| El mapeo color→variable CSS vive en App\Support\Theme::cssVars().
*/

return [
    'defaults' => [
        'version' => 1,

        // Colores de botones (ActionButton: blue/gold/purple/navy/success/danger)
        'buttons' => [
            'blue'    => '#4A90E2', // rapanel-blue
            'gold'    => '#F1C40F', // rapanel-gold
            'purple'  => '#a855f7', // rapanel-purple
            'navy'    => '#334155', // rapanel-navy-700 (botón neutral)
            'success' => '#2ECC71', // rapanel-success
            'danger'  => '#E74C3C', // rapanel-danger
        ],

        // Tema claro
        'light' => [
            'bg'   => '#f8fafc', // rapanel-navy-50  (fondo página claro)
            'text' => '#1d283a', // rapanel-text-light
        ],

        // Tema oscuro
        'dark' => [
            'bg'      => '#080d14', // rapanel-base-dark (fondo página oscuro)
            'surface' => '#0f1829', // rapanel-surface   (cards/paneles oscuro)
            'text'    => '#E2E8F0', // rapanel-text-dark
        ],
    ],
];

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
        'version' => 2,

        // ── Secciones con color separado claro/oscuro (theming por zona) ──────────
        // Aplican a Header.vue / Footer.vue → web pública + área de usuario logueado
        // (NO al panel admin, que tiene su propia barra). Defaults = colores actuales.
        'header' => [
            'light' => ['bg' => '#ffffff', 'text' => '#1d283a', 'link' => '#4A90E2'],
            'dark'  => ['bg' => '#0f172a', 'text' => '#E2E8F0', 'link' => '#ffffff'],
        ],
        'footer' => [
            'light' => ['bg' => '#f8fafc', 'text' => '#1d283a', 'link' => '#475569'],
            'dark'  => ['bg' => '#0f172a', 'text' => '#E2E8F0', 'link' => '#E2E8F0'],
        ],

        // Estilo de la home pública (HomeAlt): degradado de los títulos, color de
        // acento decorativo (rejillas/glows) y paleta de tarjetas. No varía por modo.
        'home' => [
            'title_gradient' => ['from' => '#4A90E2', 'mid' => '#F1C40F', 'to' => '#a855f7'],
            'accent'  => '#4A90E2', // acento principal (decorativos + inicio del degradado del botón)
            'accent2' => '#2563eb', // fin del degradado del botón del hero
            'ghost'   => '#4A90E2', // botón secundario del hero (Learn More)
            'card'    => ['light' => '#ffffff', 'dark' => '#0f172a'], // superficie de tarjetas de la home
            'palette' => ['#4A90E2', '#F1C40F', '#2ECC71', '#a855f7', '#E74C3C', '#e2e8f0'],
        ],

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
            'bg'   => '#f8fafc', // fondo de página claro (→ rapanel-page)
            'text' => '#1d283a', // rapanel-text-light
        ],

        // Tema oscuro
        'dark' => [
            'bg'      => '#0f172a', // fondo de página oscuro (→ rapanel-page; navy-900 real)
            'surface' => '#0f1829', // rapanel-surface   (cards/paneles oscuro)
            'text'    => '#E2E8F0', // rapanel-text-dark
        ],
    ],
];

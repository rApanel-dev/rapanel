<?php

namespace App\Support;

/**
 * Helper de theming en runtime (Fases 2-4 de PLAN_PERSONALIZACION.md).
 *
 * Convierte el tema elegido por el admin (hex) en las CSS custom properties
 * `--*-rgb` (tripletas "R G B") que consume tailwind.config.js (Fase 1) vía
 * `rgb(var(--token-rgb, <fallback>) / <alpha-value>)`.
 */
class Theme
{
    /** Defaults centralizados (= valores actuales del config Tailwind). */
    public static function defaults(): array
    {
        return config('theme.defaults');
    }

    /** '#4A90E2' → '74 144 226'. Acepta también shorthand de 3 dígitos. */
    public static function rgb(string $hex): string
    {
        $hex = ltrim(trim($hex), '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        return "{$r} {$g} {$b}";
    }

    /**
     * Mezcla un tema almacenado sobre los defaults (por sección), tolerando
     * claves faltantes. Devuelve siempre un tema completo y válido.
     */
    public static function merged(?array $theme): array
    {
        $d = self::defaults();
        if (! $theme) {
            return $d;
        }

        return [
            'version' => 1,
            'buttons' => array_merge($d['buttons'], $theme['buttons'] ?? []),
            'light'   => array_merge($d['light'],   $theme['light']   ?? []),
            'dark'    => array_merge($d['dark'],    $theme['dark']    ?? []),
        ];
    }

    /**
     * Mapeo color (hex) → variable CSS. Fuente única de verdad del theming.
     *
     * Nota navy: el botón "navy" mapea a --rapanel-navy-700-rgb (su superficie
     * en oscuro). El botón navy en claro usa rapanel-navy-100, compartido con
     * los bordes, por eso no se theme-a desde aquí (queda neutral por diseño).
     *
     * @return array<string,string> nombre-de-variable => hex
     */
    public static function varMap(?array $theme): array
    {
        $t = self::merged($theme);

        return [
            // Fondos / texto por modo
            '--rapanel-navy-50-rgb'    => $t['light']['bg'],
            '--rapanel-text-light-rgb' => $t['light']['text'],
            '--rapanel-base-dark-rgb'  => $t['dark']['bg'],
            '--rapanel-surface-rgb'    => $t['dark']['surface'],
            '--rapanel-text-dark-rgb'  => $t['dark']['text'],

            // Acentos de botones (compartidos claro/oscuro; la opacidad de
            // ActionButton adapta el resultado a cada modo)
            '--rapanel-blue-rgb'       => $t['buttons']['blue'],
            '--rapanel-gold-rgb'       => $t['buttons']['gold'],
            '--rapanel-purple-rgb'     => $t['buttons']['purple'],
            '--rapanel-navy-700-rgb'   => $t['buttons']['navy'],
            '--rapanel-success-rgb'    => $t['buttons']['success'],
            '--rapanel-danger-rgb'     => $t['buttons']['danger'],
        ];
    }

    /**
     * Cuerpo del bloque `:root { ... }` listo para inyectar en <head>
     * (anti-FOUC, server-side). Cada variable como tripleta "R G B".
     */
    public static function cssVars(?array $theme): string
    {
        $out = '';
        foreach (self::varMap($theme) as $var => $hex) {
            $out .= $var.':'.self::rgb($hex).';';
        }

        return $out;
    }
}

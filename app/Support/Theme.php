<?php

namespace App\Support;

/**
 * Helper de theming en runtime (PLAN_PERSONALIZACION.md, v2 por secciones).
 *
 * Convierte el tema del admin (hex) en CSS custom properties `--*-rgb` que
 * consume tailwind.config.js vía rgb(var(--token-rgb, <fallback>) / <alpha>).
 *
 * v2: las zonas con color distinto claro/oscuro (header, footer, fondos) se
 * emiten en DOS bloques — `:root{}` (claro) y `:root.dark{}` (oscuro) — para que
 * cada modo tenga su valor. Los acentos de botón son compartidos (van en :root).
 */
class Theme
{
    /** Defaults centralizados (= valores actuales del config Tailwind). */
    public static function defaults(): array
    {
        return config('theme.defaults');
    }

    /** '#4A90E2' → '74 144 226'. Acepta shorthand de 3 dígitos. */
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
     * Mezcla un tema almacenado sobre los defaults, tolerando claves faltantes
     * (incluye temas v1 sin header/footer). Devuelve siempre un tema completo.
     */
    public static function merged(?array $theme): array
    {
        $d = self::defaults();
        if (! $theme) {
            return $d;
        }

        return [
            'version' => 2,
            'header'  => [
                'light' => array_merge($d['header']['light'], $theme['header']['light'] ?? []),
                'dark'  => array_merge($d['header']['dark'],  $theme['header']['dark']  ?? []),
            ],
            'footer'  => [
                'light' => array_merge($d['footer']['light'], $theme['footer']['light'] ?? []),
                'dark'  => array_merge($d['footer']['dark'],  $theme['footer']['dark']  ?? []),
            ],
            'buttons' => array_merge($d['buttons'], $theme['buttons'] ?? []),
            'light'   => array_merge($d['light'],   $theme['light']   ?? []),
            'dark'    => array_merge($d['dark'],    $theme['dark']    ?? []),
            'home'    => [
                'title_gradient' => array_merge($d['home']['title_gradient'], $theme['home']['title_gradient'] ?? []),
                'accent'  => $theme['home']['accent']  ?? $d['home']['accent'],
                'accent2' => $theme['home']['accent2'] ?? $d['home']['accent2'],
                'palette' => $theme['home']['palette'] ?? $d['home']['palette'],
            ],
        ];
    }

    /**
     * Vars de estilo de la home (no varían por modo → van en :root). El degradado
     * se emite como hex (se usa en gradients, sin alpha de Tailwind); el acento
     * como tripleta RGB para poder usar rgb(var() / alpha) en rejillas/glows.
     */
    public static function homeVarsBody(?array $theme): string
    {
        $t = self::merged($theme)['home'];
        $g = $t['title_gradient'];

        return "--ha-grad-from:{$g['from']};--ha-grad-mid:{$g['mid']};--ha-grad-to:{$g['to']};"
             . '--ha-accent-rgb:'.self::rgb($t['accent']).';'
             . '--ha-accent2-rgb:'.self::rgb($t['accent2']).';';
    }

    /**
     * Variables del bloque :root (tema CLARO + acentos compartidos).
     * @return array<string,string> variable => hex
     */
    public static function lightVarMap(?array $theme): array
    {
        $t = self::merged($theme);

        return [
            // Globales claro
            '--rapanel-page-bg-rgb'     => $t['light']['bg'],   // fondo de página claro
            '--rapanel-text-light-rgb'  => $t['light']['text'],
            // Colores de BOTÓN (tokens rapanel-btn-*, solo los usa ActionButton).
            // Separados de los acentos base rapanel-* (que quedan FIJOS para los
            // colores funcionales: tipos de ítem, elementos de mob, estados).
            '--rapanel-btn-blue-rgb'    => $t['buttons']['blue'],
            '--rapanel-btn-gold-rgb'    => $t['buttons']['gold'],
            '--rapanel-btn-purple-rgb'  => $t['buttons']['purple'],
            '--rapanel-btn-navy-rgb'    => $t['buttons']['navy'],
            '--rapanel-btn-success-rgb' => $t['buttons']['success'],
            '--rapanel-btn-danger-rgb'  => $t['buttons']['danger'],
            // Header / Footer — claro
            '--rapanel-header-bg-rgb'   => $t['header']['light']['bg'],
            '--rapanel-header-text-rgb' => $t['header']['light']['text'],
            '--rapanel-header-link-rgb' => $t['header']['light']['link'],
            '--rapanel-footer-bg-rgb'   => $t['footer']['light']['bg'],
            '--rapanel-footer-text-rgb' => $t['footer']['light']['text'],
            '--rapanel-footer-link-rgb' => $t['footer']['light']['link'],
        ];
    }

    /**
     * Variables del bloque :root.dark (overrides del tema OSCURO).
     * @return array<string,string> variable => hex
     */
    public static function darkVarMap(?array $theme): array
    {
        $t = self::merged($theme);

        return [
            // Globales oscuro
            '--rapanel-page-bg-rgb'     => $t['dark']['bg'],   // fondo de página oscuro
            '--rapanel-surface-rgb'     => $t['dark']['surface'],
            '--rapanel-text-dark-rgb'   => $t['dark']['text'],
            // Header / Footer — oscuro
            '--rapanel-header-bg-rgb'   => $t['header']['dark']['bg'],
            '--rapanel-header-text-rgb' => $t['header']['dark']['text'],
            '--rapanel-header-link-rgb' => $t['header']['dark']['link'],
            '--rapanel-footer-bg-rgb'   => $t['footer']['dark']['bg'],
            '--rapanel-footer-text-rgb' => $t['footer']['dark']['text'],
            '--rapanel-footer-link-rgb' => $t['footer']['dark']['link'],
        ];
    }

    private static function body(array $map): string
    {
        $out = '';
        foreach ($map as $var => $hex) {
            $out .= $var.':'.self::rgb($hex).';';
        }

        return $out;
    }

    /**
     * CSS completo `:root{...}:root.dark{...}` listo para inyectar en <head>
     * (anti-FOUC). Se emite SIEMPRE (con defaults si no hay tema guardado) para
     * garantizar valores correctos por modo.
     */
    public static function cssVars(?array $theme): string
    {
        return ':root{'.self::body(self::lightVarMap($theme)).self::homeVarsBody($theme).'}'
             . ':root.dark{'.self::body(self::darkVarMap($theme)).'}';
    }
}

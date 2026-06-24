import { hexToRgb } from '@/helpers';

/**
 * Preview en vivo del theming (Fase 6 de PLAN_PERSONALIZACION.md).
 *
 * Mientras el admin mueve los color pickers, escribe un <style id="theme-preview">
 * con un bloque :root{...} de las CSS vars --*-rgb. Como toda la UI consume
 * var(--rapanel-*) (Fases 0+1), el sitio entero se recolorea al instante, sin
 * guardar ni recompilar.
 *
 * El <style> de preview se inserta al final del <head> → gana por orden al
 * <style id="rapanel-theme"> del SSR (Fase 4), igual especificidad. Al limpiarlo
 * (Descartar / salir de la página) se vuelve al tema guardado.
 *
 * IMPORTANTE: el mapeo color→variable debe ser idéntico al de
 * App\Support\Theme::varMap() (backend) para que preview y guardado coincidan.
 */

const STYLE_ID = 'theme-preview';

function varMap(theme) {
    return {
        // Fondos / texto por modo
        '--rapanel-navy-50-rgb':    theme.light.bg,
        '--rapanel-text-light-rgb': theme.light.text,
        '--rapanel-base-dark-rgb':  theme.dark.bg,
        '--rapanel-surface-rgb':    theme.dark.surface,
        '--rapanel-text-dark-rgb':  theme.dark.text,
        // Acentos de botones (compartidos claro/oscuro)
        '--rapanel-blue-rgb':       theme.buttons.blue,
        '--rapanel-gold-rgb':       theme.buttons.gold,
        '--rapanel-purple-rgb':     theme.buttons.purple,
        '--rapanel-navy-700-rgb':   theme.buttons.navy,
        '--rapanel-success-rgb':    theme.buttons.success,
        '--rapanel-danger-rgb':     theme.buttons.danger,
    };
}

function buildCss(theme) {
    let body = '';
    for (const [varName, hex] of Object.entries(varMap(theme))) {
        const rgb = hexToRgb(hex);
        // hex inválido (a medio escribir) → se omite y ese token cae al valor guardado/fallback
        if (rgb) body += `${varName}:${rgb};`;
    }
    return `:root{${body}}`;
}

/** Aplica un tema {buttons, light, dark} como preview en vivo (no persiste). */
export function applyThemePreview(theme) {
    if (!theme?.buttons || !theme?.light || !theme?.dark) return;

    let el = document.getElementById(STYLE_ID);
    if (!el) {
        el = document.createElement('style');
        el.id = STYLE_ID;
        document.head.appendChild(el);
    }
    el.textContent = buildCss(theme);
}

/** Quita el preview → vuelve al tema guardado (SSR) o a los fallbacks del config. */
export function clearThemePreview() {
    document.getElementById(STYLE_ID)?.remove();
}

/**
 * Tras guardar: persiste el tema en el <style id="rapanel-theme"> (el mismo que
 * emite el SSR en el <head>) y limpia el preview. Así el tema recién guardado
 * sobrevive a la navegación SPA y al desmontaje de la página sin recargar; un
 * full reload posterior lo regenera idéntico desde el servidor.
 */
export function commitThemePreview(theme) {
    if (!theme?.buttons || !theme?.light || !theme?.dark) return;

    let el = document.getElementById('rapanel-theme');
    if (!el) {
        el = document.createElement('style');
        el.id = 'rapanel-theme';
        document.head.appendChild(el);
    }
    el.textContent = buildCss(theme);
    clearThemePreview();
}

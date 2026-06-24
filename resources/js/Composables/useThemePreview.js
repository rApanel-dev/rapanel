import { hexToRgb } from '@/helpers';

/**
 * Preview en vivo del theming (v2 por secciones).
 *
 * Mientras el admin mueve los color pickers, escribe un <style id="theme-preview">
 * con DOS bloques :root{} (claro) y :root.dark{} (oscuro). Como toda la UI pública
 * + área de usuario consume var(--rapanel-*), se recolorea al instante sin guardar.
 *
 * El mapeo debe ser IDÉNTICO al de App\Support\Theme (lightVarMap/darkVarMap).
 */

const STYLE_ID = 'theme-preview';

// :root (claro) — globales claro + acentos compartidos + header/footer claro
function lightMap(t) {
    return {
        '--rapanel-navy-50-rgb':     t.light.bg,
        '--rapanel-text-light-rgb':  t.light.text,
        '--rapanel-blue-rgb':        t.buttons.blue,
        '--rapanel-gold-rgb':        t.buttons.gold,
        '--rapanel-purple-rgb':      t.buttons.purple,
        '--rapanel-navy-700-rgb':    t.buttons.navy,
        '--rapanel-success-rgb':     t.buttons.success,
        '--rapanel-danger-rgb':      t.buttons.danger,
        '--rapanel-header-bg-rgb':   t.header.light.bg,
        '--rapanel-header-text-rgb': t.header.light.text,
        '--rapanel-header-link-rgb': t.header.light.link,
        '--rapanel-footer-bg-rgb':   t.footer.light.bg,
        '--rapanel-footer-text-rgb': t.footer.light.text,
        '--rapanel-footer-link-rgb': t.footer.light.link,
    };
}

// :root.dark (oscuro) — globales oscuro + header/footer oscuro
function darkMap(t) {
    return {
        '--rapanel-base-dark-rgb':   t.dark.bg,
        '--rapanel-surface-rgb':     t.dark.surface,
        '--rapanel-text-dark-rgb':   t.dark.text,
        '--rapanel-header-bg-rgb':   t.header.dark.bg,
        '--rapanel-header-text-rgb': t.header.dark.text,
        '--rapanel-header-link-rgb': t.header.dark.link,
        '--rapanel-footer-bg-rgb':   t.footer.dark.bg,
        '--rapanel-footer-text-rgb': t.footer.dark.text,
        '--rapanel-footer-link-rgb': t.footer.dark.link,
    };
}

function body(map) {
    let out = '';
    for (const [name, hex] of Object.entries(map)) {
        const rgb = hexToRgb(hex);
        if (rgb) out += `${name}:${rgb};`; // hex inválido (a medio escribir) → se omite
    }
    return out;
}

function buildCss(t) {
    return `:root{${body(lightMap(t))}}:root.dark{${body(darkMap(t))}}`;
}

function valid(t) {
    return t?.header?.light && t?.header?.dark && t?.footer?.light && t?.footer?.dark
        && t?.buttons && t?.light && t?.dark;
}

/** Aplica un tema completo como preview en vivo (no persiste). */
export function applyThemePreview(theme) {
    if (!valid(theme)) return;

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
 * Tras guardar: persiste el tema en el <style id="rapanel-theme"> (el del SSR) y
 * limpia el preview, para que sobreviva a la navegación SPA sin recargar.
 */
export function commitThemePreview(theme) {
    if (!valid(theme)) return;

    let el = document.getElementById('rapanel-theme');
    if (!el) {
        el = document.createElement('style');
        el.id = 'rapanel-theme';
        document.head.appendChild(el);
    }
    el.textContent = buildCss(theme);
    clearThemePreview();
}

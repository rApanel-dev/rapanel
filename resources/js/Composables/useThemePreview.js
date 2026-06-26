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
        '--rapanel-page-bg-rgb':     t.light.bg,
        '--rapanel-text-light-rgb':  t.light.text,
        '--rapanel-btn-blue-rgb':    t.buttons.blue,
        '--rapanel-btn-gold-rgb':    t.buttons.gold,
        '--rapanel-btn-purple-rgb':  t.buttons.purple,
        '--rapanel-btn-navy-rgb':    t.buttons.navy,
        '--rapanel-btn-success-rgb': t.buttons.success,
        '--rapanel-btn-danger-rgb':  t.buttons.danger,
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
        '--rapanel-page-bg-rgb':     t.dark.bg,
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

// Vars de estilo de la home (degradado hex + acento RGB). No varían por modo → :root.
function homeBody(t) {
    const h = t.home || {};
    const g = h.title_gradient || {};
    let out = '';
    if (g.from) out += `--ha-grad-from:${g.from};`;
    if (g.mid)  out += `--ha-grad-mid:${g.mid};`;
    if (g.to)   out += `--ha-grad-to:${g.to};`;
    const accent = hexToRgb(h.accent);
    if (accent) out += `--ha-accent-rgb:${accent};`;
    const accent2 = hexToRgb(h.accent2);
    if (accent2) out += `--ha-accent2-rgb:${accent2};`;
    return out;
}

function buildCss(t) {
    return `:root{${body(lightMap(t))}${homeBody(t)}}:root.dark{${body(darkMap(t))}}`;
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

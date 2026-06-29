# PLAN DE IMPLEMENTACIÓN — Estandarización de UI + Personalización (Theming en runtime)

> **Estado:** propuesta de diseño. NO implementado.
> **Autor:** arquitectura rApanel · **Fecha:** 2026-06-24
> **Alcance:** dos entregables encadenados.
> - **Fase 0 — Homologación de UI** (prerequisito, entregable separado y previo).
> - **Fase 1+ — Personalización** (sección admin para recolorear todo el sitio en runtime).
>
> La Fase 1+ **depende** de la Fase 0: el theming solo sale limpio si toda la UI consume
> los mismos tokens `rapanel-*`. Si algo queda hardcodeado (`gray-700`, `#1e293b`, `bg-black`),
> el color personalizado **no** lo alcanzará y el resultado será inconsistente.

---

## 0. Resumen ejecutivo

Hoy la paleta vive **en build-time** dentro de `tailwind.config.js:21-47` como valores hex fijos
(`rapanel-navy.900 = '#0f172a'`, `rapanel-blue.DEFAULT = '#4A90E2'`, etc.). Cambiar un color exige
**recompilar** (`npm run build`). El objetivo del proyecto es que un admin recolorease **todo** el
sitio desde el panel, en vivo, sin recompilar.

La estrategia es estándar y de bajo riesgo:

1. **Fase 0** — homologar TODA la UI a tokens `rapanel-*` (botones, tablas, texto, cards, badges),
   eliminando colores hardcodeados. Sin esto, el theming es parcial.
2. **Convertir los tokens de `tailwind.config.js` de hex fijo a `var(--rapanel-*)`** con fallback.
   Las clases `bg-rapanel-blue`, `text-rapanel-text-light`, etc. siguen existiendo y funcionando,
   pero ahora resuelven a variables CSS. Cambiar la variable recolorea todo.
3. **Almacenar** los valores en `ra_site_settings` (ya existe el modelo y el patrón).
4. **Inyectar** las variables en `<head>` (`app.blade.php`) vía `share()` de Inertia — aplica sin recompilar y sin FOUC.
5. **UI admin** de Personalización con **preview en vivo** (escribir las CSS vars en `document.documentElement` mientras se mueven los pickers), Guardar / Descartar / Reset.

---

# FASE 0 — ESTANDARIZACIÓN / HOMOLOGACIÓN (PREREQUISITO)

> **Conclusión de alcance (importante):** la Fase 0 es **grande**. El barrido de colores
> hardcodeados detecta **~311 usos** de clases Tailwind genéricas de color
> (`bg-/text-/border-{gray,slate,red,green,blue,yellow,purple,…}-NNN`) repartidas en **39 archivos**
> de `resources/js/**`, más **~201 literales hex** en **26 archivos** (parte legítimos: pickers, SVG,
> defaults de seed; parte deuda real). Por eso **se propone como entregable separado y previo**, con su
> propio PR/rama y su propia verificación, antes de tocar el theming. No es bloqueante hacerla 100% para
> empezar el theming de los **botones de variante** (que ya están bien homologados), pero sí lo es para
> prometer "recolorea **todo** el sitio".

## F0.1 — Inventario del estado actual

### Botones — ✅ mayormente homologados (núcleo del theming)

- **`resources/js/Components/ActionButton.vue:15-29`** — sistema canónico de variantes ya existente:
  `blue, gold, purple, danger, success, navy, reset-look`, con tamaños `md/sm/icon`. **Todas** las
  clases usan tokens `rapanel-*` (ej. `bg-rapanel-blue/30 … text-rapanel-blue … hover:bg-rapanel-blue`).
  **Este componente es el corazón del requisito #3 del theming** (recolorear botones por variante).
- **`resources/js/Components/PrimaryButton.vue:3`** — usa `bg-rapanel-blue` + `text-rapanel-text-dark`. ✅ token.
- **`resources/js/Components/SecondaryButton.vue:13`** — usa `border-rapanel-navy-100 … bg-white dark:bg-rapanel-navy-800 …`. ⚠️ usa `bg-white` literal (aceptable como superficie, ver F0.4).
- **`resources/js/Components/DangerButton.vue:3`** — ⚠️ **deuda**: `hover:bg-red-400 … focus:ring-red-400 … active:bg-red-600`. Mezcla `bg-rapanel-danger` (token) con `red-400/red-600` (hardcode). **Homologar** a `rapanel-danger` (+ hover via opacidad u otro token `rapanel-danger`).

**Deuda de botones a homologar:**
- Botones "sueltos" (elementos `<button>`/`<Link>` con clases de color inline) en páginas en vez de usar `ActionButton`/`PrimaryButton`. Auditar especialmente:
  `resources/js/Pages/Admin/SiteSettings/Index.vue`, `Pages/Donations/Index.vue`, `Pages/Vote/Index.vue`,
  `Pages/Admin/Console.vue`, `Pages/Admin/MvpCards/Index.vue`, `Pages/Admin/MapDb/Index.vue`.
- `DangerButton.vue` → quitar `red-400/600`.

### Tablas — ⚠️ patrón existe pero no es universal

- **`resources/js/Components/DataTable.vue`** es el componente canónico de tabla:
  - Contenedor: `bg-white dark:bg-rapanel-surface` + `border-rapanel-navy-100 dark:border-white/[0.07]` (`DataTable.vue:11`).
  - thead/header: `bg-rapanel-navy-50 dark:bg-white/[0.04]` (`DataTable.vue:12`).
  - Paginación: tokens `rapanel-blue`, `rapanel-navy-100`, `text-rapanel-text-light/*`.
  - ⚠️ **Mezcla** tokens con utilidades de opacidad sobre `white` (`dark:bg-white/[0.04]`, `dark:border-white/[0.07]`). Esto es intencional para "tinte translúcido", pero **no se recolorea** con el token de superficie. Decisión F0: o (a) aceptarlo como capa de elevación neutra, o (b) introducir token `--rapanel-elevation`.
- **Deuda**: muchas tablas NO usan `DataTable.vue` y definen `thead`/bordes/hover propios con `gray-*`/`slate-*`. Detectadas con clases genéricas: `Pages/Admin/ItemDb/Index.vue`, `Pages/Admin/MobDb/Index.vue`, `Pages/Admin/MapDb/Index.vue`, `Pages/Admin/MvpCards/Index.vue`, `Pages/Info/WhoSell.vue`, `Pages/Info/MobDb/Index.vue`.
- **Acción F0**: definir el estándar de tabla = `DataTable.vue` + `<thead>` estándar; migrar las tablas sueltas o, mínimamente, reemplazar sus colores `gray/slate` por tokens.

### Texto / tipografía — ⚠️ parcial

- Tokens correctos existentes: `text-rapanel-text-light dark:text-rapanel-text-dark` (definidos en `tailwind.config.js:31-34`).
- Patrón de jerarquía ya visible en `PageHeader.vue:11-13` (título `text-rapanel-text-light dark:text-white`, descripción `text-rapanel-text-light/55 dark:text-white/45`).
- ⚠️ **Inconsistencia**: conviven `dark:text-white` (literal), `text-rapanel-text-dark` (token) y `text-gray-*`/`text-slate-*` (deuda). Tres formas de decir "texto".
- **Acción F0**: una escala canónica (ver F0.2) y reemplazar `gray/slate` por ella.

### Bordes y cards — ⚠️ parcial

- Card canónica: `DataTable.vue:11` y `PageHeader.vue:9` definen el patrón
  (`rounded-xl/2xl`, `border-rapanel-navy-100 dark:border-white/[0.07]`, sombras).
- Tokens de superficie dark ya creados: `rapanel-surface`, `rapanel-surface-deep`, `rapanel-base-dark` (`tailwind.config.js:44-46`).
- ⚠️ Deuda: cards que usan `bg-gray-800`, `border-gray-700`, hex sueltos.

### Headings / badges / chips — ⚠️ parcial

- `StatusBadge.vue`, `News::typeColor()` (devuelve colores por tipo, ver `HandleInertiaRequests.php:99`) y badges inline por toda la app.
- **Acción F0**: definir el set de badges por variante (mismo mapa que botones: gold/blue/purple/navy/success/danger) y homologar.

### Fondo del sitio — ⚠️ relevante para el theming

- **`resources/js/Components/BgMain.vue:6-10`** — imagen de fondo: usa `home_hero_bg_image` de `siteSettings`, fallback `/images/bg-main.svg`, con `dark:filter dark:brightness-50`.
  El requisito #1 del theming (imagen de fondo) **se apoya** aquí.
- Color de fondo base: hoy lo da el `<body>`/layouts vía tokens `rapanel-navy-50` (claro) y `rapanel-base-dark/surface-deep` (oscuro). El requisito #2 (colores de fondo claro/oscuro) recolorea estos tokens.

## F0.2 — Conjunto canónico de primitivos / tokens (la "fuente de verdad")

Tokens que **deben** existir y a los que todo debe migrar (todos ya en `tailwind.config.js` salvo los marcados NUEVO):

| Grupo | Token | Uso canónico |
|---|---|---|
| Superficies claro | `rapanel-navy-50` (#f8fafc) | fondo página claro |
| Superficies claro | `bg-white` | cards/paneles claro (aceptado como "superficie 0") |
| Bordes claro | `rapanel-navy-100` | bordes/divisores claro |
| Superficies dark | `rapanel-base-dark` / `rapanel-surface-deep` / `rapanel-surface` | base / topbar-sidebar / card |
| Bordes dark | `white/[0.07]` o **NUEVO** `rapanel-border-dark` | divisores dark |
| Texto | `rapanel-text-light` / `rapanel-text-dark` | body principal (+ escalas `/70 /55 /45`) |
| Acentos/variantes | `rapanel-blue` `rapanel-gold` `rapanel-purple` `rapanel-success` `rapanel-danger` `rapanel-navy` | botones, badges, chips, links |

Escala de texto canónica (reemplaza `gray/slate`):
- Título: `text-rapanel-text-light dark:text-rapanel-text-dark` (peso `font-display font-bold`)
- Body: idem sin peso
- Muted/secundario: `text-rapanel-text-light/55 dark:text-rapanel-text-dark/55`
- Disabled/hint: `…/35`

## F0.3 — Lista de cambios de homologación (resumen accionable)

1. `DangerButton.vue` — eliminar `red-400/600`, usar `rapanel-danger` (+ hover `rapanel-danger/90`).
2. Reemplazo masivo en `resources/js/**` (39 archivos): `*-gray-N`, `*-slate-N`, `*-zinc-N`, y `red/green/blue/yellow/purple-N` "decorativos" → token `rapanel-*` equivalente. **Manual y por archivo** (no sed ciego: muchos colores son semánticos correctos, ej. estados de servidor online/offline).
3. Migrar tablas sueltas a `DataTable.vue` o, mínimo, a tokens (`Admin/ItemDb`, `Admin/MobDb`, `Admin/MapDb`, `Admin/MvpCards`, `Info/WhoSell`, `Info/MobDb`).
4. Sustituir `dark:text-white` literal por `dark:text-rapanel-text-dark` donde represente "texto" (no cuando sea contraste forzado sobre un acento, ej. `hover:text-white` de los botones — ese se queda).
5. Auditar literales hex en `.vue` (26 archivos): conservar los legítimos (pickers, SVG inline, defaults de seed en `SiteSettingsController`), reemplazar los usados como color de UI directo.
6. Crear (si se decide en F0.2) tokens `rapanel-border-dark` y `rapanel-elevation` y migrar `white/[0.0x]`.

**Criterio de "hecho" de Fase 0:** `grep -rE '(bg|text|border)-(gray|slate|zinc|neutral)-[0-9]' resources/js` ≈ 0 resultados de UI estructural; los acentos decorativos pasan por tokens; los botones usan `ActionButton`/`PrimaryButton`/`DangerButton`.

**Riesgo/alcance Fase 0:** alto en nº de archivos (~39), bajo en complejidad por archivo. Recomendado dividir en sub-PRs: (a) botones, (b) tablas, (c) texto/cards, (d) badges. Verificación visual claro/oscuro por cada uno.

---

# FASE 1 — TOKENS COMO CSS CUSTOM PROPERTIES (runtime)

Objetivo: que `tailwind.config.js` deje de emitir hex fijos y emita `var(--token, <fallback hex actual>)`.
Las clases `rapanel-*` siguen iguales en todo el código (gracias a Fase 0). Solo cambia a qué resuelven.

## F1.1 — Reescritura de `tailwind.config.js`

Patrón con fallback (el fallback = valor hex actual, garantiza que si no hay variable inyectada todo
se ve **idéntico** a hoy → sin regresión y sin FOUC si la variable falta):

```js
// tailwind.config.js  (theme.extend.colors)
colors: {
  'rapanel-navy': {
    50:  'var(--rapanel-navy-50,  #f8fafc)',
    100: 'var(--rapanel-navy-100, #e2e8f0)',
    600: 'var(--rapanel-navy-600, #475569)',
    700: 'var(--rapanel-navy-700, #334155)',
    800: 'var(--rapanel-navy-800, #1e293b)',
    900: 'var(--rapanel-navy-900, #0f172a)',
  },
  'rapanel-text': {
    light: 'var(--rapanel-text-light, #1d283a)',
    dark:  'var(--rapanel-text-dark,  #E2E8F0)',
  },
  'rapanel-blue':    { DEFAULT: 'var(--rapanel-blue,    #4A90E2)', dark: 'var(--rapanel-blue-dark,    #1e3a5f)' },
  'rapanel-success': { DEFAULT: 'var(--rapanel-success, #2ECC71)', dark: 'var(--rapanel-success-dark, #14532d)' },
  'rapanel-danger':  { DEFAULT: 'var(--rapanel-danger,  #E74C3C)', dark: 'var(--rapanel-danger-dark,  #7f1d1d)' },
  'rapanel-gold':    { DEFAULT: 'var(--rapanel-gold,    #F1C40F)', dark: 'var(--rapanel-gold-dark,    #78350f)' },
  'rapanel-purple':  { DEFAULT: 'var(--rapanel-purple,  #a855f7)', dark: 'var(--rapanel-purple-dark,  #581c87)' },
  'rapanel-surface':      'var(--rapanel-surface,      #0f1829)',
  'rapanel-surface-deep': 'var(--rapanel-surface-deep, #0b1120)',
  'rapanel-base-dark':    'var(--rapanel-base-dark,    #080d14)',
}
```

> **⚠️ Compatibilidad con opacidad de Tailwind (`bg-rapanel-blue/30`).** `ActionButton.vue` usa
> intensamente `/30`, `/10`, `/40`. Tailwind implementa la opacidad inyectando un canal alfa, y para
> ello necesita el formato `<alpha-value>`. Con `var(--x, #hex)` **la opacidad puede romperse**.
> **Solución obligatoria:** definir las variables en **formato canal RGB sin coma** y usar la sintaxis
> `rgb(var(--x) / <alpha-value>)`:
> ```js
> 'rapanel-blue': {
>   DEFAULT: 'rgb(var(--rapanel-blue-rgb, 74 144 226) / <alpha-value>)',
>   dark:    'rgb(var(--rapanel-blue-dark-rgb, 30 58 95) / <alpha-value>)',
> },
> ```
> Es decir, las variables se almacenan/inyectan como tripletas `R G B` (ej. `74 144 226`), no como `#4A90E2`.
> El backend convierte el hex elegido por el admin → `R G B` antes de inyectar. Esto preserva
> `bg-rapanel-blue/30`, `hover:bg-rapanel-blue`, todo, exactamente como hoy.

## F1.2 — Claro vs Oscuro (cómo conviven)

El proyecto usa `darkMode: 'class'` (`tailwind.config.js:7`) y la clase `.dark` se setea muy temprano
en `app.blade.php:5-12`. Por tanto, las variables se declaran en dos bloques:

```css
:root {            /* tema CLARO */
  --rapanel-navy-50-rgb: 248 250 252;
  --rapanel-blue-rgb:    74 144 226;
  /* … resto de tokens claros … */
}
:root.dark {       /* tema OSCURO — overrides */
  --rapanel-base-dark-rgb:    8 13 20;
  --rapanel-surface-rgb:      15 24 41;
  --rapanel-blue-rgb:         /* color de botón azul en dark */;
  /* … */
}
```

Cada variante de botón tiene **dos** valores (claro/oscuro) → cubre el requisito #3
("colores de botones para claro y oscuro"). El requisito #2 (fondo claro/oscuro) se mapea a los
tokens de fondo (`rapanel-navy-50` en claro; `rapanel-base-dark`/`rapanel-surface-deep` en `.dark`).

---

# FASE 2 — ALMACENAMIENTO (ra_site_settings)

Se reutiliza el modelo existente **`SiteSetting`** (`app/Models/SiteSetting.php`), tabla `ra_site_settings`
(conexión `mysql`, prefijo `ra_` automático → en el modelo `protected $table = 'site_settings'`,
**sin** escribir `ra_`). PK string `key`, valor string. Helpers `getValue`/`setValue`/`setMany`
y `Cache::forget('ra_site_settings')` ya implementados (`SiteSetting.php:18-37`).

> **No se necesita tabla ni migración nuevas.** Si se prefiere agrupar, se guarda **un único** registro
> JSON bajo la clave `theme` (más limpio para el blob de tema). Recomendado: **una clave JSON `theme`**.

Esquema del JSON `theme` (defaults = valores actuales de `tailwind.config.js`, ya en formato hex para la UI;
el backend deriva las tripletas RGB al inyectar):

```jsonc
{
  "version": 1,
  "bg_image": null,                 // ruta en storage/public o null = default /images/bg-main.svg
  "light": {
    "bg":     "#f8fafc",            // rapanel-navy-50
    "text":   "#1d283a",            // rapanel-text-light
    "buttons": {
      "blue": "#4A90E2", "gold": "#F1C40F", "purple": "#a855f7",
      "navy": "#334155", "success": "#2ECC71", "danger": "#E74C3C"
    }
  },
  "dark": {
    "bg":     "#080d14",            // rapanel-base-dark
    "surface":"#0f1829",            // rapanel-surface
    "text":   "#E2E8F0",            // rapanel-text-dark
    "buttons": {
      "blue": "#1e3a5f", "gold": "#78350f", "purple": "#581c87",
      "navy": "#475569", "success": "#14532d", "danger": "#7f1d1d"
    }
  }
}
```

- **Clave única `theme`** en `ra_site_settings` (string JSON), + `bg_image` se puede dejar dentro del JSON
  o como clave separada reutilizando el patrón de `home_hero_bg_image`.
- Defaults centralizados en PHP: `config/theme.php` (NUEVO) o constante en el controlador, para Reset.

---

# FASE 3 — BACKEND (controlador, rutas, validación, upload)

## F3.1 — Rutas (admin-only)

Dentro del grupo existente `Route::middleware(['auth','admin','admin.2fa'])->prefix('admin')->name('admin.')`
(`routes/web.php:200`). Añadir:

```php
// routes/web.php — dentro del grupo admin.*
Route::prefix('appearance')->name('appearance.')->group(function () {
    Route::get('/',        [AppearanceController::class, 'index'])->name('index');
    Route::post('/',       [AppearanceController::class, 'update'])->name('update');     // colores + bg_image
    Route::post('/reset',  [AppearanceController::class, 'reset'])->name('reset');       // volver a defaults
    Route::delete('/image',[AppearanceController::class, 'removeImage'])->name('image.destroy');
});
```

Quedan protegidas por `auth + admin + admin.2fa` (mismo nivel que SiteSettings/Console — `routes/web.php:200`),
cumpliendo el requisito de seguridad #9 (solo admin + 2FA admin).

## F3.2 — Controlador `app/Http/Controllers/Admin/AppearanceController.php` (NUEVO)

Sigue el patrón de `SiteSettingsController`:

```php
public function index(): Response
{
    return Inertia::render('Admin/Appearance/Index', [
        'theme'    => json_decode(SiteSetting::getValue('theme') ?? '{}', true) ?: config('theme.defaults'),
        'defaults' => config('theme.defaults'),
        'bgImage'  => SiteSetting::getValue('theme_bg_image'),   // ruta storage o null
    ]);
}

public function update(Request $request): RedirectResponse
{
    $hex = ['nullable','string','regex:/^#[0-9a-fA-F]{6}$/'];   // validación HEX estricta (req #9)
    $request->validate([
        'light.bg' => $hex, 'light.text' => $hex,
        'light.buttons.blue' => $hex, /* … gold purple navy success danger … */
        'dark.bg' => $hex, 'dark.surface' => $hex, 'dark.text' => $hex,
        'dark.buttons.blue' => $hex,  /* … */
        'bg_image'        => 'nullable|image|mimes:jpg,jpeg,png,webp|max:10240',  // tipo+tamaño (req #9)
        'remove_bg_image' => 'nullable|boolean',
    ]);

    $theme = $request->only(['light','dark']);   // ya validado a hex
    SiteSetting::setValue('theme', json_encode($theme));

    // Imagen de fondo (mismo patrón que updateHomeHero en SiteSettingsController.php:118-131)
    if ($request->boolean('remove_bg_image')) {
        $old = SiteSetting::getValue('theme_bg_image');
        if ($old) Storage::disk('public')->delete($old);
        SiteSetting::setValue('theme_bg_image', null);
    } elseif ($request->hasFile('bg_image')) {
        $old = SiteSetting::getValue('theme_bg_image');
        if ($old) Storage::disk('public')->delete($old);
        SiteSetting::setValue('theme_bg_image',
            $request->file('bg_image')->store('settings/theme', 'public'));
    }

    Cache::forget('ra_site_settings');           // ¡clave! invalidar el cache de share()
    return back()->with('success', __('Appearance saved.'));
}

public function reset(): RedirectResponse
{
    $old = SiteSetting::getValue('theme_bg_image');
    if ($old) Storage::disk('public')->delete($old);
    SiteSetting::setValue('theme', json_encode(config('theme.defaults')));
    SiteSetting::setValue('theme_bg_image', null);
    Cache::forget('ra_site_settings');
    return back()->with('success', __('Appearance reset to defaults.'));
}
```

- **Validación de color**: regex hex de 6 dígitos (más estricta que la del proyecto en `SiteSettingsController.php:40`).
- **Validación de imagen**: `image|mimes:…|max:10240` (10MB), como `home_hero_bg_image` (`SiteSettingsController.php:99`).
- **Storage**: `settings/theme` en disco `public` → referenciada como `/storage/...` (igual que `BgMain.vue:8`).

---

# FASE 4 — INYECCIÓN EN RUNTIME (sin recompilar, sin FOUC)

Hay dos canales; se usan **ambos** por una razón concreta de FOUC:

## F4.1 — `<head>` en `app.blade.php` (anti-FOUC, server-side)

`app.blade.php` ya recibe `$siteSettings` (lo usa en `:23`). Se añade un `<style>` **inline en el head**,
**antes** del `@vite` y junto al script de dark mode (`app.blade.php:5-12`), que escribe las CSS vars
para que estén presentes **en el primer paint** (cero FOUC):

```blade
{{-- app.blade.php — tras el script de dark mode, antes de @vite --}}
@php $theme = !empty($st['theme']) ? json_decode($st['theme'], true) : null; @endphp
@if($theme)
<style id="rapanel-theme">
  :root {
    --rapanel-navy-50-rgb: {{ \App\Support\Theme::rgb($theme['light']['bg']) }};
    --rapanel-text-light-rgb: {{ \App\Support\Theme::rgb($theme['light']['text']) }};
    --rapanel-blue-rgb: {{ \App\Support\Theme::rgb($theme['light']['buttons']['blue']) }};
    /* … resto de variantes claras … */
  }
  :root.dark {
    --rapanel-base-dark-rgb: {{ \App\Support\Theme::rgb($theme['dark']['bg']) }};
    --rapanel-surface-rgb:   {{ \App\Support\Theme::rgb($theme['dark']['surface']) }};
    --rapanel-text-dark-rgb: {{ \App\Support\Theme::rgb($theme['dark']['text']) }};
    --rapanel-blue-rgb:      {{ \App\Support\Theme::rgb($theme['dark']['buttons']['blue']) }};
    /* … resto de variantes oscuras … */
  }
</style>
@endif
```

- `\App\Support\Theme::rgb('#4A90E2')` → `'74 144 226'` (helper NUEVO; hex → tripleta).
- Si `$theme` es null → no se emite `<style>` → Tailwind usa los **fallbacks** del config (Fase 1) → idéntico a hoy.
- **Inline en el head = el navegador conoce los colores antes de pintar = sin FOUC** (igual estrategia que el script de dark mode existente, `app.blade.php:6`).

## F4.2 — `share()` de Inertia (para la SPA / navegación cliente)

`HandleInertiaRequests::share()` (`:68-70`) ya expone `siteSettings` cacheado 300s. Se añade el theme parseado:

```php
// HandleInertiaRequests.php — junto a 'siteSettings'
'theme' => Cache::remember('ra_theme', 300, function () {
    return json_decode(SiteSetting::getValue('theme') ?? 'null', true);
}),
```

> **Nota cache:** `setValue('theme', …)` ya hace `Cache::forget('ra_site_settings')`
> (`SiteSetting.php:27`), pero **no** olvida `ra_theme`. El `AppearanceController` debe hacer
> `Cache::forget('ra_theme')` además (o reusar `ra_site_settings` y leer de ahí). Recomendado:
> guardar el theme dentro del blob `ra_site_settings` (clave `theme`) y leerlo desde
> `usePage().props.siteSettings.theme` — así **un solo cache** y `setValue` ya lo invalida.

`BgMain.vue` (req #1, imagen de fondo) ya lee de `siteSettings`; basta cambiar la clave
`home_hero_bg_image` → `theme_bg_image` (o leer ambas con prioridad theme).

---

# FASE 5 — UI ADMIN (página Vue de Personalización)

## F5.1 — Archivo `resources/js/Pages/Admin/Appearance/Index.vue` (NUEVO)

Sigue el patrón de `Admin/SiteSettings/Index.vue`:
- `AdminLayout` + `PageHeader` (`SiteSettings/Index.vue:4-5`).
- `useForm` de Inertia con `forceFormData: true` para subir la imagen (`SiteSettings/Index.vue:78`).
- Sub-tabs (`activeTab` ref) o secciones: **Fondo · Tema Claro · Tema Oscuro**.
- `__(key)` para i18n (`SiteSettings/Index.vue:27-31`).
- Diseño según estándar: cards `rounded-xl border-rapanel-navy-100 dark:…`, `ActionButton` para acciones.

Estructura de UI:
1. **Imagen de fondo** — uploader con preview (patrón `onLogoLight` / `URL.createObjectURL`, `SiteSettings/Index.vue:56-61`), botón "Quitar" (→ default) y "Restaurar default".
2. **Colores de fondo** — `<input type="color">` para fondo claro y fondo oscuro (+ texto claro/oscuro).
3. **Colores de botones** — grilla con 6 variantes × 2 temas (gold, blue, purple, navy, success, danger), cada celda con su color picker y un `ActionButton` de muestra de esa variante al lado (muestra viva).
4. Barra de acciones fija: **Guardar** (`PrimaryButton`), **Descartar** (revertir preview), **Reset a defaults** (`ActionButton variant="reset-look"` + `ConfirmModal`).

## F5.2 — Entrada en el menú admin

Añadir enlace "Personalización / Appearance" en `AdminLayout.vue` (sidebar admin), junto a "Site Settings".
(Verificar la lista de links del layout admin y replicar el patrón de ítem.)

---

# FASE 6 — VISTA PREVIA EN VIVO (mecanismo concreto)

El núcleo: **mientras** el admin mueve un picker, escribir la CSS var en `document.documentElement`
(`:root`). Como TODA la UI consume `var(--rapanel-*)` (Fases 0+1), el sitio entero se recolorea en vivo
sin guardar y sin recompilar.

```js
// resources/js/Composables/useThemePreview.js (NUEVO)
import { hexToRgb } from '@/helpers';

const root = document.documentElement;

// Aplica un objeto theme {light, dark} a las CSS vars en vivo (preview, no persiste)
export function applyThemePreview(theme) {
  const set = (name, hex) => root.style.setProperty(name, hexToRgb(hex)); // '#4A90E2' -> '74 144 226'
  // claras
  set('--rapanel-navy-50-rgb',   theme.light.bg);
  set('--rapanel-text-light-rgb',theme.light.text);
  for (const [k,v] of Object.entries(theme.light.buttons)) set(`--rapanel-${k}-rgb`, v);
  // oscuras (válidas; aplican cuando :root.dark)
  set('--rapanel-base-dark-rgb', theme.dark.bg);
  set('--rapanel-surface-rgb',   theme.dark.surface);
  set('--rapanel-text-dark-rgb', theme.dark.text);
  // botones dark: necesitan escribirse en una hoja .dark — ver nota
}

// Quita los overrides inline => vuelve al valor guardado (Descartar)
export function clearThemePreview(keys) {
  keys.forEach(k => root.style.removeProperty(k));
}
```

> **Detalle claro/oscuro en preview.** Las vars escritas con `root.style.setProperty` viven en el
> *inline style* de `<html>` y **no distinguen** `.dark`. Para previsualizar los colores de botón del
> tema **oscuro** mientras se ve en oscuro, basta con que el admin tenga el toggle de tema en oscuro
> (`ThemeSelector.vue`) y se escriban los valores `dark`; para previsualizar el claro, toggle en claro y
> valores `light`. Es decir: **el preview aplica el set del tema actualmente activo** (`document.documentElement.classList.contains('dark')`). La página de Appearance incluye un botón para alternar tema y comparar ambos. Alternativa más rica (opcional): inyectar un `<style id="theme-preview">` con bloques `:root{}` y `:root.dark{}` reemplazando su `textContent` en cada cambio — previsualiza ambos a la vez. **Recomendado** este enfoque por simetría con F4.1.

```js
// Variante recomendada: misma forma que el <style> del head
function buildCss(theme) {
  return `:root{${lightVars(theme)}} :root.dark{${darkVars(theme)}}`;
}
function applyThemePreview(theme) {
  let el = document.getElementById('theme-preview');
  if (!el) { el = document.createElement('style'); el.id = 'theme-preview'; document.head.appendChild(el); }
  el.textContent = buildCss(theme);   // sobreescribe el <style id="rapanel-theme"> del SSR por especificidad/orden
}
function clearThemePreview() { document.getElementById('theme-preview')?.remove(); } // Descartar
```

- **Reactividad**: `watch(form, () => applyThemePreview(form.data()), { deep: true })` en la página.
- **Guardar**: `form.post(route('admin.appearance.update'), { forceFormData:true })`. Al recargar las props,
  el `<style id="rapanel-theme">` del SSR ya trae lo guardado; se puede quitar el `theme-preview`.
- **Descartar**: `clearThemePreview()` → vuelve a lo guardado (SSR/Tailwind fallback).
- **Reset**: setea `form` = `props.defaults`, aplica preview, y postea a `admin.appearance.reset`.
- **`hexToRgb`** se añade a `resources/js/helpers.js` (junto a `ro_game_modes` ya importado en `SiteSettings/Index.vue:6`).

---

# FASE 7 — i18n (7 idiomas)

Añadir las nuevas claves a los **siete** `lang/{en,es,pt,pt-BR,fr,de,ru}.json` (regla del proyecto, CLAUDE.md §i18n).
Claves nuevas (texto base en inglés, las llaves son el inglés por convención del proyecto):

```
"Appearance", "Customize the look of your site", "Background image",
"Light theme", "Dark theme", "Background color", "Text color", "Surface color",
"Button colors", "Live preview", "Save", "Discard", "Reset to defaults",
"Remove image", "Use default background", "Appearance saved.",
"Appearance reset to defaults.", "Invalid color value.",
"Blue", "Gold", "Purple", "Navy", "Success", "Danger"
```

(Las variantes de botón pueden mostrarse con su label visible traducido o dejarse como nombre técnico.)

---

# FASE 8 — DEFAULTS / RESET

- **Fuente única de defaults**: `config/theme.php` (NUEVO) con el array `defaults` = exactamente los hex de
  `tailwind.config.js:23-46` (navy 50/100/600/700/800/900, text light/dark, blue/gold/purple/navy/success/danger
  claro y dark, surfaces). Se expone a la UI vía `index()` (`'defaults' => config('theme.defaults')`).
- **Reset backend**: `AppearanceController::reset()` borra `theme`, `theme_bg_image` e invalida cache.
- **Fallback**: si no hay registro `theme`, el `<style>` no se emite y Tailwind usa los fallbacks del config
  → el sitio se ve exactamente como hoy. Doble red de seguridad (config fallback + defaults explícitos).

---

# LISTA CONCRETA DE ARCHIVOS POR FASE

### Fase 0 — Homologación (entregable previo, ~39 archivos JS)
- `resources/js/Components/DangerButton.vue` — quitar `red-400/600` → `rapanel-danger`.
- `resources/js/Components/DataTable.vue` — confirmar estándar; (opcional) tokenizar `white/[0.0x]`.
- Tablas a migrar/tokenizar: `Pages/Admin/ItemDb/Index.vue`, `Pages/Admin/MobDb/Index.vue`,
  `Pages/Admin/MapDb/Index.vue`, `Pages/Admin/MvpCards/Index.vue`, `Pages/Info/WhoSell.vue`,
  `Pages/Info/MobDb/Index.vue`.
- Reemplazo de `gray/slate/zinc/*-N` → tokens en (entre otros, por nº de hits): `Pages/Dashboard.vue`,
  `Pages/Home.vue`, `Pages/HomeAlt.vue`, `Components/ItemDbModal.vue`, `Components/MobDbModal.vue`,
  `Pages/Profile/Partials/UpdateProfileInformationForm.vue`, `Pages/GameAccount/Show.vue`,
  `Components/Header.vue`, `Components/ClaimAccountModal.vue`, `Pages/Profile/TwoFactor.vue`,
  `Pages/Admin/Console.vue`, etc. (lista completa = los 39 archivos del barrido).
- Badges/chips: homologar a variantes (revisar `StatusBadge.vue` y `News::typeColor` consumidores).

### Fase 1 — Tokens → CSS vars
- `tailwind.config.js` — colores a `rgb(var(--x-rgb, R G B) / <alpha-value>)`.
- `resources/css/app.css` (opcional) — declarar `:root` / `:root.dark` con los defaults (refuerzo extra a F4.1).

### Fases 2-4 — Backend + runtime
- `app/Models/SiteSetting.php` — **sin cambios** (se reutiliza); opcional `Cache::forget('ra_theme')` si se usa clave separada.
- `config/theme.php` — **NUEVO** (defaults).
- `app/Support/Theme.php` — **NUEVO** (helper `rgb(hex): string`).
- `app/Http/Controllers/Admin/AppearanceController.php` — **NUEVO** (index/update/reset/removeImage).
- `routes/web.php` — añadir grupo `appearance.*` dentro del grupo admin (`:200`).
- `app/Http/Middleware/HandleInertiaRequests.php` — exponer `theme` (o dentro de `siteSettings`).
- `resources/views/app.blade.php` — `<style id="rapanel-theme">` inline en head (anti-FOUC).
- `resources/js/Components/BgMain.vue` — leer `theme_bg_image` (fallback al actual).

### Fases 5-7 — UI + preview + i18n
- `resources/js/Pages/Admin/Appearance/Index.vue` — **NUEVO** (página).
- `resources/js/Composables/useThemePreview.js` — **NUEVO** (preview en vivo).
- `resources/js/helpers.js` — añadir `hexToRgb`.
- `resources/js/Layouts/AdminLayout.vue` — link en menú admin.
- `lang/{en,es,pt,pt-BR,fr,de,ru}.json` — nuevas claves (×7).

---

# VERIFICACIÓN

1. **Sin regresión (theme vacío)**: borrar registro `theme` → el sitio se ve idéntico a hoy (fallbacks del config).
2. **Tailwind opacidad**: confirmar que `ActionButton` (`bg-rapanel-blue/30`, `hover:bg-rapanel-blue`) sigue
   renderizando correctamente tras pasar a `rgb(var() / <alpha-value>)`. **Test crítico.**
3. **Claro/Oscuro**: cambiar colores de ambos temas; toggle con `ThemeSelector` → cada set aplica al `.dark` correcto.
4. **Preview en vivo**: mover cada picker → toda la UI (botones, tablas, cards, texto, fondo) recolorea al instante sin guardar.
5. **Guardar**: persiste; recargar página dura (F5) → colores presentes en el **primer paint** (sin FOUC).
6. **FOUC**: verificar con red throttling que NO hay flash de la paleta default antes del theme (el `<style>` inline del head lo garantiza).
7. **Descartar**: revierte al guardado sin recargar.
8. **Reset**: vuelve a defaults (colores + imagen) y borra archivo de storage.
9. **Imagen de fondo**: subir / quitar / restaurar default; validación de tipo y tamaño (>10MB rechazado).
10. **Seguridad**: rutas `appearance.*` inaccesibles sin `admin` + `admin.2fa` (mismas que `console.*`/`settings.*`).
11. **Cache**: tras Guardar, el cache (`ra_site_settings`/`ra_theme`) se invalida y `share()` refleja lo nuevo.
12. **i18n**: la página se traduce en los 7 idiomas; sin claves crudas.

---

# RIESGOS

| Riesgo | Impacto | Mitigación |
|---|---|---|
| **Opacidad Tailwind rota** al pasar `var()` (especialmente `ActionButton /30 /10 /40`) | Botones sin fondo | Usar `rgb(var(--x-rgb) / <alpha-value>)` con tripletas R G B (F1.1). Verificación #2 obligatoria. |
| **FOUC** (flash de paleta default antes del theme) | Parpadeo al cargar | `<style>` inline en `<head>` server-side (F4.1), antes de Vite, junto al script dark. |
| **Cache de config/Vite** | Cambios no reflejados | El theme NO entra en config cacheada; vive en DB+cache app con `Cache::forget`. Tras editar `tailwind.config.js`/`.blade` recompilar/`config:clear` una vez (solo en deploy del feature, no en uso). |
| **Cache `ra_site_settings`/`ra_theme`** desincronizado | Admin guarda y no ve cambio | `setValue` ya hace forget de `ra_site_settings`; usar esa misma clave para el theme (recomendado) o forget explícito de `ra_theme`. |
| **Fase 0 grande** (~39 archivos) | Esfuerzo/regresión visual | Entregable separado, sub-PRs (botones/tablas/texto/badges), verificación claro+oscuro por sub-PR. Theming de botones puede arrancar antes (ya homologados). |
| **Colores ilegibles** elegidos por admin | Texto sin contraste | (Opcional) advertencia de contraste WCAG en la UI; Reset siempre disponible. |
| **Performance** | Nula | CSS vars son nativas y baratas; un solo `<style>` pequeño; sin recompilación en runtime. |
| **Validación/seguridad imagen** | Subida maliciosa | `image|mimes:jpg,jpeg,png,webp|max:10240`; regex hex estricto en colores; rutas admin+2FA. |
| **Migración accidental de colores semánticos** en Fase 0 (estados online/offline) | Romper significado | Migración manual por archivo, no `sed` global. |

---

# ORDEN DE EJECUCIÓN RECOMENDADO

```
Fase 0  Homologación (PR aparte, sub-PRs)  ── prerequisito
   │
Fase 1  tokens → rgb(var()/alpha) en tailwind.config
   │
Fase 2  storage (clave JSON `theme` en ra_site_settings) + config/theme.php defaults
   │
Fase 3  AppearanceController + rutas admin + validación
   │
Fase 4  inyección runtime (app.blade.php <style> + share)
   │
Fase 5  UI Admin/Appearance/Index.vue + link en AdminLayout
   │
Fase 6  preview en vivo (useThemePreview.js + watch)
   │
Fase 7  i18n ×7
   │
Fase 8  defaults/reset + VERIFICACIÓN completa
```

**Fin del plan.**

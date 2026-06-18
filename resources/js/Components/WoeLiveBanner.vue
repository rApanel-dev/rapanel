<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import { ChevronDownIcon, ChevronUpIcon, EyeIcon, EyeSlashIcon } from '@heroicons/vue/24/outline';

const page    = usePage();
const isAdmin = computed(() => page.props.auth?.user?.role === 'Admin');

const __ = (key, rep = {}) => {
    let t = page.props.translations?.[key] || key;
    Object.entries(rep).forEach(([k, v]) => { t = t.replace(`:${k}`, v); });
    return t;
};

const BANNER_H = '38px';

// ── Preview state ─────────────────────────────────────────────────────
const previewActive = ref(localStorage.getItem('woe_preview') === '1');

const _syncBannerVar = () => {
    const active = page.props.woeStatus?.active || localStorage.getItem('woe_preview') === '1';
    document.documentElement.style.setProperty('--woe-banner-h', active ? BANNER_H : '0px');
};
_syncBannerVar();

const togglePreview = () => {
    previewActive.value = !previewActive.value;
    localStorage.setItem('woe_preview', previewActive.value ? '1' : '0');
    window.dispatchEvent(new Event('woe-preview-changed'));
};

const syncPreview = () => { previewActive.value = localStorage.getItem('woe_preview') === '1'; };

// Re-sincronizar cuando el usuario vuelve a la pestaña (tab freeze/restore, alt+tab, etc.)
const syncOnVisible = () => { if (document.visibilityState === 'visible') syncPreview(); };

// ── Minimize ──────────────────────────────────────────────────────────
const minimized = ref(localStorage.getItem('woe_banner_minimized') === '1');

const toggleMinimize = () => {
    minimized.value = !minimized.value;
    localStorage.setItem('woe_banner_minimized', minimized.value ? '1' : '0');
};

// ── WOE active ────────────────────────────────────────────────────────
// woeActivePolled se actualiza en cada poll — no depende del prop de carga de página.
// Inicializado desde el prop para que el banner aparezca inmediatamente si ya está activo.
const woeActivePolled = ref(page.props.woeStatus?.active ?? false);
const isWoeActive     = computed(() => woeActivePolled.value || previewActive.value);

// ── Live data ─────────────────────────────────────────────────────────
const events  = ref([]);
const castles = ref([]);
let   pollTimer = null;

const fetchEvents = async () => {
    try {
        const res  = await fetch(route('info.woe.events'), { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        const data = await res.json();
        // active viene del servidor y es la fuente de verdad en tiempo real
        if (typeof data.active === 'boolean')    woeActivePolled.value = data.active;
        if (data.events)                         events.value  = data.events;
        if (data.castles)                        castles.value = data.castles;
        if (Array.isArray(data.current))         currentSession.value = data.current[0] ?? null;
    } catch { /* silent */ }
};

// El polling corre siempre — detecta inicio/fin de WOE en tiempo real.
// Intervalo más largo cuando no hay WOE activo para no desperdiciar requests.
const POLL_ACTIVE   = 30_000;
const POLL_INACTIVE = 60_000;

const startPolling = () => {
    stopPolling();
    fetchEvents();
    const interval = isWoeActive.value ? POLL_ACTIVE : POLL_INACTIVE;
    pollTimer = setInterval(fetchEvents, interval);
};
const stopPolling = () => { clearInterval(pollTimer); pollTimer = null; };

// Ajustar intervalo cuando el estado WOE cambia
watch(isWoeActive, (active) => {
    document.documentElement.style.setProperty('--woe-banner-h', active ? BANNER_H : '0px');
    startPolling(); // reinicia con el intervalo correcto
}, { immediate: true });

// ── Flash on new capture ──────────────────────────────────────────────
const tickerFlash = ref(false);
let   flashTimer  = null;

watch(() => events.value.length, (n, o) => {
    if (n > o) {
        clearTimeout(flashTimer);
        tickerFlash.value = true;
        flashTimer = setTimeout(() => { tickerFlash.value = false; }, 1200);
        // La captura nueva siempre queda en tickerItems[0] (events va más reciente primero) — mostrarla ya
        currentIndex.value = 0;
        startRotation();
    }
});

// ── Ticker — rAthena format ───────────────────────────────────────────
const ownedCastles = computed(() => castles.value.filter(c => c.guild_id > 0));

const castleLabel = (name, city) => city ? `${name} (${city})` : name;
const escHtml     = (s) => s ? String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;') : '';
// Inline styles — scoped CSS no penetra en v-html
const emblemHtml  = (guildId, emblemId = 0) => guildId > 0
    ? `<img src="/guild-emblem/${guildId}?v=${emblemId}" style="display:inline-block;width:20px;height:20px;vertical-align:middle;border-radius:2px;margin-right:4px;image-rendering:pixelated" onerror="this.style.display='none'">`
    : '';

const tickerItems = computed(() => {
    if (events.value.length > 0) {
        return events.value.slice().reverse().map(e => {
            const guild  = `(${emblemHtml(e.guild_id, e.emblem_id)}${escHtml(e.guild_name)})`;
            const castle = escHtml(castleLabel(e.castle_name, e.castle_map));
            return `🏰 ${__('The :castle castle has been conquered by the :guild guild.', { castle, guild })}`;
        });
    }
    if (ownedCastles.value.length > 0) {
        return ownedCastles.value.map(c => {
            const guild  = `(${emblemHtml(c.guild_id, c.emblem_id)}${escHtml(c.guild_name)})`;
            const castle = escHtml(castleLabel(c.castle_name, c.castle_map));
            return `🏰 ${__('The :castle castle is held by the :guild guild.', { castle, guild })}`;
        });
    }
    return [`🏰 ${escHtml(__('WOE in progress — monitoring castles…'))}`];
});

// ── Rotación vertical — un mensaje a la vez ──────────────────────────────
const ROTATE_MS = 5000;
const currentIndex = ref(0);
let   rotateTimer  = null;

const startRotation = () => {
    clearInterval(rotateTimer);
    rotateTimer = setInterval(() => {
        if (tickerItems.value.length > 1) {
            currentIndex.value = (currentIndex.value + 1) % tickerItems.value.length;
        }
    }, ROTATE_MS);
};

const currentTickerItem = computed(() => tickerItems.value[currentIndex.value] ?? tickerItems.value[0] ?? '');

// ── Sesión WOE activa — tipo + cuenta atrás ──────────────────────────────
const currentSession = ref(page.props.woeStatus?.current?.[0] ?? null);
const nowSeconds      = ref(Math.floor(Date.now() / 1000));
let   clockTimer      = null;

const countdownLabel = computed(() => {
    if (!currentSession.value) return '';
    const diff = Math.max(0, currentSession.value.end_ts - nowSeconds.value);
    const h = Math.floor(diff / 3600);
    const m = Math.floor((diff % 3600) / 60);
    const s = diff % 60;
    return h > 0
        ? `${h}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`
        : `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
});

// ── Dark mode (bordes del banner = color del header) ────────────────────
// :global(.dark) en <style scoped> no compila de forma confiable con selectores
// descendientes — se resuelve en JS observando la clase del <html>.
const isDarkMode = ref(typeof document !== 'undefined' && document.documentElement.classList.contains('dark'));
let themeObserver = null;

const bannerBg = computed(() => {
    const edge = isDarkMode.value ? '#0f172a' : '#ffffff';
    return `linear-gradient(105deg, ${edge} 0%, #551414 18%, #7f1d1d 50%, #551414 82%, ${edge} 100%)`;
});

// ── Lifecycle ─────────────────────────────────────────────────────────
const syncPreviewStorage = (e) => { if (e.key === 'woe_preview') syncPreview(); };

let inertiaUnsubscribe = null;

onMounted(() => {
    window.addEventListener('woe-preview-changed', syncPreview);
    window.addEventListener('storage', syncPreviewStorage);
    document.addEventListener('visibilitychange', syncOnVisible);
    // Re-sincronizar también en cada navegación Inertia (por si Inertia hizo reload)
    inertiaUnsubscribe = router.on('navigate', () => syncPreview());
    themeObserver = new MutationObserver(() => {
        isDarkMode.value = document.documentElement.classList.contains('dark');
    });
    themeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
    startRotation();
    clockTimer = setInterval(() => { nowSeconds.value = Math.floor(Date.now() / 1000); }, 1000);
});

onUnmounted(() => {
    stopPolling();
    clearTimeout(flashTimer);
    window.removeEventListener('woe-preview-changed', syncPreview);
    window.removeEventListener('storage', syncPreviewStorage);
    document.removeEventListener('visibilitychange', syncOnVisible);
    if (inertiaUnsubscribe) inertiaUnsubscribe();
    if (themeObserver) themeObserver.disconnect();
    clearInterval(rotateTimer);
    clearInterval(clockTimer);
});
</script>

<template>
    <Transition
        enter-active-class="transition-all duration-300 ease-out"
        enter-from-class="opacity-0 -translate-y-full"
        leave-active-class="transition-all duration-200 ease-in"
        leave-to-class="opacity-0 -translate-y-full">

        <div v-if="isWoeActive"
            class="woe-bar relative select-none overflow-hidden"
            :class="minimized ? 'woe-bar--mini' : 'woe-bar--full'"
            :style="{ background: bannerBg }">

            <!-- Gold bottom accent -->
            <div class="woe-gold-line" />

            <!-- ── Full banner ── -->
            <div v-if="!minimized" class="h-full">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center h-full">

                    <!-- Badge -->
                    <div class="woe-badge flex items-center gap-2 px-3 h-full shrink-0">
                        <span class="woe-dot" />
                        <span class="woe-badge-text">⚔ {{ __('WOE LIVE') }}</span>
                    </div>

                    <!-- Divider -->
                    <div class="woe-divider shrink-0" />

                    <!-- Ticker — un mensaje a la vez, slide vertical -->
                    <div class="flex-1 h-full relative min-w-0 overflow-hidden">
                        <Transition name="woe-slide">
                            <div :key="currentTickerItem"
                                class="ticker-text absolute inset-0 flex items-center justify-center px-3 text-center whitespace-nowrap overflow-hidden text-ellipsis"
                                :class="{ 'ticker-flash': tickerFlash }"
                                v-html="currentTickerItem"></div>
                        </Transition>
                    </div>

                    <!-- Tipo de WOE activo + cuenta atrás -->
                    <template v-if="currentSession">
                        <div class="woe-session flex items-center gap-2 px-3 h-full shrink-0">
                            <span class="woe-session-type hidden sm:inline">{{ currentSession.type_label }}</span>
                            <span class="woe-session-time tabular-nums">{{ countdownLabel }}</span>
                        </div>
                        <div class="woe-divider shrink-0" />
                    </template>

                    <!-- Controls -->
                    <div class="flex items-center gap-0.5 pl-2 h-full shrink-0">
                        <button v-if="isAdmin"
                            @click="togglePreview"
                            :title="previewActive ? __('Disable WOE Preview') : __('Enable WOE Preview')"
                            :class="['woe-ctrl-btn', previewActive ? 'woe-ctrl-active' : '']">
                            <EyeSlashIcon v-if="previewActive" class="w-3.5 h-3.5" />
                            <EyeIcon v-else class="w-3.5 h-3.5" />
                        </button>
                        <button @click="toggleMinimize"
                            class="woe-ctrl-btn"
                            :title="__('Minimize')">
                            <ChevronUpIcon class="w-3.5 h-3.5" />
                        </button>
                    </div>

                </div>
            </div>

            <!-- ── Minimized strip ── -->
            <div v-else
                class="flex items-center justify-center h-full cursor-pointer group"
                @click="toggleMinimize"
                :title="__('Expand WOE banner')">
                <span class="woe-mini-text">
                    <span class="woe-dot woe-dot--sm mr-1.5" />
                    {{ __('WOE LIVE') }}
                    <ChevronDownIcon class="inline w-3 h-3 ml-1" />
                </span>
            </div>

        </div>
    </Transition>
</template>

<style scoped>
/* ── Background ───────────────────────────────────────────────────── */
.woe-bar {
    width: 100%;
    /* background viene de :style="bannerBg" — depende del modo oscuro, ver <script> */
    z-index: 1;
}
.woe-bar--full { height: 38px; }
.woe-bar--mini { height: 4px; transition: height 0.2s ease; }
.woe-bar--mini:hover { height: 18px; }

/* ── Gold bottom accent line ──────────────────────────────────────── */
.woe-gold-line {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 1px;
    background: linear-gradient(
        90deg,
        transparent 0%,
        rgba(241, 196, 15, 0.15) 15%,
        rgba(241, 196, 15, 0.5) 40%,
        rgba(241, 196, 15, 0.5) 60%,
        rgba(241, 196, 15, 0.15) 85%,
        transparent 100%
    );
    pointer-events: none;
}

/* ── Badge ────────────────────────────────────────────────────────── */
.woe-badge {
    border-left: 2px solid rgba(241, 196, 15, 0.5);
    border-right: 1px solid rgba(241, 196, 15, 0.2);
    background: rgba(0, 0, 0, 0.32);
}

.woe-badge-text {
    font-size: 0.65rem;
    font-weight: 900;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: #F1C40F;
    text-shadow: 0 0 10px rgba(241, 196, 15, 0.45);
    white-space: nowrap;
}

/* ── Divider ──────────────────────────────────────────────────────── */
.woe-divider {
    width: 1px;
    height: 16px;
    background: linear-gradient(to bottom, transparent, rgba(241, 196, 15, 0.25), transparent);
    margin: 0 2px;
}

/* ── Ticker — slide vertical (un mensaje a la vez) ───────────────────── */
.woe-slide-enter-active,
.woe-slide-leave-active {
    transition: transform 0.45s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.45s ease;
}
.woe-slide-enter-from { transform: translateY(100%); opacity: 0; }
.woe-slide-leave-to   { transform: translateY(-100%); opacity: 0; }

.ticker-text {
    font-size: 0.875rem;
    font-weight: 600;
    letter-spacing: 0.01em;
    color: #F1C40F;
    text-shadow: 0 0 8px rgba(241, 196, 15, 0.3);
}

/* Flash on new capture event */
@keyframes capture-flash {
    0%   { filter: brightness(1); }
    20%  { filter: brightness(2.2) drop-shadow(0 0 4px rgba(241,196,15,0.9)); }
    50%  { filter: brightness(1.4); }
    100% { filter: brightness(1); }
}
.ticker-text.ticker-flash {
    animation: capture-flash 1.0s ease-out;
}

/* ── Sesión WOE activa (tipo + cuenta atrás) ─────────────────────────── */
.woe-session {
    border-left: 1px solid rgba(241, 196, 15, 0.2);
    border-right: 2px solid rgba(241, 196, 15, 0.5);
    background: rgba(0, 0, 0, 0.32);
}
.woe-session-type {
    font-size: 0.65rem;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: rgba(241, 196, 15, 0.75);
    white-space: nowrap;
}
.woe-session-time {
    font-size: 0.8rem;
    font-weight: 700;
    color: #F1C40F;
    font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
    white-space: nowrap;
}

/* ── Controls ─────────────────────────────────────────────────────── */
.woe-ctrl-btn {
    padding: 5px;
    border-radius: 4px;
    color: rgba(241, 196, 15, 0.3);
    transition: color 0.15s, background 0.15s;
}
.woe-ctrl-btn:hover {
    color: rgba(241, 196, 15, 0.8);
    background: rgba(241, 196, 15, 0.08);
}
.woe-ctrl-active {
    color: #F1C40F !important;
}

/* ── Pulse dot ────────────────────────────────────────────────────── */
.woe-dot {
    display: inline-block;
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #F1C40F;
    flex-shrink: 0;
    animation: dot-pulse 2s ease-in-out infinite;
}
.woe-dot--sm { width: 5px; height: 5px; }

@keyframes dot-pulse {
    0%, 100% { opacity: 1;    transform: scale(1);    box-shadow: 0 0 0 0   rgba(241, 196, 15, 0.7); }
    50%       { opacity: 0.5; transform: scale(1.35); box-shadow: 0 0 0 5px rgba(241, 196, 15, 0); }
}

/* ── Minimized text ───────────────────────────────────────────────── */
.woe-mini-text {
    display: inline-flex;
    align-items: center;
    font-size: 0.6rem;
    font-weight: 900;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: rgba(241, 196, 15, 0.5);
    transition: color 0.15s;
}
.group:hover .woe-mini-text { color: rgba(241, 196, 15, 0.85); }

/* ── Reduced motion ───────────────────────────────────────────────── */
@media (prefers-reduced-motion: reduce) {
    .woe-slide-enter-active,
    .woe-slide-leave-active    { transition: none; }
    .woe-dot                   { animation: none; }
    .ticker-text.ticker-flash  { animation: none; }
}
</style>

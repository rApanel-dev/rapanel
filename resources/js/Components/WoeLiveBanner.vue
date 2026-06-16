<script setup>
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue';
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
        if (typeof data.active === 'boolean') woeActivePolled.value = data.active;
        if (data.events)  events.value  = data.events;
        if (data.castles) castles.value = data.castles;
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
    }
});

// ── Ticker — rAthena format ───────────────────────────────────────────
const ownedCastles = computed(() => castles.value.filter(c => c.guild_id > 0));

const castleLabel = (name, city) => city ? `${name} (${city})` : name;
const escHtml     = (s) => s ? String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;') : '';
// Inline styles — scoped CSS no penetra en v-html
const emblemHtml  = (guildId, emblemId = 0) => guildId > 0
    ? `<img src="/guild-emblem/${guildId}?v=${emblemId}" style="display:inline-block;width:16px;height:16px;vertical-align:middle;border-radius:2px;margin:0 3px 0 1px;image-rendering:pixelated" onerror="this.style.display='none'">`
    : '';

const tickerItems = computed(() => {
    if (events.value.length > 0) {
        return events.value.slice().reverse().map(e => {
            const guild  = `${emblemHtml(e.guild_id, e.emblem_id)}${escHtml(e.guild_name)}`;
            const castle = escHtml(castleLabel(e.castle_name, e.castle_map));
            return `🏰 ${__('The :castle castle has been conquered by the :guild guild.', { castle, guild })}`;
        });
    }
    if (ownedCastles.value.length > 0) {
        return ownedCastles.value.map(c => {
            const guild  = `${emblemHtml(c.guild_id, c.emblem_id)}${escHtml(c.guild_name)}`;
            const castle = escHtml(castleLabel(c.castle_name, c.castle_map));
            return `🏰 ${__('The :castle castle is held by the :guild guild.', { castle, guild })}`;
        });
    }
    return [`🏰 ${escHtml(__('WOE in progress — monitoring castles…'))}`];
});

const tickerText = computed(() => tickerItems.value.join('&nbsp;&nbsp;&nbsp;&nbsp;·&nbsp;&nbsp;&nbsp;&nbsp;'));

// ── Ticker sizing ─────────────────────────────────────────────────────
// Animación: translateX(--cw) → translateX(-100%)
//   --cw  = ancho del wrapper: texto entra desde el borde derecho
//   -100% = ancho propio del span (T): texto sale por el borde izquierdo
// Un solo span → mensaje pasa UNA vez por loop (ticker de noticias estándar).
const tickerWrapperRef = ref(null);
const containerW = ref(800);
let   tickerRO = null;

const updateSizes = () => {
    const wrapper = tickerWrapperRef.value;
    if (!wrapper) return;
    containerW.value = wrapper.offsetWidth;
};

// Duración: (C + T) / 70px/s
// Stripear tags HTML antes de medir — v-html mete markup que infla el conteo
const stripHtml = (s) => s.replace(/<[^>]*>/g, '');
const tickerDuration = computed(() => {
    const textLen = stripHtml(tickerText.value).length;
    const approxT = textLen * 6 + tickerItems.value.length * 20; // +20px por emblema
    const travel  = containerW.value + approxT;
    return Math.max(20, Math.round(travel / 70)) + 's';
});

// ── Lifecycle ─────────────────────────────────────────────────────────
const syncPreviewStorage = (e) => { if (e.key === 'woe_preview') syncPreview(); };

watch(isWoeActive, (active) => { if (active) nextTick(updateSizes); });

let inertiaUnsubscribe = null;

onMounted(() => {
    window.addEventListener('woe-preview-changed', syncPreview);
    window.addEventListener('storage', syncPreviewStorage);
    document.addEventListener('visibilitychange', syncOnVisible);
    // Re-sincronizar también en cada navegación Inertia (por si Inertia hizo reload)
    inertiaUnsubscribe = router.on('navigate', () => syncPreview());
    nextTick(() => {
        updateSizes();
        if (tickerWrapperRef.value) {
            tickerRO = new ResizeObserver(updateSizes);
            tickerRO.observe(tickerWrapperRef.value);
        }
    });
});

onUnmounted(() => {
    stopPolling();
    clearTimeout(flashTimer);
    window.removeEventListener('woe-preview-changed', syncPreview);
    window.removeEventListener('storage', syncPreviewStorage);
    document.removeEventListener('visibilitychange', syncOnVisible);
    if (inertiaUnsubscribe) inertiaUnsubscribe();
    if (tickerRO) tickerRO.disconnect();
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
            :class="minimized ? 'woe-bar--mini' : 'woe-bar--full'">

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

                    <!-- Ticker -->
                    <div ref="tickerWrapperRef" class="flex-1 overflow-hidden h-full relative min-w-0">
                        <div class="ticker-track absolute inset-y-0 flex items-center whitespace-nowrap"
                            :class="{ 'ticker-flash': tickerFlash }"
                            :style="{ '--ticker-dur': tickerDuration }">
                            <span class="ticker-text px-4" v-html="tickerText"></span>
                        </div>
                    </div>

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
    animation: woe-bg 6s ease-in-out infinite;
    z-index: 1;
}
.woe-bar--full { height: 38px; }
.woe-bar--mini { height: 4px; transition: height 0.2s ease; }
.woe-bar--mini:hover { height: 18px; }

@keyframes woe-bg {
    0%, 100% {
        background: linear-gradient(105deg,
            #0d0101 0%,
            #220505 25%,
            #2e0707 50%,
            #220505 75%,
            #0d0101 100%);
    }
    50% {
        background: linear-gradient(105deg,
            #0d0101 0%,
            #2e0606 25%,
            #3d0909 50%,
            #2e0606 75%,
            #0d0101 100%);
    }
}

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
    border-right: 1px solid rgba(185, 28, 28, 0.3);
    background: rgba(185, 28, 28, 0.12);
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

/* ── Ticker ───────────────────────────────────────────────────────── */
/* --ticker-dur se setea via :style y no reinicia la animación al cambiar */
.ticker-track {
    animation: ticker-scroll var(--ticker-dur, 30s) linear infinite;
}
@keyframes ticker-scroll {
    from { transform: translateX(100vw); }
    to   { transform: translateX(-100%); }
}

.ticker-text {
    font-size: 0.75rem;
    font-weight: 500;
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
.ticker-flash .ticker-text {
    animation: capture-flash 1.0s ease-out;
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
    .woe-bar                   { animation: none; background: #1a0404; }
    .ticker-track              { animation: none; transform: translateX(0); }
    .woe-dot                   { animation: none; }
    .ticker-flash .ticker-text { animation: none; }
}
</style>

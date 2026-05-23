<script setup>
import { ref, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const __   = (key) => page.props.translations?.[key] || key;

// ── State ─────────────────────────────────────────────────────────────
const visible      = ref(false);
const loading      = ref(false);
const shop         = ref(null);
const items        = ref([]);
const locationCopied = ref(false);
const search       = ref('');
const sortKey      = ref('price-asc');
const copiedItem   = ref(null);

let locationTimer = null;
let itemCopyTimer = null;

// ── Computed ──────────────────────────────────────────────────────────
const filteredItems = computed(() => {
    let list = [...items.value];
    if (search.value) {
        const q = search.value.toLowerCase();
        list = list.filter(i => i.item_name.toLowerCase().includes(q));
    }
    const [field, dir] = sortKey.value.split('-');
    list.sort((a, b) => dir === 'asc' ? a[field] - b[field] : b[field] - a[field]);
    return list;
});

const totalQty  = computed(() => items.value.reduce((s, i) => s + i.amount, 0));
const minPrice  = computed(() => items.value.length ? Math.min(...items.value.map(i => i.price)) : 0);
const maxPrice  = computed(() => items.value.length ? Math.max(...items.value.map(i => i.price)) : 0);

// ── Price style ───────────────────────────────────────────────────────
const PRICE_TIERS = [
    { min: 1,            max: 99,          color: '#000080', shadow: '#FF00FF' },
    { min: 100,          max: 999,         color: '#0000FF', shadow: '#00FFFF' },
    { min: 1_000,        max: 9_999,       color: '#FF0000', shadow: '#FFFF00' },
    { min: 10_000,       max: 99_999,      color: '#FF00FF', shadow: null      },
    { min: 100_000,      max: 999_999,     color: '#0000FF', shadow: null      },
    { min: 1_000_000,    max: 9_999_999,   color: '#000000', shadow: '#00FF00' },
    { min: 10_000_000,   max: 99_999_999,  color: '#FF0000', shadow: null      },
    { min: 100_000_000,  max: 999_999_999, color: '#000000', shadow: '#BDB76B' },
    { min: 1_000_000_000,max: Infinity,    color: '#FF0000', shadow: '#FF00FF' },
];

const priceStyle = (n) => {
    const tier = PRICE_TIERS.find(t => n >= t.min && n <= t.max);
    if (!tier) return {};
    return { color: tier.color, textShadow: tier.shadow ? `1px 0 0 ${tier.shadow}` : undefined };
};

const formatPrice = (n) => Number(n).toLocaleString();
const itemImg     = (nameid) => `/data/items/item/${nameid}.png`;

const elementStyle = (el) => {
    if (el === 'Water') return 'bg-rapanel-blue/10 text-rapanel-blue border-rapanel-blue/30 dark:bg-rapanel-blue/15';
    if (el === 'Earth') return 'bg-emerald-500/10 text-emerald-600 border-emerald-500/30 dark:text-emerald-400';
    if (el === 'Fire')  return 'bg-rapanel-danger/10 text-rapanel-danger border-rapanel-danger/30';
    if (el === 'Wind')  return 'bg-cyan-500/10 text-cyan-600 border-cyan-500/30 dark:text-cyan-400';
    return '';
};

// ── Methods ───────────────────────────────────────────────────────────
const open = async (vendingId) => {
    visible.value  = true;
    loading.value  = true;
    shop.value     = null;
    items.value    = [];
    search.value   = '';
    document.body.style.overflow = 'hidden';

    try {
        const res  = await fetch(route('info.who-sell.shop', { vendingId }), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        const data = await res.json();
        shop.value  = data.shop;
        items.value = data.items;
    } finally {
        loading.value = false;
    }
};

const close = () => {
    visible.value = false;
    document.body.style.overflow = '';
};

const refresh = () => { if (shop.value) open(shop.value.id); };

const copyLocation = () => {
    if (!shop.value) return;
    const cmd = `/navigation ${shop.value.map} ${shop.value.x}/${shop.value.y}`;
    navigator.clipboard.writeText(cmd).then(() => {
        clearTimeout(locationTimer);
        locationCopied.value = true;
        locationTimer = setTimeout(() => { locationCopied.value = false; }, 1500);
    });
};

const copyItemName = (item, idx) => {
    navigator.clipboard.writeText(item.item_name).then(() => {
        clearTimeout(itemCopyTimer);
        copiedItem.value = idx;
        itemCopyTimer = setTimeout(() => { copiedItem.value = null; }, 1500);
    });
};

defineExpose({ open });
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0">

            <div v-if="visible" class="fixed inset-0 z-[60] flex items-center justify-center p-4">

                <!-- Overlay -->
                <div class="absolute inset-0 bg-black/50 dark:bg-black/70 backdrop-blur-sm" @click="close" />

                <!-- Panel -->
                <Transition
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="opacity-0 scale-95"
                    enter-to-class="opacity-100 scale-100"
                    leave-active-class="transition duration-150 ease-in"
                    leave-from-class="opacity-100 scale-100"
                    leave-to-class="opacity-0 scale-95"
                    appear>
                    <div v-if="visible"
                         class="relative z-10 w-full max-w-6xl max-h-[88vh] flex flex-col bg-white dark:bg-rapanel-navy-900 rounded-2xl shadow-2xl border border-rapanel-navy-100 dark:border-white/[0.08] overflow-hidden">

                        <!-- Header -->
                        <div class="px-6 pt-5 pb-4 border-b border-rapanel-navy-100 dark:border-white/[0.07] shrink-0">

                            <div class="flex items-start gap-3">
                                <!-- Ícono tienda -->
                                <div class="w-10 h-10 rounded-xl bg-rapanel-navy-100 dark:bg-white/10 flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5 text-rapanel-text-light dark:text-white/60" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.65V9.35m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 002.25 1.016c.896 0 1.7-.393 2.25-1.016a3.001 3.001 0 003.75.614m-16.5 0a3.004 3.004 0 01-.621-4.72L4.318 3.44A1.5 1.5 0 015.378 3h13.243a1.5 1.5 0 011.06.44l1.19 2.189a3.004 3.004 0 01-.621 4.72m-13.5 8.65h3.75a.75.75 0 00.75-.75V13.5a.75.75 0 00-.75-.75H6.75a.75.75 0 00-.75.75v3.75c0 .415.336.75.75.75z"/>
                                    </svg>
                                </div>

                                <div class="flex-1 min-w-0">
                                    <template v-if="loading && !shop">
                                        <div class="h-5 w-48 bg-rapanel-navy-100 dark:bg-white/10 rounded animate-pulse mb-1.5" />
                                        <div class="h-3 w-32 bg-rapanel-navy-100 dark:bg-white/10 rounded animate-pulse" />
                                    </template>
                                    <template v-else-if="shop">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <h2 class="text-base font-display font-bold text-rapanel-navy-900 dark:text-white leading-tight">
                                                {{ shop.title }}
                                            </h2>
                                            <button @click="copyLocation"
                                                :title="`/navigation ${shop.map} ${shop.x}/${shop.y}`"
                                                class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md text-[10px] font-semibold border transition-all duration-150 cursor-pointer select-none"
                                                :class="locationCopied
                                                    ? 'bg-emerald-500/10 text-emerald-600 border-emerald-500/25 dark:text-emerald-400 dark:border-emerald-500/25'
                                                    : 'bg-rapanel-blue/8 text-rapanel-blue border-rapanel-blue/25 hover:bg-rapanel-blue/15 dark:bg-rapanel-gold/8 dark:text-rapanel-gold dark:border-rapanel-gold/25 dark:hover:bg-rapanel-gold/15'">
                                                <svg v-if="!locationCopied" class="w-2.5 h-2.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/>
                                                </svg>
                                                <svg v-else class="w-2.5 h-2.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                                                </svg>
                                                <template v-if="!locationCopied">
                                                    <span>{{ shop.map }}</span>
                                                    <span class="font-mono opacity-70">{{ shop.x }} {{ shop.y }}</span>
                                                </template>
                                                <template v-else>{{ __('Location Copied!') }}</template>
                                            </button>
                                        </div>
                                        <p class="text-xs text-rapanel-text-light/50 dark:text-white/40 mt-0.5">
                                            {{ __('Seller') }}: <span class="font-semibold text-rapanel-text-light dark:text-white/70">{{ shop.char_name }}</span>
                                        </p>
                                    </template>
                                </div>

                                <!-- Refresh + Cerrar -->
                                <div class="flex items-center gap-1.5 shrink-0">
                                    <button @click="refresh"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs font-semibold border transition-colors
                                               bg-rapanel-navy-50 dark:bg-white/5 text-rapanel-text-light dark:text-white/60 border-rapanel-navy-200 dark:border-white/10
                                               hover:bg-rapanel-navy-100 dark:hover:bg-white/10">
                                        <svg class="w-3 h-3" :class="loading ? 'animate-spin' : ''" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/>
                                        </svg>
                                        {{ __('Refresh') }}
                                    </button>
                                    <button @click="close"
                                        class="w-8 h-8 flex items-center justify-center rounded-lg text-rapanel-text-light/40 dark:text-white/30 hover:bg-rapanel-navy-100 dark:hover:bg-white/10 hover:text-rapanel-text-light dark:hover:text-white transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Stats chips -->
                            <div v-if="!loading && shop" class="flex flex-wrap gap-2 mt-3">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-semibold bg-rapanel-navy-50 dark:bg-white/5 text-rapanel-text-light dark:text-white/50 border border-rapanel-navy-100 dark:border-white/[0.07]">
                                    {{ __('Unique items') }}: <strong>{{ items.length }}</strong>
                                </span>
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-semibold bg-rapanel-navy-50 dark:bg-white/5 text-rapanel-text-light dark:text-white/50 border border-rapanel-navy-100 dark:border-white/[0.07]">
                                    {{ __('Total quantity') }}: <strong>{{ totalQty.toLocaleString() }}</strong>
                                </span>
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-semibold bg-rapanel-navy-50 dark:bg-white/5 text-rapanel-text-light dark:text-white/50 border border-rapanel-navy-100 dark:border-white/[0.07]">
                                    {{ __('Price range') }}: <strong :style="priceStyle(minPrice)">{{ formatPrice(minPrice) }}</strong>
                                    <span class="opacity-40 mx-0.5">–</span>
                                    <strong :style="priceStyle(maxPrice)">{{ formatPrice(maxPrice) }}</strong>
                                </span>
                            </div>

                            <!-- Buscador + sort -->
                            <div v-if="!loading" class="flex gap-2 mt-3">
                                <div class="relative flex-1">
                                    <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-rapanel-text-light/30 dark:text-white/25 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                                    </svg>
                                    <input v-model="search" type="text"
                                           :placeholder="__('Search item in this shop...')"
                                           class="w-full pl-8 pr-3 py-1.5 rounded-lg border text-xs bg-white dark:bg-rapanel-navy-800 border-rapanel-navy-200 dark:border-white/10 text-rapanel-text-light dark:text-rapanel-text-dark placeholder-rapanel-text-light/30 dark:placeholder-white/20 focus:outline-none focus:ring-2 focus:ring-rapanel-blue/30 focus:border-rapanel-blue/40 transition" />
                                </div>
                                <select v-model="sortKey"
                                    class="px-2.5 py-1.5 rounded-lg border text-xs bg-white dark:bg-rapanel-navy-800 border-rapanel-navy-200 dark:border-white/10 text-rapanel-text-light dark:text-rapanel-text-dark focus:outline-none focus:ring-2 focus:ring-rapanel-blue/30 transition">
                                    <option value="price-asc">{{ __('Sort by: Price') }} ↑</option>
                                    <option value="price-desc">{{ __('Sort by: Price') }} ↓</option>
                                    <option value="amount-asc">{{ __('Sort by: Qty') }} ↑</option>
                                    <option value="amount-desc">{{ __('Sort by: Qty') }} ↓</option>
                                </select>
                            </div>
                        </div>

                        <!-- Contenido scrollable -->
                        <div class="overflow-y-auto flex-1 p-4">

                            <!-- Loading skeleton -->
                            <div v-if="loading" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                                <div v-for="n in 6" :key="n" class="rounded-xl border border-rapanel-navy-100 dark:border-white/[0.07] p-4 animate-pulse">
                                    <div class="flex gap-3">
                                        <div class="w-9 h-9 rounded-lg bg-rapanel-navy-100 dark:bg-white/10 shrink-0" />
                                        <div class="flex-1 space-y-2">
                                            <div class="h-3 bg-rapanel-navy-100 dark:bg-white/10 rounded w-3/4" />
                                            <div class="h-2.5 bg-rapanel-navy-100 dark:bg-white/10 rounded w-1/2" />
                                        </div>
                                    </div>
                                    <div class="mt-4 h-4 bg-rapanel-navy-100 dark:bg-white/10 rounded w-1/3" />
                                </div>
                            </div>

                            <!-- Grid de items -->
                            <div v-else-if="filteredItems.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                                <div v-for="(it, idx) in filteredItems" :key="idx"
                                    class="flex flex-col rounded-xl border border-rapanel-navy-100 dark:border-white/[0.07] bg-rapanel-navy-50/40 dark:bg-white/[0.02] hover:bg-rapanel-navy-50 dark:hover:bg-white/[0.04] transition-colors p-3.5">

                                    <div class="flex items-start gap-2.5">
                                        <img :src="itemImg(it.nameid)" :alt="it.item_name"
                                             class="w-9 h-9 object-contain shrink-0 mt-0.5"
                                             @error="$event.target.style.display='none'" />
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center gap-1 flex-wrap">
                                                <span v-if="it.refine > 0" class="text-xs font-black text-rapanel-blue shrink-0">+{{ it.refine }}</span>
                                                <span class="text-sm font-semibold text-rapanel-navy-900 dark:text-white leading-tight">{{ it.item_name }}</span>
                                                <span v-if="it.slots > 0 && !it.forged" class="text-xs font-semibold text-rapanel-text-light/40 dark:text-white/30">[{{ it.slots }}]</span>
                                            </div>
                                            <p class="text-[11px] text-rapanel-text-light/50 dark:text-white/35 mt-0.5">
                                                {{ __('Qty') }}: <span class="font-semibold">{{ it.amount }}</span>
                                                <span class="mx-1 opacity-30">·</span>
                                                ID: {{ it.nameid }}
                                            </p>
                                            <div v-if="it.forged" class="flex flex-wrap gap-1 mt-1.5">
                                                <span v-if="it.forged.stars_label" class="inline-flex items-center px-1.5 py-0.5 rounded-md text-[10px] font-semibold bg-rapanel-gold/10 text-rapanel-gold border border-rapanel-gold/25">{{ it.forged.stars_label }}</span>
                                                <span v-if="it.forged.element" :class="['inline-flex items-center px-1.5 py-0.5 rounded-md text-[10px] font-semibold border', elementStyle(it.forged.element)]">{{ it.forged.element }}</span>
                                                <span v-if="it.forged.creator_name" class="inline-flex items-center px-1.5 py-0.5 rounded-md text-[10px] font-semibold bg-rapanel-navy-100 dark:bg-white/10 text-rapanel-text-light dark:text-white/60 border border-rapanel-navy-200 dark:border-white/10">{{ it.forged.creator_name }}</span>
                                            </div>
                                            <div v-else-if="it.cards && it.cards.length" class="flex flex-wrap gap-1 mt-1.5">
                                                <span v-for="card in it.cards" :key="card.id" class="inline-flex items-center px-1.5 py-0.5 rounded-md text-[10px] font-semibold bg-rapanel-gold/10 text-rapanel-gold border border-rapanel-gold/25">{{ card.name }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Footer: copiar + precio -->
                                    <div class="mt-auto pt-3 flex items-center justify-between gap-2">
                                        <button @click="copyItemName(it, idx)"
                                            class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md text-[10px] font-semibold border transition-all duration-150 cursor-pointer select-none shrink-0"
                                            :class="copiedItem === idx
                                                ? 'bg-emerald-500/10 text-emerald-600 border-emerald-500/25 dark:text-emerald-400 dark:border-emerald-500/25'
                                                : 'bg-rapanel-blue/8 text-rapanel-blue border-rapanel-blue/25 hover:bg-rapanel-blue/15 dark:bg-rapanel-gold/8 dark:text-rapanel-gold dark:border-rapanel-gold/25 dark:hover:bg-rapanel-gold/15'">
                                            <svg v-if="copiedItem !== idx" class="w-2.5 h-2.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.888A2.25 2.25 0 0013.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 01-.75.75H9a.75.75 0 01-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 01-2.25 2.25H6.75A2.25 2.25 0 014.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 011.927-.184"/>
                                            </svg>
                                            <svg v-else class="w-2.5 h-2.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                                            </svg>
                                            <span>{{ copiedItem === idx ? __('Copied!') : __('Copy name') }}</span>
                                        </button>
                                        <div class="flex items-baseline gap-1">
                                            <span class="font-bold text-sm" :style="priceStyle(it.price)">{{ formatPrice(it.price) }}</span>
                                            <span class="text-sm font-semibold text-rapanel-text-light dark:text-rapanel-text-dark">Z</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Sin resultados de búsqueda -->
                            <div v-else-if="search" class="flex flex-col items-center justify-center py-16 text-center">
                                <p class="text-sm text-rapanel-text-light/50 dark:text-white/35">{{ __('No items found for your search.') }}</p>
                                <button @click="search = ''" class="mt-3 text-xs text-rapanel-blue hover:underline">{{ __('Clear search') }}</button>
                            </div>

                            <!-- Tienda vacía -->
                            <div v-else class="flex items-center justify-center py-16 text-sm text-rapanel-text-light/50 dark:text-white/35">
                                {{ __('This shop is empty or no longer active.') }}
                            </div>
                        </div>

                        <!-- Footer -->
                        <div v-if="!loading && filteredItems.length" class="px-6 py-3 border-t border-rapanel-navy-100 dark:border-white/[0.07] shrink-0">
                            <p class="text-xs text-rapanel-text-light/40 dark:text-white/30">
                                {{ __('Showing :n results').replace(':n', filteredItems.length) }}
                            </p>
                        </div>

                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>

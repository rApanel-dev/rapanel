<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import WhoSellShopModal from '@/Components/WhoSellShopModal.vue';
import { useItemDbModal } from '@/Composables/useItemDbModal';

const props = defineProps({
    items:   { type: Array,  default: () => [] },
    meta:    { type: Object, default: () => ({}) },
    filters: { type: Object, default: () => ({}) },
});

const page     = usePage();
const __       = (key) => page.props.translations?.[key] || key;
const safeRoute = (name, params = {}) => { try { return route(name, params); } catch { return '#'; } };

const search  = ref(props.filters.search ?? '');
const sortBy  = ref(props.filters.sort   ?? 'price');
const sortDir = ref(props.filters.dir    ?? 'asc');

let searchTimer = null;
watch(search, () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => applyFilters(), 500);
});

const applyFilters = () => {
    router.get(safeRoute('info.who-sell'), {
        search:  search.value || undefined,
        sort:    sortBy.value,
        dir:     sortDir.value,
    }, { preserveScroll: true, preserveState: true, replace: true });
};

const toggleSort = (col) => {
    if (sortBy.value === col) {
        sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortBy.value  = col;
        sortDir.value = 'asc';
    }
    applyFilters();
};

const sortIcon = (col) => {
    if (sortBy.value !== col) return 'neutral';
    return sortDir.value === 'asc' ? 'asc' : 'desc';
};

const itemImg = (nameid) => `/data/items/item/${nameid}.png`;

const formatPrice = (n) => Number(n).toLocaleString();

const PRICE_TIERS = [
    { min: 1,          max: 99,        color: '#000080', shadow: '#FF00FF' },
    { min: 100,        max: 999,       color: '#0000FF', shadow: '#00FFFF' },
    { min: 1_000,      max: 9_999,     color: '#FF0000', shadow: '#FFFF00' },
    { min: 10_000,     max: 99_999,    color: '#FF00FF', shadow: null      },
    { min: 100_000,    max: 999_999,   color: '#0000FF', shadow: null      },
    { min: 1_000_000,  max: 9_999_999, color: '#000000', shadow: '#00FF00' },
    { min: 10_000_000, max: 99_999_999,color: '#FF0000', shadow: null      },
    { min: 100_000_000,max: 999_999_999,color:'#000000', shadow: '#BDB76B' },
    { min: 1_000_000_000, max: Infinity,color:'#FF0000', shadow: '#FF00FF' },
];

const priceStyle = (n) => {
    const tier = PRICE_TIERS.find(t => n >= t.min && n <= t.max);
    if (!tier) return {};
    return {
        color:      tier.color,
        textShadow: tier.shadow ? `1px 0 0 ${tier.shadow}` : undefined,
    };
};

const copiedRow  = ref(null);
let copyTimer    = null;

// ── Modal tienda ──────────────────────────────────────────
const shopModal = ref(null);
const openShopModal = (vendingId) => shopModal.value?.open(vendingId);

const copyNavigation = (item, index) => {
    const cmd = `/navigation ${item.map} ${item.x}/${item.y}`;
    navigator.clipboard.writeText(cmd).then(() => {
        clearTimeout(copyTimer);
        copiedRow.value = index;
        copyTimer = setTimeout(() => { copiedRow.value = null; }, 1500);
    });
};

const elementStyle = (el) => {
    if (el === 'Water') return 'bg-rapanel-blue/10 text-rapanel-blue border-rapanel-blue/30 dark:bg-rapanel-blue/15';
    if (el === 'Earth') return 'bg-emerald-500/10 text-emerald-600 border-emerald-500/30 dark:text-emerald-400';
    if (el === 'Fire')  return 'bg-rapanel-danger/10 text-rapanel-danger border-rapanel-danger/30';
    if (el === 'Wind')  return 'bg-cyan-500/10 text-cyan-600 border-cyan-500/30 dark:text-cyan-400';
    return '';
};

const { openItemDb } = useItemDbModal();
</script>

<template>
    <Head :title="__('Who Sell')" />

    <MainLayout :show-bg="true">

        <!-- ── Header ── -->
        <div class="bg-white dark:bg-rapanel-navy-900 border-b border-rapanel-navy-100 dark:border-white/10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
                <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-6">

                    <div>
                        <nav class="flex items-center gap-1.5 text-xs text-rapanel-text-light/40 dark:text-white/30 mb-2">
                            <Link :href="safeRoute('home')" class="hover:text-rapanel-blue transition-colors">{{ __('Home') }}</Link>
                            <span>›</span>
                            <span>{{ __('Who Sell') }}</span>
                        </nav>
                        <h1 class="text-3xl font-display font-bold text-rapanel-navy-900 dark:text-white tracking-wide">
                            {{ __('Who Sell') }}
                        </h1>
                        <p class="mt-1.5 text-sm text-rapanel-text-light dark:text-rapanel-text-dark">
                            {{ __('Search active player shops by item name or shop title.') }}
                        </p>
                    </div>

                    <!-- Search -->
                    <div class="relative w-full sm:w-80 shrink-0">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-rapanel-text-light/40 dark:text-white/30 pointer-events-none"
                             fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                        </svg>
                        <input
                            v-model="search"
                            type="text"
                            :placeholder="__('Item name or shop...')"
                            class="w-full pl-9 pr-4 py-2.5 rounded-xl border border-rapanel-navy-100 dark:border-white/10 bg-white dark:bg-rapanel-navy-800 text-sm text-rapanel-text-light dark:text-rapanel-text-dark placeholder-rapanel-text-light/30 dark:placeholder-white/25 focus:outline-none focus:ring-2 focus:ring-rapanel-blue/30 focus:border-rapanel-blue/50 transition"
                        />
                        <button v-if="search"
                            @click="search = ''"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-rapanel-text-light/30 hover:text-rapanel-text-light dark:text-white/25 dark:hover:text-white/60 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                </div>

                <!-- Stats bar -->
                <div v-if="meta.total > 0" class="mt-4 text-xs text-rapanel-text-light/40 dark:text-white/30">
                    {{ __('Showing') }} {{ meta.from }}–{{ meta.to }} {{ __('of') }} {{ meta.total }} {{ __('results') }}
                </div>
            </div>
        </div>

        <!-- ── Content ── -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

            <!-- Table -->
            <div v-if="items.length" class="bg-white dark:bg-rapanel-navy-900 rounded-2xl border border-rapanel-navy-100 dark:border-white/[0.07] shadow-sm overflow-hidden mb-8">

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-rapanel-navy-100 dark:bg-rapanel-navy-800 text-left">
                                <th class="px-4 py-3 text-xs font-black uppercase tracking-widest text-rapanel-text-light dark:text-rapanel-text-dark">
                                    {{ __('Item') }}
                                </th>
                                <!-- Sortable: Amount -->
                                <th class="px-4 py-3 text-xs font-black uppercase tracking-widest text-rapanel-text-light dark:text-rapanel-text-dark cursor-pointer select-none whitespace-nowrap text-center"
                                    @click="toggleSort('amount')">
                                    <span class="inline-flex items-center justify-center gap-1">
                                        {{ __('Qty') }}
                                        <span class="flex flex-col leading-none">
                                            <svg class="w-2.5 h-2.5" :class="sortIcon('amount') === 'asc' ? 'text-rapanel-blue' : 'opacity-25'" fill="currentColor" viewBox="0 0 24 24"><path d="M12 5l7 7H5z"/></svg>
                                            <svg class="w-2.5 h-2.5" :class="sortIcon('amount') === 'desc' ? 'text-rapanel-blue' : 'opacity-25'" fill="currentColor" viewBox="0 0 24 24"><path d="M12 19l-7-7h14z"/></svg>
                                        </span>
                                    </span>
                                </th>
                                <!-- Sortable: Price -->
                                <th class="px-4 py-3 text-xs font-black uppercase tracking-widest text-rapanel-text-light dark:text-rapanel-text-dark cursor-pointer select-none whitespace-nowrap text-right"
                                    @click="toggleSort('price')">
                                    <span class="inline-flex items-center justify-end gap-1">
                                        {{ __('Price') }}
                                        <span class="flex flex-col leading-none">
                                            <svg class="w-2.5 h-2.5" :class="sortIcon('price') === 'asc' ? 'text-rapanel-blue' : 'opacity-25'" fill="currentColor" viewBox="0 0 24 24"><path d="M12 5l7 7H5z"/></svg>
                                            <svg class="w-2.5 h-2.5" :class="sortIcon('price') === 'desc' ? 'text-rapanel-blue' : 'opacity-25'" fill="currentColor" viewBox="0 0 24 24"><path d="M12 19l-7-7h14z"/></svg>
                                        </span>
                                    </span>
                                </th>
                                <th class="px-4 py-3 text-xs font-black uppercase tracking-widest text-rapanel-text-light dark:text-rapanel-text-dark">
                                    {{ __('Shop') }}
                                </th>
                                <th class="px-4 py-3 text-xs font-black uppercase tracking-widest text-rapanel-text-light dark:text-rapanel-text-dark"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-rapanel-navy-100 dark:divide-white/[0.05]">
                            <tr v-for="(item, i) in items" :key="i"
                                class="hover:bg-rapanel-navy-50 dark:hover:bg-white/[0.03] transition-colors duration-150">

                                <!-- Item (icon + nombre + ID + slots + badges) -->
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3 cursor-pointer group/item"
                                         @click="openItemDb(item.nameid, item)">

                                        <!-- Icono sin cuadro -->
                                        <img :src="itemImg(item.nameid)"
                                             :alt="item.item_name"
                                             class="w-9 h-9 object-contain shrink-0 mt-0.5"
                                             @error="$event.target.style.display='none'" />

                                        <div class="min-w-0">
                                            <!-- Refine + Nombre + Slots -->
                                            <div class="flex items-center gap-1.5 flex-wrap">
                                                <span v-if="item.refine > 0"
                                                      class="text-xs font-black text-rapanel-blue shrink-0">
                                                    +{{ item.refine }}
                                                </span>
                                                <span class="font-semibold text-rapanel-navy-900 dark:text-white text-sm">
                                                    {{ item.item_name }}
                                                </span>
                                                <span v-if="item.slots > 0 && !item.forged"
                                                      class="text-xs font-semibold text-rapanel-text-light/50 dark:text-white/35 shrink-0">
                                                    [{{ item.slots }}]
                                                </span>
                                            </div>

                                            <!-- Badges: arma forjada -->
                                            <div v-if="item.forged" class="flex flex-wrap gap-1 mt-1">
                                                <span v-if="item.forged.stars_label"
                                                      class="inline-flex items-center px-1.5 py-0.5 rounded-md text-[10px] font-semibold bg-rapanel-gold/10 text-rapanel-gold border border-rapanel-gold/25 dark:bg-rapanel-gold/15 dark:border-rapanel-gold/30">
                                                    {{ item.forged.stars_label }}
                                                </span>
                                                <span v-if="item.forged.element"
                                                      :class="['inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md text-[10px] font-semibold border', elementStyle(item.forged.element)]">
                                                    {{ item.forged.element }}
                                                </span>
                                                <span v-if="item.forged.creator_name"
                                                      class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md text-[10px] font-semibold bg-rapanel-navy-100 dark:bg-white/10 text-rapanel-text-light dark:text-white/60 border border-rapanel-navy-200 dark:border-white/15">
                                                    <svg class="w-2.5 h-2.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085"/>
                                                    </svg>
                                                    {{ item.forged.creator_name }}
                                                </span>
                                            </div>

                                            <!-- Badges: cartas normales -->
                                            <div v-else-if="item.cards.length" class="flex flex-wrap gap-1 mt-1">
                                                <span v-for="card in item.cards" :key="card.id"
                                                      class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md text-[10px] font-semibold bg-rapanel-gold/10 text-rapanel-gold border border-rapanel-gold/25 dark:bg-rapanel-gold/15 dark:border-rapanel-gold/30">
                                                    <svg class="w-2.5 h-2.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 013.75 9.375v-4.5zM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 01-1.125-1.125v-4.5zM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0113.5 9.375v-4.5z"/>
                                                    </svg>
                                                    {{ card.name }}
                                                </span>
                                            </div>

                                            <!-- ID -->
                                            <div class="mt-0.5">
                                                <span class="text-[10px] text-rapanel-text-light/40 dark:text-white/25">
                                                    ID: {{ item.nameid }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Amount -->
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex items-center justify-center min-w-[2rem] px-2 py-0.5 rounded-md text-xs font-bold bg-rapanel-navy-50 dark:bg-white/5 text-rapanel-text-light dark:text-rapanel-text-dark border border-rapanel-navy-200 dark:border-white/10">
                                        {{ item.amount }}
                                    </span>
                                </td>

                                <!-- Price -->
                                <td class="px-4 py-3 whitespace-nowrap text-right">
                                    <span class="font-bold" :style="priceStyle(item.price)">
                                        {{ formatPrice(item.price) }}
                                    </span>
                                    <span class="font-semibold text-rapanel-text-light dark:text-rapanel-text-dark ml-1">Z</span>
                                </td>

                                <!-- Shop + seller + mapa -->
                                <td class="px-4 py-3">
                                    <p class="text-sm font-semibold text-rapanel-navy-900 dark:text-white leading-tight">{{ item.shop_title }}</p>
                                    <p class="text-xs text-rapanel-text-light/50 dark:text-white/35 mt-0.5 mb-1.5">{{ item.char_name }}</p>
                                    <button @click="copyNavigation(item, i)"
                                            :title="`/navigation ${item.map} ${item.x}/${item.y}`"
                                            class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md text-[10px] font-semibold border transition-all duration-150 cursor-pointer select-none"
                                            :class="copiedRow === i
                                                ? 'bg-emerald-500/10 text-emerald-600 border-emerald-500/25 dark:bg-emerald-500/10 dark:text-emerald-400 dark:border-emerald-500/25'
                                                : 'bg-rapanel-blue/8 text-rapanel-blue border-rapanel-blue/25 hover:bg-rapanel-blue/15 hover:border-rapanel-blue/40 dark:bg-rapanel-gold/8 dark:text-rapanel-gold dark:border-rapanel-gold/25 dark:hover:bg-rapanel-gold/15 dark:hover:border-rapanel-gold/40'">
                                        <svg v-if="copiedRow !== i" class="w-2.5 h-2.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/>
                                        </svg>
                                        <svg v-else class="w-2.5 h-2.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                                        </svg>
                                        <template v-if="copiedRow !== i">
                                            <span>{{ item.map }}</span>
                                            <span class="font-mono opacity-70">{{ item.x }} {{ item.y }}</span>
                                        </template>
                                        <template v-else>
                                            <span>{{ __('Location Copied!') }}</span>
                                        </template>
                                    </button>
                                </td>

                                <!-- Ver tienda -->
                                <td class="px-4 py-3 text-center">
                                    <button @click="openShopModal(item.vending_id)"
                                            class="inline-flex items-center gap-1.5 px-3 py-1 rounded-md text-[11px] font-semibold border transition-all duration-150 select-none cursor-pointer
                                                   bg-rapanel-blue/8 text-rapanel-blue border-rapanel-blue/25 hover:bg-rapanel-blue/15 hover:border-rapanel-blue/40
                                                   dark:bg-rapanel-gold/8 dark:text-rapanel-gold dark:border-rapanel-gold/25 dark:hover:bg-rapanel-gold/15 dark:hover:border-rapanel-gold/40">
                                        <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.65V9.35m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 002.25 1.016c.896 0 1.7-.393 2.25-1.016a3.001 3.001 0 003.75.614m-16.5 0a3.004 3.004 0 01-.621-4.72L4.318 3.44A1.5 1.5 0 015.378 3h13.243a1.5 1.5 0 011.06.44l1.19 2.189a3.004 3.004 0 01-.621 4.72m-13.5 8.65h3.75a.75.75 0 00.75-.75V13.5a.75.75 0 00-.75-.75H6.75a.75.75 0 00-.75.75v3.75c0 .415.336.75.75.75z"/>
                                        </svg>
                                        {{ __('View Shop') }}
                                    </button>
                                </td>

                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Empty state -->
            <div v-else class="flex flex-col items-center justify-center py-24 text-center">
                <div class="w-16 h-16 rounded-2xl bg-rapanel-navy-50 dark:bg-white/5 flex items-center justify-center mb-4 border border-rapanel-navy-100 dark:border-white/10">
                    <svg class="w-8 h-8 text-rapanel-text-light/30 dark:text-white/20" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.65V9.35m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 002.25 1.016c.896 0 1.7-.393 2.25-1.016a3.001 3.001 0 003.75.614m-16.5 0a3.004 3.004 0 01-.621-4.72L4.318 3.44A1.5 1.5 0 015.378 3h13.243a1.5 1.5 0 011.06.44l1.19 2.189a3.004 3.004 0 01-.621 4.72m-13.5 8.65h3.75a.75.75 0 00.75-.75V13.5a.75.75 0 00-.75-.75H6.75a.75.75 0 00-.75.75v3.75c0 .415.336.75.75.75z"/>
                    </svg>
                </div>
                <p class="text-sm font-semibold text-rapanel-text-light dark:text-white mb-1">
                    {{ search ? __('No items found for your search.') : __('No active shops at this time.') }}
                </p>
                <p class="text-xs text-rapanel-text-light/50 dark:text-white/35 max-w-xs">
                    {{ search ? __('Try a different item name or shop title.') : __('Check back later when players open their shops.') }}
                </p>
                <button v-if="search" @click="search = ''"
                        class="mt-5 px-4 py-1.5 rounded-full text-xs font-bold border border-rapanel-blue/40 text-rapanel-blue hover:bg-rapanel-blue hover:text-white transition-colors duration-200">
                    {{ __('Clear search') }}
                </button>
            </div>

            <!-- Pagination -->
            <div v-if="meta.last_page > 1"
                 class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-6 border-t border-rapanel-navy-100 dark:border-white/[0.06]">
                <span class="text-xs text-rapanel-text-light/50 dark:text-white/35">
                    {{ __('Showing') }} {{ meta.from }}–{{ meta.to }} {{ __('of') }} {{ meta.total }} {{ __('results') }}
                </span>
                <div class="flex items-center gap-1">
                    <Link v-for="link in meta.links" :key="link.label"
                          :href="link.url || '#'"
                          :class="[
                              'inline-flex items-center justify-center min-w-[2rem] h-8 px-2 rounded-lg text-xs font-semibold border transition-all duration-150',
                              link.active
                                  ? 'bg-rapanel-blue text-white border-rapanel-blue'
                                  : 'border-rapanel-navy-100 dark:border-white/10 text-rapanel-text-light/60 dark:text-white/40 hover:border-rapanel-blue/40 hover:text-rapanel-blue',
                              !link.url && 'opacity-30 pointer-events-none',
                          ]"
                          v-html="link.label" />
                </div>
            </div>

        </div>
        <!-- ── Modal tienda ── -->
        <WhoSellShopModal ref="shopModal" />


    </MainLayout>
</template>

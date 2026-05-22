<script setup>
import { ref, computed, watch } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import ItemDbModal from '@/Components/ItemDbModal.vue';
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

const itemImg = (nameid) => `/data/items/icons/${nameid}.png`;

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
const modal        = ref(false);
const modalLoading = ref(false);
const modalShop    = ref(null);
const modalItems   = ref([]);
const modalCopied  = ref(false);
let modalCopyTimer = null;

const openShopModal = async (vendingId) => {
    modal.value        = true;
    modalLoading.value = true;
    modalShop.value    = null;
    modalItems.value   = [];
    document.body.style.overflow = 'hidden';

    try {
        const res  = await fetch(route('info.who-sell.shop', { vendingId }), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        const data = await res.json();
        modalShop.value  = data.shop;
        modalItems.value = data.items;
    } finally {
        modalLoading.value = false;
    }
};

const closeModal = () => {
    modal.value = false;
    document.body.style.overflow = '';
};

const copyModalNavigation = () => {
    if (!modalShop.value) return;
    const cmd = `/navigation ${modalShop.value.map} ${modalShop.value.x}/${modalShop.value.y}`;
    navigator.clipboard.writeText(cmd).then(() => {
        clearTimeout(modalCopyTimer);
        modalCopied.value = true;
        modalCopyTimer = setTimeout(() => { modalCopied.value = false; }, 1500);
    });
};

const modalSearch  = ref('');
const modalSortKey = ref('price-asc');

const modalFilteredItems = computed(() => {
    let list = [...modalItems.value];
    if (modalSearch.value) {
        const q = modalSearch.value.toLowerCase();
        list = list.filter(i => i.item_name.toLowerCase().includes(q));
    }
    const [field, dir] = modalSortKey.value.split('-');
    list.sort((a, b) => dir === 'asc' ? a[field] - b[field] : b[field] - a[field]);
    return list;
});

const modalTotalQty  = computed(() => modalItems.value.reduce((s, i) => s + i.amount, 0));
const modalMinPrice  = computed(() => modalItems.value.length ? Math.min(...modalItems.value.map(i => i.price)) : 0);
const modalMaxPrice  = computed(() => modalItems.value.length ? Math.max(...modalItems.value.map(i => i.price)) : 0);

const refreshModal = () => {
    if (!modalShop.value) return;
    openShopModal(modalShop.value.id);
};

const copiedModalItem = ref(null);
let modalItemCopyTimer = null;

const copyItemName = (item, idx) => {
    navigator.clipboard.writeText(item.item_name).then(() => {
        clearTimeout(modalItemCopyTimer);
        copiedModalItem.value = idx;
        modalItemCopyTimer = setTimeout(() => { copiedModalItem.value = null; }, 1500);
    });
};

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

const { itemDbItem, itemDbCount, openItemDb, closeItemDb } = useItemDbModal();
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
        <Teleport to="body">
            <Transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0">
                <div v-if="modal" class="fixed inset-0 z-50 flex items-center justify-center p-4">

                    <!-- Overlay -->
                    <div class="absolute inset-0 bg-black/50 dark:bg-black/70 backdrop-blur-sm" @click="closeModal" />

                    <!-- Panel con zoom -->
                    <Transition
                        enter-active-class="transition duration-200 ease-out"
                        enter-from-class="opacity-0 scale-95"
                        enter-to-class="opacity-100 scale-100"
                        leave-active-class="transition duration-150 ease-in"
                        leave-from-class="opacity-100 scale-100"
                        leave-to-class="opacity-0 scale-95"
                        appear>
                        <div v-if="modal"
                             class="relative z-10 w-full max-w-6xl max-h-[88vh] flex flex-col bg-white dark:bg-rapanel-navy-900 rounded-2xl shadow-2xl border border-rapanel-navy-100 dark:border-white/[0.08] overflow-hidden">

                            <!-- ── Header ── -->
                            <div class="px-6 pt-5 pb-4 border-b border-rapanel-navy-100 dark:border-white/[0.07] shrink-0">

                                <!-- Fila 1: ícono + título + badge + acciones -->
                                <div class="flex items-start gap-3">
                                    <!-- Ícono tienda -->
                                    <div class="w-10 h-10 rounded-xl bg-rapanel-navy-100 dark:bg-white/10 flex items-center justify-center shrink-0">
                                        <svg class="w-5 h-5 text-rapanel-text-light dark:text-white/60" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.65V9.35m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 002.25 1.016c.896 0 1.7-.393 2.25-1.016a3.001 3.001 0 003.75.614m-16.5 0a3.004 3.004 0 01-.621-4.72L4.318 3.44A1.5 1.5 0 015.378 3h13.243a1.5 1.5 0 011.06.44l1.19 2.189a3.004 3.004 0 01-.621 4.72m-13.5 8.65h3.75a.75.75 0 00.75-.75V13.5a.75.75 0 00-.75-.75H6.75a.75.75 0 00-.75.75v3.75c0 .415.336.75.75.75z"/>
                                        </svg>
                                    </div>

                                    <div class="flex-1 min-w-0">
                                        <!-- Skeleton cargando -->
                                        <template v-if="modalLoading && !modalShop">
                                            <div class="h-5 w-48 bg-rapanel-navy-100 dark:bg-white/10 rounded animate-pulse mb-1.5" />
                                            <div class="h-3 w-32 bg-rapanel-navy-100 dark:bg-white/10 rounded animate-pulse" />
                                        </template>
                                        <template v-else-if="modalShop">
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <h2 class="text-base font-display font-bold text-rapanel-navy-900 dark:text-white leading-tight">
                                                    {{ modalShop.title }}
                                                </h2>
                                                <!-- Badge ubicación -->
                                                <button @click="copyModalNavigation"
                                                        :title="`/navigation ${modalShop.map} ${modalShop.x}/${modalShop.y}`"
                                                        class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md text-[10px] font-semibold border transition-all duration-150 cursor-pointer select-none"
                                                        :class="modalCopied
                                                            ? 'bg-emerald-500/10 text-emerald-600 border-emerald-500/25 dark:text-emerald-400 dark:border-emerald-500/25'
                                                            : 'bg-rapanel-blue/8 text-rapanel-blue border-rapanel-blue/25 hover:bg-rapanel-blue/15 dark:bg-rapanel-gold/8 dark:text-rapanel-gold dark:border-rapanel-gold/25 dark:hover:bg-rapanel-gold/15'">
                                                    <svg v-if="!modalCopied" class="w-2.5 h-2.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/>
                                                    </svg>
                                                    <svg v-else class="w-2.5 h-2.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                                                    </svg>
                                                    <template v-if="!modalCopied">
                                                        <span>{{ modalShop.map }}</span>
                                                        <span class="font-mono opacity-70">{{ modalShop.x }} {{ modalShop.y }}</span>
                                                    </template>
                                                    <template v-else>{{ __('Location Copied!') }}</template>
                                                </button>
                                            </div>
                                            <p class="text-xs text-rapanel-text-light/50 dark:text-white/40 mt-0.5">
                                                {{ __('Seller') }}: <span class="font-semibold text-rapanel-text-light dark:text-white/70">{{ modalShop.char_name }}</span>
                                            </p>
                                        </template>
                                    </div>

                                    <!-- Refresh + Cerrar -->
                                    <div class="flex items-center gap-1.5 shrink-0">
                                        <button @click="refreshModal"
                                                class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs font-semibold border transition-colors
                                                       bg-rapanel-navy-50 dark:bg-white/5 text-rapanel-text-light dark:text-white/60 border-rapanel-navy-200 dark:border-white/10
                                                       hover:bg-rapanel-navy-100 dark:hover:bg-white/10">
                                            <svg class="w-3 h-3" :class="modalLoading ? 'animate-spin' : ''" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/>
                                            </svg>
                                            {{ __('Refresh') }}
                                        </button>
                                        <button @click="closeModal"
                                                class="w-8 h-8 flex items-center justify-center rounded-lg text-rapanel-text-light/40 dark:text-white/30 hover:bg-rapanel-navy-100 dark:hover:bg-white/10 hover:text-rapanel-text-light dark:hover:text-white transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                <!-- Chips de estadísticas -->
                                <div v-if="!modalLoading && modalShop" class="flex flex-wrap gap-2 mt-3">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-semibold bg-rapanel-navy-50 dark:bg-white/5 text-rapanel-text-light dark:text-white/50 border border-rapanel-navy-100 dark:border-white/[0.07]">
                                        {{ __('Unique items') }}: <strong>{{ modalItems.length }}</strong>
                                    </span>
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-semibold bg-rapanel-navy-50 dark:bg-white/5 text-rapanel-text-light dark:text-white/50 border border-rapanel-navy-100 dark:border-white/[0.07]">
                                        {{ __('Total quantity') }}: <strong>{{ modalTotalQty.toLocaleString() }}</strong>
                                    </span>
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-semibold bg-rapanel-navy-50 dark:bg-white/5 text-rapanel-text-light dark:text-white/50 border border-rapanel-navy-100 dark:border-white/[0.07]">
                                        {{ __('Price range') }}: <strong :style="priceStyle(modalMinPrice)">{{ formatPrice(modalMinPrice) }}</strong>
                                        <span class="opacity-40 mx-0.5">–</span>
                                        <strong :style="priceStyle(modalMaxPrice)">{{ formatPrice(modalMaxPrice) }}</strong>
                                    </span>
                                </div>

                                <!-- Buscador interno + sort -->
                                <div v-if="!modalLoading" class="flex gap-2 mt-3">
                                    <div class="relative flex-1">
                                        <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-rapanel-text-light/30 dark:text-white/25 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                                        </svg>
                                        <input v-model="modalSearch" type="text"
                                               :placeholder="__('Search item in this shop...')"
                                               class="w-full pl-8 pr-3 py-1.5 rounded-lg border text-xs bg-white dark:bg-rapanel-navy-800 border-rapanel-navy-200 dark:border-white/10 text-rapanel-text-light dark:text-rapanel-text-dark placeholder-rapanel-text-light/30 dark:placeholder-white/20 focus:outline-none focus:ring-2 focus:ring-rapanel-blue/30 focus:border-rapanel-blue/40 transition" />
                                    </div>
                                    <select v-model="modalSortKey"
                                            class="px-2.5 py-1.5 rounded-lg border text-xs bg-white dark:bg-rapanel-navy-800 border-rapanel-navy-200 dark:border-white/10 text-rapanel-text-light dark:text-rapanel-text-dark focus:outline-none focus:ring-2 focus:ring-rapanel-blue/30 transition">
                                        <option value="price-asc">{{ __('Sort by: Price') }} ↑</option>
                                        <option value="price-desc">{{ __('Sort by: Price') }} ↓</option>
                                        <option value="amount-asc">{{ __('Sort by: Qty') }} ↑</option>
                                        <option value="amount-desc">{{ __('Sort by: Qty') }} ↓</option>
                                    </select>
                                </div>
                            </div>

                            <!-- ── Contenido scrollable ── -->
                            <div class="overflow-y-auto flex-1 p-4">

                                <!-- Loading skeleton -->
                                <div v-if="modalLoading" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
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

                                <!-- Grid de cards -->
                                <div v-else-if="modalFilteredItems.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                                    <div v-for="(it, idx) in modalFilteredItems" :key="idx"
                                         class="flex flex-col rounded-xl border border-rapanel-navy-100 dark:border-white/[0.07] bg-rapanel-navy-50/40 dark:bg-white/[0.02] hover:bg-rapanel-navy-50 dark:hover:bg-white/[0.04] transition-colors p-3.5">

                                        <!-- Ícono + nombre -->
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
                                                <!-- Badges forged -->
                                                <div v-if="it.forged" class="flex flex-wrap gap-1 mt-1.5">
                                                    <span v-if="it.forged.stars_label" class="inline-flex items-center px-1.5 py-0.5 rounded-md text-[10px] font-semibold bg-rapanel-gold/10 text-rapanel-gold border border-rapanel-gold/25">{{ it.forged.stars_label }}</span>
                                                    <span v-if="it.forged.element" :class="['inline-flex items-center px-1.5 py-0.5 rounded-md text-[10px] font-semibold border', elementStyle(it.forged.element)]">{{ it.forged.element }}</span>
                                                    <span v-if="it.forged.creator_name" class="inline-flex items-center px-1.5 py-0.5 rounded-md text-[10px] font-semibold bg-rapanel-navy-100 dark:bg-white/10 text-rapanel-text-light dark:text-white/60 border border-rapanel-navy-200 dark:border-white/10">{{ it.forged.creator_name }}</span>
                                                </div>
                                                <!-- Badges cards -->
                                                <div v-else-if="it.cards && it.cards.length" class="flex flex-wrap gap-1 mt-1.5">
                                                    <span v-for="card in it.cards" :key="card.id" class="inline-flex items-center px-1.5 py-0.5 rounded-md text-[10px] font-semibold bg-rapanel-gold/10 text-rapanel-gold border border-rapanel-gold/25">{{ card.name }}</span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Footer: copiar nombre + precio -->
                                        <div class="mt-auto pt-3 flex items-center justify-between gap-2">
                                            <button @click="copyItemName(it, idx)"
                                                    class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md text-[10px] font-semibold border transition-all duration-150 cursor-pointer select-none shrink-0"
                                                    :class="copiedModalItem === idx
                                                        ? 'bg-emerald-500/10 text-emerald-600 border-emerald-500/25 dark:text-emerald-400 dark:border-emerald-500/25'
                                                        : 'bg-rapanel-blue/8 text-rapanel-blue border-rapanel-blue/25 hover:bg-rapanel-blue/15 dark:bg-rapanel-gold/8 dark:text-rapanel-gold dark:border-rapanel-gold/25 dark:hover:bg-rapanel-gold/15'">
                                                <svg v-if="copiedModalItem !== idx" class="w-2.5 h-2.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.888A2.25 2.25 0 0013.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 01-.75.75H9a.75.75 0 01-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 01-2.25 2.25H6.75A2.25 2.25 0 014.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 011.927-.184"/>
                                                </svg>
                                                <svg v-else class="w-2.5 h-2.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                                                </svg>
                                                <span>{{ copiedModalItem === idx ? __('Copied!') : __('Copy name') }}</span>
                                            </button>
                                            <div class="flex items-baseline gap-1">
                                                <span class="font-bold text-sm" :style="priceStyle(it.price)">{{ formatPrice(it.price) }}</span>
                                                <span class="text-sm font-semibold text-rapanel-text-light dark:text-rapanel-text-dark">Z</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Sin resultados de búsqueda -->
                                <div v-else-if="modalSearch" class="flex flex-col items-center justify-center py-16 text-center">
                                    <p class="text-sm text-rapanel-text-light/50 dark:text-white/35">{{ __('No items found for your search.') }}</p>
                                    <button @click="modalSearch = ''" class="mt-3 text-xs text-rapanel-blue hover:underline">{{ __('Clear search') }}</button>
                                </div>

                                <!-- Tienda vacía -->
                                <div v-else class="flex items-center justify-center py-16 text-sm text-rapanel-text-light/50 dark:text-white/35">
                                    {{ __('This shop is empty or no longer active.') }}
                                </div>
                            </div>

                            <!-- ── Footer ── -->
                            <div v-if="!modalLoading && modalFilteredItems.length" class="px-6 py-3 border-t border-rapanel-navy-100 dark:border-white/[0.07] shrink-0">
                                <p class="text-xs text-rapanel-text-light/40 dark:text-white/30">
                                    {{ __('Showing :n results').replace(':n', modalFilteredItems.length) }}
                                </p>
                            </div>

                        </div>
                    </Transition>
                </div>
            </Transition>
        </Teleport>

        <ItemDbModal :item="itemDbItem" :server-count="itemDbCount" @close="closeItemDb" />

    </MainLayout>
</template>

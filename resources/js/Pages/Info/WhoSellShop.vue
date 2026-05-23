<script setup>
import { ref } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import { useItemDbModal } from '@/Composables/useItemDbModal';

const props = defineProps({
    shop:  { type: Object, default: () => ({}) },
    items: { type: Array,  default: () => [] },
});

const page      = usePage();
const __        = (key) => page.props.translations?.[key] || key;
const safeRoute = (name, params = {}) => { try { return route(name, params); } catch { return '#'; } };

const itemImg   = (nameid) => `/data/items/item/${nameid}.png`;
const formatPrice = (n) => Number(n).toLocaleString();

const PRICE_TIERS = [
    { min: 1,          max: 99,         color: '#000080', shadow: '#FF00FF' },
    { min: 100,        max: 999,        color: '#0000FF', shadow: '#00FFFF' },
    { min: 1_000,      max: 9_999,      color: '#FF0000', shadow: '#FFFF00' },
    { min: 10_000,     max: 99_999,     color: '#FF00FF', shadow: null      },
    { min: 100_000,    max: 999_999,    color: '#0000FF', shadow: null      },
    { min: 1_000_000,  max: 9_999_999,  color: '#000000', shadow: '#00FF00' },
    { min: 10_000_000, max: 99_999_999, color: '#FF0000', shadow: null      },
    { min: 100_000_000,max: 999_999_999,color: '#000000', shadow: '#BDB76B' },
    { min: 1_000_000_000, max: Infinity,color: '#FF0000', shadow: '#FF00FF' },
];

const priceStyle = (n) => {
    const tier = PRICE_TIERS.find(t => n >= t.min && n <= t.max);
    if (!tier) return {};
    return { color: tier.color, textShadow: tier.shadow ? `1px 0 0 ${tier.shadow}` : undefined };
};

const elementStyle = (el) => {
    if (el === 'Water') return 'bg-rapanel-blue/10 text-rapanel-blue border-rapanel-blue/30 dark:bg-rapanel-blue/15';
    if (el === 'Earth') return 'bg-emerald-500/10 text-emerald-600 border-emerald-500/30 dark:text-emerald-400';
    if (el === 'Fire')  return 'bg-rapanel-danger/10 text-rapanel-danger border-rapanel-danger/30';
    if (el === 'Wind')  return 'bg-cyan-500/10 text-cyan-600 border-cyan-500/30 dark:text-cyan-400';
    return '';
};

const copiedRow = ref(null);
let copyTimer   = null;

const { openItemDb } = useItemDbModal();

const copyNavigation = () => {
    const cmd = `/navigation ${props.shop.map} ${props.shop.x}/${props.shop.y}`;
    navigator.clipboard.writeText(cmd).then(() => {
        clearTimeout(copyTimer);
        copiedRow.value = true;
        copyTimer = setTimeout(() => { copiedRow.value = null; }, 1500);
    });
};
</script>

<template>
    <Head :title="shop.title" />

    <MainLayout :show-bg="true">

        <!-- ── Header ── -->
        <div class="bg-white dark:bg-rapanel-navy-900 border-b border-rapanel-navy-100 dark:border-white/10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

                <nav class="flex items-center gap-1.5 text-xs text-rapanel-text-light/40 dark:text-white/30 mb-2">
                    <Link :href="safeRoute('home')" class="hover:text-rapanel-blue transition-colors">{{ __('Home') }}</Link>
                    <span>›</span>
                    <Link :href="safeRoute('info.who-sell')" class="hover:text-rapanel-blue transition-colors">{{ __('Who Sell') }}</Link>
                    <span>›</span>
                    <span>{{ shop.title }}</span>
                </nav>

                <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
                    <div>
                        <h1 class="text-3xl font-display font-bold text-rapanel-navy-900 dark:text-white tracking-wide">
                            {{ shop.title }}
                        </h1>
                        <p class="mt-1 text-sm text-rapanel-text-light dark:text-rapanel-text-dark">
                            {{ __('Seller') }}: <span class="font-semibold">{{ shop.char_name }}</span>
                            <span class="mx-2 opacity-30">·</span>
                            {{ items.length }} {{ __('items in this shop') }}
                        </p>
                    </div>

                    <!-- Botón mapa -->
                    <button @click="copyNavigation()"
                            :title="`/navigation ${shop.map} ${shop.x}/${shop.y}`"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md text-[11px] font-semibold border transition-all duration-150 cursor-pointer select-none self-start sm:self-auto"
                            :class="copiedRow
                                ? 'bg-emerald-500/10 text-emerald-600 border-emerald-500/25 dark:bg-emerald-500/10 dark:text-emerald-400 dark:border-emerald-500/25'
                                : 'bg-rapanel-blue/8 text-rapanel-blue border-rapanel-blue/25 hover:bg-rapanel-blue/15 hover:border-rapanel-blue/40 dark:bg-rapanel-gold/8 dark:text-rapanel-gold dark:border-rapanel-gold/25 dark:hover:bg-rapanel-gold/15 dark:hover:border-rapanel-gold/40'">
                        <svg v-if="!copiedRow" class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/>
                        </svg>
                        <svg v-else class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                        </svg>
                        <template v-if="!copiedRow">
                            <span>{{ shop.map }}</span>
                            <span class="font-mono opacity-70">{{ shop.x }} {{ shop.y }}</span>
                        </template>
                        <template v-else>
                            <span>{{ __('Location Copied!') }}</span>
                        </template>
                    </button>
                </div>

            </div>
        </div>

        <!-- ── Content ── -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

            <!-- Tabla -->
            <div v-if="items.length" class="bg-white dark:bg-rapanel-navy-900 rounded-2xl border border-rapanel-navy-100 dark:border-white/[0.07] shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-rapanel-navy-100 dark:bg-rapanel-navy-800 text-left">
                                <th class="px-4 py-3 text-xs font-black uppercase tracking-widest text-rapanel-text-light dark:text-rapanel-text-dark">
                                    {{ __('Item') }}
                                </th>
                                <th class="px-4 py-3 text-xs font-black uppercase tracking-widest text-rapanel-text-light dark:text-rapanel-text-dark text-center">
                                    {{ __('Qty') }}
                                </th>
                                <th class="px-4 py-3 text-xs font-black uppercase tracking-widest text-rapanel-text-light dark:text-rapanel-text-dark text-right">
                                    {{ __('Price') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-rapanel-navy-100 dark:divide-white/[0.05]">
                            <tr v-for="(item, i) in items" :key="i"
                                class="hover:bg-rapanel-navy-50 dark:hover:bg-white/[0.03] transition-colors duration-150">

                                <!-- Item -->
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3 cursor-pointer"
                                         @click="openItemDb(item.nameid, item)">
                                        <img :src="itemImg(item.nameid)" :alt="item.item_name"
                                             class="w-9 h-9 object-contain shrink-0"
                                             @error="$event.target.style.display='none'" />
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-1.5 flex-wrap">
                                                <span v-if="item.refine > 0" class="text-xs font-black text-rapanel-blue shrink-0">+{{ item.refine }}</span>
                                                <span class="font-semibold text-rapanel-navy-900 dark:text-white text-sm">{{ item.item_name }}</span>
                                                <span v-if="item.slots > 0" class="text-xs font-semibold text-rapanel-text-light/50 dark:text-white/35 shrink-0">[{{ item.slots }}]</span>
                                            </div>
                                            <!-- Forged badges -->
                                            <div v-if="item.forged" class="flex flex-wrap gap-1 mt-1">
                                                <span v-if="item.forged.stars_label" class="inline-flex items-center px-1.5 py-0.5 rounded-md text-[10px] font-semibold bg-rapanel-gold/10 text-rapanel-gold border border-rapanel-gold/25 dark:bg-rapanel-gold/15 dark:border-rapanel-gold/30">
                                                    {{ item.forged.stars_label }}
                                                </span>
                                                <span v-if="item.forged.element" :class="['inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md text-[10px] font-semibold border', elementStyle(item.forged.element)]">
                                                    {{ item.forged.element }}
                                                </span>
                                                <span v-if="item.forged.creator_name" class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md text-[10px] font-semibold bg-rapanel-navy-100 dark:bg-white/10 text-rapanel-text-light dark:text-white/60 border border-rapanel-navy-200 dark:border-white/15">
                                                    {{ item.forged.creator_name }}
                                                </span>
                                            </div>
                                            <!-- Card badges -->
                                            <div v-else-if="item.cards.length" class="flex flex-wrap gap-1 mt-1">
                                                <span v-for="card in item.cards" :key="card.id" class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md text-[10px] font-semibold bg-rapanel-gold/10 text-rapanel-gold border border-rapanel-gold/25 dark:bg-rapanel-gold/15 dark:border-rapanel-gold/30">
                                                    {{ card.name }}
                                                </span>
                                            </div>
                                            <div class="mt-0.5">
                                                <span class="text-[10px] text-rapanel-text-light/40 dark:text-white/25">ID: {{ item.nameid }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Qty -->
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex items-center justify-center min-w-[2rem] px-2 py-0.5 rounded-md text-xs font-bold bg-rapanel-navy-50 dark:bg-white/5 text-rapanel-text-light dark:text-rapanel-text-dark border border-rapanel-navy-200 dark:border-white/10">
                                        {{ item.amount }}
                                    </span>
                                </td>

                                <!-- Price -->
                                <td class="px-4 py-3 whitespace-nowrap text-right">
                                    <span class="font-bold" :style="priceStyle(item.price)">{{ formatPrice(item.price) }}</span>
                                    <span class="text-[10px] font-semibold text-rapanel-text-light dark:text-rapanel-text-dark ml-1">Z</span>
                                </td>

                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Empty -->
            <div v-else class="flex flex-col items-center justify-center py-24 text-center">
                <p class="text-sm font-semibold text-rapanel-text-light dark:text-white">
                    {{ __('This shop is empty or no longer active.') }}
                </p>
            </div>

            <!-- Volver -->
            <div class="mt-6">
                <Link :href="safeRoute('info.who-sell')"
                      class="inline-flex items-center gap-2 text-sm text-rapanel-text-light/50 dark:text-white/35 hover:text-rapanel-blue dark:hover:text-rapanel-blue transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                    </svg>
                    {{ __('Back to Who Sell') }}
                </Link>
            </div>

        </div>


    </MainLayout>
</template>

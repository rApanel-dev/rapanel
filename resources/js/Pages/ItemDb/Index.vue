<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import { useItemDbModal } from '@/Composables/useItemDbModal.js';

const props = defineProps({
    items:   Object,
    types:   Array,
    filters: Object,
});

const page = usePage();
const __   = (key) => page.props.translations?.[key] || key;

// ── Filters (debounced search) ───────────────────────────────────────
const search = ref(props.filters.search ?? '');
const type   = ref(props.filters.type ?? '');

let debounceTimer = null;

const applyFilters = () => {
    router.get(route('info.item-db'), {
        search: search.value || undefined,
        type:   type.value   || undefined,
    }, {
        preserveState: true,
        replace: true,
    });
};

watch(search, () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(applyFilters, 350);
});

watch([type], applyFilters);

// ── Detail modal ─────────────────────────────────────────────────────
const { openItemDb } = useItemDbModal();

// ── Icon / image paths ───────────────────────────────────────────────
const iconSrc     = (item) => `/data/items/item/${item.item_id}.png`;
const onIconError = (e)    => { e.target.style.display = 'none'; };

// ── Type badge ───────────────────────────────────────────────────────
const typeBadge = (t) => {
    const map = {
        Weapon:     'bg-rapanel-danger/10 text-rapanel-danger border-rapanel-danger/20',
        Armor:      'bg-rapanel-blue/10 text-rapanel-blue border-rapanel-blue/20',
        Consumable: 'bg-rapanel-success/10 text-rapanel-success border-rapanel-success/20',
        Card:       'bg-purple-500/10 text-purple-500 border-purple-500/20',
        Ammo:       'bg-amber-500/10 text-amber-500 border-amber-500/20',
        Healing:    'bg-rapanel-success/10 text-rapanel-success border-rapanel-success/20',
    };
    return map[t] ?? 'bg-rapanel-navy-100 text-rapanel-text-light border-transparent dark:bg-white/5 dark:text-rapanel-text-dark';
};


</script>

<template>
    <Head :title="__('Item DB')" />

    <MainLayout :show-bg="true">

        <!-- ── Hero / Filters ── -->
        <div class="bg-white dark:bg-rapanel-navy-900 border-b border-rapanel-navy-100 dark:border-white/10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <h1 class="text-3xl font-display font-bold text-rapanel-navy-900 dark:text-white tracking-wide">
                    {{ __('Item DB') }}
                </h1>
                <p class="mt-1.5 mb-6 text-sm text-rapanel-text-light dark:text-rapanel-text-dark">
                    {{ __('Search and explore all items available on the server.') }}
                </p>

                <div class="flex flex-wrap gap-3">
                    <div class="relative flex-1 min-w-[200px]">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-rapanel-text-light dark:text-rapanel-text-dark pointer-events-none"
                            fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M11 19a8 8 0 100-16 8 8 0 000 16z" />
                        </svg>
                        <input
                            v-model="search"
                            type="text"
                            :placeholder="`${__('Search')}…`"
                            class="w-full pl-9 pr-8 py-2 rounded-lg border border-rapanel-navy-100 dark:border-white/10 bg-white dark:bg-rapanel-navy-800 text-rapanel-navy-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-rapanel-blue/50 transition" />
                        <button v-if="search"
                            @click="search = ''"
                            class="absolute right-2.5 top-1/2 -translate-y-1/2 w-4 h-4 flex items-center justify-center
                                   text-rapanel-text-light dark:text-rapanel-text-dark hover:text-rapanel-navy-900 dark:hover:text-white transition">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <select v-model="type"
                        class="px-3 py-2 rounded-lg border border-rapanel-navy-100 dark:border-white/10 bg-white dark:bg-rapanel-navy-800 text-rapanel-navy-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-rapanel-blue/50 transition">
                        <option value="">{{ __('All Types') }}</option>
                        <option v-for="t in types" :key="t" :value="t">{{ t }}</option>
                    </select>

                    <div class="flex items-center text-sm text-rapanel-text-light dark:text-rapanel-text-dark">
                        {{ items.total.toLocaleString() }} {{ __('Total items') }}
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Grid ── -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

            <div v-if="items.data.length"
                class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-2.5">

                <button v-for="item in items.data" :key="item.item_id"
                    @click="openItemDb(item.item_id, item)"
                    class="group bg-white dark:bg-rapanel-navy-900 rounded-2xl border border-rapanel-navy-100 dark:border-white/10 shadow-sm flex items-center gap-3 px-3 py-2.5 hover:shadow-md hover:border-rapanel-blue/40 dark:hover:border-rapanel-blue/40 transition-all duration-200 text-left w-full">

                    <!-- Icono -->
                    <div class="shrink-0 rounded-xl bg-rapanel-navy-50 dark:bg-rapanel-navy-800 flex items-center justify-center p-1">
                        <img
                            :src="iconSrc(item)"
                            :alt="item.display_name || item.name"
                            class="object-contain group-hover:scale-110 transition-transform duration-300"
                            @error="onIconError" />
                    </div>

                    <!-- Info -->
                    <div class="flex-1 min-w-0">
                        <!-- Nombre + slots (mismo color, misma línea) -->
                        <p class="text-[12px] font-semibold text-rapanel-navy-900 dark:text-white leading-tight line-clamp-1">
                            {{ item.display_name || item.name }}<template v-if="item.slots > 0"> [{{ item.slots }}]</template>
                        </p>
                        <!-- ID + tipo -->
                        <div class="flex items-center gap-1.5 mt-1 flex-wrap">
                            <span class="text-[10px] text-rapanel-text-light dark:text-rapanel-text-dark tabular-nums">(ID: {{ item.item_id }})</span>
                            <span :class="['text-[9px] font-black uppercase px-1.5 py-0.5 rounded-full border', typeBadge(item.type)]">
                                {{ item.type }}
                            </span>
                        </div>
                    </div>
                </button>
            </div>

            <div v-else class="flex flex-col items-center justify-center py-24 text-center">
                <svg class="w-12 h-12 text-rapanel-navy-100 dark:text-white/10 mb-4" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0H4" />
                </svg>
                <p class="text-rapanel-text-light dark:text-rapanel-text-dark text-sm">{{ __('No items found.') }}</p>
            </div>

            <div v-if="items.last_page > 1" class="flex justify-center mt-8 gap-1.5 flex-wrap">
                <Link
                    v-for="link in items.links"
                    :key="link.label"
                    :href="link.url ?? '#'"
                    :class="[
                        'px-3 py-1.5 rounded-lg text-xs font-semibold border transition-all',
                        link.active
                            ? 'bg-rapanel-btn-blue text-white border-rapanel-btn-blue'
                            : link.url
                                ? 'bg-white dark:bg-rapanel-navy-900 text-rapanel-text-light dark:text-rapanel-text-dark border-rapanel-navy-100 dark:border-white/10 hover:border-rapanel-btn-blue hover:text-rapanel-blue'
                                : 'bg-transparent text-rapanel-text-light/40 dark:text-white/20 border-transparent cursor-default',
                    ]"
                    v-html="link.label"
                    :preserve-scroll="true"
                />
            </div>
        </div>

        <!-- ── Detail Modal ── -->


    </MainLayout>
</template>

<style scoped>
:deep(span[style*="color"]) {
    display: inline;
}
</style>

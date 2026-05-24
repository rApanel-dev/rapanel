<script setup>
import { ref, computed, watch } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';

const props = defineProps({
    maps:    Object,
    filters: Object,
});

const page = usePage();
const __   = (key) => page.props.translations?.[key] || key;

const search = ref(props.filters.search ?? '');
let debounceTimer = null;

watch(search, () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        router.get(route('info.map-db'), {
            search: search.value || undefined,
        }, { preserveState: true, replace: true });
    }, 350);
});

// ── Image fallback ────────────────────────────────────────────────────
const failedImages = ref({});
const onImgError = (mapName, e) => {
    failedImages.value = { ...failedImages.value, [mapName]: true };
    e.target.style.display = 'none';
};

// ── Hover preview ─────────────────────────────────────────────────────
const hovered    = ref(null);
const tooltipPos = ref({ x: 0, y: 0 });

const onEnter = (map, e) => { hovered.value = map; tooltipPos.value = { x: e.clientX, y: e.clientY }; };
const onLeave = () => { hovered.value = null; };
const onMove  = (e) => { if (hovered.value) tooltipPos.value = { x: e.clientX, y: e.clientY }; };

const tooltipStyle = computed(() => {
    const { x, y } = tooltipPos.value;
    const w = 240;
    const toLeft = x + w + 20 > (window.innerWidth ?? 1200);
    return toLeft
        ? { left: `${x - w - 14}px`, top: `${y - 90}px` }
        : { left: `${x + 14}px`,     top: `${y - 90}px` };
});
</script>

<template>
    <Head :title="__('Map DB')" />
    <MainLayout :show-bg="true">

        <!-- ── Hero / Filters ── -->
        <div class="bg-white dark:bg-rapanel-navy-900 border-b border-rapanel-navy-100 dark:border-white/10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

                <h1 class="text-3xl font-display font-bold text-rapanel-navy-900 dark:text-white tracking-wide">
                    {{ __('Map DB') }}
                </h1>
                <p class="mt-1.5 mb-6 text-sm text-rapanel-text-light dark:text-rapanel-text-dark">
                    {{ __('Browse maps and their monster spawn locations.') }}
                </p>

                <!-- Search -->
                <div class="flex flex-wrap gap-3">
                    <div class="relative flex-1 min-w-[200px] max-w-sm">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-rapanel-text-light dark:text-rapanel-text-dark pointer-events-none"
                            fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M11 19a8 8 0 100-16 8 8 0 000 16z" />
                        </svg>
                        <input v-model="search" type="text"
                            :placeholder="__('Search map name...')"
                            class="w-full pl-9 pr-8 py-2 rounded-lg border border-rapanel-navy-100 dark:border-white/10 bg-white dark:bg-rapanel-navy-800 text-rapanel-navy-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-rapanel-blue/50 transition" />
                        <button v-if="search" @click="search = ''"
                            class="absolute right-2.5 top-1/2 -translate-y-1/2 w-4 h-4 flex items-center justify-center text-rapanel-text-light dark:text-rapanel-text-dark hover:text-rapanel-navy-900 dark:hover:text-white transition">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <div class="flex items-center text-sm text-rapanel-text-light dark:text-rapanel-text-dark">
                        {{ maps.total.toLocaleString() }} {{ __('maps available') }}
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Grid ── -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

            <div v-if="maps.data.length > 0"
                class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-2.5">

                <Link v-for="map in maps.data" :key="map.map_name"
                    :href="route('info.map-db.show', map.map_name)"
                    class="group bg-white dark:bg-rapanel-navy-900 rounded-2xl border border-rapanel-navy-100 dark:border-white/10 shadow-sm overflow-hidden hover:shadow-md hover:border-rapanel-blue/40 dark:hover:border-rapanel-blue/40 transition-all duration-200"
                    @mouseenter="onEnter(map, $event)"
                    @mouseleave="onLeave"
                    @mousemove="onMove">

                    <!-- Accent bar -->
                    <div class="h-[3px] w-full bg-rapanel-blue/60" />

                    <!-- Map thumbnail -->
                    <div class="aspect-square bg-rapanel-navy-50 dark:bg-white/[0.03] overflow-hidden relative flex items-center justify-center">
                        <img
                            :src="`/data/maps/${map.map_name}.png`"
                            :alt="map.map_name"
                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200"
                            @error="onImgError(map.map_name, $event)" />
                        <div v-if="failedImages[map.map_name]"
                            class="absolute inset-0 flex flex-col items-center justify-center gap-1.5 bg-rapanel-navy-50 dark:bg-white/[0.03]">
                            <svg class="w-8 h-8 text-rapanel-navy-300 dark:text-white/20" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 6.75V15m6-6v8.25m.503 3.498 4.875-2.437c.381-.19.622-.58.622-1.006V4.82c0-.836-.88-1.38-1.628-1.006l-3.869 1.934c-.317.159-.69.159-1.006 0L9.503 3.252a1.125 1.125 0 0 0-1.006 0L3.622 5.689C3.24 5.88 3 6.27 3 6.695V19.18c0 .836.88 1.38 1.628 1.006l3.869-1.934c.317-.159.69-.159 1.006 0l4.994 2.497c.317.158.69.158 1.006 0Z" />
                            </svg>
                            <span class="text-[9px] font-mono text-rapanel-navy-300 dark:text-white/20">{{ map.map_name }}</span>
                        </div>
                        <!-- Spawn count badge overlay -->
                        <div v-if="map.spawns_count"
                            class="absolute bottom-1.5 right-1.5 px-1.5 py-0.5 rounded-full bg-rapanel-navy-900/70 backdrop-blur-sm text-[9px] font-bold text-white tabular-nums">
                            {{ map.spawns_count }} spawns
                        </div>
                    </div>

                    <!-- Info -->
                    <div class="px-3 py-2.5">
                        <p class="text-[11px] font-semibold text-rapanel-navy-900 dark:text-white truncate leading-tight">{{ map.map_name }}</p>
                        <p class="text-[10px] text-rapanel-text-light dark:text-rapanel-text-dark mt-0.5 tabular-nums">
                            {{ map.width }}×{{ map.height }}
                        </p>
                    </div>
                </Link>
            </div>

            <!-- Empty state -->
            <div v-else class="flex flex-col items-center justify-center py-24 text-center">
                <svg class="w-12 h-12 text-rapanel-navy-100 dark:text-white/10 mb-4" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 6.75V15m6-6v8.25m.503 3.498 4.875-2.437c.381-.19.622-.58.622-1.006V4.82c0-.836-.88-1.38-1.628-1.006l-3.869 1.934c-.317.159-.69.159-1.006 0L9.503 3.252a1.125 1.125 0 0 0-1.006 0L3.622 5.689C3.24 5.88 3 6.27 3 6.695V19.18c0 .836.88 1.38 1.628 1.006l3.869-1.934c.317-.159.69-.159 1.006 0l4.994 2.497c.317.158.69.158 1.006 0Z" />
                </svg>
                <p class="text-rapanel-text-light dark:text-rapanel-text-dark text-sm">
                    {{ search ? __('No maps match your search.') : __('No maps available. Import map_cache.dat and spawn files from the admin panel.') }}
                </p>
            </div>

            <!-- Pagination -->
            <div v-if="maps.last_page > 1" class="flex justify-center mt-8 gap-1.5 flex-wrap">
                <Link v-for="link in maps.links" :key="link.label"
                    :href="link.url ?? '#'"
                    :class="[
                        'px-3 py-1.5 rounded-lg text-xs font-semibold border transition-all',
                        link.active
                            ? 'bg-rapanel-blue text-white border-rapanel-blue'
                            : link.url
                                ? 'bg-white dark:bg-rapanel-navy-900 text-rapanel-text-light dark:text-rapanel-text-dark border-rapanel-navy-100 dark:border-white/10 hover:border-rapanel-blue hover:text-rapanel-blue'
                                : 'bg-transparent text-rapanel-text-light/40 dark:text-white/20 border-transparent cursor-default',
                    ]"
                    v-html="link.label"
                    :preserve-scroll="true" />
            </div>
        </div>

        <!-- ── Hover preview tooltip ── -->
        <Teleport to="body">
            <div v-if="hovered && !failedImages[hovered.map_name]"
                class="fixed z-[999] pointer-events-none w-60 rounded-xl overflow-hidden shadow-2xl border border-rapanel-navy-100 dark:border-white/[0.12] bg-white dark:bg-rapanel-navy-900"
                :style="tooltipStyle">
                <img :src="`/data/maps/${hovered.map_name}.png`"
                    class="w-full block"
                    @error="onImgError(hovered.map_name, $event)" />
                <div class="px-3 py-2 border-t border-rapanel-navy-100 dark:border-white/[0.07]">
                    <p class="font-mono text-xs font-bold text-rapanel-navy-900 dark:text-white">{{ hovered.map_name }}</p>
                    <p class="text-[10px] text-rapanel-text-light dark:text-rapanel-text-dark mt-0.5 tabular-nums">
                        {{ hovered.width }}×{{ hovered.height }}
                        <span v-if="hovered.spawns_count" class="ml-2">· {{ hovered.spawns_count }} spawns</span>
                    </p>
                </div>
            </div>
        </Teleport>

    </MainLayout>
</template>

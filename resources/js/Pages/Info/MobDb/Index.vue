<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import ItemDbModal from '@/Components/ItemDbModal.vue';
import { useItemDbModal } from '@/Composables/useItemDbModal.js';

const props = defineProps({
    mobs:     Object,
    races:    Array,
    elements: Array,
    filters:  Object,
});

const page = usePage();
const __   = (key) => page.props.translations?.[key] || key;

// ── Filters ───────────────────────────────────────────────────────────
const search  = ref(props.filters.search  ?? '');
const filter  = ref(props.filters.filter  ?? '');
const race    = ref(props.filters.race    ?? '');
const element = ref(props.filters.element ?? '');

let debounceTimer = null;

const applyFilters = () => {
    router.get(route('info.mob-db'), {
        search:  search.value  || undefined,
        filter:  filter.value  || undefined,
        race:    race.value    || undefined,
        element: element.value || undefined,
    }, { preserveState: true, replace: true });
};

watch(search, () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(applyFilters, 350);
});
watch([filter, race, element], applyFilters);

// ── Badge helpers ─────────────────────────────────────────────────────
const classBg = (mob) => {
    if (mob.is_mvp)           return 'bg-rapanel-gold';
    if (mob.class === 'Boss') return 'bg-orange-500';
    return 'bg-rapanel-navy-300 dark:bg-rapanel-navy-600';
};

const classBadgeStyle = (mob) => {
    if (mob.is_mvp)           return 'bg-rapanel-gold/15 text-rapanel-gold border-rapanel-gold/30';
    if (mob.class === 'Boss') return 'bg-orange-500/15 text-orange-400 border-orange-500/30';
    return null;
};

const elementBadge = (el) => {
    const map = {
        Neutral: 'bg-gray-100 text-gray-500 border-gray-200 dark:bg-white/5 dark:text-gray-400 dark:border-white/10',
        Water:   'bg-blue-50 text-blue-600 border-blue-200 dark:bg-blue-400/10 dark:text-blue-400 dark:border-blue-400/20',
        Earth:   'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-700/10 dark:text-amber-500 dark:border-amber-700/20',
        Fire:    'bg-red-50 text-red-600 border-red-200 dark:bg-red-500/10 dark:text-red-400 dark:border-red-500/20',
        Wind:    'bg-emerald-50 text-emerald-600 border-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-400 dark:border-emerald-500/20',
        Poison:  'bg-purple-50 text-purple-600 border-purple-200 dark:bg-purple-500/10 dark:text-purple-400 dark:border-purple-500/20',
        Holy:    'bg-yellow-50 text-yellow-600 border-yellow-200 dark:bg-yellow-400/10 dark:text-yellow-300 dark:border-yellow-400/20',
        Dark:    'bg-violet-50 text-violet-700 border-violet-200 dark:bg-violet-900/20 dark:text-violet-400 dark:border-violet-900/30',
        Ghost:   'bg-slate-100 text-slate-500 border-slate-200 dark:bg-slate-400/10 dark:text-slate-400 dark:border-slate-400/20',
        Undead:  'bg-zinc-100 text-zinc-600 border-zinc-200 dark:bg-zinc-700/20 dark:text-zinc-400 dark:border-zinc-700/30',
    };
    return map[el] ?? 'bg-gray-100 text-gray-500 border-gray-200 dark:bg-white/5 dark:text-white/50 dark:border-white/10';
};

const raceBadge = (r) => {
    const map = {
        Formless:  'bg-gray-100 text-gray-500 border-gray-200 dark:bg-white/5 dark:text-white/40 dark:border-white/10',
        Undead:    'bg-zinc-100 text-zinc-600 border-zinc-200 dark:bg-zinc-700/20 dark:text-zinc-400 dark:border-zinc-700/30',
        Brute:     'bg-amber-50 text-amber-600 border-amber-200 dark:bg-amber-600/10 dark:text-amber-500 dark:border-amber-600/20',
        Plant:     'bg-emerald-50 text-emerald-600 border-emerald-200 dark:bg-emerald-600/10 dark:text-emerald-500 dark:border-emerald-600/20',
        Insect:    'bg-lime-50 text-lime-600 border-lime-200 dark:bg-lime-500/10 dark:text-lime-400 dark:border-lime-500/20',
        Fish:      'bg-blue-50 text-blue-500 border-blue-200 dark:bg-blue-400/10 dark:text-blue-400 dark:border-blue-400/20',
        Demon:     'bg-red-50 text-red-600 border-red-200 dark:bg-red-900/20 dark:text-red-400 dark:border-red-900/30',
        DemiHuman: 'bg-sky-50 text-sky-600 border-sky-200 dark:bg-sky-500/10 dark:text-sky-400 dark:border-sky-500/20',
        Angel:     'bg-yellow-50 text-yellow-600 border-yellow-200 dark:bg-yellow-300/10 dark:text-yellow-300 dark:border-yellow-300/20',
        Dragon:    'bg-orange-50 text-orange-600 border-orange-200 dark:bg-orange-700/10 dark:text-orange-500 dark:border-orange-700/20',
    };
    return map[r] ?? 'bg-gray-100 text-gray-500 border-gray-200 dark:bg-white/5 dark:text-white/50 dark:border-white/10';
};

// ── Mob Detail Modal ──────────────────────────────────────────────────
const selectedMob    = ref(null);
const mobDetail      = ref(null);
const detailLoading  = ref(false);

const openMob = async (mob) => {
    selectedMob.value  = mob;
    mobDetail.value    = null;
    detailLoading.value = true;
    try {
        const res     = await fetch(route('info.mob-db.show', mob.id));
        mobDetail.value = await res.json();
    } catch { /* silencioso */ }
    finally { detailLoading.value = false; }
};

const closeMob = () => { selectedMob.value = null; mobDetail.value = null; };

const fmtRate = (r) => {
    if (!r) return '?';
    if (r >= 10000) return '100%';
    const p = r / 100;
    return (Number.isInteger(p) ? p : parseFloat(p.toFixed(2))) + '%';
};

const statLabels = {
    str: 'STR', agi: 'AGI', vit: 'VIT', int: 'INT', dex: 'DEX', luk: 'LUK',
    attack: 'ATK', attack2: 'ATK2', defense: 'DEF', magic_defense: 'MDEF',
    attack_range: 'Atk Range', walk_speed: 'Walk Speed', ai: 'AI',
};

const displayStats = (stats) => {
    if (!stats) return [];
    const keys = ['str','agi','vit','int','dex','luk','attack','attack2','defense','magic_defense','attack_range','walk_speed'];
    return keys.filter(k => stats[k] !== undefined).map(k => ({ label: statLabels[k], value: stats[k] }));
};

// ── ItemDb modal para drops ───────────────────────────────────────────
const { itemDbItem, itemDbCount, openItemDb, closeItemDb } = useItemDbModal();
</script>

<template>
    <Head :title="__('Mob DB')" />

    <MainLayout :show-bg="true">

        <!-- ── Hero / Filters ── -->
        <div class="bg-white dark:bg-rapanel-navy-900 border-b border-rapanel-navy-100 dark:border-white/10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

                <h1 class="text-3xl font-display font-bold text-rapanel-navy-900 dark:text-white tracking-wide">
                    {{ __('Mob DB') }}
                </h1>
                <p class="mt-1.5 mb-6 text-sm text-rapanel-text-light dark:text-rapanel-text-dark">
                    {{ __('Search monsters by name or ID.') }}
                </p>

                <!-- Búsqueda + selects -->
                <div class="flex flex-wrap gap-3 mb-4">
                    <div class="relative flex-1 min-w-[200px]">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-rapanel-text-light dark:text-rapanel-text-dark pointer-events-none"
                            fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M11 19a8 8 0 100-16 8 8 0 000 16z" />
                        </svg>
                        <input v-model="search" type="text" :placeholder="__('Search monsters...')"
                            class="w-full pl-9 pr-8 py-2 rounded-lg border border-rapanel-navy-100 dark:border-white/10 bg-white dark:bg-rapanel-navy-800 text-rapanel-navy-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-rapanel-blue/50 transition" />
                        <button v-if="search" @click="search = ''"
                            class="absolute right-2.5 top-1/2 -translate-y-1/2 w-4 h-4 flex items-center justify-center text-rapanel-text-light dark:text-rapanel-text-dark hover:text-rapanel-navy-900 dark:hover:text-white transition">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <select v-model="race"
                        class="px-3 py-2 rounded-lg border border-rapanel-navy-100 dark:border-white/10 bg-white dark:bg-rapanel-navy-800 text-rapanel-navy-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-rapanel-blue/50 transition">
                        <option value="">{{ __('Race') }}: {{ __('All') }}</option>
                        <option v-for="r in races" :key="r" :value="r">{{ r }}</option>
                    </select>

                    <select v-model="element"
                        class="px-3 py-2 rounded-lg border border-rapanel-navy-100 dark:border-white/10 bg-white dark:bg-rapanel-navy-800 text-rapanel-navy-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-rapanel-blue/50 transition">
                        <option value="">{{ __('Element') }}: {{ __('All') }}</option>
                        <option v-for="el in elements" :key="el" :value="el">{{ el }}</option>
                    </select>

                    <div class="flex items-center text-sm text-rapanel-text-light dark:text-rapanel-text-dark">
                        {{ mobs.total.toLocaleString() }} {{ __('Total mobs') }}
                    </div>
                </div>

                <!-- Filtros de clase (pills) -->
                <div class="flex items-center gap-2 flex-wrap">
                    <button v-for="f in [['', __('All Mobs')], ['mvp', 'MVP'], ['boss', __('Boss')], ['normal', __('Normal')]]"
                        :key="f[0]"
                        @click="filter = f[0]"
                        :class="[
                            'px-3 py-1 rounded-full text-xs font-semibold border transition-all',
                            filter === f[0]
                                ? 'bg-rapanel-navy-900 dark:bg-rapanel-gold text-white dark:text-rapanel-navy-900 border-rapanel-navy-900 dark:border-rapanel-gold'
                                : 'bg-white dark:bg-rapanel-navy-800 text-rapanel-text-light dark:text-rapanel-text-dark border-rapanel-navy-100 dark:border-white/10 hover:border-rapanel-blue hover:text-rapanel-blue dark:hover:border-rapanel-blue dark:hover:text-rapanel-blue'
                        ]">
                        {{ f[1] }}
                    </button>
                </div>
            </div>
        </div>

        <!-- ── Grid ── -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

            <div v-if="mobs.data.length"
                class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-2.5">

                <button v-for="mob in mobs.data" :key="mob.id"
                    @click="openMob(mob)"
                    class="group bg-white dark:bg-rapanel-navy-900 rounded-2xl border border-rapanel-navy-100 dark:border-white/10 shadow-sm overflow-hidden hover:shadow-md hover:border-rapanel-blue/40 dark:hover:border-rapanel-blue/40 transition-all duration-200 text-left w-full">

                    <!-- Barra de acento superior según clase -->
                    <div :class="classBg(mob)" class="h-[3px] w-full" />

                    <div class="p-3">
                        <!-- Nombre + badge MVP/Boss -->
                        <div class="flex items-start justify-between gap-1 mb-1.5">
                            <p class="text-[12px] font-semibold text-rapanel-navy-900 dark:text-white leading-tight line-clamp-2">
                                {{ mob.name }}
                            </p>
                            <span v-if="classBadgeStyle(mob)"
                                :class="['text-[9px] font-black uppercase px-1.5 py-0.5 rounded-full border shrink-0 ml-1', classBadgeStyle(mob)]">
                                {{ mob.is_mvp ? 'MVP' : __('Boss') }}
                            </span>
                        </div>

                        <!-- ID + Level -->
                        <div class="text-[10px] text-rapanel-text-light dark:text-rapanel-text-dark tabular-nums mb-0.5">
                            #{{ mob.id }} · Lv. {{ mob.level }}
                        </div>

                        <!-- HP -->
                        <div class="text-[10px] text-rapanel-text-light dark:text-rapanel-text-dark tabular-nums mb-2">
                            HP: {{ mob.hp.toLocaleString() }}
                        </div>

                        <!-- Badges element + race -->
                        <div class="flex flex-wrap gap-1">
                            <span :class="['text-[9px] font-bold px-1.5 py-0.5 rounded-full border', elementBadge(mob.element)]">
                                {{ mob.element }}
                            </span>
                            <span :class="['text-[9px] font-bold px-1.5 py-0.5 rounded-full border', raceBadge(mob.race)]">
                                {{ mob.race }}
                            </span>
                        </div>
                    </div>
                </button>
            </div>

            <!-- Empty state -->
            <div v-else class="flex flex-col items-center justify-center py-24 text-center">
                <svg class="w-12 h-12 text-rapanel-navy-100 dark:text-white/10 mb-4" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="text-rapanel-text-light dark:text-rapanel-text-dark text-sm">{{ __('No mobs found.') }}</p>
            </div>

            <!-- Pagination -->
            <div v-if="mobs.last_page > 1" class="flex justify-center mt-8 gap-1.5 flex-wrap">
                <Link v-for="link in mobs.links" :key="link.label"
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
                    :preserve-scroll="true"
                />
            </div>
        </div>

        <!-- ── Mob Detail Modal ── -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0 scale-95"
                enter-to-class="opacity-100 scale-100"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100 scale-100"
                leave-to-class="opacity-0 scale-95">

                <div v-if="selectedMob"
                    class="fixed inset-0 z-50 flex items-end sm:items-center justify-center sm:p-4 bg-rapanel-navy-900/80 backdrop-blur-sm"
                    @click.self="closeMob">

                    <div class="relative w-full sm:max-w-3xl max-h-[92vh] sm:max-h-[88vh] flex flex-col overflow-hidden
                                rounded-t-2xl sm:rounded-2xl shadow-2xl bg-rapanel-navy-900 ring-1 ring-white/10">

                        <!-- Barra de acento top -->
                        <div :class="classBg(selectedMob)" class="h-[3px] shrink-0" />

                        <!-- Header -->
                        <div class="flex items-center gap-4 px-5 py-4 shrink-0">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h2 class="font-bold text-white text-xl leading-tight">{{ selectedMob.name }}</h2>
                                    <span v-if="classBadgeStyle(selectedMob)"
                                        :class="['text-[10px] font-black uppercase px-2 py-0.5 rounded-full border', classBadgeStyle(selectedMob)]">
                                        {{ selectedMob.is_mvp ? 'MVP' : __('Boss') }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-3 mt-1 flex-wrap text-[11px] text-white/50">
                                    <span class="font-mono">ID: {{ selectedMob.id }}</span>
                                    <span>Lv. {{ selectedMob.level }}</span>
                                    <span>HP: {{ selectedMob.hp.toLocaleString() }}</span>
                                    <span :class="['px-1.5 py-0.5 rounded-full border text-[10px]', elementBadge(selectedMob.element)]">{{ selectedMob.element }}</span>
                                    <span :class="['px-1.5 py-0.5 rounded-full border text-[10px]', raceBadge(selectedMob.race)]">{{ selectedMob.race }}</span>
                                    <span v-if="selectedMob.size" class="bg-white/5 text-white/50 border-white/10 px-1.5 py-0.5 rounded-full border text-[10px]">{{ selectedMob.size }}</span>
                                </div>
                            </div>

                            <button @click="closeMob"
                                class="shrink-0 w-8 h-8 flex items-center justify-center rounded-lg cursor-pointer text-white/40 hover:text-white hover:bg-white/10 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- Body scrollable -->
                        <div class="overflow-y-auto flex-1 px-5 pb-5 space-y-4 bg-rapanel-navy-800/60">

                            <!-- Loading spinner -->
                            <div v-if="detailLoading" class="flex items-center justify-center py-10">
                                <svg class="animate-spin w-6 h-6 text-rapanel-gold" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                                </svg>
                            </div>

                            <template v-else-if="mobDetail">

                                <!-- Stats -->
                                <div v-if="displayStats(mobDetail.stats).length"
                                    class="bg-rapanel-navy-900 rounded-xl border border-white/10 overflow-hidden">
                                    <div class="flex items-center gap-2 px-4 py-3 border-b border-white/10">
                                        <svg class="w-4 h-4 text-rapanel-gold shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                        </svg>
                                        <span class="font-semibold text-sm text-white">{{ __('Stats') }}</span>
                                    </div>
                                    <div class="grid grid-cols-3 sm:grid-cols-4 divide-x divide-y divide-white/[0.06]">
                                        <div v-for="s in displayStats(mobDetail.stats)" :key="s.label"
                                            class="flex flex-col items-center py-3 px-2">
                                            <span class="text-[10px] font-black uppercase tracking-wider text-white/35">{{ s.label }}</span>
                                            <span class="text-sm font-bold text-white tabular-nums mt-0.5">{{ s.value }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Drops -->
                                <div v-if="mobDetail.drops?.length"
                                    class="bg-rapanel-navy-900 rounded-xl border border-white/10 overflow-hidden">
                                    <div class="flex items-center gap-2 px-4 py-3 border-b border-white/10">
                                        <svg class="w-4 h-4 text-rapanel-gold shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                        </svg>
                                        <span class="font-semibold text-sm text-white">{{ __('Drops') }}</span>
                                        <span class="text-[10px] bg-white/10 text-white/50 rounded-full px-1.5 py-0.5 font-bold">{{ mobDetail.drops.length }}</span>
                                    </div>
                                    <div class="divide-y divide-white/[0.06]">
                                        <button v-for="drop in mobDetail.drops" :key="drop.item"
                                            :disabled="!drop.item_data"
                                            @click="drop.item_data && openItemDb(drop.item_data.item_id, drop.item_data)"
                                            :class="['w-full flex items-center gap-3 px-4 py-2.5 text-left transition', drop.item_data ? 'hover:bg-white/5 cursor-pointer' : 'cursor-default']">

                                            <!-- Icono -->
                                            <div class="shrink-0 w-8 h-8 rounded-lg bg-rapanel-navy-800 flex items-center justify-center overflow-hidden">
                                                <img v-if="drop.item_data"
                                                    :src="`/data/items/icons/${drop.item_data.item_id}.png`"
                                                    :alt="drop.item_data.display_name || drop.item_data.name"
                                                    class="object-contain"
                                                    @error="$event.target.style.display='none'" />
                                                <svg v-else class="w-4 h-4 text-white/20" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                                </svg>
                                            </div>

                                            <!-- Nombre -->
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm text-white truncate">
                                                    {{ drop.item_data?.display_name || drop.item_data?.name || drop.item }}
                                                </p>
                                                <p v-if="drop.item_data" class="text-[10px] text-white/35 font-mono">{{ drop.item }}</p>
                                            </div>

                                            <!-- Rate -->
                                            <span :class="[
                                                'text-sm font-bold tabular-nums shrink-0',
                                                drop.rate >= 5000 ? 'text-rapanel-success' : drop.rate >= 500 ? 'text-rapanel-gold' : 'text-white/60'
                                            ]">{{ fmtRate(drop.rate) }}</span>
                                        </button>
                                    </div>
                                </div>

                                <!-- MVP Drops -->
                                <div v-if="mobDetail.mvp_drops?.length"
                                    class="bg-rapanel-navy-900 rounded-xl border border-rapanel-gold/20 overflow-hidden">
                                    <div class="flex items-center gap-2 px-4 py-3 border-b border-rapanel-gold/20">
                                        <svg class="w-4 h-4 text-rapanel-gold shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                                        </svg>
                                        <span class="font-semibold text-sm text-rapanel-gold">{{ __('MVP Drops') }}</span>
                                        <span class="text-[10px] bg-rapanel-gold/15 text-rapanel-gold border border-rapanel-gold/30 rounded-full px-1.5 py-0.5 font-bold">{{ mobDetail.mvp_drops.length }}</span>
                                    </div>
                                    <div class="divide-y divide-white/[0.06]">
                                        <button v-for="drop in mobDetail.mvp_drops" :key="drop.item"
                                            :disabled="!drop.item_data"
                                            @click="drop.item_data && openItemDb(drop.item_data.item_id, drop.item_data)"
                                            :class="['w-full flex items-center gap-3 px-4 py-2.5 text-left transition', drop.item_data ? 'hover:bg-white/5 cursor-pointer' : 'cursor-default']">

                                            <div class="shrink-0 w-8 h-8 rounded-lg bg-rapanel-navy-800 flex items-center justify-center overflow-hidden">
                                                <img v-if="drop.item_data"
                                                    :src="`/data/items/icons/${drop.item_data.item_id}.png`"
                                                    :alt="drop.item_data.display_name || drop.item_data.name"
                                                    class="object-contain"
                                                    @error="$event.target.style.display='none'" />
                                                <svg v-else class="w-4 h-4 text-white/20" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                                </svg>
                                            </div>

                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm text-white truncate">
                                                    {{ drop.item_data?.display_name || drop.item_data?.name || drop.item }}
                                                </p>
                                                <p v-if="drop.item_data" class="text-[10px] text-white/35 font-mono">{{ drop.item }}</p>
                                            </div>

                                            <span :class="[
                                                'text-sm font-bold tabular-nums shrink-0',
                                                drop.rate >= 5000 ? 'text-rapanel-success' : drop.rate >= 500 ? 'text-rapanel-gold' : 'text-white/60'
                                            ]">{{ fmtRate(drop.rate) }}</span>
                                        </button>
                                    </div>
                                </div>

                                <!-- Sin drops -->
                                <div v-if="!mobDetail.drops?.length && !mobDetail.mvp_drops?.length"
                                    class="flex flex-col items-center justify-center py-8 text-center gap-2">
                                    <p class="text-white/30 text-sm">{{ __('No drops data') }}</p>
                                </div>

                            </template>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- ItemDbModal para drops clickeables -->
        <ItemDbModal :item="itemDbItem" :server-count="itemDbCount" @close="closeItemDb" />

    </MainLayout>
</template>

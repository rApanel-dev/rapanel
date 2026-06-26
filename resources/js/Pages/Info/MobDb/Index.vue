<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import { useMobDbModal }  from '@/Composables/useMobDbModal.js';

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
const { openMobDb } = useMobDbModal();

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
                                : 'bg-white dark:bg-rapanel-navy-800 text-rapanel-text-light dark:text-rapanel-text-dark border-rapanel-navy-100 dark:border-white/10 hover:border-rapanel-btn-blue hover:text-rapanel-blue dark:hover:border-rapanel-btn-blue dark:hover:text-rapanel-blue'
                        ]">
                        {{ f[1] }}
                    </button>
                </div>
            </div>
        </div>

        <!-- ── Grid ── -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

            <div v-if="mobs.data.length"
                class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-2.5">

                <button v-for="mob in mobs.data" :key="mob.id"
                    @click="openMobDb(mob)"
                    class="group bg-white dark:bg-rapanel-navy-900 rounded-2xl border border-rapanel-navy-100 dark:border-white/10 shadow-sm overflow-hidden hover:shadow-md hover:border-rapanel-blue/40 dark:hover:border-rapanel-blue/40 transition-all duration-200 text-left w-full flex flex-col">

                    <!-- Barra de acento superior según clase -->
                    <div :class="classBg(mob)" class="h-[3px] w-full shrink-0" />

                    <div class="p-3 flex items-center gap-2.5 flex-1">

                        <!-- Imagen del mob -->
                        <div class="shrink-0 w-12 h-12 sm:w-14 sm:h-14 flex items-center justify-center overflow-hidden">
                            <img :src="`/data/monsters/${mob.id}.gif`"
                                 :alt="mob.name"
                                 class="max-w-full max-h-full object-contain"
                                 @error="$event.target.style.display = 'none'" />
                        </div>

                        <!-- Datos -->
                        <div class="flex-1 min-w-0">
                            <!-- Nombre + badge MVP/Boss -->
                            <div class="flex items-start justify-between gap-1 mb-1">
                                <p class="text-[11px] font-semibold text-rapanel-navy-900 dark:text-white leading-tight line-clamp-2">
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
                            <div class="text-[10px] text-rapanel-text-light dark:text-rapanel-text-dark tabular-nums mb-1.5">
                                HP: {{ mob.hp.toLocaleString() }}
                            </div>

                            <!-- Badges element + race + size -->
                            <div class="flex flex-wrap gap-1">
                                <span :class="['text-[9px] font-bold px-1.5 py-0.5 rounded-full border', elementBadge(mob.element)]">
                                    {{ mob.element }}{{ mob.element_level > 1 ? ` ${mob.element_level}` : '' }}
                                </span>
                                <span :class="['text-[9px] font-bold px-1.5 py-0.5 rounded-full border', raceBadge(mob.race)]">
                                    {{ mob.race }}
                                </span>
                                <span v-if="mob.size"
                                    class="text-[9px] font-bold px-1.5 py-0.5 rounded-full border bg-rapanel-navy-50 dark:bg-white/5 text-rapanel-text-light dark:text-white/50 border-rapanel-navy-200 dark:border-white/10">
                                    {{ mob.size }}
                                </span>
                            </div>
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


    </MainLayout>
</template>

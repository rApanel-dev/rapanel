<script setup>
import { ref, computed, watch } from 'vue';

import { usePage } from '@inertiajs/vue3';
import { useItemDbModal } from '@/Composables/useItemDbModal.js';
import { useMobDbModal }  from '@/Composables/useMobDbModal.js';
import { useModalStack }  from '@/Composables/useModalStack.js';

const page = usePage();
const __   = (key) => page.props.translations?.[key] || key;

const { selectedMob, mobDetail, detailLoading, mobTab, closeMobDb } = useMobDbModal();
const { openItemDb } = useItemDbModal();

const { acquire } = useModalStack();
const modalZ = ref(60);
watch(() => selectedMob.value, (val) => { if (val) modalZ.value = acquire(); });

const fmtRate = (r) => {
    if (!r) return '?';
    if (r >= 10000) return '100%';
    const p = r / 100;
    return (Number.isInteger(p) ? p : parseFloat(p.toFixed(2))) + '%';
};

const fmtAdjusted = (r) => {
    if (r === null || r === undefined) return null;
    return parseFloat(r.toFixed(2)) + '%';
};

const classBg = (mob) => {
    if (!mob) return '';
    if (mob.is_mvp)           return 'bg-rapanel-gold';
    if (mob.class === 'Boss') return 'bg-orange-500';
    return 'bg-rapanel-navy-300 dark:bg-rapanel-navy-600';
};

const classBadgeStyle = (mob) => {
    if (!mob) return null;
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

const spawnSort = ref('desc');
const sortedSpawns = computed(() => {
    const list = [...(mobDetail.value?.spawns ?? [])];
    return list.sort((a, b) =>
        spawnSort.value === 'desc'
            ? b.total_amount - a.total_amount
            : a.total_amount - b.total_amount
    );
});

// ── Description tab helpers ───────────────────────────────────────────
const primaryStats = computed(() => {
    const s = mobDetail.value?.stats;
    if (!s) return [];
    return [
        { label: 'STR', value: s.str ?? 1 },
        { label: 'AGI', value: s.agi ?? 1 },
        { label: 'VIT', value: s.vit ?? 1 },
        { label: 'INT', value: s.int ?? 1 },
        { label: 'DEX', value: s.dex ?? 1 },
        { label: 'LUK', value: s.luk ?? 1 },
    ];
});

const maxPrimaryStat = computed(() => {
    const s = mobDetail.value?.stats;
    if (!s) return 1;
    return Math.max(s.str ?? 1, s.agi ?? 1, s.vit ?? 1, s.int ?? 1, s.dex ?? 1, s.luk ?? 1, 1);
});

const fmtAtk = computed(() => {
    const s = mobDetail.value?.stats;
    if (!s?.attack) return null;
    if (!s.attack2 || s.attack2 === 0) return s.attack.toLocaleString();
    return `${s.attack.toLocaleString()} ~ ${s.attack2.toLocaleString()}`;
});

const activeModes = computed(() => {
    const modes = mobDetail.value?.modes;
    if (!modes) return [];
    const overrides = { Mvp: 'MVP' };
    return Object.entries(modes)
        .filter(([, v]) => v === true)
        .map(([k]) => overrides[k] ?? k.replace(/([A-Z])/g, ' $1').trim());
});

// ── Skills helpers ────────────────────────────────────────────────────
const fmtMs = (ms) => {
    if (!ms) return '0s';
    const s = ms / 1000;
    return (Number.isInteger(s) ? s : parseFloat(s.toFixed(1))) + 's';
};

const fmtRespawn = (ms) => {
    if (!ms || ms <= 0) return null;
    const s = Math.round(ms / 1000);
    if (s < 60)  return `${s}s`;
    const m = Math.floor(s / 60), rs = s % 60;
    if (m < 60)  return rs ? `${m}m ${rs}s` : `${m}m`;
    const h = Math.floor(m / 60), rm = m % 60;
    return rm ? `${h}h ${rm}m` : `${h}h`;
};

const stateBadge = (state) => {
    const map = {
        attack:      'bg-red-500/15 text-red-400 border-red-500/25',
        idle:        'bg-slate-400/15 text-slate-400 border-slate-400/25',
        walk:        'bg-sky-400/15 text-sky-400 border-sky-400/25',
        chase:       'bg-orange-400/15 text-orange-400 border-orange-400/25',
        follow:      'bg-amber-400/15 text-amber-400 border-amber-400/25',
        angry:       'bg-rose-400/15 text-rose-400 border-rose-400/25',
        loot:        'bg-emerald-400/15 text-emerald-400 border-emerald-400/25',
        any:         'bg-purple-400/15 text-purple-400 border-purple-400/25',
        anytarget:   'bg-purple-400/15 text-purple-400 border-purple-400/25',
        dead:        'bg-zinc-500/15 text-zinc-400 border-zinc-500/25',
    };
    return map[state?.toLowerCase()] ?? 'bg-white/5 text-white/50 border-white/10';
};

const skillConditionVal = (skill) => {
    if (skill.condition_value !== undefined && skill.condition_value !== null) {
        return skill.condition_value;
    }
    return null;
};
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95">

            <div v-if="selectedMob"
                class="fixed inset-0 flex items-end sm:items-center justify-center sm:p-4 bg-rapanel-navy-900/80 backdrop-blur-sm"
                :style="{ zIndex: modalZ }"
                @click.self="closeMobDb">

                <div class="relative w-full sm:max-w-4xl max-h-[95vh] sm:max-h-[90vh] flex flex-col overflow-hidden
                            rounded-t-2xl sm:rounded-2xl shadow-2xl bg-white dark:bg-rapanel-navy-900 ring-1 ring-rapanel-navy-200 dark:ring-white/10">

                    <!-- Barra de acento top -->
                    <div :class="classBg(selectedMob)" class="h-[3px] shrink-0" />

                    <!-- ── Header ── -->
                    <div class="flex items-center gap-4 px-5 py-4 shrink-0 bg-white dark:bg-rapanel-navy-900">

                        <div class="shrink-0 w-[72px] h-[72px] flex items-center justify-center">
                            <img :src="`/data/monsters/${selectedMob.id}.gif`"
                                 :alt="selectedMob.name"
                                 class="max-w-full max-h-full object-contain"
                                 @error="$event.target.style.display='none'" />
                        </div>

                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <h2 class="font-bold text-rapanel-navy-900 dark:text-white text-xl leading-tight">
                                    {{ selectedMob.name }}
                                </h2>
                                <span v-if="classBadgeStyle(selectedMob)"
                                    :class="['text-[10px] font-black uppercase px-2 py-0.5 rounded-full border', classBadgeStyle(selectedMob)]">
                                    {{ selectedMob.is_mvp ? 'MVP' : __('Boss') }}
                                </span>
                            </div>
                            <p v-if="selectedMob.aegis_name" class="font-mono text-[11px] text-rapanel-text-light/50 dark:text-white/35 mt-0.5">
                                {{ selectedMob.aegis_name }}
                            </p>
                            <div class="flex items-center gap-2 mt-2 flex-wrap">
                                <span class="text-[11px] font-bold px-2.5 py-0.5 rounded-full bg-rapanel-navy-100 text-rapanel-text-light dark:bg-white/10 dark:text-white/70">
                                    ID: {{ selectedMob.id }}
                                </span>
                                <span v-if="selectedMob.level"
                                    class="text-[11px] font-bold px-2.5 py-0.5 rounded-full bg-rapanel-navy-100 text-rapanel-text-light dark:bg-white/10 dark:text-white/70">
                                    Lv. {{ selectedMob.level }}
                                </span>
                                <span :class="['text-[10px] font-black uppercase px-2 py-0.5 rounded-full border tracking-wide', elementBadge(selectedMob.element)]">
                                    {{ selectedMob.element }}{{ mobDetail?.stats?.element_level ? ` ${mobDetail.stats.element_level}` : '' }}
                                </span>
                                <span :class="['text-[10px] font-black uppercase px-2 py-0.5 rounded-full border tracking-wide', raceBadge(selectedMob.race)]">
                                    {{ selectedMob.race }}
                                </span>
                                <span v-if="selectedMob.size"
                                    class="text-[10px] font-black uppercase px-2 py-0.5 rounded-full border tracking-wide
                                           bg-rapanel-navy-100/60 text-rapanel-text-light dark:bg-white/5 dark:text-white/50 dark:border-white/10">
                                    {{ selectedMob.size }}
                                </span>
                            </div>
                        </div>

                        <button @click="closeMobDb"
                            class="shrink-0 w-8 h-8 flex items-center justify-center rounded-lg cursor-pointer
                                   text-rapanel-text-light/40 dark:text-white/40 hover:text-rapanel-navy-900 dark:hover:text-white hover:bg-rapanel-navy-100 dark:hover:bg-white/10 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- ── Tab bar ── -->
                    <div class="grid grid-cols-2 sm:flex shrink-0 bg-white dark:bg-rapanel-navy-900 border-b border-rapanel-navy-100 dark:border-white/10">

                        <button @click="mobTab = 'info'"
                            :class="mobTab === 'info'
                                ? 'bg-rapanel-gold text-rapanel-navy-900'
                                : 'text-rapanel-text-light/60 dark:text-white/50 hover:text-rapanel-navy-900 dark:hover:text-white hover:bg-rapanel-navy-50 dark:hover:bg-white/5'"
                            class="flex-1 flex items-start gap-2 px-3 py-2.5 rounded-t-xl text-left transition-all duration-150 min-w-0">
                            <svg class="w-4 h-4 mt-0.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                            <div class="min-w-0">
                                <div class="text-sm font-bold leading-tight truncate">{{ __('Description') }}</div>
                                <div :class="mobTab === 'info' ? 'text-rapanel-navy-900/60' : 'text-rapanel-text-light/40 dark:text-white/30'"
                                     class="text-[10px] mt-0.5 leading-tight truncate hidden sm:block">{{ __('Stats and attributes') }}</div>
                            </div>
                        </button>

                        <button @click="mobTab = 'drops'"
                            :class="mobTab === 'drops'
                                ? 'bg-rapanel-gold text-rapanel-navy-900'
                                : 'text-rapanel-text-light/60 dark:text-white/50 hover:text-rapanel-navy-900 dark:hover:text-white hover:bg-rapanel-navy-50 dark:hover:bg-white/5'"
                            class="flex-1 flex items-start gap-2 px-3 py-2.5 rounded-t-xl text-left transition-all duration-150 min-w-0">
                            <svg class="w-4 h-4 mt-0.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                            <div class="min-w-0">
                                <div class="text-sm font-bold leading-tight truncate flex items-center gap-1.5">
                                    {{ __('Drops') }}
                                    <span v-if="mobDetail"
                                        :class="mobTab === 'drops' ? 'bg-rapanel-navy-900/15 text-rapanel-navy-900' : 'bg-rapanel-navy-100 dark:bg-white/10 text-rapanel-text-light dark:text-white/50'"
                                        class="text-[9px] font-black px-1.5 py-0.5 rounded-full">
                                        {{ (mobDetail.drops?.length ?? 0) + (mobDetail.mvp_drops?.length ?? 0) }}
                                    </span>
                                </div>
                                <div :class="mobTab === 'drops' ? 'text-rapanel-navy-900/60' : 'text-rapanel-text-light/40 dark:text-white/30'"
                                     class="text-[10px] mt-0.5 leading-tight truncate hidden sm:block">{{ __('Item drops') }}</div>
                            </div>
                        </button>

                        <button @click="mobTab = 'skills'"
                            :class="mobTab === 'skills'
                                ? 'bg-rapanel-gold text-rapanel-navy-900'
                                : 'text-rapanel-text-light/60 dark:text-white/50 hover:text-rapanel-navy-900 dark:hover:text-white hover:bg-rapanel-navy-50 dark:hover:bg-white/5'"
                            class="flex-1 flex items-start gap-2 px-3 py-2.5 rounded-t-xl text-left transition-all duration-150 min-w-0">
                            <svg class="w-4 h-4 mt-0.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                            </svg>
                            <div class="min-w-0">
                                <div class="text-sm font-bold leading-tight truncate flex items-center gap-1.5">
                                    {{ __('Skills') }}
                                    <span v-if="mobDetail?.skills?.length"
                                        :class="mobTab === 'skills' ? 'bg-rapanel-navy-900/15 text-rapanel-navy-900' : 'bg-rapanel-navy-100 dark:bg-white/10 text-rapanel-text-light dark:text-white/50'"
                                        class="text-[9px] font-black px-1.5 py-0.5 rounded-full">
                                        {{ mobDetail.skills.length }}
                                    </span>
                                </div>
                                <div :class="mobTab === 'skills' ? 'text-rapanel-navy-900/60' : 'text-rapanel-text-light/40 dark:text-white/30'"
                                     class="text-[10px] mt-0.5 leading-tight truncate hidden sm:block">{{ __('Monster skills') }}</div>
                            </div>
                        </button>

                        <button @click="mobTab = 'map'"
                            :class="mobTab === 'map'
                                ? 'bg-rapanel-gold text-rapanel-navy-900'
                                : 'text-rapanel-text-light/60 dark:text-white/50 hover:text-rapanel-navy-900 dark:hover:text-white hover:bg-rapanel-navy-50 dark:hover:bg-white/5'"
                            class="flex-1 flex items-start gap-2 px-3 py-2.5 rounded-t-xl text-left transition-all duration-150 min-w-0">
                            <svg class="w-4 h-4 mt-0.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                            </svg>
                            <div class="min-w-0">
                                <div class="text-sm font-bold leading-tight truncate">{{ __('Respawn Map') }}</div>
                                <div :class="mobTab === 'map' ? 'text-rapanel-navy-900/60' : 'text-rapanel-text-light/40 dark:text-white/30'"
                                     class="text-[10px] mt-0.5 leading-tight truncate hidden sm:block">{{ __('Spawn locations') }}</div>
                            </div>
                        </button>
                    </div>

                    <!-- ── Body ── -->
                    <div class="overflow-y-auto flex-1 p-5 bg-rapanel-navy-50 dark:bg-rapanel-navy-800/60">

                        <div v-if="detailLoading" class="flex items-center justify-center py-12">
                            <svg class="animate-spin w-6 h-6 text-rapanel-gold" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                            </svg>
                        </div>

                        <!-- ── Tab: Descripción ── -->
                        <div v-else-if="mobDetail && mobTab === 'info'" class="space-y-3">

                            <!-- ── Vitals + Stats (2 cols) ── -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <!-- HP / SP -->
                                <div class="bg-white dark:bg-rapanel-navy-900 rounded-xl border border-rapanel-navy-100 dark:border-white/10 p-4 space-y-3">
                                    <div>
                                        <div class="flex items-center justify-between mb-1.5">
                                            <span class="text-[11px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/35">HP</span>
                                            <span class="text-base font-bold text-rapanel-navy-900 dark:text-white tabular-nums">{{ mobDetail.hp.toLocaleString() }}</span>
                                        </div>
                                        <div class="h-2 rounded-full bg-rapanel-navy-100 dark:bg-white/10 overflow-hidden">
                                            <div class="h-full rounded-full bg-gradient-to-r from-emerald-500 to-emerald-400" style="width:100%"></div>
                                        </div>
                                    </div>
                                    <div v-if="mobDetail.stats?.sp > 0">
                                        <div class="flex items-center justify-between mb-1.5">
                                            <span class="text-[11px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/35">SP</span>
                                            <span class="text-base font-bold text-rapanel-navy-900 dark:text-white tabular-nums">{{ mobDetail.stats.sp.toLocaleString() }}</span>
                                        </div>
                                        <div class="h-2 rounded-full bg-rapanel-navy-100 dark:bg-white/10 overflow-hidden">
                                            <div class="h-full rounded-full bg-gradient-to-r from-blue-500 to-blue-400" style="width:100%"></div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Stats con barras -->
                                <div v-if="primaryStats.length"
                                    class="bg-white dark:bg-rapanel-navy-900 rounded-xl border border-rapanel-navy-100 dark:border-white/10 p-4">
                                    <p class="text-[11px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/35 mb-3">{{ __('Stats') }}</p>
                                    <div class="grid grid-cols-2 gap-x-4 gap-y-2">
                                        <div v-for="s in primaryStats" :key="s.label" class="flex items-center gap-2">
                                            <span class="text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/35 w-7 shrink-0">{{ s.label }}</span>
                                            <div class="flex-1 h-1.5 rounded-full bg-rapanel-navy-100 dark:bg-white/10 overflow-hidden">
                                                <div class="h-full rounded-full bg-rapanel-gold/70"
                                                    :style="{ width: Math.round(s.value / maxPrimaryStat * 100) + '%' }"></div>
                                            </div>
                                            <span class="text-xs font-bold text-rapanel-navy-900 dark:text-white tabular-nums w-7 text-right shrink-0">{{ s.value }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- ── Combat: ATK / DEF / MDEF destacados + chips ── -->
                            <div class="bg-white dark:bg-rapanel-navy-900 rounded-xl border border-rapanel-navy-100 dark:border-white/10 overflow-hidden">
                                <p class="text-[11px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/35 px-4 pt-4 pb-3">{{ __('Combat') }}</p>
                                <!-- ATK / DEF / MDEF grandes -->
                                <div class="grid grid-cols-3 divide-x divide-rapanel-navy-100 dark:divide-white/[0.06] border-t border-rapanel-navy-100 dark:border-white/[0.06]">
                                    <div class="flex flex-col items-center py-4 px-2">
                                        <span class="text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/35 mb-1">ATK</span>
                                        <span class="text-base font-bold text-rapanel-navy-900 dark:text-white tabular-nums text-center">{{ fmtAtk ?? '—' }}</span>
                                    </div>
                                    <div class="flex flex-col items-center py-4 px-2">
                                        <span class="text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/35 mb-1">DEF</span>
                                        <span class="text-base font-bold text-rapanel-navy-900 dark:text-white tabular-nums">{{ mobDetail.stats?.defense ?? '—' }}</span>
                                    </div>
                                    <div class="flex flex-col items-center py-4 px-2">
                                        <span class="text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/35 mb-1">MDEF</span>
                                        <span class="text-base font-bold text-rapanel-navy-900 dark:text-white tabular-nums">{{ mobDetail.stats?.magic_defense ?? '—' }}</span>
                                    </div>
                                </div>
                                <!-- Chips secundarios -->
                                <div class="flex flex-wrap gap-2 px-4 py-3 border-t border-rapanel-navy-100 dark:border-white/[0.06]">
                                    <span v-if="mobDetail.stats?.resistance > 0"
                                        class="inline-flex items-center gap-1 text-[11px] font-bold px-2.5 py-1 rounded-lg bg-rapanel-navy-50 dark:bg-white/5 text-rapanel-text-light dark:text-white/60 border border-rapanel-navy-100 dark:border-white/10">
                                        RES <span class="text-[7px] font-black uppercase px-0.5 rounded bg-rapanel-blue/15 text-rapanel-blue border border-rapanel-blue/20">RE</span>
                                        <span class="font-bold text-rapanel-navy-900 dark:text-white">{{ mobDetail.stats.resistance }}</span>
                                    </span>
                                    <span v-if="mobDetail.stats?.magic_resistance > 0"
                                        class="inline-flex items-center gap-1 text-[11px] font-bold px-2.5 py-1 rounded-lg bg-rapanel-navy-50 dark:bg-white/5 text-rapanel-text-light dark:text-white/60 border border-rapanel-navy-100 dark:border-white/10">
                                        MRES <span class="text-[7px] font-black uppercase px-0.5 rounded bg-rapanel-blue/15 text-rapanel-blue border border-rapanel-blue/20">RE</span>
                                        <span class="font-bold text-rapanel-navy-900 dark:text-white">{{ mobDetail.stats.magic_resistance }}</span>
                                    </span>
                                    <span v-if="mobDetail.stats?.attack_range"
                                        class="inline-flex items-center gap-1.5 text-[11px] px-2.5 py-1 rounded-lg bg-rapanel-navy-50 dark:bg-white/5 text-rapanel-text-light dark:text-white/60 border border-rapanel-navy-100 dark:border-white/10">
                                        <span class="font-black uppercase tracking-wide">{{ __('Atk Range') }}</span>
                                        <span class="font-bold text-rapanel-navy-900 dark:text-white">{{ mobDetail.stats.attack_range }}</span>
                                    </span>
                                    <span v-if="mobDetail.stats?.skill_range"
                                        class="inline-flex items-center gap-1.5 text-[11px] px-2.5 py-1 rounded-lg bg-rapanel-navy-50 dark:bg-white/5 text-rapanel-text-light dark:text-white/60 border border-rapanel-navy-100 dark:border-white/10">
                                        <span class="font-black uppercase tracking-wide">{{ __('Skill Range') }}</span>
                                        <span class="font-bold text-rapanel-navy-900 dark:text-white">{{ mobDetail.stats.skill_range }}</span>
                                    </span>
                                    <span v-if="mobDetail.stats?.chase_range"
                                        class="inline-flex items-center gap-1.5 text-[11px] px-2.5 py-1 rounded-lg bg-rapanel-navy-50 dark:bg-white/5 text-rapanel-text-light dark:text-white/60 border border-rapanel-navy-100 dark:border-white/10">
                                        <span class="font-black uppercase tracking-wide">{{ __('Chase Range') }}</span>
                                        <span class="font-bold text-rapanel-navy-900 dark:text-white">{{ mobDetail.stats.chase_range }}</span>
                                    </span>
                                    <span v-if="mobDetail.stats?.walk_speed"
                                        class="inline-flex items-center gap-1.5 text-[11px] px-2.5 py-1 rounded-lg bg-rapanel-navy-50 dark:bg-white/5 text-rapanel-text-light dark:text-white/60 border border-rapanel-navy-100 dark:border-white/10">
                                        <span class="font-black uppercase tracking-wide">{{ __('Walk Speed') }}</span>
                                        <span class="font-bold text-rapanel-navy-900 dark:text-white">{{ mobDetail.stats.walk_speed }}</span>
                                    </span>
                                    <span v-if="mobDetail.stats?.attack_delay"
                                        class="inline-flex items-center gap-1.5 text-[11px] px-2.5 py-1 rounded-lg bg-rapanel-navy-50 dark:bg-white/5 text-rapanel-text-light dark:text-white/60 border border-rapanel-navy-100 dark:border-white/10">
                                        <span class="font-black uppercase tracking-wide">{{ __('Atk Delay') }}</span>
                                        <span class="font-bold text-rapanel-navy-900 dark:text-white">{{ mobDetail.stats.attack_delay }}</span>
                                    </span>
                                    <span v-if="mobDetail.stats?.attack_motion"
                                        class="inline-flex items-center gap-1.5 text-[11px] px-2.5 py-1 rounded-lg bg-rapanel-navy-50 dark:bg-white/5 text-rapanel-text-light dark:text-white/60 border border-rapanel-navy-100 dark:border-white/10">
                                        <span class="font-black uppercase tracking-wide">{{ __('Atk Motion') }}</span>
                                        <span class="font-bold text-rapanel-navy-900 dark:text-white">{{ mobDetail.stats.attack_motion }}</span>
                                    </span>
                                    <span v-if="mobDetail.stats?.damage_motion"
                                        class="inline-flex items-center gap-1.5 text-[11px] px-2.5 py-1 rounded-lg bg-rapanel-navy-50 dark:bg-white/5 text-rapanel-text-light dark:text-white/60 border border-rapanel-navy-100 dark:border-white/10">
                                        <span class="font-black uppercase tracking-wide">{{ __('Dmg Motion') }}</span>
                                        <span class="font-bold text-rapanel-navy-900 dark:text-white">{{ mobDetail.stats.damage_motion }}</span>
                                    </span>
                                    <span v-if="mobDetail.stats?.damage_taken !== undefined && mobDetail.stats.damage_taken !== 100"
                                        class="inline-flex items-center gap-1.5 text-[11px] px-2.5 py-1 rounded-lg bg-rapanel-navy-50 dark:bg-white/5 text-rapanel-text-light dark:text-white/60 border border-rapanel-navy-100 dark:border-white/10">
                                        <span class="font-black uppercase tracking-wide">{{ __('Dmg Taken') }}</span>
                                        <span class="font-bold text-rapanel-navy-900 dark:text-white">{{ mobDetail.stats.damage_taken }}%</span>
                                    </span>
                                </div>
                            </div>

                            <!-- ── EXP + Monster Modes (2 cols) ── -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">

                                <!-- Monster Modes -->
                                <div v-if="activeModes.length"
                                    :class="!(mobDetail.base_exp || mobDetail.stats?.job_exp || mobDetail.mvp_exp) ? 'col-span-2' : ''"
                                    class="bg-white dark:bg-rapanel-navy-900 rounded-xl border border-rapanel-navy-100 dark:border-white/10 p-4">
                                    <p class="text-[11px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/35 mb-3">{{ __('Monster Mode') }}</p>
                                    <div class="flex flex-wrap gap-2">
                                        <span v-for="mode in activeModes" :key="mode"
                                            :class="mode === 'MVP' ? 'bg-rapanel-gold/15 text-rapanel-gold border-rapanel-gold/30' : 'bg-rapanel-navy-100/60 dark:bg-white/[0.06] text-rapanel-text-light dark:text-white/60 border-rapanel-navy-200 dark:border-white/10'"
                                            class="text-[10px] font-bold px-2.5 py-1 rounded-full border">
                                            {{ mode }}
                                        </span>
                                    </div>
                                </div>

                                <!-- EXP -->
                                <div v-if="mobDetail.base_exp || mobDetail.stats?.job_exp || mobDetail.mvp_exp"
                                    :class="!activeModes.length ? 'col-span-2' : ''"
                                    class="bg-white dark:bg-rapanel-navy-900 rounded-xl border border-rapanel-navy-100 dark:border-white/10 overflow-hidden">
                                    <p class="text-[11px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/35 px-4 pt-4 pb-3">EXP</p>
                                    <div class="divide-y divide-rapanel-navy-100 dark:divide-white/[0.06] border-t border-rapanel-navy-100 dark:border-white/[0.06]">

                                        <!-- Base EXP -->
                                        <div v-if="mobDetail.base_exp" class="flex items-start gap-3 px-4 py-2.5">
                                            <div class="w-7 h-7 mt-0.5 rounded-lg bg-blue-500/10 flex items-center justify-center shrink-0">
                                                <svg class="w-3.5 h-3.5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                                </svg>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-[11px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/35 leading-5">{{ __('Base EXP') }}</p>
                                            </div>
                                            <div class="text-right shrink-0">
                                                <span class="text-sm font-bold tabular-nums block leading-5 text-blue-400">
                                                    {{ Math.round(mobDetail.base_exp * $page.props.baseExpRate / 100).toLocaleString() }}
                                                </span>
                                                <span v-if="$page.props.baseExpRate !== 100" class="text-[10px] text-rapanel-text-light/40 dark:text-white/30 tabular-nums block mt-0.5">
                                                    {{ mobDetail.base_exp.toLocaleString() }}
                                                </span>
                                            </div>
                                        </div>

                                        <!-- Job EXP -->
                                        <div v-if="mobDetail.stats?.job_exp" class="flex items-start gap-3 px-4 py-2.5">
                                            <div class="w-7 h-7 mt-0.5 rounded-lg bg-purple-500/10 flex items-center justify-center shrink-0">
                                                <svg class="w-3.5 h-3.5 text-purple-400" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M6 6V5a3 3 0 013-3h2a3 3 0 013 3v1h2a2 2 0 012 2v3.57A22.952 22.952 0 0110 13a22.95 22.95 0 01-8-1.43V8a2 2 0 012-2h2zm2-1a1 1 0 011-1h2a1 1 0 011 1v1H8V5zm1 5a1 1 0 011-1h.01a1 1 0 110 2H10a1 1 0 01-1-1z" clip-rule="evenodd"/>
                                                    <path d="M2 13.692V16a2 2 0 002 2h12a2 2 0 002-2v-2.308A24.974 24.974 0 0110 15c-2.796 0-5.487-.46-8-1.308z"/>
                                                </svg>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-[11px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/35 leading-5">{{ __('Job EXP') }}</p>
                                            </div>
                                            <div class="text-right shrink-0">
                                                <span class="text-sm font-bold tabular-nums block leading-5 text-purple-400">
                                                    {{ Math.round(Number(mobDetail.stats.job_exp) * $page.props.jobExpRate / 100).toLocaleString() }}
                                                </span>
                                                <span v-if="$page.props.jobExpRate !== 100" class="text-[10px] text-rapanel-text-light/40 dark:text-white/30 tabular-nums block mt-0.5">
                                                    {{ Number(mobDetail.stats.job_exp).toLocaleString() }}
                                                </span>
                                            </div>
                                        </div>

                                        <!-- MVP EXP -->
                                        <div v-if="mobDetail.mvp_exp" class="flex items-start gap-3 px-4 py-2.5">
                                            <div class="w-7 h-7 mt-0.5 rounded-lg bg-rapanel-gold/15 flex items-center justify-center shrink-0">
                                                <svg class="w-3.5 h-3.5 text-rapanel-gold" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                                </svg>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-[11px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/35 leading-5">MVP EXP</p>
                                            </div>
                                            <div class="text-right shrink-0">
                                                <span class="text-sm font-bold tabular-nums block leading-5 text-rapanel-gold">
                                                    {{ Math.round(mobDetail.mvp_exp * $page.props.mvpExpRate / 100).toLocaleString() }}
                                                </span>
                                                <span v-if="$page.props.mvpExpRate !== 100" class="text-[10px] text-rapanel-text-light/40 dark:text-white/30 tabular-nums block mt-0.5">
                                                    {{ mobDetail.mvp_exp.toLocaleString() }}
                                                </span>
                                            </div>
                                        </div>

                                    </div>
                                </div>

                            </div>

                        </div>

                        <!-- ── Tab: Drops ── -->
                        <div v-else-if="mobDetail && mobTab === 'drops'" class="space-y-4">

                            <!-- Regular Drops -->
                            <div v-if="mobDetail.drops?.length"
                                class="bg-white dark:bg-rapanel-navy-900 rounded-xl border border-rapanel-navy-100 dark:border-white/10 overflow-hidden">
                                <div class="flex items-center gap-2 px-4 py-3 border-b border-rapanel-navy-100 dark:border-white/10">
                                    <svg class="w-4 h-4 text-rapanel-gold shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                    </svg>
                                    <span class="font-semibold text-sm text-rapanel-navy-900 dark:text-white">{{ __('Drops') }}</span>
                                    <span class="text-[10px] bg-rapanel-navy-100 dark:bg-white/10 text-rapanel-text-light dark:text-white/50 rounded-full px-1.5 py-0.5 font-bold">{{ mobDetail.drops.length }}</span>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-3 divide-y sm:divide-y-0 sm:divide-x-0 divide-rapanel-navy-100 dark:divide-white/[0.06]">
                                    <button v-for="drop in mobDetail.drops" :key="drop.item"
                                        :disabled="!drop.item_data"
                                        @click="drop.item_data && openItemDb(drop.item_data.item_id, drop.item_data)"
                                        :class="['flex items-start gap-3 px-4 py-2.5 text-left transition',
                                                 drop.item_data ? 'hover:bg-rapanel-navy-50 dark:hover:bg-white/5 cursor-pointer' : 'cursor-default']">
                                        <div class="shrink-0 w-7 h-7 mt-0.5 rounded-lg bg-rapanel-navy-100 dark:bg-rapanel-navy-800 flex items-center justify-center overflow-hidden">
                                            <img v-if="drop.item_data"
                                                :src="`/data/items/item/${drop.item_data.item_id}.png`"
                                                :alt="drop.item_data.display_name || drop.item_data.name"
                                                class="object-contain"
                                                @error="$event.target.style.display='none'" />
                                            <svg v-else class="w-3.5 h-3.5 text-rapanel-text-light/30 dark:text-white/20" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                            </svg>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm text-rapanel-navy-900 dark:text-white truncate">
                                                {{ drop.item_data?.display_name || drop.item_data?.name || drop.item }}<span v-if="drop.item_data?.slots > 0"> [{{ drop.item_data.slots }}]</span>
                                            </p>
                                            <div class="flex items-center gap-1.5 mt-0.5">
                                                <p v-if="drop.item_data" class="text-[10px] text-rapanel-text-light/50 dark:text-white/35 font-mono">{{ drop.item }}</p>
                                                <span v-if="drop.nosteal"
                                                    class="text-[8px] font-black uppercase px-1 py-px rounded bg-rapanel-danger/10 text-rapanel-danger border border-rapanel-danger/20">
                                                    {{ __('No Steal') }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="text-right shrink-0">
                                            <span :class="[
                                                'text-sm font-bold tabular-nums block leading-5',
                                                (drop.adjusted_rate ?? drop.rate / 100) >= 50 ? 'text-rapanel-success' : (drop.adjusted_rate ?? drop.rate / 100) >= 5 ? 'text-rapanel-gold' : 'text-rapanel-text-light dark:text-white/60'
                                            ]">{{ fmtAdjusted(drop.adjusted_rate) ?? fmtRate(drop.rate) }}</span>
                                            <span v-if="drop.adjusted_rate !== null" class="text-[10px] text-rapanel-text-light/40 dark:text-white/30 tabular-nums block mt-0.5">{{ fmtRate(drop.rate) }}</span>
                                        </div>
                                    </button>
                                </div>
                            </div>

                            <!-- MVP Drops -->
                            <div v-if="mobDetail.mvp_drops?.length"
                                class="bg-white dark:bg-rapanel-navy-900 rounded-xl border border-rapanel-gold/30 dark:border-rapanel-gold/20 overflow-hidden">
                                <div class="flex items-center gap-2 px-4 py-3 border-b border-rapanel-gold/30 dark:border-rapanel-gold/20">
                                    <svg class="w-4 h-4 text-rapanel-gold shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                                    </svg>
                                    <span class="font-semibold text-sm text-rapanel-gold">{{ __('MVP Drops') }}</span>
                                    <span class="text-[10px] bg-rapanel-gold/15 text-rapanel-gold border border-rapanel-gold/30 rounded-full px-1.5 py-0.5 font-bold">{{ mobDetail.mvp_drops.length }}</span>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-3 divide-y sm:divide-y-0 sm:divide-x-0 divide-rapanel-navy-100 dark:divide-white/[0.06]">
                                    <button v-for="drop in mobDetail.mvp_drops" :key="drop.item"
                                        :disabled="!drop.item_data"
                                        @click="drop.item_data && openItemDb(drop.item_data.item_id, drop.item_data)"
                                        :class="['flex items-start gap-3 px-4 py-2.5 text-left transition',
                                                 drop.item_data ? 'hover:bg-rapanel-navy-50 dark:hover:bg-white/5 cursor-pointer' : 'cursor-default']">
                                        <div class="shrink-0 w-7 h-7 mt-0.5 rounded-lg bg-rapanel-navy-100 dark:bg-rapanel-navy-800 flex items-center justify-center overflow-hidden">
                                            <img v-if="drop.item_data"
                                                :src="`/data/items/item/${drop.item_data.item_id}.png`"
                                                :alt="drop.item_data.display_name || drop.item_data.name"
                                                class="object-contain"
                                                @error="$event.target.style.display='none'" />
                                            <svg v-else class="w-3.5 h-3.5 text-white/20" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                            </svg>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm text-rapanel-navy-900 dark:text-white truncate">
                                                {{ drop.item_data?.display_name || drop.item_data?.name || drop.item }}<span v-if="drop.item_data?.slots > 0"> [{{ drop.item_data.slots }}]</span>
                                            </p>
                                            <p v-if="drop.item_data" class="text-[10px] text-rapanel-text-light/50 dark:text-white/35 font-mono">{{ drop.item }}</p>
                                        </div>
                                        <div class="text-right shrink-0">
                                            <span :class="[
                                                'text-sm font-bold tabular-nums block leading-5',
                                                (drop.adjusted_rate ?? drop.rate / 100) >= 50 ? 'text-rapanel-success' : (drop.adjusted_rate ?? drop.rate / 100) >= 5 ? 'text-rapanel-gold' : 'text-rapanel-text-light dark:text-white/60'
                                            ]">{{ fmtAdjusted(drop.adjusted_rate) ?? fmtRate(drop.rate) }}</span>
                                            <span v-if="drop.adjusted_rate !== null" class="text-[10px] text-rapanel-text-light/40 dark:text-white/30 tabular-nums block mt-0.5">{{ fmtRate(drop.rate) }}</span>
                                        </div>
                                    </button>
                                </div>
                            </div>

                            <div v-if="!mobDetail.drops?.length && !mobDetail.mvp_drops?.length"
                                class="flex flex-col items-center justify-center py-10 text-center gap-2">
                                <p class="text-rapanel-text-light/40 dark:text-white/30 text-sm">{{ __('No drops data') }}</p>
                            </div>
                        </div>

                        <!-- ── Tab: Skills ── -->
                        <div v-else-if="mobDetail && mobTab === 'skills'">

                            <div v-if="!mobDetail.skills?.length"
                                class="flex flex-col items-center justify-center py-16 text-center gap-4">
                                <div class="w-16 h-16 rounded-2xl bg-rapanel-navy-100 dark:bg-white/5 flex items-center justify-center">
                                    <svg class="w-8 h-8 text-rapanel-navy-300 dark:text-white/20" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                                    </svg>
                                </div>
                                <p class="text-rapanel-text-light/50 dark:text-white/30 text-sm">{{ __('No skill data available.') }}</p>
                                <p class="text-rapanel-text-light/35 dark:text-white/20 text-xs">{{ __('Import mob_skill_db.txt from the Admin panel.') }}</p>
                            </div>

                            <div v-else class="bg-white dark:bg-rapanel-navy-900 rounded-xl border border-rapanel-navy-100 dark:border-white/10 overflow-hidden">
                                <div class="flex items-center gap-2 px-4 py-3 border-b border-rapanel-navy-100 dark:border-white/10">
                                    <svg class="w-4 h-4 text-rapanel-gold shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                                    </svg>
                                    <span class="font-semibold text-sm text-rapanel-navy-900 dark:text-white">{{ __('Skills') }}</span>
                                    <span class="text-[10px] bg-rapanel-navy-100 dark:bg-white/10 text-rapanel-text-light dark:text-white/50 rounded-full px-1.5 py-0.5 font-bold">{{ mobDetail.skills.length }}</span>
                                </div>

                                <!-- Desktop table -->
                                <div class="hidden sm:block overflow-x-auto">
                                    <table class="w-full text-xs">
                                        <thead>
                                            <tr class="bg-rapanel-navy-50 dark:bg-white/[0.03] border-b border-rapanel-navy-100 dark:border-white/[0.06]">
                                                <th class="text-left px-4 py-2.5 font-black uppercase tracking-widest text-[10px] text-rapanel-text-light/50 dark:text-white/35">{{ __('Skill') }}</th>
                                                <th class="text-center px-3 py-2.5 font-black uppercase tracking-widest text-[10px] text-rapanel-text-light/50 dark:text-white/35">Lv</th>
                                                <th class="text-center px-3 py-2.5 font-black uppercase tracking-widest text-[10px] text-rapanel-text-light/50 dark:text-white/35">{{ __('State') }}</th>
                                                <th class="text-center px-3 py-2.5 font-black uppercase tracking-widest text-[10px] text-rapanel-text-light/50 dark:text-white/35">{{ __('Rate') }}</th>
                                                <th class="text-center px-3 py-2.5 font-black uppercase tracking-widest text-[10px] text-rapanel-text-light/50 dark:text-white/35">{{ __('Cast') }}</th>
                                                <th class="text-center px-3 py-2.5 font-black uppercase tracking-widest text-[10px] text-rapanel-text-light/50 dark:text-white/35">{{ __('Delay') }}</th>
                                                <th class="text-center px-3 py-2.5 font-black uppercase tracking-widest text-[10px] text-rapanel-text-light/50 dark:text-white/35">{{ __('Target') }}</th>
                                                <th class="text-left px-3 py-2.5 font-black uppercase tracking-widest text-[10px] text-rapanel-text-light/50 dark:text-white/35">{{ __('Condition') }}</th>
                                                <th class="text-center px-3 py-2.5 font-black uppercase tracking-widest text-[10px] text-rapanel-text-light/50 dark:text-white/35">{{ __('Val') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-rapanel-navy-100 dark:divide-white/[0.05]">
                                            <tr v-for="(skill, idx) in mobDetail.skills" :key="idx"
                                                class="hover:bg-rapanel-navy-50/60 dark:hover:bg-white/[0.02] transition">
                                                <td class="px-4 py-2.5">
                                                    <span class="font-mono text-xs text-rapanel-navy-900 dark:text-white font-semibold">{{ skill.name }}</span>
                                                </td>
                                                <td class="px-3 py-2.5 text-center">
                                                    <span class="text-xs font-bold text-rapanel-navy-900 dark:text-white tabular-nums">{{ skill.level }}</span>
                                                </td>
                                                <td class="px-3 py-2.5 text-center">
                                                    <span :class="['text-[9px] font-black uppercase px-2 py-0.5 rounded-full border', stateBadge(skill.state)]">
                                                        {{ skill.state }}
                                                    </span>
                                                </td>
                                                <td class="px-3 py-2.5 text-center">
                                                    <span :class="[
                                                        'text-xs font-bold tabular-nums',
                                                        skill.rate >= 10000 ? 'text-rapanel-success' : skill.rate >= 5000 ? 'text-rapanel-gold' : 'text-rapanel-text-light dark:text-white/60'
                                                    ]">{{ (skill.rate / 100).toFixed(skill.rate % 100 === 0 ? 0 : 2) }}%</span>
                                                </td>
                                                <td class="px-3 py-2.5 text-center text-xs text-rapanel-text-light dark:text-white/50 tabular-nums">{{ fmtMs(skill.cast_time) }}</td>
                                                <td class="px-3 py-2.5 text-center text-xs text-rapanel-text-light dark:text-white/50 tabular-nums">{{ fmtMs(skill.delay) }}</td>
                                                <td class="px-3 py-2.5 text-center">
                                                    <span class="text-[10px] font-semibold text-rapanel-navy-900 dark:text-white/70">{{ skill.target }}</span>
                                                </td>
                                                <td class="px-3 py-2.5">
                                                    <span class="text-[10px] font-mono text-rapanel-text-light dark:text-white/50">{{ skill.condition }}</span>
                                                </td>
                                                <td class="px-3 py-2.5 text-center">
                                                    <span v-if="skillConditionVal(skill) !== null"
                                                        class="text-xs font-bold tabular-nums text-rapanel-navy-900 dark:text-white/70">
                                                        {{ skillConditionVal(skill) }}
                                                    </span>
                                                    <span v-else class="text-rapanel-text-light/25 dark:text-white/20">—</span>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Mobile cards -->
                                <div class="sm:hidden divide-y divide-rapanel-navy-100 dark:divide-white/[0.06]">
                                    <div v-for="(skill, idx) in mobDetail.skills" :key="idx"
                                        class="px-4 py-3 space-y-2">
                                        <div class="flex items-center justify-between gap-2">
                                            <span class="font-mono text-xs font-semibold text-rapanel-navy-900 dark:text-white truncate">{{ skill.name }}</span>
                                            <span :class="['text-[9px] font-black uppercase px-2 py-0.5 rounded-full border shrink-0', stateBadge(skill.state)]">
                                                {{ skill.state }}
                                            </span>
                                        </div>
                                        <div class="flex flex-wrap gap-x-4 gap-y-1 text-[11px]">
                                            <span class="text-rapanel-text-light/50 dark:text-white/35">Lv <span class="font-bold text-rapanel-navy-900 dark:text-white">{{ skill.level }}</span></span>
                                            <span :class="[
                                                'font-bold tabular-nums',
                                                skill.rate >= 10000 ? 'text-rapanel-success' : skill.rate >= 5000 ? 'text-rapanel-gold' : 'text-rapanel-text-light dark:text-white/60'
                                            ]">{{ (skill.rate / 100).toFixed(skill.rate % 100 === 0 ? 0 : 2) }}%</span>
                                            <span class="text-rapanel-text-light/50 dark:text-white/35">{{ __('Cast') }} <span class="font-semibold text-rapanel-navy-900 dark:text-white/70">{{ fmtMs(skill.cast_time) }}</span></span>
                                            <span class="text-rapanel-text-light/50 dark:text-white/35">{{ __('Delay') }} <span class="font-semibold text-rapanel-navy-900 dark:text-white/70">{{ fmtMs(skill.delay) }}</span></span>
                                        </div>
                                        <div class="flex flex-wrap gap-x-4 gap-y-1 text-[11px]">
                                            <span class="text-rapanel-text-light/50 dark:text-white/35">{{ __('Target') }} <span class="font-semibold text-rapanel-navy-900 dark:text-white/70">{{ skill.target }}</span></span>
                                            <span class="text-rapanel-text-light/50 dark:text-white/35">{{ __('Condition') }} <span class="font-mono text-rapanel-navy-900 dark:text-white/70">{{ skill.condition }}<span v-if="skillConditionVal(skill) !== null"> ({{ skillConditionVal(skill) }})</span></span></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ── Tab: Mapa de Respawn ── -->
                        <div v-else-if="mobDetail && mobTab === 'map'">

                            <!-- Sin spawns -->
                            <div v-if="!mobDetail.spawns?.length"
                                class="flex flex-col items-center justify-center py-16 text-center gap-4">
                                <div class="w-16 h-16 rounded-2xl bg-rapanel-navy-100 dark:bg-white/5 flex items-center justify-center">
                                    <svg class="w-8 h-8 text-rapanel-navy-300 dark:text-white/20" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                                    </svg>
                                </div>
                                <p class="text-rapanel-text-light/50 dark:text-white/30 text-sm">{{ __('No spawn locations found.') }}</p>
                            </div>

                            <!-- Grid de mapas -->
                            <div v-else>

                                <!-- Barra de controles -->
                                <div class="flex items-center justify-between mb-3 gap-2">
                                    <p class="text-xs text-rapanel-text-light dark:text-white/40">
                                        {{ mobDetail.spawns.length }} {{ __('maps') }}
                                    </p>
                                    <div class="flex items-center gap-1">
                                        <button @click="spawnSort = 'desc'"
                                            :class="spawnSort === 'desc'
                                                ? 'bg-rapanel-blue text-white border-rapanel-blue'
                                                : 'bg-white dark:bg-rapanel-navy-900 text-rapanel-text-light dark:text-white/50 border-rapanel-navy-100 dark:border-white/10 hover:border-rapanel-blue hover:text-rapanel-blue'"
                                            class="flex items-center gap-1 px-2.5 py-1 rounded-lg border text-[10px] font-bold transition">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12" />
                                            </svg>
                                            {{ __('Most') }}
                                        </button>
                                        <button @click="spawnSort = 'asc'"
                                            :class="spawnSort === 'asc'
                                                ? 'bg-rapanel-blue text-white border-rapanel-blue'
                                                : 'bg-white dark:bg-rapanel-navy-900 text-rapanel-text-light dark:text-white/50 border-rapanel-navy-100 dark:border-white/10 hover:border-rapanel-blue hover:text-rapanel-blue'"
                                            class="flex items-center gap-1 px-2.5 py-1 rounded-lg border text-[10px] font-bold transition">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 4h13M3 8h9m-9 4h9m5-4v12m0 0l-4-4m4 4l4-4" />
                                            </svg>
                                            {{ __('Least') }}
                                        </button>
                                    </div>
                                </div>

                                <!-- Cards: 2 columnas -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                    <a v-for="spawn in sortedSpawns" :key="spawn.map_name"
                                        :href="`/info/map-db/${spawn.map_name}`"
                                        class="group flex items-start gap-3 bg-white dark:bg-rapanel-navy-900 rounded-xl border border-rapanel-navy-100 dark:border-white/10 p-3 hover:shadow-md hover:border-rapanel-blue/40 dark:hover:border-rapanel-blue/40 transition-all duration-200">

                                        <!-- Thumbnail -->
                                        <div class="shrink-0 w-14 h-14 rounded-lg bg-rapanel-navy-50 dark:bg-white/[0.04] overflow-hidden relative">
                                            <img :src="`/data/maps/${spawn.map_name}.png`"
                                                :alt="spawn.map_name"
                                                class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-200"
                                                @error="$event.target.style.display='none'" />
                                            <div v-if="spawn.random_amount > 0 && spawn.placed_points === 0"
                                                class="absolute inset-0 flex items-center justify-center bg-amber-500/20">
                                                <span class="text-[7px] font-black text-amber-500 uppercase tracking-wide leading-none text-center">{{ __('Random') }}</span>
                                            </div>
                                        </div>

                                        <!-- Info -->
                                        <div class="flex-1 min-w-0 pt-0.5">
                                            <!-- Nombre del mapa -->
                                            <p class="text-sm font-bold text-rapanel-navy-900 dark:text-white truncate leading-tight">
                                                {{ spawn.display_name || spawn.map_name }}
                                            </p>
                                            <p v-if="spawn.display_name" class="text-[10px] font-mono text-rapanel-text-light/50 dark:text-white/35 truncate leading-tight mt-0.5">
                                                {{ spawn.map_name }}
                                            </p>

                                            <!-- Cantidad + respawn -->
                                            <div class="flex items-center gap-2 mt-1.5 flex-wrap">
                                                <span class="text-[11px] font-black text-rapanel-blue tabular-nums">
                                                    ×{{ spawn.total_amount }}
                                                </span>
                                                <span v-if="fmtRespawn(spawn.min_delay)"
                                                    class="inline-flex items-center gap-1 text-[11px] font-bold text-rapanel-text-light/60 dark:text-white/40 tabular-nums">
                                                    <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                        <circle cx="12" cy="12" r="9" stroke-linecap="round"/>
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 7v5l3 3" />
                                                    </svg>
                                                    {{ fmtRespawn(spawn.min_delay) }}
                                                </span>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>

</template>

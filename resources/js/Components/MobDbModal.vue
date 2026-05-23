<script setup>
import { usePage } from '@inertiajs/vue3';
import { useItemDbModal } from '@/Composables/useItemDbModal.js';
import { useMobDbModal }  from '@/Composables/useMobDbModal.js';

const page = usePage();
const __   = (key) => page.props.translations?.[key] || key;

const { selectedMob, mobDetail, detailLoading, mobTab, closeMobDb } = useMobDbModal();
const { openItemDb } = useItemDbModal();

const fmtRate = (r) => {
    if (!r) return '?';
    if (r >= 10000) return '100%';
    const p = r / 100;
    return (Number.isInteger(p) ? p : parseFloat(p.toFixed(2))) + '%';
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

const statLabels = {
    str: 'STR', agi: 'AGI', vit: 'VIT', int: 'INT', dex: 'DEX', luk: 'LUK',
    attack: 'ATK', attack2: 'ATK2', defense: 'DEF', magic_defense: 'MDEF',
    attack_range: 'Atk Range', skill_range: 'Skill Range', chase_range: 'Chase Range',
};

const displayStats = (stats) => {
    if (!stats) return [];
    const keys = ['str','agi','vit','int','dex','luk','attack','attack2','defense','magic_defense','attack_range','skill_range','chase_range'];
    const result = keys.filter(k => stats[k] !== undefined).map(k => ({ label: statLabels[k], value: stats[k] }));
    if (stats.resistance       > 0) result.push({ label: 'RES',  value: stats.resistance,       renewal: true });
    if (stats.magic_resistance > 0) result.push({ label: 'MRES', value: stats.magic_resistance, renewal: true });
    return result;
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
                class="fixed inset-0 z-[60] flex items-end sm:items-center justify-center sm:p-4 bg-rapanel-navy-900/80 backdrop-blur-sm"
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
                                <span :class="['text-[10px] font-black uppercase px-2 py-0.5 rounded-full border tracking-wide', elementBadge(selectedMob.element)]">
                                    {{ selectedMob.element }}
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
                    <div class="flex shrink-0 bg-white dark:bg-rapanel-navy-900 border-b border-rapanel-navy-100 dark:border-white/10">

                        <button @click="mobTab = 'info'"
                            :class="mobTab === 'info'
                                ? 'bg-rapanel-gold text-rapanel-navy-900'
                                : 'text-rapanel-text-light/60 dark:text-white/50 hover:text-rapanel-navy-900 dark:hover:text-white hover:bg-rapanel-navy-50 dark:hover:bg-white/5'"
                            class="flex-1 flex items-start gap-2.5 px-4 py-2.5 rounded-t-xl text-left transition-all duration-150 min-w-0">
                            <svg class="w-4 h-4 mt-0.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                            <div class="min-w-0">
                                <div class="text-sm font-bold leading-tight truncate">{{ __('Description') }}</div>
                                <div :class="mobTab === 'info' ? 'text-rapanel-navy-900/60' : 'text-rapanel-text-light/40 dark:text-white/30'"
                                     class="text-[10px] mt-0.5 leading-tight truncate">{{ __('Stats, drops and attributes') }}</div>
                            </div>
                        </button>

                        <button @click="mobTab = 'map'"
                            :class="mobTab === 'map'
                                ? 'bg-rapanel-gold text-rapanel-navy-900'
                                : 'text-rapanel-text-light/60 dark:text-white/50 hover:text-rapanel-navy-900 dark:hover:text-white hover:bg-rapanel-navy-50 dark:hover:bg-white/5'"
                            class="flex-1 flex items-start gap-2.5 px-4 py-2.5 rounded-t-xl text-left transition-all duration-150 min-w-0">
                            <svg class="w-4 h-4 mt-0.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                            </svg>
                            <div class="min-w-0">
                                <div class="text-sm font-bold leading-tight truncate">{{ __('Respawn Map') }}</div>
                                <div :class="mobTab === 'map' ? 'text-rapanel-navy-900/60' : 'text-rapanel-text-light/40 dark:text-white/30'"
                                     class="text-[10px] mt-0.5 leading-tight truncate">{{ __('Spawn locations') }}</div>
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
                        <div v-else-if="mobDetail && mobTab === 'info'"
                             class="grid grid-cols-1 md:grid-cols-3 gap-4">

                            <!-- Columna 1: Stats -->
                            <div class="space-y-4">
                                <div v-if="displayStats(mobDetail.stats).length"
                                    class="bg-white dark:bg-rapanel-navy-900 rounded-xl border border-rapanel-navy-100 dark:border-white/10 overflow-hidden">
                                    <div class="flex items-center gap-2 px-4 py-3 border-b border-rapanel-navy-100 dark:border-white/10">
                                        <svg class="w-4 h-4 text-rapanel-gold shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                        </svg>
                                        <span class="font-semibold text-sm text-rapanel-navy-900 dark:text-white">{{ __('Stats') }}</span>
                                    </div>
                                    <div class="divide-y divide-rapanel-navy-100 dark:divide-white/[0.06]">
                                        <div v-for="s in displayStats(mobDetail.stats)" :key="s.label"
                                            class="flex justify-between items-center px-4 py-2.5">
                                            <span class="text-[11px] font-black uppercase tracking-wider text-rapanel-text-light/60 dark:text-white/40 flex items-center gap-1.5">
                                                {{ __(s.label) }}
                                                <span v-if="s.renewal"
                                                    class="text-[7px] font-black uppercase tracking-wide px-1 py-px rounded bg-rapanel-blue/10 text-rapanel-blue border border-rapanel-blue/20">
                                                    RE
                                                </span>
                                            </span>
                                            <span class="text-sm font-bold text-rapanel-navy-900 dark:text-white tabular-nums">{{ s.value }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Columna 2: Info general -->
                            <div class="space-y-4">
                                <div class="bg-white dark:bg-rapanel-navy-900 rounded-xl border border-rapanel-navy-100 dark:border-white/10 overflow-hidden">
                                    <div class="flex items-center gap-2 px-4 py-3 border-b border-rapanel-navy-100 dark:border-white/10">
                                        <svg class="w-4 h-4 text-rapanel-gold shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span class="font-semibold text-sm text-rapanel-navy-900 dark:text-white">{{ __('Information') }}</span>
                                    </div>
                                    <div class="divide-y divide-rapanel-navy-100 dark:divide-white/[0.06]">
                                        <div class="flex justify-between items-center px-4 py-2.5">
                                            <span class="text-[11px] font-black uppercase tracking-wider text-rapanel-text-light/60 dark:text-white/40">{{ __('Level') }}</span>
                                            <span class="text-sm font-bold text-rapanel-navy-900 dark:text-white tabular-nums">{{ mobDetail.level }}</span>
                                        </div>
                                        <div class="flex justify-between items-center px-4 py-2.5">
                                            <span class="text-[11px] font-black uppercase tracking-wider text-rapanel-text-light/60 dark:text-white/40">HP</span>
                                            <span class="text-sm font-bold text-rapanel-navy-900 dark:text-white tabular-nums">{{ mobDetail.hp.toLocaleString() }}</span>
                                        </div>
                                        <div v-if="mobDetail.base_exp" class="flex justify-between items-center px-4 py-2.5">
                                            <span class="text-[11px] font-black uppercase tracking-wider text-rapanel-text-light/60 dark:text-white/40">{{ __('Base EXP') }}</span>
                                            <span class="text-sm font-bold text-rapanel-navy-900 dark:text-white tabular-nums">{{ mobDetail.base_exp.toLocaleString() }}</span>
                                        </div>
                                        <div v-if="mobDetail.stats?.job_exp" class="flex justify-between items-center px-4 py-2.5">
                                            <span class="text-[11px] font-black uppercase tracking-wider text-rapanel-text-light/60 dark:text-white/40">{{ __('Job EXP') }}</span>
                                            <span class="text-sm font-bold text-rapanel-navy-900 dark:text-white tabular-nums">{{ Number(mobDetail.stats.job_exp).toLocaleString() }}</span>
                                        </div>
                                        <div v-if="mobDetail.mvp_exp" class="flex justify-between items-center px-4 py-2.5">
                                            <span class="text-[11px] font-black uppercase tracking-wider text-rapanel-text-light/60 dark:text-white/40">MVP EXP</span>
                                            <span class="text-sm font-bold text-rapanel-gold tabular-nums">{{ mobDetail.mvp_exp.toLocaleString() }}</span>
                                        </div>
                                        <div v-if="mobDetail.size" class="flex justify-between items-center px-4 py-2.5">
                                            <span class="text-[11px] font-black uppercase tracking-wider text-rapanel-text-light/60 dark:text-white/40">{{ __('Size') }}</span>
                                            <span class="text-sm font-bold text-rapanel-navy-900 dark:text-white">{{ mobDetail.size }}</span>
                                        </div>
                                        <div v-if="mobDetail.class" class="flex justify-between items-center px-4 py-2.5">
                                            <span class="text-[11px] font-black uppercase tracking-wider text-rapanel-text-light/60 dark:text-white/40">{{ __('Class') }}</span>
                                            <span class="text-sm font-bold text-rapanel-navy-900 dark:text-white">{{ mobDetail.class }}</span>
                                        </div>
                                        <div v-if="mobDetail.stats?.element_level" class="flex justify-between items-center px-4 py-2.5">
                                            <span class="text-[11px] font-black uppercase tracking-wider text-rapanel-text-light/60 dark:text-white/40">{{ __('Element Lv.') }}</span>
                                            <span class="text-sm font-bold text-rapanel-navy-900 dark:text-white tabular-nums">{{ mobDetail.element }} {{ mobDetail.stats.element_level }}</span>
                                        </div>
                                        <div v-if="mobDetail.stats?.ai !== undefined" class="flex justify-between items-center px-4 py-2.5">
                                            <span class="text-[11px] font-black uppercase tracking-wider text-rapanel-text-light/60 dark:text-white/40">AI</span>
                                            <span class="text-sm font-bold text-rapanel-navy-900 dark:text-white tabular-nums">{{ mobDetail.stats.ai }}</span>
                                        </div>
                                        <div v-if="mobDetail.stats?.walk_speed" class="flex justify-between items-center px-4 py-2.5">
                                            <span class="text-[11px] font-black uppercase tracking-wider text-rapanel-text-light/60 dark:text-white/40">{{ __('Walk Speed') }}</span>
                                            <span class="text-sm font-bold text-rapanel-navy-900 dark:text-white tabular-nums">{{ mobDetail.stats.walk_speed }}</span>
                                        </div>
                                        <div v-if="mobDetail.stats?.attack_delay" class="flex justify-between items-center px-4 py-2.5">
                                            <span class="text-[11px] font-black uppercase tracking-wider text-rapanel-text-light/60 dark:text-white/40">{{ __('Atk Delay') }}</span>
                                            <span class="text-sm font-bold text-rapanel-navy-900 dark:text-white tabular-nums">{{ mobDetail.stats.attack_delay }}</span>
                                        </div>
                                        <div v-if="mobDetail.stats?.attack_motion" class="flex justify-between items-center px-4 py-2.5">
                                            <span class="text-[11px] font-black uppercase tracking-wider text-rapanel-text-light/60 dark:text-white/40">{{ __('Atk Motion') }}</span>
                                            <span class="text-sm font-bold text-rapanel-navy-900 dark:text-white tabular-nums">{{ mobDetail.stats.attack_motion }}</span>
                                        </div>
                                        <div v-if="mobDetail.stats?.damage_motion" class="flex justify-between items-center px-4 py-2.5">
                                            <span class="text-[11px] font-black uppercase tracking-wider text-rapanel-text-light/60 dark:text-white/40">{{ __('Dmg Motion') }}</span>
                                            <span class="text-sm font-bold text-rapanel-navy-900 dark:text-white tabular-nums">{{ mobDetail.stats.damage_motion }}</span>
                                        </div>
                                        <div v-if="mobDetail.stats?.damage_taken !== undefined && mobDetail.stats.damage_taken !== 100" class="flex justify-between items-center px-4 py-2.5">
                                            <span class="text-[11px] font-black uppercase tracking-wider text-rapanel-text-light/60 dark:text-white/40">{{ __('Dmg Taken') }}</span>
                                            <span class="text-sm font-bold text-rapanel-navy-900 dark:text-white tabular-nums">{{ mobDetail.stats.damage_taken }}%</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Columna 3: Drops -->
                            <div class="space-y-4">

                                <div v-if="mobDetail.drops?.length"
                                    class="bg-white dark:bg-rapanel-navy-900 rounded-xl border border-rapanel-navy-100 dark:border-white/10 overflow-hidden">
                                    <div class="flex items-center gap-2 px-4 py-3 border-b border-rapanel-navy-100 dark:border-white/10">
                                        <svg class="w-4 h-4 text-rapanel-gold shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                        </svg>
                                        <span class="font-semibold text-sm text-rapanel-navy-900 dark:text-white">{{ __('Drops') }}</span>
                                        <span class="text-[10px] bg-rapanel-navy-100 dark:bg-white/10 text-rapanel-text-light dark:text-white/50 rounded-full px-1.5 py-0.5 font-bold">{{ mobDetail.drops.length }}</span>
                                    </div>
                                    <div class="divide-y divide-rapanel-navy-100 dark:divide-white/[0.06]">
                                        <button v-for="drop in mobDetail.drops" :key="drop.item"
                                            :disabled="!drop.item_data"
                                            @click="drop.item_data && openItemDb(drop.item_data.item_id, drop.item_data)"
                                            :class="['w-full flex items-center gap-3 px-4 py-2.5 text-left transition',
                                                     drop.item_data ? 'hover:bg-rapanel-navy-50 dark:hover:bg-white/5 cursor-pointer' : 'cursor-default']">
                                            <div class="shrink-0 w-7 h-7 rounded-lg bg-rapanel-navy-100 dark:bg-rapanel-navy-800 flex items-center justify-center overflow-hidden">
                                                <img v-if="drop.item_data"
                                                    :src="`/data/items/icons/${drop.item_data.item_id}.png`"
                                                    :alt="drop.item_data.display_name || drop.item_data.name"
                                                    class="object-contain"
                                                    @error="$event.target.style.display='none'" />
                                                <svg v-else class="w-3.5 h-3.5 text-rapanel-text-light/30 dark:text-white/20" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                                </svg>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm text-rapanel-navy-900 dark:text-white truncate">
                                                    {{ drop.item_data?.display_name || drop.item_data?.name || drop.item }}
                                                </p>
                                                <div class="flex items-center gap-1.5 mt-0.5">
                                                    <p v-if="drop.item_data" class="text-[10px] text-rapanel-text-light/50 dark:text-white/35 font-mono">{{ drop.item }}</p>
                                                    <span v-if="drop.nosteal"
                                                        class="text-[8px] font-black uppercase px-1 py-px rounded bg-rapanel-danger/10 text-rapanel-danger border border-rapanel-danger/20">
                                                        {{ __('No Steal') }}
                                                    </span>
                                                </div>
                                            </div>
                                            <span :class="[
                                                'text-sm font-bold tabular-nums shrink-0',
                                                drop.rate >= 5000 ? 'text-rapanel-success' : drop.rate >= 500 ? 'text-rapanel-gold' : 'text-rapanel-text-light dark:text-white/60'
                                            ]">{{ fmtRate(drop.rate) }}</span>
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
                                    <div class="divide-y divide-rapanel-navy-100 dark:divide-white/[0.06]">
                                        <button v-for="drop in mobDetail.mvp_drops" :key="drop.item"
                                            :disabled="!drop.item_data"
                                            @click="drop.item_data && openItemDb(drop.item_data.item_id, drop.item_data)"
                                            :class="['w-full flex items-center gap-3 px-4 py-2.5 text-left transition',
                                                     drop.item_data ? 'hover:bg-rapanel-navy-50 dark:hover:bg-white/5 cursor-pointer' : 'cursor-default']">
                                            <div class="shrink-0 w-7 h-7 rounded-lg bg-rapanel-navy-100 dark:bg-rapanel-navy-800 flex items-center justify-center overflow-hidden">
                                                <img v-if="drop.item_data"
                                                    :src="`/data/items/icons/${drop.item_data.item_id}.png`"
                                                    :alt="drop.item_data.display_name || drop.item_data.name"
                                                    class="object-contain"
                                                    @error="$event.target.style.display='none'" />
                                                <svg v-else class="w-3.5 h-3.5 text-white/20" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                                </svg>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm text-rapanel-navy-900 dark:text-white truncate">
                                                    {{ drop.item_data?.display_name || drop.item_data?.name || drop.item }}
                                                </p>
                                                <p v-if="drop.item_data" class="text-[10px] text-rapanel-text-light/50 dark:text-white/35 font-mono">{{ drop.item }}</p>
                                            </div>
                                            <span :class="[
                                                'text-sm font-bold tabular-nums shrink-0',
                                                drop.rate >= 5000 ? 'text-rapanel-success' : drop.rate >= 500 ? 'text-rapanel-gold' : 'text-rapanel-text-light dark:text-white/60'
                                            ]">{{ fmtRate(drop.rate) }}</span>
                                        </button>
                                    </div>
                                </div>

                                <div v-if="!mobDetail.drops?.length && !mobDetail.mvp_drops?.length"
                                    class="flex flex-col items-center justify-center py-10 text-center gap-2">
                                    <p class="text-rapanel-text-light/40 dark:text-white/30 text-sm">{{ __('No drops data') }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- ── Tab: Mapa de Respawn ── -->
                        <div v-else-if="mobTab === 'map'"
                             class="flex flex-col items-center justify-center py-16 text-center gap-4">
                            <div class="w-16 h-16 rounded-2xl bg-rapanel-navy-100 dark:bg-white/5 flex items-center justify-center">
                                <svg class="w-8 h-8 text-rapanel-navy-300 dark:text-white/20" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-rapanel-navy-900 dark:text-white font-semibold text-sm">{{ __('Coming soon') }}</p>
                                <p class="text-rapanel-text-light/50 dark:text-white/30 text-xs mt-1">Los datos de spawn todavía no están disponibles.</p>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>

</template>

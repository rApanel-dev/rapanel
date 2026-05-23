<script setup>
import { usePage } from '@inertiajs/vue3';
import { getJobName, isCartClass, formatNum, formatMap, onImgError, itemLabel } from '@/Composables/useRoHelpers';
import ActionButton from '@/Components/ActionButton.vue';
import { useItemDbModal } from '@/Composables/useItemDbModal';

const props = defineProps({
    char:      { type: Object, default: null },
    cardNames: { type: Object, default: () => ({}) },
});

const emit = defineEmits(['close', 'reset-look', 'reset-position', 'open-preferences', 'change-slot']);

const page = usePage();
const __ = (key) => page.props.translations?.[key] || key;

const getCardName = (id) => {
    if (!id || id <= 0 || id === 254 || id === 255) return null;
    return props.cardNames?.[id] || null;
};

const barPct = (cur, max) => max > 0 ? Math.min(100, Math.round(cur / max * 100)) : 0;

const { openItemDb } = useItemDbModal();
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="ease-out duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="ease-in duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="char" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-rapanel-navy-900/80 backdrop-blur-sm">
                <div class="relative w-full max-w-7xl max-h-[92vh] overflow-y-auto bg-white dark:bg-rapanel-navy-900 rounded-xl shadow-2xl ring-1 ring-rapanel-navy-100 dark:ring-white/10">

                    <!-- Header -->
                    <div class="sticky top-0 z-10 px-6 py-4 bg-white dark:bg-rapanel-navy-900 border-b border-rapanel-navy-100 dark:border-white/10 rounded-t-xl">
                        <!-- Fila superior: título + botones desktop / título + cerrar móvil -->
                        <div class="flex items-center justify-between gap-2">
                            <div>
                                <p class="text-[10px] uppercase tracking-widest font-bold text-rapanel-text-light/40 dark:text-rapanel-text-dark/40">{{ __('Viewing Character') }}</p>
                                <h2 class="text-lg font-display font-bold text-rapanel-blue uppercase tracking-wide">{{ char.name }}</h2>
                            </div>
                            <!-- Desktop: todos los botones en línea (orden: gold, blue, purple, navy) -->
                            <div class="hidden sm:flex items-center gap-2">
                                <ActionButton variant="gold" @click="emit('open-preferences', char)">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    {{ __('Character Preferences') }}
                                </ActionButton>
                                <ActionButton variant="blue" @click="emit('change-slot', char)" :disabled="char.online > 0">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5"/></svg>
                                    {{ __('Change Slot') }}
                                </ActionButton>
                                <ActionButton variant="purple" @click="emit('reset-look', char)" :disabled="char.online > 0">
                                    {{ __('Reset Look') }}
                                </ActionButton>
                                <ActionButton variant="navy" @click="emit('reset-position', char)" :disabled="char.online > 0">
                                    {{ __('Reset Position') }}
                                </ActionButton>
                                <button @click="emit('close')" class="w-8 h-8 flex items-center justify-center rounded-lg bg-rapanel-navy-100 dark:bg-gray-700 hover:bg-rapanel-danger hover:text-white transition-all">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                            <!-- Móvil: solo cerrar -->
                            <button @click="emit('close')" class="sm:hidden w-8 h-8 flex items-center justify-center rounded-lg bg-rapanel-navy-100 dark:bg-gray-700 hover:bg-rapanel-danger hover:text-white transition-all shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <!-- Móvil: 4 botones en 2 columnas (orden: gold, blue, purple, navy) -->
                        <div class="grid grid-cols-2 gap-2 mt-3 sm:hidden">
                            <ActionButton variant="gold" @click="emit('open-preferences', char)" class="justify-center py-2">
                                {{ __('Character Preferences') }}
                            </ActionButton>
                            <ActionButton variant="blue" @click="emit('change-slot', char)" :disabled="char.online > 0" class="justify-center py-2">
                                {{ __('Change Slot') }}
                            </ActionButton>
                            <ActionButton variant="purple" @click="emit('reset-look', char)" :disabled="char.online > 0" class="justify-center py-2">
                                {{ __('Reset Look') }}
                            </ActionButton>
                            <ActionButton variant="navy" @click="emit('reset-position', char)" :disabled="char.online > 0" class="justify-center py-2">
                                {{ __('Reset Position') }}
                            </ActionButton>
                        </div>
                    </div>

                    <div class="p-6 space-y-6">

                        <!-- ===== CHARACTER INFORMATION ===== -->
                        <section>
                            <h3 class="text-base font-display font-bold uppercase tracking-widest text-rapanel-text-light dark:text-rapanel-text-dark mb-4">
                                {{ __('Character Information for') }} <span class="text-rapanel-blue">{{ char.name }}</span>
                            </h3>

                            <div class="space-y-3">

                                <!-- 1. PERFIL E IDENTIFICACIÓN -->
                                <div class="bg-rapanel-navy-100/70 dark:bg-rapanel-navy-800 rounded-xl p-4 border border-rapanel-navy-100 dark:border-white/10">
                                    <p class="text-[9px] uppercase tracking-widest font-extrabold text-rapanel-text-light/30 dark:text-rapanel-text-dark/30 mb-3">{{ __('Profile & Identification') }}</p>
                                    <div class="flex flex-col sm:flex-row sm:items-center gap-3 flex-wrap">
                                        <!-- Job Icon + Name + Class -->
                                        <div class="flex items-center gap-3 flex-1 min-w-0">
                                            <img :src="`/data/gameaccount/job_icons/icon_jobs_${char.class ?? 0}.png`" @error="onImgError" class="w-12 h-12 object-contain shrink-0" :alt="getJobName(char.class ?? 0)" />
                                            <div class="min-w-0">
                                                <h4 class="font-display font-bold text-rapanel-blue text-base leading-tight truncate">{{ char.name }}</h4>
                                                <p class="text-sm text-rapanel-navy-900 dark:text-white font-medium truncate">{{ getJobName(char.class ?? 0) }}</p>
                                            </div>
                                        </div>
                                        <!-- Badges: ID → Slot → Status -->
                                        <div class="flex flex-wrap items-center gap-2 shrink-0">
                                            <!-- ID badge (gold) -->
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold border bg-rapanel-gold/10 text-rapanel-gold border-rapanel-gold/20">
                                                <span class="text-[9px] font-extrabold opacity-60 uppercase tracking-wider">ID</span>
                                                <span class="font-mono">{{ char.char_id }}</span>
                                            </span>
                                            <!-- Slot badge (violet) -->
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold border bg-rapanel-purple/10 text-rapanel-purple border-rapanel-purple/20">
                                                <span class="text-[9px] font-extrabold opacity-60 uppercase tracking-wider">{{ __('Slot') }}</span>
                                                <span>{{ (char.char_num ?? 0) + 1 }}</span>
                                            </span>
                                            <!-- Status badge (green / gray) -->
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold border"
                                                :class="char.online > 0
                                                    ? 'bg-rapanel-success/10 text-rapanel-success border-rapanel-success/20'
                                                    : 'bg-gray-400/10 text-gray-400 border-gray-400/20'"
                                            >
                                                <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="char.online > 0 ? 'bg-rapanel-success animate-pulse' : 'bg-gray-400'"></span>
                                                {{ char.online > 0 ? __('Online') : __('Offline') }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- 2 + 3. VITALIDAD Y COMBATE (lado a lado en desktop) -->
                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">

                                <!-- 2. VITALIDAD Y PROGRESO -->
                                <div class="bg-rapanel-navy-100/70 dark:bg-rapanel-navy-800 rounded-xl p-4 border border-rapanel-navy-100 dark:border-white/10">
                                    <p class="text-[9px] uppercase tracking-widest font-extrabold text-rapanel-text-light/30 dark:text-rapanel-text-dark/30 mb-3">{{ __('Vitality & Progress') }}</p>
                                    <!-- HP + SP bars -->
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-4">
                                        <!-- HP -->
                                        <div>
                                            <div class="flex justify-between items-center mb-1">
                                                <span class="text-[9px] uppercase tracking-widest font-extrabold text-rapanel-danger/70">HP</span>
                                                <span class="text-[10px] font-semibold text-rapanel-navy-900 dark:text-white font-mono">{{ formatNum(char.hp) }} / {{ formatNum(char.max_hp) }}</span>
                                            </div>
                                            <div class="w-full bg-rapanel-navy-100 dark:bg-gray-700 rounded-full h-2">
                                                <div class="bg-rapanel-danger h-2 rounded-full transition-all duration-500"
                                                    :style="{ width: barPct(char.hp, char.max_hp) + '%' }"
                                                ></div>
                                            </div>
                                        </div>
                                        <!-- SP -->
                                        <div>
                                            <div class="flex justify-between items-center mb-1">
                                                <span class="text-[9px] uppercase tracking-widest font-extrabold text-rapanel-blue/70">SP</span>
                                                <span class="text-[10px] font-semibold text-rapanel-navy-900 dark:text-white font-mono">{{ formatNum(char.sp) }} / {{ formatNum(char.max_sp) }}</span>
                                            </div>
                                            <div class="w-full bg-rapanel-navy-100 dark:bg-gray-700 rounded-full h-2">
                                                <div class="bg-rapanel-blue h-2 rounded-full transition-all duration-500"
                                                    :style="{ width: barPct(char.sp, char.max_sp) + '%' }"
                                                ></div>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Levels + EXP + Deaths -->
                                    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
                                        <div v-for="item in [
                                            { label: __('Base Lv'),  value: char.base_level,            highlight: true },
                                            { label: __('Job Lv'),   value: char.job_level,             highlight: true },
                                            { label: __('Base EXP'), value: formatNum(char.base_exp),   highlight: false },
                                            { label: __('Job EXP'),  value: formatNum(char.job_exp),    highlight: false },
                                            { label: __('Deaths'),   value: formatNum(char.death_count), danger: true },
                                        ]" :key="item.label" class="flex flex-col gap-0.5">
                                            <span class="text-[9px] uppercase tracking-widest font-extrabold text-rapanel-text-light/40 dark:text-rapanel-text-dark/40">{{ item.label }}</span>
                                            <span class="font-bold text-xs leading-tight"
                                                :class="item.danger ? 'text-rapanel-danger' : item.highlight ? 'text-rapanel-navy-900 dark:text-white text-sm' : 'text-rapanel-text-light/70 dark:text-rapanel-text-dark/70'"
                                            >{{ item.value }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- 3. ESTADÍSTICAS DE COMBATE -->
                                <div class="bg-rapanel-navy-100/70 dark:bg-rapanel-navy-800 rounded-xl p-4 border border-rapanel-navy-100 dark:border-white/10 flex flex-col">
                                    <p class="text-[9px] uppercase tracking-widest font-extrabold text-rapanel-text-light/30 dark:text-rapanel-text-dark/30 mb-3">{{ __('Combat Statistics') }}</p>
                                    <!-- Stats base: 6 columnas -->
                                    <div class="grid grid-cols-6 gap-2 mb-3">
                                        <div v-for="stat in [
                                            { l: 'STR', v: char.str },
                                            { l: 'AGI', v: char.agi },
                                            { l: 'VIT', v: char.vit },
                                            { l: 'INT', v: char.int },
                                            { l: 'DEX', v: char.dex },
                                            { l: 'LUK', v: char.luk },
                                        ]" :key="stat.l"
                                            class="flex flex-col items-center gap-0.5 bg-white dark:bg-rapanel-navy-800 rounded-lg px-2 py-2.5 border border-rapanel-navy-100 dark:border-gray-700/40 text-center"
                                        >
                                            <span class="text-[9px] uppercase tracking-widest font-extrabold text-rapanel-text-light/40 dark:text-rapanel-text-dark/40">{{ stat.l }}</span>
                                            <span class="font-bold text-rapanel-navy-900 dark:text-white text-base">{{ stat.v ?? 0 }}</span>
                                        </div>
                                    </div>
                                    <!-- Status Points + Skill Points -->
                                    <div class="grid grid-cols-2 gap-2 mt-auto">
                                        <div class="flex items-center justify-between bg-white dark:bg-rapanel-navy-800 rounded-lg px-3 py-2 border border-rapanel-navy-100 dark:border-gray-700/40">
                                            <span class="text-[9px] uppercase tracking-widest font-extrabold text-rapanel-text-light/40 dark:text-rapanel-text-dark/40">{{ __('Status Points') }}</span>
                                            <span class="font-bold text-rapanel-gold text-sm">{{ char.status_point ?? 0 }}</span>
                                        </div>
                                        <div class="flex items-center justify-between bg-white dark:bg-rapanel-navy-800 rounded-lg px-3 py-2 border border-rapanel-navy-100 dark:border-gray-700/40">
                                            <span class="text-[9px] uppercase tracking-widest font-extrabold text-rapanel-text-light/40 dark:text-rapanel-text-dark/40">{{ __('Skill Points') }}</span>
                                            <span class="font-bold text-rapanel-gold text-sm">{{ char.skill_point ?? 0 }}</span>
                                        </div>
                                    </div>
                                </div>

                                </div><!-- end grid 2+3 -->

                                <!-- 4 + 5. MUNDO Y SOCIAL (lado a lado en desktop) -->
                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">

                                <!-- 4. MUNDO Y ECONOMÍA -->
                                <div class="bg-rapanel-navy-100/70 dark:bg-rapanel-navy-800 rounded-xl p-4 border border-rapanel-navy-100 dark:border-white/10">
                                    <p class="text-[9px] uppercase tracking-widest font-extrabold text-rapanel-text-light/30 dark:text-rapanel-text-dark/30 mb-3">{{ __('World & Economy') }}</p>
                                    <div class="flex flex-col gap-2">

                                        <!-- Zeny -->
                                        <div class="flex items-center gap-3 rounded-xl px-4 py-3 bg-rapanel-gold/20 dark:bg-rapanel-gold/10 border border-rapanel-gold/40 dark:border-rapanel-gold/30">
                                            <div class="w-8 h-8 flex items-center justify-center rounded-lg bg-rapanel-gold/20 shrink-0">
                                                <img src="/data/gameaccount/bank.png" class="w-6 h-6 object-contain" alt="" />
                                            </div>
                                            <div class="min-w-0">
                                                <p class="text-[9px] uppercase tracking-widest font-extrabold text-rapanel-gold/70">{{ __('Zeny') }}</p>
                                                <p class="font-bold text-sm text-rapanel-gold truncate">{{ formatNum(char.zeny) }} z</p>
                                            </div>
                                        </div>

                                        <!-- Last Map + Position -->
                                        <div class="flex items-center gap-3 rounded-xl px-4 py-3 bg-rapanel-blue/20 dark:bg-rapanel-blue/10 border border-rapanel-blue/40 dark:border-rapanel-blue/30">
                                            <div class="w-8 h-8 flex items-center justify-center rounded-lg bg-rapanel-blue/20 shrink-0">
                                                <img src="/data/gameaccount/lastmap.png" class="w-6 h-6 object-contain" alt="" />
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <p class="text-[9px] uppercase tracking-widest font-extrabold text-rapanel-blue/70">{{ __('Last Map') }}</p>
                                                <p class="font-bold text-sm text-rapanel-blue truncate">{{ formatMap(char.last_map) }}</p>
                                            </div>
                                            <div class="text-right shrink-0">
                                                <p class="text-[9px] uppercase tracking-widest font-extrabold text-rapanel-blue/60">{{ __('Position') }}</p>
                                                <p class="font-mono font-semibold text-sm text-rapanel-blue">({{ char.last_x }}, {{ char.last_y }})</p>
                                            </div>
                                        </div>

                                        <!-- Save Map + Save Position -->
                                        <div class="flex items-center gap-3 rounded-xl px-4 py-3 bg-rapanel-success/20 dark:bg-rapanel-success/10 border border-rapanel-success/40 dark:border-rapanel-success/30">
                                            <div class="w-8 h-8 flex items-center justify-center rounded-lg bg-rapanel-success/20 shrink-0">
                                                <img src="/data/gameaccount/savemap.png" class="w-6 h-6 object-contain" alt="" />
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <p class="text-[9px] uppercase tracking-widest font-extrabold text-rapanel-success/70">{{ __('Save Map') }}</p>
                                                <p class="font-bold text-sm text-rapanel-success truncate">{{ formatMap(char.save_map) }}</p>
                                            </div>
                                            <div class="text-right shrink-0">
                                                <p class="text-[9px] uppercase tracking-widest font-extrabold text-rapanel-success/60">{{ __('Position') }}</p>
                                                <p class="font-mono font-semibold text-sm text-rapanel-success">({{ char.save_x }}, {{ char.save_y }})</p>
                                            </div>
                                        </div>

                                    </div>
                                </div>

                                <!-- 5. SOCIAL Y COMUNIDAD -->
                                <div class="bg-rapanel-navy-100/70 dark:bg-rapanel-navy-800 rounded-xl p-4 border border-rapanel-navy-100 dark:border-white/10">
                                    <p class="text-[9px] uppercase tracking-widest font-extrabold text-rapanel-text-light/30 dark:text-rapanel-text-dark/30 mb-3">{{ __('Social & Community') }}</p>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">

                                        <!-- Sub-tarjeta Guild -->
                                        <div class="rounded-xl border border-rapanel-blue/25 overflow-hidden flex flex-col">
                                            <div class="flex items-center gap-2 px-3 py-2 bg-rapanel-blue/10 border-b border-rapanel-blue/20">
                                                <img v-if="char.guild_id" :src="`/guild-emblem/${char.guild_id}`" @error="onImgError" class="w-5 h-5 object-contain shrink-0" alt="" />
                                                <span v-else class="w-2 h-2 rounded-full bg-rapanel-blue shrink-0"></span>
                                                <span class="text-[9px] uppercase tracking-widest font-extrabold text-rapanel-blue">{{ __('Guild') }}</span>
                                            </div>
                                            <div class="divide-y divide-rapanel-navy-100 dark:divide-white/10 bg-white dark:bg-rapanel-navy-900 flex-1">
                                                <div v-for="item in [
                                                    { label: __('Guild Name'),     value: char.guild_name     || __('None') },
                                                    { label: __('Guild Level'),    value: char.guild_level    ?? '—' },
                                                    { label: __('Guild Position'), value: char.guild_position || __('None') },
                                                    { label: __('Tax Level'),      value: (char.guild_tax ?? 0) + '%' },
                                                ]" :key="item.label" class="flex items-center justify-between gap-2 px-3 py-2">
                                                    <span class="text-[9px] uppercase tracking-widest font-extrabold text-rapanel-text-light/40 dark:text-rapanel-text-dark/40 shrink-0">{{ item.label }}</span>
                                                    <span class="font-semibold text-rapanel-navy-900 dark:text-white text-xs text-right truncate">{{ item.value }}</span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Sub-tarjeta Family -->
                                        <div class="rounded-xl border border-rapanel-purple/25 overflow-hidden flex flex-col">
                                            <div class="flex items-center gap-2 px-3 py-2 bg-rapanel-purple/10 border-b border-rapanel-purple/20">
                                                <span class="w-2 h-2 rounded-full bg-rapanel-purple shrink-0"></span>
                                                <span class="text-[9px] uppercase tracking-widest font-extrabold text-rapanel-purple">{{ __('Family') }}</span>
                                            </div>
                                            <div class="divide-y divide-rapanel-navy-100 dark:divide-white/10 bg-white dark:bg-rapanel-navy-900 flex-1">
                                                <div v-for="item in [
                                                    { label: __('Partner'), value: char.partner_name || __('None') },
                                                    { label: __('Child'),   value: char.child_name   || __('None') },
                                                    { label: __('Mother'),  value: char.mother_name  || __('None') },
                                                    { label: __('Father'),  value: char.father_name  || __('None') },
                                                ]" :key="item.label" class="flex items-center justify-between gap-2 px-3 py-2">
                                                    <span class="text-[9px] uppercase tracking-widest font-extrabold text-rapanel-text-light/40 dark:text-rapanel-text-dark/40 shrink-0">{{ item.label }}</span>
                                                    <span class="font-semibold text-rapanel-navy-900 dark:text-white text-xs text-right truncate">{{ item.value }}</span>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </div>

                                </div><!-- end grid 4+5 -->

                            </div>
                        </section>

                        <!-- ===== PARTY MEMBERS + FRIENDS (lado a lado) ===== -->
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

                            <!-- OTHER PARTY MEMBERS -->
                            <section v-if="char.party_name && char.party_members?.length > 0">
                                <h3 class="text-base font-display font-bold uppercase tracking-widest text-rapanel-text-light dark:text-rapanel-text-dark mb-3">
                                    {{ __('Other Party Members of') }} <span class="text-rapanel-blue uppercase">{{ char.party_name }}</span>
                                </h3>
                                <div class="rounded-xl overflow-hidden border border-rapanel-navy-100 dark:border-white/10">
                                    <table class="w-full text-xs text-left">
                                        <thead class="bg-rapanel-navy-100/70 dark:bg-rapanel-navy-800 text-[10px] uppercase tracking-widest font-bold text-rapanel-text-light/50 dark:text-rapanel-text-dark/50">
                                            <tr>
                                                <th class="px-3 py-2">{{ __('Character Name') }}</th>
                                                <th class="px-3 py-2">{{ __('Job Class') }}</th>
                                                <th class="px-3 py-2 text-center">{{ __('Base Lv') }}</th>
                                                <th class="px-3 py-2 text-center">{{ __('Job Lv') }}</th>
                                                <th class="px-3 py-2 text-center">{{ __('Status') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-rapanel-navy-100/50 dark:divide-gray-700/30">
                                            <tr v-for="m in char.party_members" :key="m.char_id" class="bg-white dark:bg-rapanel-navy-900 hover:bg-rapanel-navy-100/70 dark:hover:bg-rapanel-navy-800">
                                                <td class="px-3 py-2 font-medium text-rapanel-navy-900 dark:text-white">{{ m.name }}</td>
                                                <td class="px-3 py-2 text-rapanel-text-light/60 dark:text-rapanel-text-dark/60">
                                                    <div class="flex items-center gap-2">
                                                        <div class="relative shrink-0">
                                                            <img :src="`/data/gameaccount/job_icons/icon_jobs_${m.class}.png`" @error="onImgError" class="w-5 h-5 object-contain block" :alt="getJobName(m.class)" />
                                                            <img v-if="m.name === char.party_leader_name"
                                                                src="/data/gameaccount/job_icons/ico_partyCrown.png"
                                                                class="absolute -top-1.5 left-1/2 -translate-x-1/2 w-3.5 h-3.5 object-contain"
                                                                alt="Leader"
                                                            />
                                                        </div>
                                                        <span>{{ getJobName(m.class) }}</span>
                                                    </div>
                                                </td>
                                                <td class="px-3 py-2 text-center font-bold text-rapanel-navy-900 dark:text-white">{{ m.base_level }}</td>
                                                <td class="px-3 py-2 text-center font-bold text-rapanel-navy-900 dark:text-white">{{ m.job_level }}</td>
                                                <td class="px-3 py-2 text-center">
                                                    <span :class="m.online > 0 ? 'text-rapanel-success' : 'text-gray-400'" class="font-bold text-[10px]">{{ m.online > 0 ? __('Online') : __('Offline') }}</span>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </section>

                            <!-- FRIENDS -->
                            <section>
                                <h3 class="text-base font-display font-bold uppercase tracking-widest text-rapanel-text-light dark:text-rapanel-text-dark mb-3">
                                    {{ __('Friends of') }} <span class="text-rapanel-blue uppercase">{{ char.name }}</span>
                                    <span class="ml-1 font-normal">({{ char.friends?.length ?? 0 }})</span>
                                </h3>
                                <div v-if="!char.friends?.length" class="text-xs italic text-rapanel-text-light/30 dark:text-rapanel-text-dark/30 py-4">
                                    {{ char.name }} {{ __('has no friends.') }}
                                </div>
                                <div v-else class="rounded-xl overflow-hidden border border-rapanel-navy-100 dark:border-white/10">
                                    <table class="w-full text-xs text-left">
                                        <thead class="bg-rapanel-navy-100/70 dark:bg-rapanel-navy-800 text-[10px] uppercase tracking-widest font-bold text-rapanel-text-light/50 dark:text-rapanel-text-dark/50">
                                            <tr>
                                                <th class="px-3 py-2">{{ __('Character Name') }}</th>
                                                <th class="px-3 py-2">{{ __('Job Class') }}</th>
                                                <th class="px-3 py-2 text-center">{{ __('Base Lv') }}</th>
                                                <th class="px-3 py-2 text-center">{{ __('Job Lv') }}</th>
                                                <th class="px-3 py-2 text-center">{{ __('Status') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-rapanel-navy-100/50 dark:divide-gray-700/30">
                                            <tr v-for="f in char.friends" :key="f.char_id" class="bg-white dark:bg-rapanel-navy-900 hover:bg-rapanel-navy-100/70 dark:hover:bg-rapanel-navy-800">
                                                <td class="px-3 py-2 font-medium text-rapanel-navy-900 dark:text-white">{{ f.name }}</td>
                                                <td class="px-3 py-2 text-rapanel-text-light/60 dark:text-rapanel-text-dark/60">
                                                    <div class="flex items-center gap-1.5">
                                                        <img :src="`/data/gameaccount/job_icons/icon_jobs_${f.class}.png`" @error="onImgError" class="w-5 h-5 object-contain shrink-0" :alt="getJobName(f.class)" />
                                                        <span>{{ getJobName(f.class) }}</span>
                                                    </div>
                                                </td>
                                                <td class="px-3 py-2 text-center font-bold text-rapanel-navy-900 dark:text-white">{{ f.base_level }}</td>
                                                <td class="px-3 py-2 text-center font-bold text-rapanel-navy-900 dark:text-white">{{ f.job_level }}</td>
                                                <td class="px-3 py-2 text-center">
                                                    <span :class="f.online > 0 ? 'text-rapanel-success' : 'text-gray-400'" class="font-bold text-[10px]">{{ f.online > 0 ? __('Online') : __('Offline') }}</span>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </section>

                        </div><!-- end grid party+friends -->

                        <!-- ===== INVENTORY ITEMS ===== -->
                        <section>
                            <h3 class="text-base font-display font-bold uppercase tracking-widest text-rapanel-text-light dark:text-rapanel-text-dark mb-3">
                                {{ __('Inventory Items of') }} <span class="text-rapanel-blue uppercase">{{ char.name }}</span>
                                <span class="ml-1 font-normal">({{ char.inventory?.length ?? 0 }})</span>
                            </h3>
                            <div v-if="!char.inventory?.length" class="text-xs italic text-rapanel-text-light/30 dark:text-rapanel-text-dark/30 py-4 text-center">{{ __('No items found.') }}</div>
                            <div v-else class="rounded-xl overflow-hidden border border-rapanel-navy-100 dark:border-white/10 overflow-x-auto">
                                <table class="w-full text-xs text-left">
                                    <thead class="bg-rapanel-navy-100/70 dark:bg-rapanel-navy-800 text-[10px] uppercase tracking-widest font-bold text-rapanel-text-light/50 dark:text-rapanel-text-dark/50">
                                        <tr>
                                            <th class="px-2 py-2 text-center">ID</th>
                                            <th class="px-2 py-2 w-8">{{ __('Icon') }}</th>
                                            <th class="px-3 py-2">{{ __('Item Name') }}</th>
                                            <th class="px-3 py-2 text-center">{{ __('Amount') }}</th>
                                            <th class="px-3 py-2 text-center">{{ __('Identified') }}</th>
                                            <th class="px-3 py-2 text-center">{{ __('Slot 1') }}</th>
                                            <th class="px-3 py-2 text-center">{{ __('Slot 2') }}</th>
                                            <th class="px-3 py-2 text-center">{{ __('Slot 3') }}</th>
                                            <th class="px-3 py-2 text-center">{{ __('Slot 4') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-rapanel-navy-100/50 dark:divide-gray-700/30">
                                        <tr v-for="(item, idx) in char.inventory" :key="idx"
                                            class="bg-white dark:bg-rapanel-navy-900 hover:bg-rapanel-navy-100/70 dark:hover:bg-rapanel-navy-800"
                                            :class="{ 'bg-rapanel-blue/5': item.equip > 0 }"
                                        >
                                            <td class="px-2 py-1.5 text-center font-mono text-[10px] text-rapanel-text-light/50 dark:text-rapanel-text-dark/40 cursor-pointer hover:text-rapanel-blue"
                                                @click="openItemDb(item.nameid, item)">{{ item.nameid }}</td>
                                            <td class="px-2 py-1.5 text-center cursor-pointer"
                                                @click="openItemDb(item.nameid, item)">
                                                <img :src="`/data/items/icons/${item.nameid}.png`" @error="onImgError" class="w-7 h-7 object-contain mx-auto hover:scale-110 transition-transform" />
                                            </td>
                                            <td class="px-3 py-1.5 cursor-pointer"
                                                @click="openItemDb(item.nameid, item)">
                                                <span v-if="item.refine > 0" class="font-bold text-rapanel-danger dark:text-rapanel-gold mr-1">+{{ item.refine }}</span>
                                                <span class="font-medium text-rapanel-navy-900 dark:text-white hover:text-rapanel-blue dark:hover:text-rapanel-blue transition-colors">{{ itemLabel(item) }}</span>
                                                <span v-if="item.equip > 0" class="ml-1.5 inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold bg-rapanel-blue/10 text-rapanel-blue border border-rapanel-blue/20">{{ __('Equipped') }}</span>
                                                <span v-if="item.attribute > 0" class="ml-1 text-rapanel-danger font-bold text-[10px]">[{{ __('Broken') }}]</span>
                                            </td>
                                            <td class="px-3 py-1.5 text-center text-rapanel-text-light/60 dark:text-rapanel-text-dark/60">{{ item.amount }}</td>
                                            <td class="px-3 py-1.5 text-center">
                                                <span :class="item.identify ? 'text-rapanel-success' : 'text-rapanel-danger'" class="font-bold">{{ item.identify ? __('Yes') : __('No') }}</span>
                                            </td>
                                            <td v-for="slot in ['card0','card1','card2','card3']" :key="slot" class="px-3 py-1.5 text-center text-rapanel-text-light/40 dark:text-rapanel-text-dark/30 italic">
                                                <span v-if="getCardName(item[slot])"
                                                    class="text-rapanel-blue not-italic font-medium cursor-pointer hover:underline"
                                                    @click="openItemDb(item[slot], { nameid: item[slot] })">{{ getCardName(item[slot]) }}</span>
                                                <span v-else>{{ __('None') }}</span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </section>

                        <!-- ===== CART INVENTORY ===== -->
                        <section v-if="isCartClass(char.class)">
                            <h3 class="text-base font-display font-bold uppercase tracking-widest text-rapanel-text-light dark:text-rapanel-text-dark mb-3">
                                {{ __('Cart Inventory Items of') }} <span class="text-rapanel-gold uppercase">{{ char.name }}</span>
                                <span class="ml-1 font-normal">({{ char.cart_inventory?.length ?? 0 }})</span>
                            </h3>
                            <div v-if="!char.cart_inventory?.length" class="text-xs italic text-rapanel-text-light/30 dark:text-rapanel-text-dark/30 py-4 text-center">{{ __('No cart items found.') }}</div>
                            <div v-else class="rounded-xl overflow-hidden border border-rapanel-navy-100 dark:border-white/10 overflow-x-auto">
                                <table class="w-full text-xs text-left">
                                    <thead class="bg-rapanel-navy-100/70 dark:bg-rapanel-navy-800 text-[10px] uppercase tracking-widest font-bold text-rapanel-text-light/50 dark:text-rapanel-text-dark/50">
                                        <tr>
                                            <th class="px-2 py-2 text-center">ID</th>
                                            <th class="px-2 py-2 w-8">{{ __('Icon') }}</th>
                                            <th class="px-3 py-2">{{ __('Item Name') }}</th>
                                            <th class="px-3 py-2 text-center">{{ __('Amount') }}</th>
                                            <th class="px-3 py-2 text-center">{{ __('Identified') }}</th>
                                            <th class="px-3 py-2 text-center">{{ __('Slot 1') }}</th>
                                            <th class="px-3 py-2 text-center">{{ __('Slot 2') }}</th>
                                            <th class="px-3 py-2 text-center">{{ __('Slot 3') }}</th>
                                            <th class="px-3 py-2 text-center">{{ __('Slot 4') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-rapanel-navy-100/50 dark:divide-gray-700/30">
                                        <tr v-for="(item, idx) in char.cart_inventory" :key="idx"
                                            class="bg-white dark:bg-rapanel-navy-900 hover:bg-rapanel-navy-100/70 dark:hover:bg-rapanel-navy-800"
                                        >
                                            <td class="px-2 py-1.5 text-center font-mono text-[10px] text-rapanel-text-light/50 dark:text-rapanel-text-dark/40 cursor-pointer hover:text-rapanel-blue"
                                                @click="openItemDb(item.nameid, item)">{{ item.nameid }}</td>
                                            <td class="px-2 py-1.5 text-center cursor-pointer"
                                                @click="openItemDb(item.nameid, item)">
                                                <img :src="`/data/items/icons/${item.nameid}.png`" @error="onImgError" class="w-7 h-7 object-contain mx-auto hover:scale-110 transition-transform" />
                                            </td>
                                            <td class="px-3 py-1.5 cursor-pointer"
                                                @click="openItemDb(item.nameid, item)">
                                                <span v-if="item.refine > 0" class="font-bold text-rapanel-danger dark:text-rapanel-gold mr-1">+{{ item.refine }}</span>
                                                <span class="font-medium text-rapanel-navy-900 dark:text-white hover:text-rapanel-blue dark:hover:text-rapanel-blue transition-colors">{{ itemLabel(item) }}</span>
                                                <span v-if="item.attribute > 0" class="ml-1 text-rapanel-danger font-bold text-[10px]">[{{ __('Broken') }}]</span>
                                            </td>
                                            <td class="px-3 py-1.5 text-center text-rapanel-text-light/60 dark:text-rapanel-text-dark/60">{{ item.amount }}</td>
                                            <td class="px-3 py-1.5 text-center">
                                                <span :class="item.identify ? 'text-rapanel-success' : 'text-rapanel-danger'" class="font-bold">{{ item.identify ? __('Yes') : __('No') }}</span>
                                            </td>
                                            <td v-for="slot in ['card0','card1','card2','card3']" :key="slot" class="px-3 py-1.5 text-center text-rapanel-text-light/40 dark:text-rapanel-text-dark/30 italic">
                                                <span v-if="getCardName(item[slot])"
                                                    class="text-rapanel-blue not-italic font-medium cursor-pointer hover:underline"
                                                    @click="openItemDb(item[slot], { nameid: item[slot] })">{{ getCardName(item[slot]) }}</span>
                                                <span v-else>{{ __('None') }}</span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </section>

                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>

</template>

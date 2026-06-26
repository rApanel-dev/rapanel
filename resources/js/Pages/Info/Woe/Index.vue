<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import { ShieldExclamationIcon, ClockIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    schedules: { type: Array, default: () => [] },
});

const page = usePage();
const __   = (key) => page.props.translations?.[key] || key;

const DAY_NAMES = computed(() => [
    __('Sunday'), __('Monday'), __('Tuesday'), __('Wednesday'),
    __('Thursday'), __('Friday'), __('Saturday'),
]);

const TYPE_COLORS = {
    1: { bg: 'bg-amber-500/10 border-amber-500/20', text: 'text-amber-600 dark:text-amber-400', dot: 'bg-amber-500' },
    2: { bg: 'bg-rapanel-blue/10 border-rapanel-blue/20', text: 'text-rapanel-blue', dot: 'bg-rapanel-blue' },
    3: { bg: 'bg-purple-500/10 border-purple-500/20', text: 'text-purple-500', dot: 'bg-purple-500' },
};

const TYPE_LABELS = { 1: 'WOE 1 (FE)', 2: 'WOE 2 (SE)', 3: 'WOE TE' };

const TYPE_DESC = computed(() => ({
    1: __('First Edition — Classic castles with multiple rooms and guardians.'),
    2: __('Second Edition — Barricade-based castles, strategy-focused GvG.'),
    3: __('Tournament Edition — Available to all class tiers.'),
}));

// ── Countdown ────────────────────────────────────────────────────────
const now = ref(Math.floor(Date.now() / 1000));
let interval;

onMounted(() => { interval = setInterval(() => { now.value = Math.floor(Date.now() / 1000); }, 1000); });
onUnmounted(() => clearInterval(interval));

const formatCountdown = (ts) => {
    const diff = ts - now.value;
    if (diff <= 0) return __('Starting now');
    const d = Math.floor(diff / 86400);
    const h = Math.floor((diff % 86400) / 3600);
    const m = Math.floor((diff % 3600) / 60);
    const s = diff % 60;
    if (d > 0) return `${d}d ${h}h ${String(m).padStart(2, '0')}m`;
    if (h > 0) return `${h}h ${String(m).padStart(2, '0')}m`;
    return `${m}m ${String(s).padStart(2, '0')}s`;
};

// ── Filter by type ───────────────────────────────────────────────────
const activeFilter = ref(null);

const filteredSchedules = computed(() =>
    activeFilter.value === null
        ? props.schedules
        : props.schedules.filter(s => s.type === activeFilter.value)
);

const typeCounts = computed(() => {
    const c = { 1: 0, 2: 0, 3: 0 };
    props.schedules.forEach(s => c[s.type]++);
    return c;
});

const activeCount = computed(() => props.schedules.filter(s => s.is_active).length);
</script>

<template>
    <Head :title="__('War of Emperium')" />

    <MainLayout :show-bg="true">

        <!-- ── Header ── -->
        <div class="bg-white dark:bg-rapanel-navy-900 border-b border-rapanel-navy-100 dark:border-white/10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-display font-bold text-rapanel-navy-900 dark:text-white tracking-wide">
                        {{ __('War of Emperium') }}
                    </h1>
                    <p class="mt-1.5 text-sm text-rapanel-text-light dark:text-rapanel-text-dark">
                        {{ schedules.length }} {{ __('scheduled sessions') }}
                        <template v-if="activeCount">
                            · <span class="text-rapanel-success font-semibold">{{ activeCount }} {{ __('active now') }}</span>
                        </template>
                    </p>
                </div>

                <div v-if="activeCount"
                    class="flex items-center gap-2 px-4 py-2 rounded-full bg-rapanel-success/10 border border-rapanel-success/20">
                    <span class="w-2 h-2 rounded-full bg-rapanel-success animate-pulse" />
                    <span class="text-sm font-bold text-rapanel-success">{{ __('WOE is ACTIVE') }}</span>
                </div>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

            <!-- ── Type filters ── -->
            <div class="flex items-center gap-2 flex-wrap mb-6">
                <button @click="activeFilter = null"
                    :class="[
                        'inline-flex items-center gap-2 px-4 py-1.5 rounded-full text-sm font-semibold border transition',
                        activeFilter === null
                            ? 'bg-rapanel-navy-900 dark:bg-white text-white dark:text-rapanel-navy-900 border-rapanel-navy-900 dark:border-white'
                            : 'bg-white dark:bg-transparent text-rapanel-text-light dark:text-rapanel-text-dark border-rapanel-navy-100 dark:border-white/10 hover:border-rapanel-btn-blue hover:text-rapanel-blue'
                    ]">
                    {{ __('All') }}
                    <span class="text-[10px] font-black px-1.5 py-0.5 rounded-full bg-rapanel-navy-100 dark:bg-white/10">{{ schedules.length }}</span>
                </button>
                <button v-for="(label, t) in TYPE_LABELS" :key="t"
                    @click="activeFilter = Number(t)"
                    :class="[
                        'inline-flex items-center gap-2 px-4 py-1.5 rounded-full text-sm font-semibold border transition',
                        activeFilter === Number(t)
                            ? 'bg-rapanel-navy-900 dark:bg-white text-white dark:text-rapanel-navy-900 border-rapanel-navy-900 dark:border-white'
                            : 'bg-white dark:bg-transparent text-rapanel-text-light dark:text-rapanel-text-dark border-rapanel-navy-100 dark:border-white/10 hover:border-rapanel-btn-blue hover:text-rapanel-blue'
                    ]">
                    {{ label }}
                    <span class="text-[10px] font-black px-1.5 py-0.5 rounded-full bg-rapanel-navy-100 dark:bg-white/10">{{ typeCounts[t] }}</span>
                </button>
            </div>

            <!-- ── Empty state ── -->
            <div v-if="!filteredSchedules.length"
                class="flex flex-col items-center justify-center py-20 gap-4 text-center">
                <ShieldExclamationIcon class="w-14 h-14 text-rapanel-navy-200 dark:text-white/20" />
                <p class="text-rapanel-text-light dark:text-rapanel-text-dark text-sm">
                    {{ __('No WOE sessions scheduled.') }}
                </p>
            </div>

            <!-- ── Schedule cards ── -->
            <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div v-for="s in filteredSchedules" :key="s.id"
                    :class="[
                        'relative rounded-2xl border flex flex-col overflow-hidden shadow-sm',
                        s.is_active
                            ? 'bg-rapanel-success/5 border-rapanel-success/30'
                            : 'bg-white dark:bg-rapanel-navy-900 border-rapanel-navy-100 dark:border-white/10'
                    ]">

                    <!-- Session image -->
                    <div v-if="s.image_url" class="w-full h-36 overflow-hidden shrink-0">
                        <img :src="s.image_url" :alt="s.label || TYPE_LABELS[s.type]"
                            class="w-full h-full object-cover" loading="lazy" />
                    </div>

                    <!-- Card body -->
                    <div class="p-5 flex flex-col gap-3 flex-1">
                        <!-- Active pulse dot -->
                        <span v-if="s.is_active" class="absolute top-4 right-4 flex h-3 w-3">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-rapanel-success opacity-75" />
                            <span class="relative inline-flex rounded-full h-3 w-3 bg-rapanel-success" />
                        </span>

                        <!-- Type badge + label -->
                        <div class="flex items-center gap-2 flex-wrap">
                            <span :class="['inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold border', TYPE_COLORS[s.type].bg, TYPE_COLORS[s.type].text]">
                                <span :class="['w-1.5 h-1.5 rounded-full mr-1.5', TYPE_COLORS[s.type].dot]" />
                                {{ TYPE_LABELS[s.type] }}
                            </span>
                            <span v-if="s.label" class="text-sm font-bold text-rapanel-navy-900 dark:text-white">{{ s.label }}</span>
                        </div>

                        <!-- Description -->
                        <p class="text-xs text-rapanel-text-light dark:text-rapanel-text-dark leading-relaxed">
                            {{ TYPE_DESC[s.type] }}
                        </p>

                        <!-- Schedule -->
                        <div class="flex items-center gap-2 text-sm font-mono font-semibold text-rapanel-navy-900 dark:text-white mt-auto">
                            <ClockIcon class="w-4 h-4 text-rapanel-text-light dark:text-rapanel-text-dark shrink-0" />
                            {{ DAY_NAMES[s.start_day] }} {{ s.start_time }} – {{ DAY_NAMES[s.end_day] }} {{ s.end_time }}
                        </div>

                        <!-- Status -->
                        <div v-if="s.is_active"
                            class="flex items-center gap-1.5 text-xs font-bold text-rapanel-success">
                            <span class="w-1.5 h-1.5 rounded-full bg-rapanel-success" />
                            {{ __('Active now') }}
                        </div>
                        <div v-else-if="s.next_ts"
                            class="text-xs text-rapanel-text-light dark:text-rapanel-text-dark">
                            {{ __('Starts in') }}: <span class="font-bold text-rapanel-navy-900 dark:text-white tabular-nums">{{ formatCountdown(s.next_ts) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </MainLayout>
</template>

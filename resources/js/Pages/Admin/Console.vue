<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { router } from '@inertiajs/vue3';
import { ref, onUnmounted } from 'vue';
import { ArrowPathIcon, PlayIcon, StopIcon, CommandLineIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    content:       String,
    error:         String,
    availableLogs: Array,
    selectedLog:   String,
    lines:         Number,
});

const selectedLog = ref(props.selectedLog);
const lines       = ref(props.lines);
const autoRefresh = ref(false);
const lastRefresh = ref(new Date());
let intervalId    = null;

const safeRoute = (name) => { try { return route(name); } catch { return '#'; } };

const refresh = (resetScroll = false) => {
    router.get(safeRoute('admin.console.index'), {
        log:   selectedLog.value,
        lines: lines.value,
    }, {
        preserveScroll: !resetScroll,
        only: ['content', 'error', 'availableLogs', 'selectedLog'],
        onSuccess: () => { lastRefresh.value = new Date(); },
    });
};

const toggleAutoRefresh = () => {
    autoRefresh.value = !autoRefresh.value;
    if (autoRefresh.value) {
        intervalId = setInterval(() => refresh(), 5000);
    } else {
        clearInterval(intervalId);
        intervalId = null;
    }
};

const onLogChange = () => refresh(true);
const onLinesChange = () => refresh(true);

onUnmounted(() => { if (intervalId) clearInterval(intervalId); });

const formatTime = (d) => d.toLocaleTimeString();
</script>

<template>
    <AdminLayout>
        <div class="space-y-4 h-full flex flex-col">

            <!-- Header -->
            <div>
                <h1 class="text-2xl font-bold text-rapanel-text-light dark:text-white">Console</h1>
                <p class="text-sm text-rapanel-text-light/60 dark:text-white/50 mt-1">rAthena server log viewer</p>
            </div>

            <!-- Controls -->
            <div class="bg-white dark:bg-rapanel-navy-800 rounded-xl border border-rapanel-navy-100 dark:border-white/10 p-4 flex flex-col sm:flex-row gap-3 items-start sm:items-center">

                <!-- Log file selector -->
                <div class="flex-1">
                    <label class="block text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40 mb-1">Log File</label>
                    <select v-model="selectedLog" @change="onLogChange"
                        :disabled="!availableLogs?.length"
                        class="w-full text-sm bg-rapanel-navy-50 dark:bg-rapanel-navy-700 border border-rapanel-navy-100 dark:border-white/10 rounded-lg px-3 py-2 text-rapanel-text-light dark:text-white focus:outline-none focus:ring-2 focus:ring-rapanel-blue disabled:opacity-50">
                        <option v-if="!availableLogs?.length" value="">No log files found</option>
                        <option v-for="log in availableLogs" :key="log.key" :value="log.key">
                            {{ log.label }} ({{ log.file }})
                        </option>
                    </select>
                </div>

                <!-- Line count -->
                <div>
                    <label class="block text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40 mb-1">Lines</label>
                    <select v-model="lines" @change="onLinesChange"
                        class="text-sm bg-rapanel-navy-50 dark:bg-rapanel-navy-700 border border-rapanel-navy-100 dark:border-white/10 rounded-lg px-3 py-2 text-rapanel-text-light dark:text-white focus:outline-none focus:ring-2 focus:ring-rapanel-blue">
                        <option :value="50">50</option>
                        <option :value="100">100</option>
                        <option :value="200">200</option>
                        <option :value="500">500</option>
                    </select>
                </div>

                <!-- Action buttons -->
                <div class="flex items-end gap-2">
                    <button @click="refresh()"
                        class="flex items-center gap-1.5 px-4 py-2 bg-rapanel-blue text-white text-sm font-semibold rounded-lg hover:opacity-90 transition">
                        <ArrowPathIcon class="w-4 h-4" />
                        Refresh
                    </button>

                    <button @click="toggleAutoRefresh"
                        :class="autoRefresh
                            ? 'bg-red-500 hover:bg-red-600'
                            : 'bg-green-600 hover:bg-green-700',
                            'flex items-center gap-1.5 px-4 py-2 text-white text-sm font-semibold rounded-lg transition'">
                        <PlayIcon v-if="!autoRefresh" class="w-4 h-4" />
                        <StopIcon v-else class="w-4 h-4" />
                        {{ autoRefresh ? 'Stop' : 'Auto' }}
                    </button>
                </div>

                <!-- Last refresh -->
                <div class="text-[10px] text-rapanel-text-light/50 dark:text-white/40 whitespace-nowrap sm:ml-auto">
                    <div v-if="autoRefresh" class="flex items-center gap-1 text-green-500">
                        <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span>
                        Live · {{ formatTime(lastRefresh) }}
                    </div>
                    <div v-else>
                        Last: {{ formatTime(lastRefresh) }}
                    </div>
                </div>
            </div>

            <!-- Error state -->
            <div v-if="error"
                class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700/40 rounded-xl p-5">
                <div class="flex items-start gap-3">
                    <CommandLineIcon class="w-5 h-5 text-amber-500 shrink-0 mt-0.5" />
                    <div>
                        <div class="font-semibold text-amber-700 dark:text-amber-400 text-sm">Log file unavailable</div>
                        <div class="text-sm text-amber-600 dark:text-amber-500 mt-1">{{ error }}</div>
                        <div class="mt-3 text-xs text-amber-500 dark:text-amber-400/70 font-mono bg-amber-100 dark:bg-amber-900/30 rounded p-2">
                            Add to .env: <span class="font-bold">RA_LOG_PATH=/path/to/rathena/log</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Terminal output -->
            <div v-else class="flex-1 bg-slate-900 rounded-xl border border-slate-700 overflow-hidden flex flex-col">
                <div class="flex items-center gap-2 px-4 py-2.5 bg-slate-800 border-b border-slate-700">
                    <div class="flex gap-1.5">
                        <div class="w-3 h-3 rounded-full bg-red-500"></div>
                        <div class="w-3 h-3 rounded-full bg-yellow-500"></div>
                        <div class="w-3 h-3 rounded-full bg-green-500"></div>
                    </div>
                    <span class="text-xs text-white/40 font-mono ml-2">
                        {{ availableLogs?.find(l => l.key === selectedLog)?.file ?? selectedLog + '.log' }}
                        · last {{ lines }} lines
                    </span>
                    <span v-if="autoRefresh" class="ml-auto flex items-center gap-1 text-[10px] text-green-400">
                        <span class="w-1.5 h-1.5 rounded-full bg-green-400 animate-pulse"></span>
                        LIVE
                    </span>
                </div>

                <div class="flex-1 overflow-y-auto p-4 max-h-[60vh]">
                    <pre v-if="content" class="text-xs text-green-300 font-mono leading-relaxed whitespace-pre-wrap break-all">{{ content }}</pre>
                    <div v-else class="flex items-center justify-center h-32 text-rapanel-text-light/50 dark:text-white/40 text-sm">
                        No content available.
                    </div>
                </div>
            </div>

        </div>
    </AdminLayout>
</template>

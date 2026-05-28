<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { router, usePage } from '@inertiajs/vue3';
import { ref, watch, onMounted, onUnmounted, nextTick } from 'vue';
import {
    ArrowPathIcon, PlayIcon, StopIcon, CommandLineIcon,
    SignalIcon, SignalSlashIcon,
} from '@heroicons/vue/24/outline';
import { useConsoleSocket } from '@/Composables/useConsoleSocket.js';

// ─── i18n ─────────────────────────────────────────────────────────────────────
const page = usePage();
const __ = (key, rep = {}) => {
    let t = page.props.translations?.[key] || key;
    Object.entries(rep).forEach(([k, v]) => { t = t.replace(`:${k}`, v); });
    return t;
};

// ─── Props ────────────────────────────────────────────────────────────────────
const props = defineProps({
    // Game logs (existing)
    content:       String,
    error:         String,
    availableLogs: Array,
    selectedLog:   String,
    lines:         Number,
    // Live console (new)
    wsPublicUrl:   String,
    wsEnabled:     Boolean,
});

// ─── Tab ──────────────────────────────────────────────────────────────────────
const activeTab = ref('live');

// ─── Game logs logic (unchanged) ─────────────────────────────────────────────
const selectedLog = ref(props.selectedLog);
const logLines    = ref(props.lines);
const autoRefresh = ref(false);
const lastRefresh = ref(new Date());
let   intervalId  = null;

const refresh = (resetScroll = false) => {
    router.get('/admin/console', {
        log:   selectedLog.value,
        lines: logLines.value,
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

const onLogChange   = () => refresh(true);
const onLinesChange = () => refresh(true);

// ─── Live console ─────────────────────────────────────────────────────────────
const SERVERS      = ['login', 'char', 'map', 'web'];
const activeServer = ref('login');
const autoScroll   = ref(true);
const controlling  = ref({});

// One composable per server
const consoles = Object.fromEntries(
    SERVERS.map(name => [name, useConsoleSocket(name, props.wsPublicUrl)])
);

// DOM ref for the active terminal
const termRef = ref(null);

// Auto-scroll when new lines arrive on the active server
SERVERS.forEach(name => {
    watch(
        () => consoles[name].lines.value.length,
        async () => {
            if (!autoScroll.value || activeServer.value !== name) return;
            await nextTick();
            if (termRef.value) termRef.value.scrollTop = termRef.value.scrollHeight;
        }
    );
});

// Scroll to bottom when switching servers
watch(activeServer, async () => {
    await nextTick();
    if (autoScroll.value && termRef.value) termRef.value.scrollTop = termRef.value.scrollHeight;
});

// Connect when tab or active server changes
onMounted(() => {
    if (props.wsEnabled && activeTab.value === 'live') {
        SERVERS.forEach(name => consoles[name].connect());
    }
});

watch(activeTab, (tab) => {
    if (tab === 'live' && props.wsEnabled) {
        consoles[activeServer.value].connect();
    }
});

watch(activeServer, (name) => {
    if (props.wsEnabled && activeTab.value === 'live') {
        consoles[name].connect();
    }
});

// ─── Server control ───────────────────────────────────────────────────────────
async function serverAction(name, action) {
    controlling.value[name] = action;
    try {
        await fetch(`/admin/console/server/${name}/${action}`, {
            method:  'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
        });
    } finally {
        delete controlling.value[name];
    }
}

// ─── Status helpers ───────────────────────────────────────────────────────────
const STATUS_COLOR = {
    running:  'text-green-400',
    stopped:  'text-slate-400',
    starting: 'text-yellow-400',
    stopping: 'text-orange-400',
    crashed:  'text-red-400',
};
const STATUS_DOT = {
    running:  'bg-green-400 animate-pulse',
    stopped:  'bg-slate-500',
    starting: 'bg-yellow-400 animate-pulse',
    stopping: 'bg-orange-400 animate-pulse',
    crashed:  'bg-red-500',
};
const statusColor = s => STATUS_COLOR[s] ?? 'text-slate-400';
const statusDot   = s => STATUS_DOT[s]   ?? 'bg-slate-500';

const formatTime = d => d.toLocaleTimeString();

// ─── Cleanup ──────────────────────────────────────────────────────────────────
onUnmounted(() => {
    if (intervalId) clearInterval(intervalId);
    SERVERS.forEach(name => consoles[name].disconnect());
});
</script>

<template>
    <AdminLayout>
        <div class="space-y-4 flex flex-col h-full">

            <!-- Header -->
            <div>
                <h1 class="text-2xl font-bold text-rapanel-text-light dark:text-white">{{ __('Console') }}</h1>
                <p class="text-sm text-rapanel-text-light/60 dark:text-white/50 mt-1">{{ __('rAthena server monitoring') }}</p>
            </div>

            <!-- Tab switcher -->
            <div class="flex gap-1 bg-rapanel-navy-100 dark:bg-rapanel-navy-800 rounded-lg p-1 w-fit">
                <button v-for="tab in [{ key: 'live', label: __('Live Console') }, { key: 'logs', label: __('Game Logs') }]"
                    :key="tab.key"
                    @click="activeTab = tab.key"
                    :class="[
                        activeTab === tab.key
                            ? 'bg-white dark:bg-rapanel-navy-700 text-rapanel-text-light dark:text-white shadow-sm'
                            : 'text-rapanel-text-light/50 dark:text-white/40 hover:text-rapanel-text-light dark:hover:text-white',
                        'px-4 py-1.5 text-sm font-semibold rounded-md transition-all',
                    ]">
                    {{ tab.label }}
                </button>
            </div>

            <!-- ══════════════════════ LIVE CONSOLE ══════════════════════ -->
            <template v-if="activeTab === 'live'">

                <!-- Not configured notice -->
                <div v-if="!wsEnabled"
                    class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700/40 rounded-xl p-5">
                    <div class="flex items-start gap-3">
                        <CommandLineIcon class="w-5 h-5 text-amber-500 shrink-0 mt-0.5" />
                        <div>
                            <p class="font-semibold text-amber-700 dark:text-amber-400 text-sm">
                                {{ __('Live console not configured') }}
                            </p>
                            <p class="text-sm text-amber-600 dark:text-amber-500 mt-1">
                                Deploy <code class="font-mono">ws-server/</code> on your rAthena host and add to <code class="font-mono">.env</code>:
                            </p>
                            <pre class="mt-3 text-xs text-amber-500 dark:text-amber-400/70 font-mono bg-amber-100 dark:bg-amber-900/30 rounded p-2 leading-relaxed">RA_WS_INTERNAL_URL=http://localhost:3001
RA_WS_PUBLIC_URL=ws://localhost:3001
RA_WS_SECRET=your_secret_here</pre>
                        </div>
                    </div>
                </div>

                <template v-else>

                    <!-- Server selector tabs -->
                    <div class="flex gap-2 flex-wrap">
                        <button v-for="name in SERVERS" :key="name"
                            @click="activeServer = name"
                            :class="[
                                activeServer === name
                                    ? 'bg-white dark:bg-rapanel-navy-700 border-rapanel-navy-200 dark:border-white/20 shadow-sm'
                                    : 'bg-transparent border-rapanel-navy-100 dark:border-white/10 hover:bg-white/50 dark:hover:bg-rapanel-navy-700/50',
                                'flex items-center gap-2 px-4 py-2 rounded-lg border text-sm font-semibold transition-all',
                            ]">
                            <span :class="['w-2 h-2 rounded-full shrink-0', statusDot(consoles[name].status.value)]" />
                            <span class="capitalize text-rapanel-text-light dark:text-white">{{ name }}-server</span>
                            <span :class="['text-xs font-normal capitalize', statusColor(consoles[name].status.value)]">
                                {{ consoles[name].status.value }}
                            </span>
                        </button>
                    </div>

                    <!-- Console panel — single panel for the active server -->
                    <div class="flex flex-col gap-3">

                        <!-- Controls bar -->
                        <div class="bg-white dark:bg-rapanel-navy-800 rounded-xl border border-rapanel-navy-100 dark:border-white/10 p-4 flex items-center gap-3 flex-wrap">
                            <!-- Status + connection -->
                            <div class="flex items-center gap-3">
                                <div class="flex items-center gap-2">
                                    <span :class="['w-2 h-2 rounded-full', statusDot(consoles[activeServer].status.value)]" />
                                    <span :class="['text-sm font-semibold capitalize', statusColor(consoles[activeServer].status.value)]">
                                        {{ consoles[activeServer].status.value }}
                                    </span>
                                </div>
                                <div v-if="consoles[activeServer].connected.value"
                                    class="flex items-center gap-1 text-xs text-green-400">
                                    <SignalIcon class="w-3.5 h-3.5" />
                                    <span>{{ __('WebSocket') }}</span>
                                </div>
                                <div v-else class="flex items-center gap-1 text-xs text-slate-400">
                                    <SignalSlashIcon class="w-3.5 h-3.5" />
                                    <span>{{ __('Connecting…') }}</span>
                                </div>
                            </div>

                            <!-- Action buttons -->
                            <div class="flex gap-2 ml-auto flex-wrap">
                                <button @click="serverAction(activeServer, 'start')"
                                    :disabled="['running','starting'].includes(consoles[activeServer].status.value) || controlling[activeServer]"
                                    class="flex items-center gap-1.5 px-3 py-1.5 bg-green-600 hover:bg-green-700 disabled:opacity-40 disabled:cursor-not-allowed text-white text-xs font-semibold rounded-lg transition">
                                    <PlayIcon class="w-3.5 h-3.5" />
                                    {{ __('Start') }}
                                </button>
                                <button @click="serverAction(activeServer, 'stop')"
                                    :disabled="['stopped','stopping'].includes(consoles[activeServer].status.value) || controlling[activeServer]"
                                    class="flex items-center gap-1.5 px-3 py-1.5 bg-red-600 hover:bg-red-700 disabled:opacity-40 disabled:cursor-not-allowed text-white text-xs font-semibold rounded-lg transition">
                                    <StopIcon class="w-3.5 h-3.5" />
                                    {{ __('Stop') }}
                                </button>
                                <button @click="serverAction(activeServer, 'restart')"
                                    :disabled="consoles[activeServer].status.value === 'stopped' || controlling[activeServer]"
                                    class="flex items-center gap-1.5 px-3 py-1.5 bg-rapanel-blue hover:opacity-90 disabled:opacity-40 disabled:cursor-not-allowed text-white text-xs font-semibold rounded-lg transition">
                                    <ArrowPathIcon class="w-3.5 h-3.5" :class="{ 'animate-spin': controlling[activeServer] === 'restart' }" />
                                    {{ __('Restart') }}
                                </button>
                                <button @click="autoScroll = !autoScroll"
                                    :class="autoScroll
                                        ? 'bg-rapanel-navy-700 text-white border-rapanel-navy-600'
                                        : 'bg-transparent text-rapanel-text-light/50 dark:text-white/40 border-rapanel-navy-100 dark:border-white/10'"
                                    class="px-3 py-1.5 text-xs font-semibold rounded-lg border transition">
                                    {{ __('Auto-scroll') }}
                                </button>
                            </div>
                        </div>

                        <!-- Terminal window -->
                        <div class="bg-slate-900 rounded-xl border border-slate-700 overflow-hidden flex flex-col"
                            style="height: 55vh">
                            <!-- Titlebar -->
                            <div class="flex items-center gap-2 px-4 py-2.5 bg-slate-800 border-b border-slate-700 shrink-0">
                                <div class="flex gap-1.5">
                                    <div class="w-3 h-3 rounded-full bg-red-500" />
                                    <div class="w-3 h-3 rounded-full bg-yellow-500" />
                                    <div class="w-3 h-3 rounded-full bg-green-500" />
                                </div>
                                <span class="text-xs text-white/40 font-mono ml-2 capitalize">{{ activeServer }}-server</span>
                                <span class="ml-auto text-xs text-white/30 font-mono">
                                    {{ consoles[activeServer].lines.value.length }} {{ __('Lines') }}
                                </span>
                            </div>
                            <!-- Output -->
                            <div ref="termRef"
                                class="flex-1 overflow-y-auto p-4 font-mono text-xs leading-relaxed">
                                <div v-if="!consoles[activeServer].lines.value.length"
                                    class="text-slate-600 italic select-none">
                                    {{ __('No output yet…') }}
                                </div>
                                <div v-for="(line, i) in consoles[activeServer].lines.value"
                                    :key="i"
                                    class="whitespace-pre-wrap break-all text-slate-300"
                                    v-html="line" />
                            </div>
                        </div>

                    </div>
                </template>
            </template>

            <!-- ══════════════════════ GAME LOGS ══════════════════════ -->
            <template v-else>

                <!-- Controls -->
                <div class="bg-white dark:bg-rapanel-navy-800 rounded-xl border border-rapanel-navy-100 dark:border-white/10 p-4 flex flex-col sm:flex-row gap-3 items-start sm:items-center">
                    <div class="flex-1">
                        <label class="block text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40 mb-1">{{ __('Log File') }}</label>
                        <select v-model="selectedLog" @change="onLogChange"
                            :disabled="!availableLogs?.length"
                            class="w-full text-sm bg-rapanel-navy-50 dark:bg-rapanel-navy-700 border border-rapanel-navy-100 dark:border-white/10 rounded-lg px-3 py-2 text-rapanel-text-light dark:text-white focus:outline-none focus:ring-2 focus:ring-rapanel-blue disabled:opacity-50">
                            <option v-if="!availableLogs?.length" value="">{{ __('No log files found') }}</option>
                            <option v-for="log in availableLogs" :key="log.key" :value="log.key">
                                {{ log.label }} ({{ log.file }})
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40 mb-1">{{ __('Lines') }}</label>
                        <select v-model="logLines" @change="onLinesChange"
                            class="text-sm bg-rapanel-navy-50 dark:bg-rapanel-navy-700 border border-rapanel-navy-100 dark:border-white/10 rounded-lg px-3 py-2 text-rapanel-text-light dark:text-white focus:outline-none focus:ring-2 focus:ring-rapanel-blue">
                            <option :value="50">50</option>
                            <option :value="100">100</option>
                            <option :value="200">200</option>
                            <option :value="500">500</option>
                        </select>
                    </div>
                    <div class="flex items-end gap-2">
                        <button @click="refresh()"
                            class="flex items-center gap-1.5 px-4 py-2 bg-rapanel-blue text-white text-sm font-semibold rounded-lg hover:opacity-90 transition">
                            <ArrowPathIcon class="w-4 h-4" />
                            {{ __('Refresh') }}
                        </button>
                        <button @click="toggleAutoRefresh"
                            :class="autoRefresh ? 'bg-red-500 hover:bg-red-600' : 'bg-green-600 hover:bg-green-700'"
                            class="flex items-center gap-1.5 px-4 py-2 text-white text-sm font-semibold rounded-lg transition">
                            <PlayIcon v-if="!autoRefresh" class="w-4 h-4" />
                            <StopIcon v-else class="w-4 h-4" />
                            {{ autoRefresh ? __('Stop') : __('Auto') }}
                        </button>
                    </div>
                    <div class="text-[10px] text-rapanel-text-light/50 dark:text-white/40 whitespace-nowrap sm:ml-auto">
                        <div v-if="autoRefresh" class="flex items-center gap-1 text-green-500">
                            <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse" />
                            {{ __('Live') }} · {{ formatTime(lastRefresh) }}
                        </div>
                        <div v-else>{{ __('Last') }}: {{ formatTime(lastRefresh) }}</div>
                    </div>
                </div>

                <!-- Error -->
                <div v-if="error"
                    class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700/40 rounded-xl p-5">
                    <div class="flex items-start gap-3">
                        <CommandLineIcon class="w-5 h-5 text-amber-500 shrink-0 mt-0.5" />
                        <div>
                            <p class="font-semibold text-amber-700 dark:text-amber-400 text-sm">{{ __('Log file unavailable') }}</p>
                            <p class="text-sm text-amber-600 dark:text-amber-500 mt-1">{{ error }}</p>
                            <div class="mt-3 text-xs text-amber-500 dark:text-amber-400/70 font-mono bg-amber-100 dark:bg-amber-900/30 rounded p-2">
                                {{ __('Add to .env:') }} <span class="font-bold">RA_LOG_PATH=/path/to/rathena/log</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Terminal output -->
                <div v-else class="flex-1 bg-slate-900 rounded-xl border border-slate-700 overflow-hidden flex flex-col">
                    <div class="flex items-center gap-2 px-4 py-2.5 bg-slate-800 border-b border-slate-700">
                        <div class="flex gap-1.5">
                            <div class="w-3 h-3 rounded-full bg-red-500" />
                            <div class="w-3 h-3 rounded-full bg-yellow-500" />
                            <div class="w-3 h-3 rounded-full bg-green-500" />
                        </div>
                        <span class="text-xs text-white/40 font-mono ml-2">
                            {{ availableLogs?.find(l => l.key === selectedLog)?.file ?? selectedLog + '.log' }}
                            · {{ __('last :n lines', { n: logLines }) }}
                        </span>
                        <span v-if="autoRefresh" class="ml-auto flex items-center gap-1 text-[10px] text-green-400">
                            <span class="w-1.5 h-1.5 rounded-full bg-green-400 animate-pulse" />
                            {{ __('LIVE') }}
                        </span>
                    </div>
                    <div class="flex-1 overflow-y-auto p-4 max-h-[60vh]">
                        <pre v-if="content"
                            class="text-xs text-green-300 font-mono leading-relaxed whitespace-pre-wrap break-all">{{ content }}</pre>
                        <div v-else
                            class="flex items-center justify-center h-32 text-rapanel-text-light/50 dark:text-white/40 text-sm">
                            {{ __('No content available.') }}
                        </div>
                    </div>
                </div>

            </template>

        </div>
    </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { usePage } from '@inertiajs/vue3';
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
    wsPublicUrl: String,
    wsEnabled:   Boolean,
});

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

onMounted(() => {
    if (props.wsEnabled) {
        SERVERS.forEach(name => consoles[name].connect());
    }
});

watch(activeServer, (name) => {
    if (props.wsEnabled) {
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

// ─── Cleanup ──────────────────────────────────────────────────────────────────
onUnmounted(() => {
    SERVERS.forEach(name => consoles[name].disconnect());
});
</script>

<template>
    <AdminLayout>
        <div class="space-y-4 flex flex-col h-full">

            <PageHeader :title="__('Console')" :description="__('rAthena server monitoring')" class="mb-4" />

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

                <!-- Server tabs + controls in one row -->
                <div class="flex flex-col gap-3 sm:grid sm:items-stretch" style="grid-template-columns: 55fr 45fr">

                    <!-- Left: server selector tabs -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                        <button v-for="name in SERVERS" :key="name"
                            @click="activeServer = name"
                            :class="[
                                activeServer === name
                                    ? 'bg-white dark:bg-rapanel-navy-700 border-rapanel-navy-200 dark:border-white/20 shadow-sm'
                                    : 'bg-transparent border-rapanel-navy-100 dark:border-white/10 hover:bg-white/50 dark:hover:bg-rapanel-navy-700/50',
                                'flex items-center justify-center gap-2 px-4 py-2 rounded-lg border text-sm font-semibold transition-all',
                            ]">
                            <span :class="['w-2 h-2 rounded-full shrink-0', statusDot(consoles[name].status.value)]" />
                            <span class="capitalize text-rapanel-text-light dark:text-white">{{ name }}-server</span>
                            <span :class="['text-xs font-normal capitalize hidden sm:inline', statusColor(consoles[name].status.value)]">
                                {{ consoles[name].status.value }}
                            </span>
                        </button>
                    </div>

                    <!-- Right: controls bar -->
                    <div class="bg-white dark:bg-rapanel-surface rounded-xl border border-rapanel-navy-100 dark:border-white/10 p-3 sm:px-4 sm:py-0 flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-3 sm:flex-wrap">
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
                        <div class="grid grid-cols-2 gap-1.5 sm:flex sm:gap-2 sm:ml-auto sm:flex-wrap">
                            <button @click="serverAction(activeServer, 'start')"
                                :disabled="['running','starting'].includes(consoles[activeServer].status.value) || controlling[activeServer]"
                                class="flex items-center justify-center gap-1.5 px-3 py-2 sm:py-1.5 bg-green-600 hover:bg-green-700 disabled:opacity-40 disabled:cursor-not-allowed text-white text-xs font-semibold rounded-lg transition">
                                <PlayIcon class="w-3.5 h-3.5" />
                                {{ __('Start') }}
                            </button>
                            <button @click="serverAction(activeServer, 'stop')"
                                :disabled="['stopped','stopping'].includes(consoles[activeServer].status.value) || controlling[activeServer]"
                                class="flex items-center justify-center gap-1.5 px-3 py-2 sm:py-1.5 bg-red-600 hover:bg-red-700 disabled:opacity-40 disabled:cursor-not-allowed text-white text-xs font-semibold rounded-lg transition">
                                <StopIcon class="w-3.5 h-3.5" />
                                {{ __('Stop') }}
                            </button>
                            <button @click="serverAction(activeServer, 'restart')"
                                :disabled="consoles[activeServer].status.value === 'stopped' || controlling[activeServer]"
                                class="flex items-center justify-center gap-1.5 px-3 py-2 sm:py-1.5 bg-rapanel-blue hover:opacity-90 disabled:opacity-40 disabled:cursor-not-allowed text-white text-xs font-semibold rounded-lg transition">
                                <ArrowPathIcon class="w-3.5 h-3.5" :class="{ 'animate-spin': controlling[activeServer] === 'restart' }" />
                                {{ __('Restart') }}
                            </button>
                            <button @click="autoScroll = !autoScroll"
                                :class="autoScroll
                                    ? 'bg-rapanel-navy-700 text-white border-rapanel-navy-600'
                                    : 'bg-transparent text-rapanel-text-light/50 dark:text-white/40 border-rapanel-navy-100 dark:border-white/10'"
                                class="flex items-center justify-center px-3 py-2 sm:py-1.5 text-xs font-semibold rounded-lg border transition">
                                {{ __('Auto-scroll') }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Terminal window -->
                <div class="bg-slate-900 rounded-xl border border-slate-700 overflow-hidden flex flex-col h-[calc(100vh-28rem)] sm:h-[calc(100vh-17rem)]">
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
                            class="flex-1 overflow-y-auto p-3 sm:p-4 font-mono text-[11px] sm:text-xs leading-relaxed">
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

            </template>

        </div>
    </AdminLayout>
</template>

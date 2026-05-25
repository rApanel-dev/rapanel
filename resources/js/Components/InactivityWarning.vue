<script setup>
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
    countdown: { type: Number, required: true },
    onStay:    { type: Function, required: true },
    onLogout:  { type: Function, required: true },
});

const page = usePage();
const __ = (key) => page.props.translations?.[key] || key;

const formatTime = (seconds) => {
    const m = Math.floor(seconds / 60);
    const s = seconds % 60;
    return m > 0
        ? `${m}:${String(s).padStart(2, '0')}`
        : `${s}s`;
};
</script>

<template>
    <Teleport to="body">
        <div class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/60 backdrop-blur-sm">
            <div class="w-full max-w-sm mx-4 bg-white dark:bg-rapanel-navy-900 rounded-2xl shadow-2xl border border-rapanel-navy-100 dark:border-rapanel-navy-700 p-8 text-center">

                <!-- Icono -->
                <div class="flex justify-center mb-5">
                    <div class="w-16 h-16 rounded-full bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                        <svg class="w-8 h-8 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>

                <h2 class="text-xl font-bold text-rapanel-navy-900 dark:text-white mb-2">
                    {{ __('Session Expiring Soon') }}
                </h2>
                <p class="text-sm text-rapanel-text-light/60 dark:text-rapanel-text-dark mb-6">
                    {{ __('Your session will close due to inactivity in') }}
                    <span class="font-bold text-rapanel-danger text-base mx-1">{{ formatTime(countdown) }}</span>
                </p>

                <div class="flex gap-3 justify-center">
                    <button
                        @click="onLogout"
                        class="px-4 py-2 rounded-lg text-sm font-medium border border-rapanel-navy-200 dark:border-rapanel-navy-700 text-rapanel-text-light/60 dark:text-rapanel-text-dark hover:bg-rapanel-navy-100 dark:hover:bg-rapanel-navy-800 transition-colors"
                    >
                        {{ __('Log Out Now') }}
                    </button>
                    <button
                        @click="onStay"
                        class="px-5 py-2 rounded-lg text-sm font-semibold bg-rapanel-blue text-white hover:brightness-110 transition-all"
                    >
                        {{ __('Stay Connected') }}
                    </button>
                </div>

            </div>
        </div>
    </Teleport>
</template>

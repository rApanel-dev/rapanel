<script setup>
import { computed, onMounted, onUnmounted } from 'vue';
import { SHORTCUTS } from '@/Composables/useAdminShortcuts.js';
import { XMarkIcon } from '@heroicons/vue/24/outline';
import { usePage } from '@inertiajs/vue3';

const props = defineProps({ show: { type: Boolean, default: false } });
const emit = defineEmits(['close']);

const page = usePage();
const __ = (key) => page.props.translations?.[key] || key;

const headingId = 'shortcuts-help-title';

const grouped = computed(() => {
    const groups = {};
    SHORTCUTS.forEach(s => {
        if (!groups[s.group]) groups[s.group] = [];
        groups[s.group].push(s);
    });
    return groups;
});

const onKeydown = (e) => {
    if (props.show && e.key === 'Escape') emit('close');
};
onMounted(() => document.addEventListener('keydown', onKeydown));
onUnmounted(() => document.removeEventListener('keydown', onKeydown));
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="show"
                role="dialog"
                aria-modal="true"
                :aria-labelledby="headingId"
                class="fixed inset-0 z-50 flex items-center justify-center p-4"
                @click.self="emit('close')">
                <!-- Backdrop -->
                <div class="absolute inset-0 bg-rapanel-navy-900/75 dark:bg-black/80" aria-hidden="true" @click="emit('close')" />

                <!-- Panel -->
                <div class="relative w-full max-w-lg bg-white dark:bg-rapanel-surface rounded-2xl shadow-[0_20px_60px_rgba(0,0,0,0.5)] border border-rapanel-navy-100 dark:border-white/[0.07] overflow-hidden">
                    <!-- Header -->
                    <div class="flex items-center justify-between px-5 py-4 border-b border-rapanel-navy-100 dark:border-white/[0.07]">
                        <div class="flex items-center gap-2">
                            <h2 :id="headingId" class="text-sm font-bold text-rapanel-navy-900 dark:text-white">{{ __('Keyboard Shortcuts') }}</h2>
                            <span class="text-[10px] font-mono text-rapanel-text-light/40 dark:text-white/30" aria-hidden="true">press <kbd class="px-1 py-px rounded bg-rapanel-navy-100 dark:bg-white/[0.07] border border-rapanel-navy-200 dark:border-white/10 text-[9px]">?</kbd> to toggle</span>
                        </div>
                        <button @click="emit('close')"
                            :aria-label="__('Close shortcuts')"
                            class="p-1.5 rounded-lg hover:bg-rapanel-navy-100 dark:hover:bg-white/[0.07] text-rapanel-text-light/50 dark:text-white/40 transition-colors">
                            <XMarkIcon class="w-4 h-4" aria-hidden="true" />
                        </button>
                    </div>

                    <!-- Shortcuts grid -->
                    <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-0.5">
                        <template v-for="(items, group) in grouped" :key="group">
                            <div class="col-span-full mt-4 mb-2 first:mt-0">
                                <span class="text-[9px] font-black uppercase tracking-[0.18em] text-rapanel-gold">{{ group }}</span>
                            </div>
                            <div v-for="s in items" :key="s.seq.join('')"
                                class="flex items-center justify-between py-1.5 px-2 rounded-lg hover:bg-rapanel-navy-50 dark:hover:bg-white/[0.03] transition-colors group">
                                <span class="text-xs text-rapanel-text-light/70 dark:text-white/60 group-hover:text-rapanel-text-light dark:group-hover:text-white/80 transition-colors">
                                    {{ s.label }}
                                </span>
                                <div class="flex items-center gap-1" aria-hidden="true">
                                    <kbd v-for="(k, i) in s.seq" :key="i"
                                        class="inline-flex items-center justify-center min-w-[1.5rem] h-6 px-1.5 rounded text-[10px] font-mono font-bold bg-rapanel-navy-100 dark:bg-white/[0.08] border border-b-2 border-rapanel-navy-200 dark:border-white/[0.14] text-rapanel-navy-700 dark:text-white/80">
                                        {{ k }}
                                    </kbd>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Footer -->
                    <div class="px-5 py-3 border-t border-rapanel-navy-100 dark:border-white/[0.07] bg-rapanel-navy-50 dark:bg-white/[0.02]">
                        <p class="text-[10px] text-rapanel-text-light/40 dark:text-white/25">
                            Shortcuts are disabled when a text field is focused. Press <kbd class="px-1 py-px rounded bg-rapanel-navy-100 dark:bg-white/[0.07] border border-rapanel-navy-200 dark:border-white/10 text-[9px] font-mono">Esc</kbd> to cancel a pending sequence.
                        </p>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

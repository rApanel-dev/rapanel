<script setup>
import Modal from '@/Components/Modal.vue';
import { ExclamationTriangleIcon, TrashIcon, ShieldExclamationIcon, CheckCircleIcon } from '@heroicons/vue/24/outline';
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
    show:         { type: Boolean, default: false },
    title:        { type: String,  default: '' },
    message:      { type: String,  default: '' },
    entity:       { type: String,  default: '' },
    confirmLabel: { type: String,  default: '' },
    cancelLabel:  { type: String,  default: '' },
    variant:      { type: String,  default: 'danger' }, // danger | warning | default
    loading:      { type: Boolean, default: false },
});

const emit = defineEmits(['confirm', 'close']);

const page = usePage();
const __ = (key) => page.props.translations?.[key] || key;

const iconMap = {
    danger:  TrashIcon,
    warning: ShieldExclamationIcon,
    default: CheckCircleIcon,
};

const iconColorMap = {
    danger:  'text-rapanel-danger',
    warning: 'text-amber-500 dark:text-amber-400',
    default: 'text-rapanel-blue',
};

const iconBgMap = {
    danger:  'bg-rapanel-danger/10',
    warning: 'bg-amber-500/10',
    default: 'bg-rapanel-blue/10',
};

const btnMap = {
    danger:  'bg-rapanel-danger hover:bg-rapanel-danger/80 text-white',
    warning: 'bg-amber-500 hover:bg-amber-600 text-white',
    default: 'bg-rapanel-blue hover:bg-rapanel-blue/80 text-white',
};
</script>

<template>
    <Modal :show="show" max-width="sm" @close="emit('close')">
        <div class="p-6">
            <div class="flex items-start gap-4">
                <!-- Icon -->
                <div :class="['flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center', iconBgMap[variant] ?? iconBgMap.danger]">
                    <component :is="iconMap[variant] ?? iconMap.danger"
                        :class="['w-5 h-5', iconColorMap[variant] ?? iconColorMap.danger]" />
                </div>

                <!-- Content -->
                <div class="flex-1 min-w-0">
                    <h3 class="text-base font-semibold text-rapanel-text-light dark:text-white">{{ title }}</h3>
                    <p v-if="message" class="mt-1 text-sm text-rapanel-text-light/60 dark:text-white/50 leading-relaxed">{{ message }}</p>
                    <div v-if="entity"
                        class="mt-3 px-3 py-2 rounded-lg bg-rapanel-navy-50 dark:bg-white/[0.05] border border-rapanel-navy-100 dark:border-white/[0.07]">
                        <span class="text-sm font-mono font-semibold text-rapanel-text-light dark:text-white break-all">{{ entity }}</span>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="mt-5 flex justify-end gap-2.5">
                <button
                    @click="emit('close')"
                    :disabled="loading"
                    class="px-4 py-2 text-sm font-medium rounded-lg border border-rapanel-navy-100 dark:border-white/[0.08] bg-white dark:bg-white/[0.04] text-rapanel-text-light/70 dark:text-white/60 hover:bg-rapanel-navy-50 dark:hover:bg-white/[0.07] transition-colors disabled:opacity-50 cursor-pointer">
                    {{ cancelLabel || __('Cancel') }}
                </button>
                <button
                    @click="emit('confirm')"
                    :disabled="loading"
                    :class="['px-4 py-2 text-sm font-medium rounded-lg transition-colors disabled:opacity-50 cursor-pointer', btnMap[variant] ?? btnMap.danger]">
                    {{ confirmLabel || __('Confirm') }}
                </button>
            </div>
        </div>
    </Modal>
</template>

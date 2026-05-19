<script setup>
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
    variant:  { type: String,  default: 'blue' },
    size:     { type: String,  default: 'md' },
    disabled: { type: Boolean, default: false },
    title:    { type: String,  default: undefined },
    type:     { type: String,  default: 'button' },
});

const page = usePage();
const __ = (key) => page.props.translations?.[key] || key;

const variantClasses = {
    blue:         'bg-rapanel-blue/30 dark:bg-rapanel-blue/10 text-rapanel-blue border-rapanel-blue/40 dark:border-rapanel-blue/20 hover:bg-rapanel-blue hover:text-white',
    gold:         'bg-rapanel-gold/30 dark:bg-rapanel-gold/10 text-rapanel-gold border-rapanel-gold/40 dark:border-rapanel-gold/20 hover:bg-rapanel-gold hover:text-white',
    purple:       'bg-rapanel-purple/30 dark:bg-rapanel-purple/10 text-rapanel-purple border-rapanel-purple/40 dark:border-rapanel-purple/20 hover:bg-rapanel-purple hover:text-white',
    danger:       'bg-rapanel-danger/30 dark:bg-rapanel-danger/10 text-rapanel-danger border-rapanel-danger/40 dark:border-rapanel-danger/20 hover:bg-rapanel-danger hover:text-white',
    success:      'bg-rapanel-success/30 dark:bg-rapanel-success/10 text-rapanel-success border-rapanel-success/40 dark:border-rapanel-success/20 hover:bg-rapanel-success hover:text-white',
    navy:         'bg-rapanel-navy-100 dark:bg-rapanel-navy-700 text-rapanel-navy-900 dark:text-rapanel-text-dark border-rapanel-navy-100 dark:border-rapanel-navy-600 hover:bg-rapanel-navy-900 hover:text-white dark:hover:bg-rapanel-navy-600',
    'reset-look': 'bg-rapanel-danger/10 dark:bg-rapanel-gold/10 text-rapanel-danger dark:text-rapanel-gold border-rapanel-danger/40 dark:border-rapanel-gold/20 hover:bg-rapanel-danger hover:text-white dark:hover:bg-rapanel-gold dark:hover:text-rapanel-navy-900',
};

const sizeClasses = {
    md: 'px-3 py-1.5 text-xs',
    sm: 'px-2.5 py-1 text-xs',
};
</script>

<template>
    <button
        :type="type"
        :disabled="disabled"
        :title="title"
        class="inline-flex items-center gap-1.5 rounded-lg font-bold border transition-all"
        :class="[
            variantClasses[variant] ?? variantClasses.blue,
            sizeClasses[size] ?? sizeClasses.md,
            disabled ? 'opacity-30 cursor-not-allowed pointer-events-none' : '',
        ]"
    >
        <slot />
    </button>
</template>

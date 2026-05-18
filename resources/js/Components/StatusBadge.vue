<script setup>
defineProps({
    variant: { type: String, default: 'neutral' }, // success | danger | neutral
    label: { type: String, required: true },
    dot: { type: Boolean, default: false },
    size: { type: String, default: 'md' }, // md (rounded-full, dashboard) | sm (rounded, admin tables)
});

const variantClasses = {
    success: 'bg-rapanel-success/10 text-rapanel-success border-rapanel-success/20',
    danger:  'bg-rapanel-danger/10 text-rapanel-danger border-rapanel-danger/20',
    neutral: 'bg-rapanel-navy-100 dark:bg-white/10 text-rapanel-text-light/60 dark:text-white/50 border-transparent',
};

const dotClasses = {
    success: 'bg-rapanel-success animate-pulse',
    danger:  'bg-rapanel-danger',
    neutral: 'bg-rapanel-text-light/30 dark:bg-white/30',
};
</script>

<template>
    <span :class="[
        'inline-flex items-center border font-black uppercase tracking-wider',
        size === 'md' ? 'rounded-full px-2.5 py-0.5 text-xs' : 'rounded px-2 py-0.5 text-[10px] tracking-widest',
        variantClasses[variant] ?? variantClasses.neutral,
    ]">
        <span v-if="dot" :class="['w-1.5 h-1.5 rounded-full me-1.5', dotClasses[variant] ?? dotClasses.neutral]"></span>
        {{ label }}
    </span>
</template>

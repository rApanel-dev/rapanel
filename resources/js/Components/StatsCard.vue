<script setup>
defineProps({
    label: String,
    value: [String, Number],
    valueClass: { type: String, default: 'text-rapanel-text-light dark:text-white' },
    muted: { type: Boolean, default: false },
});
</script>

<template>
    <div :class="[
        'rounded-xl p-5 border shadow-sm transition-all duration-200 hover:scale-[1.02]',
        muted
            ? 'bg-rapanel-navy-100/70 dark:bg-rapanel-navy-800 border-rapanel-navy-100 dark:border-white/10'
            : 'bg-white dark:bg-rapanel-navy-800 border-rapanel-navy-100 dark:border-white/10 dark:shadow-black/20',
    ]">
        <div :class="['flex items-center mb-3', $slots.icon ? 'justify-between' : 'justify-start']">
            <span class="text-xs font-bold uppercase tracking-widest text-rapanel-text-light/40 dark:text-white/40">{{ label }}</span>
            <slot name="icon" />
        </div>
        <div :class="['text-3xl font-bold', valueClass]">{{ value }}</div>
        <div v-if="$slots.footer" class="mt-2">
            <slot name="footer" />
        </div>
    </div>
</template>

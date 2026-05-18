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
        'rounded-xl p-5 border shadow-sm transition-transform hover:scale-[1.02]',
        muted
            ? 'bg-rapanel-navy-50/50 dark:bg-black/20 border-rapanel-navy-100 dark:border-gray-700/30'
            : 'bg-white dark:bg-rapanel-navy-800 border-rapanel-navy-100 dark:border-white/10',
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

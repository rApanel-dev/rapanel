<script setup>
defineProps({
    label:      String,
    value:      [String, Number],
    muted:      { type: Boolean, default: false },
    accent:     { type: String,  default: 'blue' },
    valueClass: { type: String,  default: '' },
});

const accentStrip = {
    blue:   'from-rapanel-blue/70 via-rapanel-blue/30 to-transparent',
    gold:   'from-rapanel-gold/70 via-rapanel-gold/30 to-transparent',
    purple: 'from-rapanel-purple/70 via-rapanel-purple/30 to-transparent',
    green:  'from-rapanel-success/70 via-rapanel-success/30 to-transparent',
    red:    'from-rapanel-danger/70 via-rapanel-danger/30 to-transparent',
};

const accentValue = {
    blue:   'text-rapanel-blue',
    gold:   'text-rapanel-gold',
    purple: 'text-rapanel-purple',
    green:  'text-rapanel-success',
    red:    'text-rapanel-danger',
};
</script>

<template>
    <div :class="[
        'rounded-xl border shadow-sm transition-all duration-200 hover:scale-[1.02] hover:shadow-md overflow-hidden',
        muted
            ? 'bg-rapanel-navy-100/70 dark:bg-rapanel-surface border-rapanel-navy-100 dark:border-white/10'
            : 'bg-white dark:bg-rapanel-surface border-rapanel-navy-100 dark:border-white/[0.08] dark:shadow-black/20',
    ]">
        <!-- Top accent gradient strip -->
        <div :class="['h-[3px] bg-gradient-to-r', accentStrip[accent] ?? accentStrip.blue]" />

        <div class="p-5">
            <div :class="['flex items-center mb-3.5', $slots.icon ? 'justify-between' : 'justify-start']">
                <span class="text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/40 dark:text-white/35">{{ label }}</span>
                <div v-if="$slots.icon" class="p-2 rounded-lg bg-rapanel-navy-50 dark:bg-white/[0.06]">
                    <slot name="icon" />
                </div>
            </div>
            <div :class="['text-3xl font-bold tabular-nums', valueClass || accentValue[accent] || 'text-rapanel-text-light dark:text-white']">
                {{ value }}
            </div>
            <div v-if="$slots.footer" class="mt-2.5">
                <slot name="footer" />
            </div>
        </div>
    </div>
</template>

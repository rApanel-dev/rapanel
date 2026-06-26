<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    title: String,
    paginator: Object,
});
</script>

<template>
    <div class="bg-white dark:bg-rapanel-surface rounded-xl border border-rapanel-navy-100 dark:border-white/[0.07] shadow-[0_4px_24px_rgba(0,0,0,0.28)] dark:shadow-[0_4px_32px_rgba(0,0,0,0.55)] overflow-hidden">
        <div v-if="title || $slots.header" class="px-5 py-3 border-b border-rapanel-navy-100 dark:border-white/[0.07] bg-rapanel-navy-50 dark:bg-white/[0.04]">
            <slot name="header">
                <h3 class="text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/45 dark:text-white/35">{{ title }}</h3>
            </slot>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <slot />
            </table>
        </div>

        <div v-if="paginator && paginator.last_page > 1"
            class="flex items-center justify-between px-5 py-3 border-t border-rapanel-navy-100 dark:border-white/[0.07] bg-rapanel-navy-50 dark:bg-white/[0.025]">
            <span class="text-xs text-rapanel-text-light/45 dark:text-white/35">
                {{ paginator.from }}–{{ paginator.to }} <span class="text-rapanel-text-light/30 dark:text-white/20">of</span> {{ paginator.total }}
            </span>
            <div class="flex gap-1">
                <template v-for="link in paginator.links" :key="link.label">
                    <Link v-if="link.url" :href="link.url" v-html="link.label"
                        :class="[
                            link.active
                                ? 'bg-rapanel-btn-blue text-white border-rapanel-btn-blue shadow-sm'
                                : 'bg-white dark:bg-white/[0.04] text-rapanel-text-light/65 dark:text-white/60 hover:bg-rapanel-navy-100 dark:hover:bg-white/[0.08] border-rapanel-navy-100 dark:border-white/[0.08]',
                            'px-3 py-1.5 rounded-lg text-xs font-medium border transition-all duration-150'
                        ]" />
                    <span v-else v-html="link.label"
                        class="px-3 py-1.5 rounded-lg text-xs text-rapanel-text-light/25 dark:text-white/15" />
                </template>
            </div>
        </div>

        <div v-else-if="paginator"
            class="px-5 py-3 border-t border-rapanel-navy-100 dark:border-white/[0.07] bg-rapanel-navy-50 dark:bg-white/[0.025]">
            <span class="text-xs text-rapanel-text-light/40 dark:text-white/30">
                {{ paginator.from ?? 0 }}–{{ paginator.to ?? 0 }} of {{ paginator.total }}
            </span>
        </div>
    </div>
</template>

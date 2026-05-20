<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    title: String,
    paginator: Object, // Inertia paginator: { data, links, last_page, from, to, total }
});
</script>

<template>
    <div class="bg-white dark:bg-[#0f1829] rounded-xl border border-rapanel-navy-100 dark:border-white/[0.08] shadow-[0_4px_24px_rgba(0,0,0,0.35)] dark:shadow-[0_4px_32px_rgba(0,0,0,0.6)] overflow-hidden">
        <div v-if="title || $slots.header" class="px-5 py-3 border-b border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-white/5">
            <slot name="header">
                <h3 class="text-xs font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40">{{ title }}</h3>
            </slot>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <slot />
            </table>
        </div>

        <div v-if="paginator && paginator.last_page > 1"
            class="flex items-center justify-between px-4 py-3 border-t border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-white/5">
            <span class="text-xs text-rapanel-text-light/50 dark:text-white/40">
                {{ paginator.from }}–{{ paginator.to }} of {{ paginator.total }}
            </span>
            <div class="flex gap-1">
                <template v-for="link in paginator.links" :key="link.label">
                    <Link v-if="link.url" :href="link.url" v-html="link.label"
                        :class="[
                            link.active
                                ? 'bg-rapanel-blue text-white border-rapanel-blue'
                                : 'bg-white dark:bg-white/5 text-rapanel-text-light/70 dark:text-white/70 hover:bg-rapanel-navy-100 dark:hover:bg-white/10 border-rapanel-navy-100 dark:border-white/10',
                            'px-3 py-1.5 rounded text-xs font-medium border transition'
                        ]" />
                    <span v-else v-html="link.label"
                        class="px-3 py-1.5 rounded text-xs text-rapanel-text-light/30 dark:text-white/20" />
                </template>
            </div>
        </div>
    </div>
</template>

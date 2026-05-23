<script setup>
import { ref, computed } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import { useItemDbModal } from '@/Composables/useItemDbModal.js';

const props = defineProps({
    cards: { type: Array, default: () => [] },
});

const page  = usePage();
const __    = (key) => page.props.translations?.[key] || key;

const sortBy = ref('id'); // 'id' | 'name' | 'count'

const sorted = computed(() => {
    return [...props.cards].sort((a, b) => {
        if (sortBy.value === 'count') return b.total - a.total || a.id - b.id;
        if (sortBy.value === 'name')  return a.name.localeCompare(b.name);
        return a.id - b.id;
    });
});

const totalInServer = computed(() => props.cards.reduce((sum, c) => sum + c.total, 0));

const imgSrc = (card) => `/data/items/cards/${card.id}.png`;

// ── Detail modal ─────────────────────────────────────────────────────
const { openItemDb } = useItemDbModal();
</script>

<template>
    <Head :title="__('MvP Cards')" />

    <MainLayout :show-bg="true">

        <!-- ── Hero ── -->
        <div class="bg-white dark:bg-rapanel-navy-900 border-b border-rapanel-navy-100 dark:border-white/10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-display font-bold text-rapanel-navy-900 dark:text-white tracking-wide">
                        {{ __('MvP Cards') }}
                    </h1>
                    <p class="mt-1.5 text-sm text-rapanel-text-light dark:text-rapanel-text-dark">
                        {{ __('Total cards in server') }}: <span class="font-semibold text-rapanel-navy-900 dark:text-white">{{ totalInServer.toLocaleString() }}</span>
                    </p>
                </div>

                <!-- Sort toggle -->
                <div class="flex items-center gap-2 shrink-0">
                    <span class="text-xs text-rapanel-text-light dark:text-rapanel-text-dark">{{ __('Sort by') }}:</span>
                    <button
                        @click="sortBy = 'id'"
                        :class="[
                            'px-3 py-1 rounded-full text-xs font-semibold border transition-all',
                            sortBy === 'id'
                                ? 'bg-rapanel-blue text-white border-rapanel-blue'
                                : 'bg-white dark:bg-rapanel-navy-800 text-rapanel-text-light dark:text-rapanel-text-dark border-rapanel-navy-100 dark:border-white/10 hover:border-rapanel-blue'
                        ]">
                        ID
                    </button>
                    <button
                        @click="sortBy = 'name'"
                        :class="[
                            'px-3 py-1 rounded-full text-xs font-semibold border transition-all',
                            sortBy === 'name'
                                ? 'bg-rapanel-blue text-white border-rapanel-blue'
                                : 'bg-white dark:bg-rapanel-navy-800 text-rapanel-text-light dark:text-rapanel-text-dark border-rapanel-navy-100 dark:border-white/10 hover:border-rapanel-blue'
                        ]">
                        {{ __('Name') }}
                    </button>
                    <button
                        @click="sortBy = 'count'"
                        :class="[
                            'px-3 py-1 rounded-full text-xs font-semibold border transition-all',
                            sortBy === 'count'
                                ? 'bg-rapanel-blue text-white border-rapanel-blue'
                                : 'bg-white dark:bg-rapanel-navy-800 text-rapanel-text-light dark:text-rapanel-text-dark border-rapanel-navy-100 dark:border-white/10 hover:border-rapanel-blue'
                        ]">
                        {{ __('Count') }}
                    </button>
                </div>
            </div>
        </div>

        <!-- ── Grid ── -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

            <div v-if="sorted.length"
                class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-6 xl:grid-cols-8 gap-3">

                <button v-for="card in sorted" :key="card.id"
                    @click="openItemDb(card.id, card)"
                    class="bg-white dark:bg-rapanel-navy-900 rounded-2xl border border-rapanel-navy-100 dark:border-white/10 shadow-sm overflow-hidden flex flex-col hover:shadow-lg hover:border-rapanel-blue/40 dark:hover:border-rapanel-blue/40 transition-all duration-200 group text-left cursor-pointer pt-[3px] px-[3px]">

                    <!-- Ilustración retrato 3:4 -->
                    <div class="w-full aspect-[3/4] overflow-hidden rounded-t-xl bg-rapanel-navy-50 dark:bg-rapanel-navy-800 border border-rapanel-navy-100 dark:border-white/10 flex items-center justify-center">
                        <img
                            :src="imgSrc(card)"
                            :alt="card.name"
                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                            @error="$event.target.style.display='none'"
                        />
                    </div>

                    <!-- Nombre + contador -->
                    <div class="px-2.5 pt-2 pb-2.5 flex flex-col gap-1.5">
                        <p class="text-center text-[11px] font-semibold text-rapanel-navy-900 dark:text-white leading-tight line-clamp-2">
                            {{ card.name }}
                        </p>
                        <div class="flex justify-center">
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold border"
                                :class="card.total > 0
                                    ? 'bg-rapanel-danger/10 text-rapanel-danger border-rapanel-danger/30 dark:bg-rapanel-gold/15 dark:text-rapanel-gold dark:border-rapanel-gold/40'
                                    : 'bg-transparent border-rapanel-navy-100 dark:border-white/10 text-rapanel-text-light dark:text-rapanel-text-dark'">
                                <svg class="w-2.5 h-2.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 0v3.75m-16.5-3.75v3.75m16.5 0v3.75M3.75 13.5v3.75" />
                                </svg>
                                {{ card.total.toLocaleString() }}
                            </span>
                        </div>
                    </div>

                </button>
            </div>

            <!-- Empty state -->
            <div v-else class="flex flex-col items-center justify-center py-24 text-center">
                <p class="text-rapanel-text-light dark:text-rapanel-text-dark text-sm">
                    {{ __('No MVP cards found.') }}
                </p>
            </div>

        </div>

    </MainLayout>
</template>

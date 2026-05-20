<script setup>
import { ref, computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';

const props = defineProps({
    downloads:  { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
});

const page = usePage();
const __ = (key) => page.props.translations?.[key] || key;

const activeCategory = ref(null); // null = All

const filtered = computed(() =>
    activeCategory.value === null
        ? props.downloads
        : props.downloads.filter(d => d.category_id === activeCategory.value)
);
</script>

<template>
    <Head :title="__('Downloads')" />

    <MainLayout :show-bg="true">
        <!-- ── Hero ── -->
        <div class="bg-white dark:bg-rapanel-navy-900 border-b border-rapanel-navy-100 dark:border-white/10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
                <h1 class="text-3xl font-display font-bold text-rapanel-navy-900 dark:text-white tracking-wide">
                    {{ __('Downloads') }}
                </h1>
                <p class="mt-1.5 text-sm text-rapanel-text-light dark:text-rapanel-text-dark">
                    {{ __('Download the game client, patches and tools to get started.') }}
                </p>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

            <!-- ── Category tabs ── -->
            <div v-if="categories.length" class="flex flex-wrap gap-2 mb-8">
                <button
                    @click="activeCategory = null"
                    :class="[
                        'px-4 py-1.5 rounded-full text-sm font-semibold border transition-all',
                        activeCategory === null
                            ? 'bg-rapanel-blue text-white border-rapanel-blue shadow-sm'
                            : 'bg-white dark:bg-rapanel-navy-800 text-rapanel-text-light dark:text-rapanel-text-dark border-rapanel-navy-100 dark:border-white/10 hover:border-rapanel-blue dark:hover:border-rapanel-blue'
                    ]">
                    {{ __('All') }}
                </button>
                <button
                    v-for="cat in categories" :key="cat.id"
                    @click="activeCategory = cat.id"
                    :class="[
                        'px-4 py-1.5 rounded-full text-sm font-semibold border transition-all',
                        activeCategory === cat.id
                            ? 'bg-rapanel-blue text-white border-rapanel-blue shadow-sm'
                            : 'bg-white dark:bg-rapanel-navy-800 text-rapanel-text-light dark:text-rapanel-text-dark border-rapanel-navy-100 dark:border-white/10 hover:border-rapanel-blue dark:hover:border-rapanel-blue'
                    ]">
                    <span v-if="cat.icon" class="mr-1">{{ cat.icon }}</span>{{ cat.name }}
                </button>
            </div>

            <!-- ── Grid ── -->
            <div v-if="filtered.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <div v-for="dl in filtered" :key="dl.id"
                    class="bg-white dark:bg-rapanel-navy-900 rounded-2xl border border-rapanel-navy-100 dark:border-white/10 shadow-sm overflow-hidden flex flex-col group hover:shadow-md hover:border-rapanel-blue/40 dark:hover:border-rapanel-blue/40 transition-all duration-200">

                    <!-- Image / icon area -->
                    <div class="relative h-44 shrink-0 overflow-hidden">
                        <img v-if="dl.image_url"
                            :src="dl.image_url"
                            :alt="dl.name"
                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" />
                        <div v-else
                            class="w-full h-full bg-gradient-to-br from-rapanel-navy-100 to-rapanel-navy-50 dark:from-rapanel-navy-800 dark:to-rapanel-navy-900 flex items-center justify-center">
                            <svg class="w-16 h-16 text-rapanel-blue/30 dark:text-rapanel-blue/20" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                            </svg>
                        </div>

                        <!-- Auth badge -->
                        <span v-if="dl.is_only_auth"
                            class="absolute top-2 right-2 text-[10px] font-black uppercase tracking-wider px-2 py-0.5 rounded bg-rapanel-gold/90 text-rapanel-navy-900">
                            {{ __('Members only') }}
                        </span>
                    </div>

                    <!-- Content -->
                    <div class="flex flex-col flex-1 p-5">
                        <!-- Category badge -->
                        <div v-if="dl.category_name" class="mb-2">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-rapanel-blue">
                                <span v-if="dl.category_icon" class="mr-0.5">{{ dl.category_icon }}</span>{{ dl.category_name }}
                            </span>
                        </div>

                        <h2 class="text-base font-bold text-rapanel-navy-900 dark:text-white leading-snug mb-1.5">
                            {{ dl.name }}
                        </h2>

                        <p v-if="dl.description"
                            class="text-sm text-rapanel-text-light dark:text-rapanel-text-dark leading-relaxed line-clamp-2 flex-1 mb-4">
                            {{ dl.description }}
                        </p>
                        <div v-else class="flex-1 mb-4" />

                        <!-- Footer: downloads count + buttons -->
                        <div class="flex items-center justify-between pt-3 border-t border-rapanel-navy-100 dark:border-white/10">
                            <span class="text-xs text-rapanel-text-light dark:text-rapanel-text-dark flex items-center gap-1">
                                <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                </svg>
                                {{ dl.download_count.toLocaleString() }}
                            </span>

                            <div class="flex items-center gap-2">
                                <Link :href="route('downloads.show', dl.slug)"
                                    class="text-xs font-semibold text-rapanel-text-light dark:text-rapanel-text-dark hover:text-rapanel-blue transition-colors">
                                    {{ __('Details') }}
                                </Link>
                                <a :href="route('downloads.get', dl.slug)"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold bg-rapanel-blue text-white hover:opacity-90 transition-opacity shadow-sm">
                                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                    </svg>
                                    {{ __('Download') }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Empty state ── -->
            <div v-else class="flex flex-col items-center justify-center py-24 text-center">
                <svg class="w-16 h-16 text-rapanel-navy-100 dark:text-white/10 mb-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                </svg>
                <p class="text-rapanel-text-light dark:text-rapanel-text-dark text-sm">
                    {{ __('No downloads available yet.') }}
                </p>
            </div>

        </div>
    </MainLayout>
</template>

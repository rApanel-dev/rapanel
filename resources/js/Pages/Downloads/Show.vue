<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';

defineProps({
    download: { type: Object, required: true },
});

const page = usePage();
const __ = (key) => page.props.translations?.[key] || key;
</script>

<template>
    <Head :title="download.name + ' — ' + __('Downloads')" />

    <MainLayout :show-bg="true">
        <!-- Breadcrumb -->
        <div class="bg-white dark:bg-rapanel-navy-900 border-b border-rapanel-navy-100 dark:border-white/10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3 flex items-center gap-2 text-xs text-rapanel-text-light dark:text-rapanel-text-dark">
                <Link :href="route('downloads')" class="hover:text-rapanel-blue transition-colors">{{ __('Downloads') }}</Link>
                <span class="opacity-40">/</span>
                <span class="text-rapanel-navy-900 dark:text-white font-medium">{{ download.name }}</span>
            </div>
        </div>

        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="bg-white dark:bg-rapanel-navy-900 rounded-2xl border border-rapanel-navy-100 dark:border-white/10 shadow-sm overflow-hidden">

                <!-- Image -->
                <div v-if="download.image_url" class="h-64 overflow-hidden">
                    <img :src="download.image_url" :alt="download.name" class="w-full h-full object-cover" />
                </div>
                <div v-else class="h-48 bg-gradient-to-br from-rapanel-navy-100 to-rapanel-navy-50 dark:from-rapanel-navy-800 dark:to-rapanel-navy-900 flex items-center justify-center">
                    <svg class="w-20 h-20 text-rapanel-blue/20" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                </div>

                <!-- Content -->
                <div class="p-8">
                    <!-- Category -->
                    <div v-if="download.category_name" class="mb-3">
                        <span class="text-xs font-bold uppercase tracking-wider text-rapanel-blue">
                            <span v-if="download.category_icon" class="mr-0.5">{{ download.category_icon }}</span>
                            {{ download.category_name }}
                        </span>
                    </div>

                    <h1 class="text-3xl font-display font-bold text-rapanel-navy-900 dark:text-white mb-3">
                        {{ download.name }}
                    </h1>

                    <!-- Meta -->
                    <div class="flex flex-wrap items-center gap-4 mb-6 text-xs text-rapanel-text-light dark:text-rapanel-text-dark">
                        <span class="flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5" />
                            </svg>
                            {{ download.created_at }}
                        </span>
                        <span class="flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                            </svg>
                            {{ download.download_count.toLocaleString() }} {{ __('downloads') }}
                        </span>
                        <span v-if="download.file_name" class="flex items-center gap-1 font-mono text-[11px]">
                            📦 {{ download.file_name }}
                        </span>
                        <span v-if="download.is_only_auth"
                            class="px-2 py-0.5 rounded text-[10px] font-black uppercase bg-rapanel-gold/20 text-rapanel-gold border border-rapanel-gold/30">
                            {{ __('Members only') }}
                        </span>
                    </div>

                    <!-- Description -->
                    <div v-if="download.description"
                        class="text-sm text-rapanel-text-light dark:text-rapanel-text-dark leading-relaxed mb-8 whitespace-pre-line">
                        {{ download.description }}
                    </div>

                    <!-- Download button -->
                    <div class="flex flex-col sm:flex-row items-center gap-3 pt-6 border-t border-rapanel-navy-100 dark:border-white/10">
                        <a :href="route('downloads.get', download.slug)"
                            class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-8 py-3 rounded-xl bg-rapanel-blue text-white font-bold text-sm hover:opacity-90 transition-opacity shadow-md shadow-rapanel-blue/20">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                            </svg>
                            {{ __('Download Now') }}
                        </a>

                        <Link :href="route('downloads')"
                            class="text-sm text-rapanel-text-light dark:text-rapanel-text-dark hover:text-rapanel-blue transition-colors">
                            ← {{ __('Back to Downloads') }}
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </MainLayout>
</template>

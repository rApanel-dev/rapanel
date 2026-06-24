<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';

defineProps({
    title: { type: String, default: '' },
});

const page = usePage();
const __ = (key) => page.props.translations?.[key] || key;
</script>

<template>
    <Head :title="title ? __(title) : __('Coming soon')" />

    <MainLayout :show-bg="true">

        <!-- Page header -->
        <div class="bg-white dark:bg-rapanel-navy-900 border-b border-rapanel-navy-100 dark:border-white/10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
                <nav class="flex items-center gap-1.5 text-xs text-rapanel-text-light/40 dark:text-white/30 mb-2">
                    <Link :href="route('home')" class="hover:text-rapanel-blue transition-colors">{{ __('Home') }}</Link>
                    <span>›</span>
                    <span>{{ title ? __(title) : __('Coming soon') }}</span>
                </nav>
                <h1 class="text-3xl font-display font-bold text-rapanel-navy-900 dark:text-white tracking-wide">
                    {{ title ? __(title) : __('Coming soon') }}
                </h1>
            </div>
        </div>

        <!-- Content -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
            <div class="flex flex-col items-center text-center gap-6">

                <div class="bg-white/80 dark:bg-rapanel-navy-900/80 backdrop-blur-sm border border-rapanel-navy-100 dark:border-white/10 rounded-2xl shadow-xl px-10 py-10 flex flex-col items-center gap-6 max-w-sm w-full">

                    <!-- Icon -->
                    <div class="w-20 h-20 rounded-2xl bg-rapanel-navy-100 dark:bg-rapanel-navy-800 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 text-rapanel-navy-500 dark:text-rapanel-navy-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>

                    <div>
                        <h2 class="text-2xl font-display font-bold text-rapanel-navy-900 dark:text-white mb-2">
                            {{ __('Coming soon') }}
                        </h2>
                        <p class="text-sm text-rapanel-text-light/70 dark:text-rapanel-text-dark/70">
                            {{ __('This section is not available yet.') }}
                        </p>
                    </div>

                    <Link
                        :href="route('home')"
                        class="inline-flex items-center gap-2 text-sm font-semibold text-rapanel-blue dark:text-blue-400 hover:underline"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                        </svg>
                        {{ __('Back to Home') }}
                    </Link>

                </div>

            </div>
        </div>

    </MainLayout>
</template>

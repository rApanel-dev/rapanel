<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import { XCircleIcon } from '@heroicons/vue/24/outline';

const page = usePage();
const __ = (key, rep = {}) => {
    let t = page.props.translations?.[key] || key;
    Object.entries(rep).forEach(([k, v]) => { t = t.replace(`:${k}`, v); });
    return t;
};
</script>

<template>
    <Head :title="__('Payment cancelled.')" />
    <MainLayout :show-bg="true">
        <div class="max-w-md mx-auto px-4 py-20 text-center">
            <div class="flex justify-center mb-6">
                <div class="w-20 h-20 rounded-full bg-rapanel-danger/10 flex items-center justify-center">
                    <XCircleIcon class="w-10 h-10 text-rapanel-danger" />
                </div>
            </div>
            <h1 class="text-2xl font-display font-bold text-rapanel-navy-900 dark:text-white mb-2">
                {{ __('Payment cancelled.') }}
            </h1>
            <p class="text-rapanel-text-light/70 dark:text-rapanel-text-dark/70 mb-8">
                {{ __('Your payment was cancelled. No charge was made.') }}
            </p>
            <Link :href="route('donations')"
                class="px-5 py-2.5 rounded-xl bg-rapanel-blue text-white font-semibold text-sm hover:opacity-90 transition">
                {{ __('Back to Donations') }}
            </Link>
        </div>
    </MainLayout>
</template>

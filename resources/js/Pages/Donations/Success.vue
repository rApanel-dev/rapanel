<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import { CheckCircleIcon, SparklesIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    cashpoints: { type: Number, default: null },
});

const page = usePage();
const __ = (key, rep = {}) => {
    let t = page.props.translations?.[key] || key;
    Object.entries(rep).forEach(([k, v]) => { t = t.replace(`:${k}`, v); });
    return t;
};
</script>

<template>
    <Head :title="__('Payment successful!')" />
    <MainLayout :show-bg="true">
        <div class="max-w-md mx-auto px-4 py-20 text-center">
            <div class="flex justify-center mb-6">
                <div class="w-20 h-20 rounded-full bg-rapanel-success/10 flex items-center justify-center">
                    <CheckCircleIcon class="w-10 h-10 text-rapanel-success" />
                </div>
            </div>
            <h1 class="text-2xl font-display font-bold text-rapanel-navy-900 dark:text-white mb-2">
                {{ __('Payment successful!') }}
            </h1>
            <p class="text-rapanel-text-light/70 dark:text-rapanel-text-dark/70 mb-2">
                {{ __('Thank you for your donation!') }}
            </p>
            <p v-if="cashpoints" class="flex items-center justify-center gap-2 text-rapanel-gold font-bold text-lg mb-8">
                <SparklesIcon class="w-5 h-5" />
                +{{ cashpoints.toLocaleString() }} CashPoints
            </p>
            <p v-else class="text-rapanel-text-light/60 dark:text-rapanel-text-dark/60 text-sm mb-8">
                {{ __('Your CashPoints have been added to your account.') }}
            </p>
            <div class="flex gap-3 justify-center">
                <Link :href="route('dashboard')"
                    class="px-5 py-2.5 rounded-xl bg-rapanel-blue text-white font-semibold text-sm hover:opacity-90 transition">
                    {{ __('Go to Dashboard') }}
                </Link>
                <Link :href="route('donations')"
                    class="px-5 py-2.5 rounded-xl border border-rapanel-navy-200 dark:border-white/10 text-rapanel-text-light dark:text-white font-semibold text-sm hover:bg-rapanel-navy-50 dark:hover:bg-white/5 transition">
                    {{ __('Donate Again') }}
                </Link>
            </div>
        </div>
    </MainLayout>
</template>

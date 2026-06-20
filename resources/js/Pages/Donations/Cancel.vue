<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import { XCircleIcon, ArrowLeftIcon, ArrowPathIcon } from '@heroicons/vue/24/outline';

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
        <div class="min-h-[80vh] flex items-center justify-center px-4 py-10">
            <div class="w-full max-w-md">

                <!-- Card -->
                <div class="relative overflow-hidden rounded-3xl bg-rapanel-navy-900/80 backdrop-blur-2xl border border-white/10 shadow-2xl shadow-black/60 text-center">

                    <!-- Top glow line -->
                    <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-rapanel-danger/60 to-transparent" />
                    <!-- Danger ambient glow -->
                    <div class="absolute left-1/2 top-0 -translate-x-1/2 -translate-y-1/2 w-64 h-20 bg-rapanel-danger/10 blur-3xl rounded-full pointer-events-none" />

                    <div class="relative px-8 pt-10 pb-8 space-y-6">

                        <!-- Icon -->
                        <div class="flex justify-center">
                            <div class="relative w-20 h-20 rounded-full bg-gradient-to-br from-rapanel-danger/25 to-rapanel-danger/5 border border-rapanel-danger/25 flex items-center justify-center shadow-xl shadow-rapanel-danger/15">
                                <XCircleIcon class="w-10 h-10 text-rapanel-danger" />
                            </div>
                        </div>

                        <!-- Title -->
                        <div>
                            <h1 class="text-2xl font-display font-bold text-white mb-1">
                                {{ __('Payment cancelled.') }}
                            </h1>
                            <p class="text-white/50 text-sm">
                                {{ __('Your payment was cancelled. No charge was made.') }}
                            </p>
                        </div>

                        <!-- Divider -->
                        <div class="border-t border-white/8" />

                        <!-- Buttons -->
                        <div class="flex gap-3">
                            <Link :href="route('donations')"
                                class="flex-1 flex items-center justify-center gap-1.5 py-3 rounded-xl bg-rapanel-blue text-white font-semibold text-sm hover:opacity-90 active:scale-95 transition shadow-lg shadow-rapanel-blue/30">
                                <ArrowPathIcon class="w-4 h-4" />
                                {{ __('Try Again') }}
                            </Link>
                            <Link :href="route('home')"
                                class="flex-1 flex items-center justify-center gap-1.5 py-3 rounded-xl bg-white/8 hover:bg-white/12 border border-white/10 text-white/80 font-semibold text-sm transition active:scale-95">
                                <ArrowLeftIcon class="w-4 h-4" />
                                {{ __('Go to Home') }}
                            </Link>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </MainLayout>
</template>

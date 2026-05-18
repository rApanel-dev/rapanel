<script setup>
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const __ = (key) => page.props.translations?.[key] || key;

const currentYear = new Date().getFullYear();
</script>

<template>
    <footer class="bg-white dark:bg-rapanel-navy-900 transition-colors duration-200">

        <!-- Accent gradient line — ties brand colors together -->
        <div class="h-px bg-gradient-to-r from-rapanel-blue via-rapanel-purple to-rapanel-gold opacity-60 dark:opacity-40"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="relative flex flex-col sm:flex-row items-center justify-between gap-4 py-7">

                <!-- Left: copyright (vertical) -->
                <div class="flex flex-col gap-0.5 text-sm text-rapanel-text-light dark:text-rapanel-text-dark">
                    <span>© {{ currentYear }} <span class="font-bold">{{ $page.props.serverName }}</span></span>
                    <span>{{ __('Powered by') }} <span class="text-rapanel-blue font-semibold">rApanel</span></span>
                </div>

                <!-- Center: made with love -->
                <p class="text-sm text-rapanel-text-light dark:text-rapanel-text-dark flex items-center gap-1.5 sm:absolute sm:left-1/2 sm:-translate-x-1/2">
                    {{ __('Made with') }}
                    <span class="animate-pulse text-rapanel-danger text-sm leading-none">❤</span>
                </p>

                <!-- Right: social icons -->
                <div class="flex items-center gap-4">
                    <a href="#" aria-label="Discord" class="opacity-70 hover:opacity-100 transition-opacity duration-200">
                        <img src="/images/discord.png" class="h-7 w-auto" alt="Discord" />
                    </a>
                    <a href="#" aria-label="Facebook" class="opacity-70 hover:opacity-100 transition-opacity duration-200">
                        <img src="/images/fb.png" class="h-7 w-auto" alt="Facebook" />
                    </a>
                    <a href="#" aria-label="Instagram" class="opacity-70 hover:opacity-100 transition-opacity duration-200">
                        <img src="/images/ig.png" class="h-7 w-auto" alt="Instagram" />
                    </a>
                </div>

            </div>
        </div>
    </footer>
</template>

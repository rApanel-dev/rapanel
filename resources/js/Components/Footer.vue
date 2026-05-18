<script setup>
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();
const __ = (key) => page.props.translations?.[key] || key;
const safeRoute = (routeName) => {
    try { return route(routeName); } catch { return '#'; }
};

const currentYear = new Date().getFullYear();
</script>

<template>
    <!--
        Same surface as Header: bg-white / dark:bg-rapanel-navy-900
        Text tokens from tailwind.config: rapanel-text-light / rapanel-text-dark
        Border: rapanel-navy-100 (light) / white/10 (dark)  — matches header border-b
    -->
    <footer class="bg-white dark:bg-rapanel-navy-900
                   border-t border-rapanel-navy-100 dark:border-white/10
                   shadow-sm dark:shadow-2xl
                   transition-colors duration-200">

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

            <div class="flex flex-col md:flex-row items-center justify-between gap-8">

                <!-- Brand -->
                <div class="flex flex-col items-center md:items-start gap-3">
                    <Link :href="safeRoute('home')">
                        <img src="/images/logo.png" class="h-9 md:h-12 w-auto max-w-[150px] md:max-w-none object-contain" alt="rApanel" />
                    </Link>
                    <p class="text-sm text-rapanel-text-light/50 dark:text-rapanel-text-dark/50 max-w-xs text-center md:text-left">
                        {{ __('A classic Ragnarok Online experience, reimagined for a new era.') }}
                    </p>
                </div>

                <!-- Quick links -->
                <nav class="flex flex-wrap justify-center gap-x-8 gap-y-2">
                    <Link :href="safeRoute('home')"
                          class="text-sm font-medium text-rapanel-text-light/70 dark:text-rapanel-text-dark/70
                                 hover:text-rapanel-blue dark:hover:text-white transition">
                        {{ __('Home') }}
                    </Link>
                    <Link :href="safeRoute('login')"
                          class="text-sm font-medium text-rapanel-text-light/70 dark:text-rapanel-text-dark/70
                                 hover:text-rapanel-blue dark:hover:text-white transition">
                        {{ __('Login') }}
                    </Link>
                    <Link :href="safeRoute('register')"
                          class="text-sm font-medium text-rapanel-text-light/70 dark:text-rapanel-text-dark/70
                                 hover:text-rapanel-blue dark:hover:text-white transition">
                        {{ __('Register') }}
                    </Link>
                </nav>

                <!-- Social icons -->
                <div class="flex items-center gap-3">
                    <a href="#"
                       class="w-9 h-9 flex items-center justify-center rounded-md
                              bg-rapanel-navy-50 dark:bg-white/5
                              border border-rapanel-navy-100 dark:border-white/10
                              hover:border-rapanel-blue dark:hover:border-white/30
                              transition"
                       aria-label="Discord">
                        <img src="/images/discord.png" class="h-5 w-auto" alt="Discord" />
                    </a>
                    <a href="#"
                       class="w-9 h-9 flex items-center justify-center rounded-md
                              bg-rapanel-navy-50 dark:bg-white/5
                              border border-rapanel-navy-100 dark:border-white/10
                              hover:border-rapanel-blue dark:hover:border-white/30
                              transition"
                       aria-label="Facebook">
                        <img src="/images/fb.png" class="h-5 w-auto" alt="Facebook" />
                    </a>
                    <a href="#"
                       class="w-9 h-9 flex items-center justify-center rounded-md
                              bg-rapanel-navy-50 dark:bg-white/5
                              border border-rapanel-navy-100 dark:border-white/10
                              hover:border-rapanel-blue dark:hover:border-white/30
                              transition"
                       aria-label="Instagram">
                        <img src="/images/ig.png" class="h-5 w-auto" alt="Instagram" />
                    </a>
                </div>

            </div>

            <!-- Divider + copyright -->
            <div class="mt-8 pt-6 border-t border-rapanel-navy-100 dark:border-white/10
                        flex flex-col sm:flex-row items-center justify-between gap-2">
                <p class="text-xs text-rapanel-text-light/40 dark:text-rapanel-text-dark/40">
                    © {{ currentYear }} rApanel — All rights reserved
                </p>
                <p class="text-xs text-rapanel-text-light/30 dark:text-rapanel-text-dark/30">
                    Powered by
                    <span class="text-rapanel-text-light/50 dark:text-rapanel-text-dark/50">rAthena</span>
                </p>
            </div>

        </div>
    </footer>
</template>

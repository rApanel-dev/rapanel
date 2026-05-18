<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
import Header from '@/Components/Header.vue';
import BgMain from '@/Components/BgMain.vue';
</script>

<template>
    <BgMain />
    <Header class="relative z-50 shadow-md" />

    <div class="flex min-h-[calc(100vh-64px)] flex-col items-center sm:justify-center py-6 sm:py-0">
        <div>
            <Link href="/">
                <ApplicationLogo class="h-20 w-20 fill-current text-rapanel-blue" />
            </Link>
        </div>

        <div class="mt-6 w-full overflow-hidden bg-white dark:bg-rapanel-navy-800/80 dark:backdrop-blur-md px-6 py-4 shadow-md sm:max-w-md sm:rounded-lg border border-transparent dark:border-white/10">
            <slot />
        </div>
    </div>
</template>

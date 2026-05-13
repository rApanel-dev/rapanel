<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
import Header from '@/Components/Header.vue';
</script>

<template>
    <Header class="relative z-50 shadow-md" />

    <div
        class="flex min-h-[calc(100vh-64px)] flex-col items-center bg-rapanel-navy-50 dark:bg-rapanel-navy-900 sm:justify-center py-6 sm:py-0"
    >
        <div>
            <Link href="/">
                <ApplicationLogo class="h-20 w-20 fill-current text-rapanel-blue" />
            </Link>
        </div>

        <div
            class="mt-6 w-full overflow-hidden bg-white dark:bg-rapanel-navy-800 px-6 py-4 shadow-md sm:max-w-md sm:rounded-lg"
        >
            <slot />
        </div>
    </div>
</template>

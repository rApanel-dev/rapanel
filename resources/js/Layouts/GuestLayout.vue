<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
import Header from '@/Components/Header.vue';
</script>

<template>
    <Header class="absolute top-0 left-0 w-full z-50 shadow-md" />

    <div
        class="flex min-h-screen flex-col items-center bg-rapanel-navy-50 dark:bg-rapanel-navy-900 pt-6 sm:justify-center sm:pt-0"
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

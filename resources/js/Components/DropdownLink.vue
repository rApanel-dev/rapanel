<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    href: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <Link
        :href="href"
        class="block w-full px-4 py-2 text-start text-sm leading-5 text-rapanel-text-light dark:text-rapanel-text-dark transition duration-150 ease-in-out hover:bg-rapanel-navy-50 dark:hover:bg-rapanel-navy-900 focus:bg-rapanel-navy-50 dark:focus:bg-rapanel-navy-900 focus:outline-none"
    >
        <slot />
    </Link>
</template>

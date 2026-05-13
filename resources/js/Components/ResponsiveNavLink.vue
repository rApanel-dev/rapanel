<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    href: {
        type: String,
        required: true,
    },
    active: {
        type: Boolean,
    },
});

const classes = computed(() =>
    props.active
        ? 'block w-full ps-3 pe-4 py-2 border-l-4 border-rapanel-blue text-start text-base font-medium text-rapanel-blue dark:text-white bg-rapanel-navy-50 dark:bg-rapanel-navy-800 focus:outline-none focus:text-rapanel-navy-900 dark:focus:text-white focus:bg-rapanel-navy-100 dark:focus:bg-rapanel-navy-900 focus:border-rapanel-blue transition duration-150 ease-in-out'
        : 'block w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-base font-medium text-rapanel-text-light/70 dark:text-rapanel-text-dark/70 hover:text-rapanel-text-light dark:hover:text-white hover:bg-rapanel-navy-50 dark:hover:bg-rapanel-navy-800 hover:border-rapanel-navy-100 dark:hover:border-rapanel-navy-800 focus:outline-none focus:text-rapanel-text-light dark:focus:text-white focus:bg-rapanel-navy-50 dark:focus:bg-rapanel-navy-800 focus:border-rapanel-navy-100 dark:focus:border-rapanel-navy-800 transition duration-150 ease-in-out',
);
</script>

<template>
    <Link :href="href" :class="classes">
        <slot />
    </Link>
</template>

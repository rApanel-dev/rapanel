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
        ? 'inline-flex items-center px-1 pt-1 border-b-2 border-rapanel-blue text-sm font-medium leading-5 text-rapanel-text-light dark:text-white focus:outline-none focus:border-rapanel-blue transition duration-150 ease-in-out'
        : 'inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-rapanel-text-light/70 dark:text-rapanel-text-dark/70 hover:text-rapanel-text-light dark:hover:text-white hover:border-rapanel-navy-100 dark:hover:border-white/20 focus:outline-none focus:text-rapanel-text-light dark:focus:text-white focus:border-rapanel-navy-100 dark:focus:border-white/20 transition duration-150 ease-in-out',
);
</script>

<template>
    <Link :href="href" :class="classes">
        <slot />
    </Link>
</template>

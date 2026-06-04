<script setup>
defineProps({
    type: {
        type: String,
        default: 'button',
    },
});
</script>

<template>
    <button
        :type="type"
        class="font-display inline-flex items-center rounded-md border border-rapanel-navy-100 dark:border-rapanel-navy-800 bg-white dark:bg-rapanel-navy-800 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-rapanel-text-light dark:text-rapanel-text-dark shadow-sm transition duration-150 ease-in-out hover:bg-rapanel-navy-50 dark:hover:bg-rapanel-navy-900 focus:outline-none focus:ring-2 focus:ring-rapanel-blue focus:ring-offset-2 dark:focus:ring-offset-rapanel-navy-900 disabled:opacity-25"
    >
        <slot />
    </button>
</template>

<script setup>
defineProps({
    title: String,
    description: String,
});
</script>

<template>
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-5 border-b border-rapanel-navy-100 dark:border-white/[0.055]">
        <div>
            <h1 class="text-2xl font-display font-bold tracking-wide text-rapanel-text-light dark:text-white">{{ title }}</h1>
            <p v-if="description || $slots.description" class="text-sm text-rapanel-text-light/55 dark:text-white/45 mt-1">
                <slot name="description">{{ description }}</slot>
            </p>
        </div>
        <slot />
    </div>
</template>

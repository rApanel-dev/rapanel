<script setup>
defineProps({
    title: String,
    description: String,
});
</script>

<template>
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-rapanel-text-light dark:text-white">{{ title }}</h1>
            <p v-if="description" class="text-sm text-rapanel-text-light/60 dark:text-white/50 mt-1">{{ description }}</p>
        </div>
        <slot />
    </div>
</template>

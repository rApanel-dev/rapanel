<script setup>
import Header from '@/Components/Header.vue';
</script>

<template>
    <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
        <Header />

        <main>
            <slot />
        </main>
    </div>
</template>

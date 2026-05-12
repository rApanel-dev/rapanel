<script setup>
import Header from '@/Components/Header.vue';
</script>

<template>
    <div class="min-h-screen bg-rapanel-navy-50 dark:bg-rapanel-navy-900 text-rapanel-text-light dark:text-rapanel-text-dark transition-colors duration-300">
        
        <Header />

        <main>
            <slot />
        </main>

    </div>
</template>
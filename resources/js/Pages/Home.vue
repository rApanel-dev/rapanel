<script setup>
import { Head } from '@inertiajs/vue3';
import Header from '@/Components/Header.vue';
</script>

<template>
    <Head :title="__('Home')" />

    <div class="min-h-screen bg-gray-100 dark:bg-black">
        
        <Header />

        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        </main>

    </div>
</template>

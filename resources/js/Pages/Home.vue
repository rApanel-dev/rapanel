<script setup>
import { Head } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue'; // Importamos el Layout
</script>

<template>
    <Head :title="__('Home')" />

    <MainLayout>
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <h1 class="text-2xl font-bold">Bienvenido a rApanel</h1>
        </main>
    </MainLayout>
</template>
<script setup>
import Header from '@/Components/Header.vue';
import BgMain from '@/Components/BgMain.vue';
import Footer from '@/Components/Footer.vue';

defineProps({
    showBg: { type: Boolean, default: false },
});
</script>

<template>
    <div :class="[
        'min-h-screen flex flex-col text-rapanel-text-light dark:text-rapanel-text-dark transition-colors duration-300',
        showBg ? '' : 'bg-rapanel-navy-50 dark:bg-rapanel-navy-900',
    ]">
        <BgMain v-if="showBg" />

        <div class="sticky top-0 z-50">
            <Header />
        </div>

        <main class="flex-1">
            <slot />
        </main>

        <Footer />
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';

defineProps({ compact: { type: Boolean, default: false } });

const isDark = ref(false);

const toggleTheme = () => {
    isDark.value = !isDark.value;
    if (isDark.value) {
        document.documentElement.classList.add('dark');
        localStorage.theme = 'dark';
    } else {
        document.documentElement.classList.remove('dark');
        localStorage.theme = 'light';
    }
};

onMounted(() => {
    isDark.value = document.documentElement.classList.contains('dark');
});
</script>

<template>
    <button
        @click="toggleTheme"
        :class="compact
            ? 'flex items-center justify-center w-7 h-7 rounded-md bg-rapanel-navy-100 dark:bg-rapanel-navy-800 text-rapanel-navy-900 dark:text-rapanel-gold transition-colors duration-300'
            : 'p-2 rounded-lg bg-rapanel-navy-100 dark:bg-rapanel-navy-800 text-rapanel-navy-900 dark:text-rapanel-gold transition-colors duration-300'"
    >
        <svg v-if="!isDark" xmlns="http://www.w3.org/2000/svg" :class="compact ? 'h-4 w-4' : 'h-6 w-6'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
        </svg>
        <svg v-else xmlns="http://www.w3.org/2000/svg" :class="compact ? 'h-4 w-4' : 'h-6 w-6'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707m12.728 0l-.707-.707M6.343 6.343l-.707-.707M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
        </svg>
    </button>
</template>

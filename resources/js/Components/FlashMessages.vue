<script setup>
defineProps({
    success: String,
    error: String,
});
</script>

<template>
    <div v-if="success" class="flex items-center gap-3 px-5 py-3 rounded-xl bg-rapanel-success/10 border border-rapanel-success/30 text-rapanel-success text-sm font-medium">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        {{ success }}
    </div>
    <div v-if="error" class="flex items-center gap-3 px-5 py-3 rounded-xl bg-rapanel-danger/10 border border-rapanel-danger/30 text-rapanel-danger text-sm font-medium">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9.303 3.376c.864 1.505-.15 3.374-1.95 3.374H2.647c-1.8 0-2.815-1.869-1.951-3.374L10.049 4.126c.9-1.56 3.002-1.56 3.902 0L21.303 16.126z" />
        </svg>
        {{ error }}
    </div>
</template>

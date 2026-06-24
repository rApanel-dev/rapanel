<script setup>
defineProps({
    message: { type: String, default: 'No data found.' },
    colspan: { type: Number, default: 1 },
});
</script>

<template>
    <tr>
        <td :colspan="colspan" class="px-6 py-12 text-center text-rapanel-text-light/40 dark:text-rapanel-text-dark/45 italic">
            <div class="flex flex-col items-center gap-2">
                <slot name="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 opacity-20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                    </svg>
                </slot>
                <span class="text-sm">{{ message }}</span>
            </div>
        </td>
    </tr>
</template>

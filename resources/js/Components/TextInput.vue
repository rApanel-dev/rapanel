<script setup>
import { onMounted, ref } from 'vue';

const model = defineModel({
    type: String,
    required: true,
});

const input = ref(null);

onMounted(() => {
    if (input.value.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value.focus() });
</script>

<template>
    <input
        class="rounded-md border-rapanel-navy-100 dark:border-rapanel-navy-800 bg-white dark:bg-rapanel-navy-800 text-rapanel-text-light dark:text-rapanel-text-dark shadow-sm focus:border-rapanel-blue focus:ring-rapanel-blue transition-colors duration-200"
        v-model="model"
        ref="input"
    />
</template>

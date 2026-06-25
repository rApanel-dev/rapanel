<script setup>
// Preset del componente canónico: botón secundario = ActionButton ghost (superficie neutra).
import ActionButton from '@/Components/ActionButton.vue';
</script>

<template>
    <ActionButton fill="ghost" size="lg">
        <slot />
    </ActionButton>
</template>

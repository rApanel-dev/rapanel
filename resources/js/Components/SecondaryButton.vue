<script setup>
// Preset del componente canónico: botón secundario = ActionButton ghost (superficie neutra).
// type='button' por defecto (como el SecondaryButton original).
import ActionButton from '@/Components/ActionButton.vue';
defineProps({ type: { type: String, default: 'button' } });
</script>

<template>
    <ActionButton fill="ghost" size="lg" :type="type">
        <slot />
    </ActionButton>
</template>

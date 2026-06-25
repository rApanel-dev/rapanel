<script setup>
// Preset del componente canónico: botón de peligro = ActionButton sólido danger.
import ActionButton from '@/Components/ActionButton.vue';
</script>

<template>
    <ActionButton variant="danger" fill="solid" size="lg">
        <slot />
    </ActionButton>
</template>

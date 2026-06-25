<script setup>
// Preset del componente canónico: botón primario = ActionButton sólido azul.
import ActionButton from '@/Components/ActionButton.vue';
</script>

<template>
    <ActionButton variant="blue" fill="solid" size="lg">
        <slot />
    </ActionButton>
</template>

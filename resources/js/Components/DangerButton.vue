<script setup>
// Preset del componente canónico: botón de peligro = ActionButton sólido danger.
// type='submit' por defecto (replica el <button> nativo original).
import ActionButton from '@/Components/ActionButton.vue';
defineProps({ type: { type: String, default: 'submit' } });
</script>

<template>
    <ActionButton variant="danger" fill="solid" size="lg" :type="type">
        <slot />
    </ActionButton>
</template>

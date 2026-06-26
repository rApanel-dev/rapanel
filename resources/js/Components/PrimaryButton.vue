<script setup>
// Preset del componente canónico: botón primario = ActionButton sólido azul.
// type='submit' por defecto (replica el <button> nativo original: en un <form>
// enviaba el formulario). Se puede sobreescribir con type="button".
import ActionButton from '@/Components/ActionButton.vue';
defineProps({ type: { type: String, default: 'submit' } });
</script>

<template>
    <ActionButton variant="blue" fill="solid" size="lg" :type="type">
        <slot />
    </ActionButton>
</template>

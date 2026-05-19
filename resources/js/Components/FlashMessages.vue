<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
    success: String,
    error:   String,
    warning: String,
});

const visible = ref(false);
let timer = null;

const show = () => {
    visible.value = true;
    clearTimeout(timer);
    timer = setTimeout(() => { visible.value = false; }, 5000);
};

watch(() => props.success, (v) => { if (v) show(); }, { immediate: true });
watch(() => props.error,   (v) => { if (v) show(); }, { immediate: true });
watch(() => props.warning, (v) => { if (v) show(); }, { immediate: true });
</script>

<template>
    <Transition
        enter-active-class="transition ease-out duration-300"
        enter-from-class="opacity-0 -translate-y-1"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition ease-in duration-200"
        leave-from-class="opacity-100 translate-y-0"
        leave-to-class="opacity-0 -translate-y-1"
    >
        <div v-if="visible && (success || error || warning)">
            <!-- Success -->
            <div v-if="success" class="flex items-center gap-3 px-5 py-3.5 rounded-xl bg-rapanel-success text-rapanel-success-dark font-semibold text-sm shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                {{ success }}
            </div>
            <!-- Error -->
            <div v-else-if="error" class="flex items-center gap-3 px-5 py-3.5 rounded-xl bg-rapanel-danger text-rapanel-danger-dark font-semibold text-sm shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9.303 3.376c.864 1.505-.15 3.374-1.95 3.374H2.647c-1.8 0-2.815-1.869-1.951-3.374L10.049 4.126c.9-1.56 3.002-1.56 3.902 0L21.303 16.126z" />
                </svg>
                {{ error }}
            </div>
            <!-- Warning / Alert -->
            <div v-else-if="warning" class="flex items-center gap-3 px-5 py-3.5 rounded-xl bg-rapanel-gold text-rapanel-gold-dark font-semibold text-sm shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126z" />
                </svg>
                {{ warning }}
            </div>
        </div>
    </Transition>
</template>

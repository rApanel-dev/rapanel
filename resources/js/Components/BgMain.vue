<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

const st  = computed(() => usePage().props.siteSettings ?? {})
const src = computed(() =>
    st.value.home_hero_bg_image
        ? '/storage/' + st.value.home_hero_bg_image
        : '/images/bg-main.svg'
)
</script>

<template>
    <img
        :src="src"
        class="fixed inset-0 w-full h-full object-cover object-bottom -z-10 dark:filter dark:brightness-50 pointer-events-none select-none"
        aria-hidden="true"
        alt=""
    />
</template>

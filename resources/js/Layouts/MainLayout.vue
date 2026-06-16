<script setup>
import Header     from '@/Components/Header.vue';
import BgMain     from '@/Components/BgMain.vue';
import Footer     from '@/Components/Footer.vue';
import ItemDbModal from '@/Components/ItemDbModal.vue';
import MobDbModal  from '@/Components/MobDbModal.vue';
import InactivityWarning from '@/Components/InactivityWarning.vue';
import WoeLiveBanner from '@/Components/WoeLiveBanner.vue';
import { useInactivityTimer } from '@/Composables/useInactivityTimer';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps({
    showBg: { type: Boolean, default: false },
});

const page            = usePage();
const isAuthenticated = computed(() => !!page.props.auth?.user);
const timeout         = computed(() => page.props.inactivityTimeout ?? 30);

const { showWarning, countdown, stayActive } = useInactivityTimer(
    timeout.value,
    isAuthenticated.value,
);

const doLogout = () => { import('@inertiajs/vue3').then(({ router }) => router.post(route('logout'))); };
</script>

<template>
    <div :class="[
        'min-h-screen flex flex-col text-rapanel-text-light dark:text-rapanel-text-dark transition-colors duration-300',
        showBg ? '' : 'bg-rapanel-navy-50 dark:bg-rapanel-navy-900',
    ]">
        <BgMain v-if="showBg" />

        <div class="sticky top-0 z-50">
            <WoeLiveBanner />
            <Header />
        </div>

        <main class="flex-1">
            <slot />
        </main>

        <Footer />
        <ItemDbModal />
        <MobDbModal />

        <InactivityWarning
            v-if="showWarning"
            :countdown="countdown"
            :on-stay="stayActive"
            :on-logout="doLogout"
        />
    </div>
</template>

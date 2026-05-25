<script setup>
import { computed, onMounted, onUnmounted, watch } from 'vue';
import MainLayout from '@/Layouts/MainLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, useForm, usePage, router } from '@inertiajs/vue3';

const props = defineProps({
    status: { type: String },
});

const page = usePage();
const __ = (key) => page.props.translations?.[key] || key;

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};

const verificationLinkSent = computed(() => props.status === 'verification-link-sent');

// Polling: detecta verificación desde otro dispositivo
let pollTimer = null;

onMounted(() => {
    pollTimer = setInterval(() => {
        router.reload({ only: ['auth'] });
    }, 4000);
});

onUnmounted(() => {
    clearInterval(pollTimer);
});

watch(
    () => page.props.auth.user?.email_verified_at,
    (verifiedAt) => {
        if (verifiedAt) {
            clearInterval(pollTimer);
            router.visit(route('dashboard'));
        }
    }
);
</script>

<template>
    <Head :title="__('Email Verification')"><meta name="robots" content="noindex, nofollow"></Head>

    <MainLayout :show-bg="true">
        <div class="flex items-center justify-center px-4 py-12">
            <div class="w-full max-w-4xl min-h-[620px] flex overflow-hidden rounded-2xl shadow-2xl border border-rapanel-navy-100 dark:border-white/10">

                <!-- Left: artwork -->
                <div class="hidden md:block w-5/12 shrink-0">
                    <img src="/images/register.jpg" class="w-full h-full object-cover" alt="" />
                </div>

                <!-- Right: content -->
                <div class="flex-1 bg-white dark:bg-rapanel-navy-900 px-8 py-10 flex flex-col justify-center">

                    <!-- Header -->
                    <div class="mb-7">
                        <h1 class="text-2xl font-display font-bold text-rapanel-navy-900 dark:text-white tracking-wide">
                            {{ __('Email Verification') }}
                        </h1>
                        <p class="mt-1.5 text-sm text-rapanel-text-light dark:text-rapanel-text-dark">
                            {{ __('Thanks for signing up! Please verify your email address by clicking the link we sent you. If you did not receive the email, we will send another.') }}
                        </p>
                    </div>

                    <!-- Success message -->
                    <div v-if="verificationLinkSent" class="mb-5 px-4 py-3 rounded-lg bg-rapanel-success/10 border border-rapanel-success/30 text-sm font-medium text-rapanel-success">
                        {{ __('A new verification link has been sent to your email address.') }}
                    </div>

                    <form @submit.prevent="submit" class="space-y-4">
                        <div class="pt-1 space-y-3">
                            <PrimaryButton
                                class="w-full justify-center uppercase tracking-widest"
                                :class="{ 'opacity-25': form.processing }"
                                :disabled="form.processing"
                            >
                                {{ __('Resend Verification Email') }}
                            </PrimaryButton>

<p class="text-center text-sm text-rapanel-text-light dark:text-rapanel-text-dark">
                                {{ __('Wrong account?') }}
                                <Link
                                    :href="route('logout')"
                                    method="post"
                                    as="button"
                                    class="text-rapanel-blue font-semibold hover:underline ms-1"
                                >
                                    {{ __('Log Out') }}
                                </Link>
                            </p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </MainLayout>
</template>

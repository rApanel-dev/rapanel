<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';

defineProps({
    status: { type: String },
});

const page = usePage();
const __ = (key) => page.props.translations?.[key] || key;

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <Head :title="__('Forgot Password')"><meta name="robots" content="noindex, nofollow"></Head>

    <MainLayout :show-bg="true">
        <div class="flex items-center justify-center px-4 py-12">
            <div class="w-full max-w-4xl min-h-[620px] flex overflow-hidden rounded-2xl shadow-2xl border border-rapanel-navy-100 dark:border-white/10">

                <!-- Left: artwork -->
                <div class="hidden md:block w-5/12 shrink-0">
                    <img src="/images/register.jpg" class="w-full h-full object-cover" alt="" />
                </div>

                <!-- Right: form -->
                <div class="flex-1 bg-white dark:bg-rapanel-navy-900 px-8 py-10 flex flex-col justify-center">

                    <!-- Header -->
                    <div class="mb-7">
                        <h1 class="text-2xl font-display font-bold text-rapanel-navy-900 dark:text-white tracking-wide">
                            {{ __('Forgot your password?') }}
                        </h1>
                        <p class="mt-1.5 text-sm text-rapanel-text-light dark:text-rapanel-text-dark">
                            {{ __('No problem. Enter your email and we will send you a password reset link.') }}
                        </p>
                    </div>

                    <!-- Status message -->
                    <div v-if="status" class="mb-4 px-4 py-3 rounded-lg bg-rapanel-success/10 border border-rapanel-success/30 text-sm font-medium text-rapanel-success">
                        {{ status }}
                    </div>

                    <form @submit.prevent="submit" class="space-y-4">

                        <!-- Email -->
                        <div>
                            <InputLabel for="email" :value="__('Email')" />
                            <TextInput
                                id="email"
                                type="email"
                                class="mt-1 block w-full"
                                v-model="form.email"
                                required
                                autofocus
                                autocomplete="username"
                            />
                            <InputError class="mt-1" :message="form.errors.email" />
                        </div>

                        <!-- Submit -->
                        <div class="pt-1 space-y-3">
                            <PrimaryButton
                                class="w-full justify-center uppercase tracking-widest"
                                :class="{ 'opacity-25': form.processing }"
                                :disabled="form.processing"
                            >
                                {{ __('Send Reset Link') }}
                            </PrimaryButton>

                            <p class="text-center text-sm text-rapanel-text-light dark:text-rapanel-text-dark">
                                {{ __('Remember your password?') }}
                                <Link :href="route('login')" class="text-rapanel-blue font-semibold hover:underline ms-1">
                                    {{ __('Sign In') }}
                                </Link>
                            </p>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </MainLayout>
</template>

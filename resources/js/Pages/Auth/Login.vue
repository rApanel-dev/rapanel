<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';

defineProps({
    canResetPassword: { type: Boolean },
    status:           { type: String },
});

const page = usePage();
const __ = (key) => page.props.translations?.[key] || key;

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head :title="__('Log in')" />

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
                            {{ __('Welcome Back') }}
                        </h1>
                        <p class="mt-1.5 text-sm text-rapanel-text-light dark:text-rapanel-text-dark">
                            {{ __('Enter your credentials to access your master account.') }}
                        </p>
                    </div>

                    <!-- Status message (e.g. password reset confirmation) -->
                    <div v-if="status" class="mb-4 text-sm font-medium text-rapanel-success">
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

                        <!-- Password -->
                        <div>
                            <div class="flex items-center justify-between">
                                <InputLabel for="password" :value="__('Password')" />
                                <Link
                                    v-if="canResetPassword"
                                    :href="route('password.request')"
                                    class="text-xs text-rapanel-blue hover:underline"
                                >
                                    {{ __('Forgot your password?') }}
                                </Link>
                            </div>
                            <TextInput
                                id="password"
                                type="password"
                                class="mt-1 block w-full"
                                v-model="form.password"
                                required
                                autocomplete="current-password"
                            />
                            <InputError class="mt-1" :message="form.errors.password" />
                        </div>

                        <!-- Remember me -->
                        <label class="flex items-center gap-2 cursor-pointer">
                            <Checkbox name="remember" v-model:checked="form.remember" />
                            <span class="text-sm text-rapanel-text-light dark:text-rapanel-text-dark">
                                {{ __('Remember me') }}
                            </span>
                        </label>

                        <!-- Submit -->
                        <div class="pt-1 space-y-3">
                            <PrimaryButton
                                class="w-full justify-center uppercase tracking-widest"
                                :class="{ 'opacity-25': form.processing }"
                                :disabled="form.processing"
                            >
                                {{ __('Sign In') }}
                            </PrimaryButton>

                            <p class="text-center text-sm text-rapanel-text-light dark:text-rapanel-text-dark">
                                {{ __("Don't have an account?") }}
                                <Link :href="route('register')" class="text-rapanel-blue font-semibold hover:underline ms-1">
                                    {{ __('Register') }}
                                </Link>
                            </p>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </MainLayout>
</template>

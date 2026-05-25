<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';

const props = defineProps({
    email: { type: String, required: true },
    token: { type: String, required: true },
});

const page = usePage();
const __ = (key) => page.props.translations?.[key] || key;

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head :title="__('Reset Password')"><meta name="robots" content="noindex, nofollow"></Head>

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
                            {{ __('Reset Password') }}
                        </h1>
                        <p class="mt-1.5 text-sm text-rapanel-text-light dark:text-rapanel-text-dark">
                            {{ __('Choose a new secure password for your account.') }}
                        </p>
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
                                autocomplete="username"
                            />
                            <InputError class="mt-1" :message="form.errors.email" />
                        </div>

                        <!-- Password + Confirm -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <InputLabel for="password" :value="__('New Password')" />
                                <TextInput
                                    id="password"
                                    type="password"
                                    class="mt-1 block w-full"
                                    v-model="form.password"
                                    required
                                    autofocus
                                    autocomplete="new-password"
                                />
                                <InputError class="mt-1" :message="form.errors.password" />
                            </div>
                            <div>
                                <InputLabel for="password_confirmation" :value="__('Confirm Password')" />
                                <TextInput
                                    id="password_confirmation"
                                    type="password"
                                    class="mt-1 block w-full"
                                    v-model="form.password_confirmation"
                                    required
                                    autocomplete="new-password"
                                />
                                <InputError class="mt-1" :message="form.errors.password_confirmation" />
                            </div>
                        </div>

                        <!-- Submit -->
                        <div class="pt-1 space-y-3">
                            <PrimaryButton
                                class="w-full justify-center uppercase tracking-widest"
                                :class="{ 'opacity-25': form.processing }"
                                :disabled="form.processing"
                            >
                                {{ __('Reset Password') }}
                            </PrimaryButton>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </MainLayout>
</template>

<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { country_data } from "@/helpers";

const page = usePage();
const __ = (key) => page.props.translations?.[key] || key;

const form = useForm({
    name: '',
    email: '',
    country: '',
    birthdate: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head :title="__('Register')"><meta name="robots" content="noindex, nofollow"></Head>

    <MainLayout :show-bg="true">
        <div class="flex items-center justify-center px-4 py-12">
            <div class="w-full max-w-4xl min-h-[618px] flex overflow-hidden rounded-2xl shadow-2xl border border-rapanel-navy-100 dark:border-white/10">

                <!-- Left: artwork -->
                <div class="hidden md:block w-5/12 shrink-0">
                    <img src="/images/register.jpg" class="w-full h-full object-cover" alt="" />
                </div>

                <!-- Right: form -->
                <div class="flex-1 bg-white dark:bg-rapanel-navy-900 px-8 py-10 flex flex-col justify-center">

                    <!-- Header -->
                    <div class="mb-7">
                        <h1 class="text-2xl font-display font-bold text-rapanel-navy-900 dark:text-white tracking-wide">
                            {{ __('Create a Master Account') }}
                        </h1>
                        <p class="mt-1.5 text-sm text-rapanel-text-light dark:text-rapanel-text-dark">
                            {{ __('After creating your master account, remember to create a game account from your dashboard.') }}
                        </p>
                    </div>

                    <form @submit.prevent="submit" class="space-y-4">

                        <!-- Display Name -->
                        <div>
                            <InputLabel for="name" :value="__('Display Name')" />
                            <TextInput
                                id="name"
                                type="text"
                                class="mt-1 block w-full"
                                v-model="form.name"
                                required
                                autofocus
                                autocomplete="nickname"
                            />
                            <InputError class="mt-1" :message="form.errors.name" />
                        </div>

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

                        <!-- Birthdate + Country -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <InputLabel for="birthdate" :value="__('Birthdate')" />
                                <TextInput
                                    id="birthdate"
                                    type="date"
                                    class="mt-1 block w-full"
                                    v-model="form.birthdate"
                                    required
                                />
                                <InputError class="mt-1" :message="form.errors.birthdate" />
                            </div>
                            <div>
                                <InputLabel for="country" :value="__('Country')" />
                                <select
                                    id="country"
                                    v-model="form.country"
                                    class="mt-1 block w-full rounded-md shadow-sm bg-white dark:bg-rapanel-navy-800 border-rapanel-navy-100 dark:border-rapanel-navy-800 text-rapanel-text-light dark:text-white focus:ring-rapanel-blue focus:border-rapanel-blue"
                                    required
                                >
                                    <option value="" disabled>{{ __('Select your country') }}</option>
                                    <option v-for="c in country_data" :key="c.code" :value="c.code">{{ c.name }}</option>
                                </select>
                                <InputError class="mt-1" :message="form.errors.country" />
                            </div>
                        </div>

                        <!-- Password + Confirm -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <InputLabel for="password" :value="__('Password')" />
                                <TextInput
                                    id="password"
                                    type="password"
                                    class="mt-1 block w-full"
                                    v-model="form.password"
                                    required
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

                        <!-- Note -->
                        <p class="text-xs italic text-rapanel-danger">
                            {{ __('Note: Your master account password is different from your game account password.') }}
                        </p>

                        <!-- Submit -->
                        <div class="pt-1 space-y-3">
                            <PrimaryButton
                                class="w-full justify-center uppercase tracking-widest"
                                :class="{ 'opacity-25': form.processing }"
                                :disabled="form.processing"
                            >
                                {{ __('Create Master Account') }}
                            </PrimaryButton>

                            <p class="text-center text-sm text-rapanel-text-light dark:text-rapanel-text-dark">
                                {{ __('Already registered?') }}
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

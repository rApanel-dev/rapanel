<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';

const page = usePage();
const __ = (key) => page.props.translations?.[key] || key;

const form = useForm({ code: '' });

const submit = () => {
    form.post(route('admin.verify-2fa.store'), {
        onFinish: () => form.reset(),
    });
};
</script>

<template>
    <Head :title="__('Admin Access Verification')" />

    <AdminLayout>
        <div class="min-h-[60vh] flex items-center justify-center px-4 py-12">
            <div class="w-full max-w-sm">
                <div class="bg-white dark:bg-rapanel-navy-900 rounded-2xl shadow-xl border border-rapanel-navy-100 dark:border-rapanel-navy-800 p-8">

                    <div class="flex justify-center mb-6">
                        <div class="w-16 h-16 rounded-full bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                            <svg class="w-8 h-8 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                        </div>
                    </div>

                    <h1 class="text-xl font-bold text-center text-rapanel-text-light dark:text-white mb-2">
                        {{ __('Admin Access Verification') }}
                    </h1>

                    <p class="text-sm text-center text-rapanel-text-light/60 dark:text-rapanel-text-dark mb-8">
                        {{ __('Enter your authenticator code to access the admin panel.') }}
                    </p>

                    <form @submit.prevent="submit" class="space-y-5">
                        <div>
                            <InputLabel :value="__('Code from your authenticator app')" />
                            <TextInput
                                v-model="form.code"
                                type="text"
                                inputmode="numeric"
                                autocomplete="one-time-code"
                                autofocus
                                maxlength="6"
                                class="mt-1 block w-full tracking-widest text-center"
                                :placeholder="__('6-digit code')"
                            />
                            <InputError :message="form.errors.code" class="mt-1" />
                        </div>

                        <button
                            type="submit"
                            :disabled="form.processing || form.code.length !== 6"
                            class="w-full justify-center inline-flex items-center px-4 py-2 bg-rapanel-gold border border-transparent rounded-lg font-semibold text-sm text-white tracking-wide transition disabled:opacity-50 hover:brightness-110 focus:outline-none"
                        >
                            {{ __('Verify') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

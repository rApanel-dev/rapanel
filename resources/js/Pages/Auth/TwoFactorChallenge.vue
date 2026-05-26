<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const page = usePage();
const __ = (key) => page.props.translations?.[key] || key;

const useRecovery = ref(false);

const form = useForm({
    code:          '',
    recovery_code: '',
});

const submit = () => {
    form.post(route('two-factor.challenge'), {
        onFinish: () => form.reset(),
    });
};
</script>

<template>
    <Head :title="__('Two Factor Authentication')">
        <meta name="robots" content="noindex, nofollow">
    </Head>

    <MainLayout :show-bg="true">
        <div class="min-h-[calc(100vh-8.5rem)] flex items-center justify-center px-4 py-12">
            <div class="w-full max-w-md">
                <div class="bg-white dark:bg-rapanel-navy-900 rounded-2xl shadow-xl border border-rapanel-navy-100 dark:border-rapanel-navy-800 p-8">

                    <!-- Icono -->
                    <div class="flex justify-center mb-6">
                        <div class="w-16 h-16 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                            <svg class="w-8 h-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        </div>
                    </div>

                    <h1 class="text-2xl font-display font-bold text-center text-rapanel-navy-900 dark:text-white tracking-wide mb-2">
                        {{ __('Two Factor Authentication') }}
                    </h1>

                    <p class="text-sm text-center text-rapanel-text-light/60 dark:text-rapanel-text-dark mb-8">
                        {{ useRecovery
                            ? __('Enter one of your recovery codes to access your account.')
                            : __('Open your authenticator app and enter the 6-digit code.') }}
                    </p>

                    <form @submit.prevent="submit" class="space-y-5">

                        <!-- Código TOTP -->
                        <div v-if="!useRecovery">
                            <InputLabel :value="__('Authentication Code')" />
                            <TextInput
                                v-model="form.code"
                                type="text"
                                inputmode="numeric"
                                autocomplete="one-time-code"
                                autofocus
                                maxlength="6"
                                class="mt-1 block w-full tracking-widest text-center text-lg"
                                :placeholder="__('000000')"
                            />
                            <InputError :message="form.errors.code" class="mt-1" />
                        </div>

                        <!-- Código de recuperación -->
                        <div v-else>
                            <InputLabel :value="__('Recovery Code')" />
                            <TextInput
                                v-model="form.recovery_code"
                                type="text"
                                autocomplete="off"
                                autofocus
                                class="mt-1 block w-full font-mono"
                                :placeholder="__('xxxxxxxxxx-xxxxxxxxxx')"
                            />
                            <InputError :message="form.errors.code" class="mt-1" />
                        </div>

                        <PrimaryButton class="w-full justify-center" :disabled="form.processing">
                            {{ __('Verify') }}
                        </PrimaryButton>
                    </form>

                    <!-- Toggle recovery -->
                    <button
                        type="button"
                        @click="useRecovery = !useRecovery; form.reset()"
                        class="mt-6 w-full text-center text-sm text-blue-600 dark:text-blue-400 hover:underline"
                    >
                        {{ useRecovery ? __('Use authentication code instead') : __('Use a recovery code instead') }}
                    </button>
                </div>
            </div>
        </div>
    </MainLayout>
</template>

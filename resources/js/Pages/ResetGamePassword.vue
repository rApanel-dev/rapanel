<script setup>
import { Head, useForm, usePage } from '@inertiajs/vue3';
import Header from '@/Components/Header.vue';
import Footer from '@/Components/Footer.vue';
import BgMain from '@/Components/BgMain.vue';
import ActionButton from '@/Components/ActionButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';

const page  = usePage();
const __    = (key) => page.props.translations?.[key] || key;
const safeRoute = (name, params = {}) => { try { return route(name, params); } catch { return '#'; } };

const props = defineProps({
    token:  { type: String, required: true },
    userid: { type: String, default: '' },
});

const form = useForm({
    password:              '',
    password_confirmation: '',
});

const submit = () => form.post(safeRoute('game-password.update', { token: props.token }));
</script>

<template>
    <Head :title="__('Reset Game Password')" />

    <BgMain />
    <Header />

    <main class="min-h-screen pt-28 pb-16 px-4">
        <div class="max-w-md mx-auto">

            <div class="bg-white dark:bg-rapanel-navy-900 rounded-2xl border border-rapanel-navy-100 dark:border-white/10 shadow-sm p-8">

                <!-- Icon + title -->
                <div class="flex flex-col items-center mb-8">
                    <div class="w-14 h-14 rounded-2xl bg-rapanel-success/10 flex items-center justify-center mb-4">
                        <svg class="w-7 h-7 text-rapanel-success" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5V6.75a4.5 4.5 0 119 0v3.75M3.75 21.75h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H3.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/>
                        </svg>
                    </div>
                    <h1 class="text-xl font-display font-bold text-rapanel-navy-900 dark:text-white tracking-wide">{{ __('Reset Game Password') }}</h1>
                    <p class="text-sm text-rapanel-text-light dark:text-rapanel-text-dark opacity-60 text-center mt-1">
                        {{ __('Setting new password for') }} <span class="font-semibold text-rapanel-navy-900 dark:text-white">{{ userid }}</span>
                    </p>
                </div>

                <form @submit.prevent="submit" class="space-y-5">
                    <div>
                        <InputLabel :value="__('New Password')" />
                        <TextInput v-model="form.password" type="password" class="mt-1 w-full"
                                   :placeholder="__('Min 4 characters, max 32')"
                                   autocomplete="new-password" autofocus />
                        <InputError :message="form.errors.password" class="mt-1" />
                    </div>

                    <div>
                        <InputLabel :value="__('Confirm New Password')" />
                        <TextInput v-model="form.password_confirmation" type="password" class="mt-1 w-full"
                                   :placeholder="__('Repeat the new password')"
                                   autocomplete="new-password" />
                        <InputError :message="form.errors.password_confirmation" class="mt-1" />
                    </div>

                    <ActionButton type="submit" variant="success" fill="solid" size="lg" :disabled="form.processing" class="w-full">
                        {{ form.processing ? __('Saving...') : __('Save New Password') }}
                    </ActionButton>
                </form>
            </div>
        </div>
    </main>

    <Footer />
</template>

<script setup>
import { Head, useForm, usePage, router } from '@inertiajs/vue3';
import { watch } from 'vue';
import Header from '@/Components/Header.vue';
import Footer from '@/Components/Footer.vue';
import BgMain from '@/Components/BgMain.vue';
import ActionButton from '@/Components/ActionButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import FlashMessages from '@/Components/FlashMessages.vue';

const page  = usePage();
const __    = (key, r = {}) => {
    let t = page.props.translations?.[key] || key;
    Object.entries(r).forEach(([k, v]) => { t = t.replace(`:${k}`, v); });
    return t;
};
const safeRoute = (name, params = {}) => { try { return route(name, params); } catch { return '#'; } };

defineProps({
    tokenError: { type: String, default: null },
});

const form = useForm({
    userid: '',
    email:  '',
});

const submit = () => form.post(safeRoute('game-password.send'));

watch(() => page.props.flash?.success, (val) => {
    if (val) setTimeout(() => router.visit(safeRoute('dashboard')), 3000);
});
</script>

<template>
    <Head :title="__('Forgot Game Password')" />

    <BgMain />
    <Header />

    <main class="min-h-screen pt-28 pb-16 px-4">
        <div class="max-w-md mx-auto">

            <FlashMessages class="mb-6"
                :success="$page.props.flash?.success"
                :error="$page.props.flash?.error" />

            <!-- Token error -->
            <div v-if="tokenError"
                 class="mb-6 rounded-xl border border-rapanel-danger/30 bg-rapanel-danger/10 px-4 py-3 text-sm text-rapanel-danger">
                {{ tokenError }}
            </div>

            <!-- Card -->
            <div class="bg-white dark:bg-rapanel-navy-900 rounded-2xl border border-rapanel-navy-100 dark:border-white/10 shadow-sm p-8">

                <!-- Icon + title -->
                <div class="flex flex-col items-center mb-8">
                    <div class="w-14 h-14 rounded-2xl bg-rapanel-blue/10 flex items-center justify-center mb-4">
                        <svg class="w-7 h-7 text-rapanel-blue" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/>
                        </svg>
                    </div>
                    <h1 class="text-xl font-display font-bold text-rapanel-navy-900 dark:text-white tracking-wide">{{ __('Forgot Game Password') }}</h1>
                    <p class="text-sm text-rapanel-text-light dark:text-rapanel-text-dark opacity-60 text-center mt-1">
                        {{ __('forgot_game_password_desc') }}
                    </p>
                </div>

                <form @submit.prevent="submit" class="space-y-5">
                    <div>
                        <InputLabel :value="__('Game Account Username')" />
                        <TextInput v-model="form.userid" type="text" class="mt-1 w-full"
                                   :placeholder="__('Your in-game username')"
                                   autocomplete="off" autofocus />
                        <InputError :message="form.errors.userid" class="mt-1" />
                    </div>

                    <div>
                        <InputLabel :value="__('Registered Email (game account)')" />
                        <TextInput v-model="form.email" type="email" class="mt-1 w-full"
                                   :placeholder="__('Email used when creating the game account')"
                                   autocomplete="off" />
                        <InputError :message="form.errors.email" class="mt-1" />
                    </div>

                    <ActionButton type="submit" variant="blue" fill="solid" size="lg" :disabled="form.processing" class="w-full">
                        {{ form.processing ? __('Sending...') : __('Send Reset Link') }}
                    </ActionButton>
                </form>

                <div class="mt-6 text-center">
                    <a :href="safeRoute('dashboard')"
                       class="text-sm text-rapanel-blue hover:underline">
                        ← {{ __('Back to Dashboard') }}
                    </a>
                </div>
            </div>

            <!-- Help note -->
            <p v-if="!$page.props.flash?.success" class="mt-4 text-center text-xs text-rapanel-text-light dark:text-rapanel-text-dark opacity-40">
                {{ __('If your game account email is invalid, please contact a Game Master for assistance.') }}
            </p>
        </div>
    </main>

    <Footer />
</template>

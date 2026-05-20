<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';

const page = usePage();
const __ = (key) => page.props.translations?.[key] || key;

defineProps({
    mustVerifyEmail: { type: Boolean },
    status:          { type: String },
});

const user = page.props.auth.user;

const form = useForm({
    name:      user.name,
    email:     user.email,
    birthdate: user.birthdate ?? '',
});
</script>

<template>
    <form @submit.prevent="form.patch(route('profile.update'))" class="space-y-5">

        <!-- Display Name -->
        <div>
            <InputLabel for="name" :value="__('Display Name')" />
            <TextInput
                id="name"
                type="text"
                class="mt-1 block w-full bg-white dark:bg-rapanel-navy-800"
                v-model="form.name"
                required
                autofocus
                autocomplete="name"
            />
            <InputError class="mt-1.5" :message="form.errors.name" />
        </div>

        <!-- Email -->
        <div>
            <InputLabel for="email" :value="__('Email Address')" />
            <TextInput
                id="email"
                type="email"
                class="mt-1 block w-full bg-white dark:bg-rapanel-navy-800"
                v-model="form.email"
                required
                autocomplete="username"
            />
            <InputError class="mt-1.5" :message="form.errors.email" />
        </div>

        <!-- Birthdate -->
        <div>
            <InputLabel for="birthdate" :value="__('Birthdate')" />
            <TextInput
                id="birthdate"
                type="date"
                class="mt-1 block w-full bg-white dark:bg-rapanel-navy-800"
                v-model="form.birthdate"
                autocomplete="bday"
            />
            <InputError class="mt-1.5" :message="form.errors.birthdate" />
        </div>

        <!-- Email unverified notice -->
        <div
            v-if="mustVerifyEmail && user.email_verified_at === null"
            class="p-4 rounded-lg bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800/30"
        >
            <p class="text-sm text-yellow-800 dark:text-yellow-200">
                {{ __('Your email address is unverified.') }}
                <Link
                    :href="route('verification.send')"
                    method="post"
                    as="button"
                    class="ms-1 font-bold underline hover:no-underline focus:outline-none"
                >
                    {{ __('Click here to re-send the verification email.') }}
                </Link>
            </p>
            <p
                v-show="status === 'verification-link-sent'"
                class="mt-2 text-sm font-bold text-green-600 dark:text-green-400 flex items-center gap-1.5"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                {{ __('A new verification link has been sent to your email address.') }}
            </p>
        </div>

        <!-- Actions -->
        <div class="flex items-center gap-4 pt-1">
            <PrimaryButton :disabled="form.processing" :class="{ 'opacity-25': form.processing }">
                {{ __('Save Changes') }}
            </PrimaryButton>

            <Transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0 translate-x-2"
                enter-to-class="opacity-100 translate-x-0"
                leave-active-class="transition duration-150 ease-in"
                leave-to-class="opacity-0 translate-x-2"
            >
                <p v-if="form.recentlySuccessful" class="text-sm font-bold text-rapanel-success flex items-center gap-1.5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    {{ __('Profile updated.') }}
                </p>
            </Transition>
        </div>

    </form>
</template>

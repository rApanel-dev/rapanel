<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const page = usePage();
const __ = (key) => page.props.translations?.[key] || key;

const passwordInput        = ref(null);
const currentPasswordInput = ref(null);
const totpInput            = ref(null);

const hasTwoFactor = computed(() => !!page.props.auth?.user?.two_factor_confirmed_at);

const form = useForm({
    current_password:      '',
    password:              '',
    password_confirmation: '',
    totp_code:             '',
});

const updatePassword = () => {
    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: () => {
            if (form.errors.password) {
                form.reset('password', 'password_confirmation');
                passwordInput.value.focus();
            }
            if (form.errors.current_password) {
                form.reset('current_password');
                currentPasswordInput.value.focus();
            }
            if (form.errors.totp_code) {
                form.reset('totp_code');
                totpInput.value?.focus();
            }
        },
    });
};
</script>

<template>
    <form @submit.prevent="updatePassword" class="space-y-5">

        <div>
            <InputLabel for="current_password" :value="__('Current Password')" />
            <TextInput
                id="current_password"
                ref="currentPasswordInput"
                v-model="form.current_password"
                type="password"
                class="mt-1 block w-full bg-white dark:bg-rapanel-navy-800"
                autocomplete="current-password"
            />
            <InputError :message="form.errors.current_password" class="mt-1.5" />
        </div>

        <div v-if="hasTwoFactor">
            <InputLabel for="upd_totp" :value="__('Code from your authenticator app')" />
            <TextInput
                id="upd_totp"
                ref="totpInput"
                v-model="form.totp_code"
                type="text"
                inputmode="numeric"
                autocomplete="one-time-code"
                maxlength="6"
                class="mt-1 block w-full bg-white dark:bg-rapanel-navy-800 tracking-widest text-center"
                :placeholder="__('6-digit code')"
            />
            <InputError :message="form.errors.totp_code" class="mt-1.5" />
        </div>

        <div>
            <InputLabel for="password" :value="__('New Password')" />
            <TextInput
                id="password"
                ref="passwordInput"
                v-model="form.password"
                type="password"
                class="mt-1 block w-full bg-white dark:bg-rapanel-navy-800"
                autocomplete="new-password"
            />
            <InputError :message="form.errors.password" class="mt-1.5" />
        </div>

        <div>
            <InputLabel for="password_confirmation" :value="__('Confirm Password')" />
            <TextInput
                id="password_confirmation"
                v-model="form.password_confirmation"
                type="password"
                class="mt-1 block w-full bg-white dark:bg-rapanel-navy-800"
                autocomplete="new-password"
            />
            <InputError :message="form.errors.password_confirmation" class="mt-1.5" />
        </div>

        <div class="flex items-center gap-4 pt-1">
            <PrimaryButton :disabled="form.processing" :class="{ 'opacity-25': form.processing }">
                {{ __('Update Password') }}
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
                    {{ __('Saved successfully.') }}
                </p>
            </Transition>
        </div>

    </form>
</template>

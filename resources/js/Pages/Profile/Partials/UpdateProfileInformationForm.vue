<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { country_data } from '@/helpers';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const page = usePage();
const __ = (key) => page.props.translations?.[key] || key;

defineProps({
    mustVerifyEmail: { type: Boolean },
    status:          { type: String },
});

const user         = page.props.auth.user;
const hasTwoFactor = computed(() => !!user?.two_factor_confirmed_at);

// ── Formulario principal (nombre + birthdate) ──────────────────────────────
const LANGUAGES = [
    { code: 'es',    label: 'Español',        flag: '🇪🇸' },
    { code: 'en',    label: 'English',        flag: '🇬🇧' },
    { code: 'pt',    label: 'Português',      flag: '🇵🇹' },
    { code: 'pt-BR', label: 'Português (BR)', flag: '🇧🇷' },
    { code: 'fr',    label: 'Français',       flag: '🇫🇷' },
    { code: 'de',    label: 'Deutsch',        flag: '🇩🇪' },
    { code: 'ru',    label: 'Русский',        flag: '🇷🇺' },
];

const form = useForm({
    name:      user.name,
    birthdate: user.birthdate ?? '',
    country:   user.country  ?? '',
    locale:    user.locale   ?? page.props.locale ?? '',
});

// ── Modal cambio de email ──────────────────────────────────────────────────
const showEmailModal  = ref(false);
const showPassword    = ref(false);

const emailForm = useForm({
    email:            '',
    email_confirmation: '',
    current_password: '',
    totp_code:        '',
});

function openEmailModal() {
    emailForm.reset();
    emailForm.clearErrors();
    showPassword.value = false;
    showEmailModal.value = true;
}

function closeEmailModal() {
    showEmailModal.value = false;
}

const emailsMatch = computed(() =>
    emailForm.email_confirmation.length > 0 &&
    emailForm.email === emailForm.email_confirmation
);

const emailsMismatch = computed(() =>
    emailForm.email_confirmation.length > 0 &&
    emailForm.email !== emailForm.email_confirmation
);

function submitEmailChange() {
    emailForm.put(route('profile.email'), {
        onSuccess: () => closeEmailModal(),
        onError:   () => {},
    });
}
</script>

<template>
    <!-- ── Formulario principal ────────────────────────────────────────── -->
    <form @submit.prevent="form.patch(route('profile.update'))" class="space-y-5">

        <!-- Row 1: Display Name + Preferred Language -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
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

            <div>
                <InputLabel for="locale" :value="__('Preferred Language')" />
                <select
                    id="locale"
                    v-model="form.locale"
                    class="mt-1 block w-full rounded-md border border-gray-200 dark:border-rapanel-navy-700
                           bg-white dark:bg-rapanel-navy-800
                           px-3 py-2 text-sm text-rapanel-text-light dark:text-rapanel-text-dark
                           focus:border-rapanel-blue focus:ring-rapanel-blue shadow-sm"
                >
                    <option value="" disabled>{{ __('Select your language') }}</option>
                    <option v-for="lang in LANGUAGES" :key="lang.code" :value="lang.code">
                        {{ lang.flag }} {{ lang.label }}
                    </option>
                </select>
                <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                    {{ __('Used for notification emails and panel language.') }}
                </p>
                <InputError class="mt-1.5" :message="form.errors.locale" />
            </div>
        </div>

        <!-- Email (ancho completo) -->
        <div>
            <InputLabel for="email-display" :value="__('Email')" />
            <div class="mt-1 flex items-center gap-2">
                <div
                    id="email-display"
                    class="flex-1 rounded-md border border-gray-200 dark:border-rapanel-navy-700
                           bg-gray-50 dark:bg-rapanel-navy-800/60
                           px-3 py-2 text-sm text-gray-500 dark:text-gray-400
                           flex items-center gap-2 select-all"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 shrink-0 text-gray-400 dark:text-gray-500" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                    </svg>
                    {{ user.email }}
                </div>
                <button
                    type="button"
                    @click="openEmailModal"
                    class="shrink-0 rounded-md border border-rapanel-blue/40 bg-rapanel-blue/10
                           px-3 py-2 text-xs font-semibold text-rapanel-blue
                           hover:bg-rapanel-blue hover:text-white
                           dark:border-rapanel-blue/30 dark:bg-rapanel-blue/10 dark:text-rapanel-blue
                           dark:hover:bg-rapanel-blue dark:hover:text-white
                           transition-colors duration-150"
                >
                    {{ __('Change Email') }}
                </button>
            </div>
        </div>

        <!-- Row 2: Birthdate + Country -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <InputLabel for="birthdate" :value="__('Birthdate')" />
                <TextInput
                    id="birthdate"
                    type="date"
                    class="mt-1 block w-full bg-white dark:bg-rapanel-navy-800 dark:[color-scheme:dark]"
                    v-model="form.birthdate"
                    autocomplete="bday"
                />
                <InputError class="mt-1.5" :message="form.errors.birthdate" />
            </div>

            <div>
                <InputLabel for="country" :value="__('Country')" />
                <select
                    id="country"
                    v-model="form.country"
                    class="mt-1 block w-full rounded-md border border-gray-200 dark:border-rapanel-navy-700
                           bg-white dark:bg-rapanel-navy-800
                           px-3 py-2 text-sm text-rapanel-text-light dark:text-rapanel-text-dark
                           focus:border-rapanel-blue focus:ring-rapanel-blue shadow-sm"
                >
                    <option value="" disabled>{{ __('Select your country') }}</option>
                    <option v-for="c in country_data" :key="c.code" :value="c.code">{{ c.name }}</option>
                </select>
                <InputError class="mt-1.5" :message="form.errors.country" />
            </div>
        </div>

        <!-- Last Login / Last IP (read-only) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-1">
                    {{ __('Last Login') }}
                </p>
                <div class="rounded-md border border-gray-200 dark:border-rapanel-navy-700
                            bg-gray-50 dark:bg-rapanel-navy-800/60
                            px-3 py-2 text-sm text-gray-500 dark:text-gray-400 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 shrink-0 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                    </svg>
                    <span>{{ user.last_login ? new Date(user.last_login).toLocaleString() : __('Never') }}</span>
                </div>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-1">
                    {{ __('Last IP') }}
                </p>
                <div class="rounded-md border border-gray-200 dark:border-rapanel-navy-700
                            bg-gray-50 dark:bg-rapanel-navy-800/60
                            px-3 py-2 text-sm text-gray-500 dark:text-gray-400 flex items-center gap-2 font-mono">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 shrink-0 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                    </svg>
                    <span>{{ user.last_ip || __('Unknown') }}</span>
                </div>
            </div>
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

    <!-- ── Modal cambio de email ───────────────────────────────────────── -->
    <Modal :show="showEmailModal" max-width="md" @close="closeEmailModal">
        <div class="p-6">

            <!-- Header -->
            <div class="flex items-start justify-between mb-5">
                <div>
                    <h2 class="text-lg font-bold text-rapanel-navy-900 dark:text-white">
                        {{ __('Change Email') }}
                    </h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ hasTwoFactor
                            ? __('Enter your new email address and confirm with your authenticator code.')
                            : __('Enter your new email address and confirm with your current password.') }}
                    </p>
                </div>
                <button
                    type="button"
                    @click="closeEmailModal"
                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors ml-4"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>

            <form @submit.prevent="submitEmailChange" class="space-y-4">

                <!-- Email actual (referencia, solo lectura) -->
                <div>
                    <InputLabel :value="__('Current Email')" />
                    <div class="mt-1 rounded-md border border-gray-200 dark:border-rapanel-navy-700
                                bg-gray-50 dark:bg-rapanel-navy-800/60
                                px-3 py-2 text-sm text-gray-400 dark:text-gray-500">
                        {{ user.email }}
                    </div>
                </div>

                <!-- Nuevo email -->
                <div>
                    <InputLabel for="new-email" :value="__('New Email')" />
                    <TextInput
                        id="new-email"
                        type="email"
                        class="mt-1 block w-full bg-white dark:bg-rapanel-navy-800"
                        v-model="emailForm.email"
                        required
                        autofocus
                        autocomplete="new-password"
                        :placeholder="__('New Email')"
                    />
                    <InputError class="mt-1.5" :message="emailForm.errors.email" />
                </div>

                <!-- Confirmar nuevo email -->
                <div>
                    <InputLabel for="confirm-email" :value="__('Confirm New Email')" />
                    <TextInput
                        id="confirm-email"
                        type="email"
                        class="mt-1 block w-full bg-white dark:bg-rapanel-navy-800"
                        v-model="emailForm.email_confirmation"
                        required
                        autocomplete="new-password"
                        :placeholder="__('Confirm New Email')"
                        @paste.prevent
                        @drop.prevent
                    />
                    <InputError class="mt-1.5" :message="emailForm.errors.email_confirmation" />

                    <!-- Check en tiempo real -->
                    <Transition
                        enter-active-class="transition duration-150 ease-out"
                        enter-from-class="opacity-0 -translate-y-1"
                        enter-to-class="opacity-100 translate-y-0"
                    >
                        <p v-if="emailsMatch" class="mt-1.5 flex items-center gap-1.5 text-xs font-semibold text-rapanel-success">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 shrink-0" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            {{ __('Emails match') }}
                        </p>
                        <p v-else-if="emailsMismatch" class="mt-1.5 flex items-center gap-1.5 text-xs font-semibold text-rapanel-danger">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 shrink-0" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm-1-9a1 1 0 012 0v4a1 1 0 01-2 0V9zm1 7a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                            </svg>
                            {{ __('Emails do not match') }}
                        </p>
                    </Transition>
                </div>

                <!-- Contraseña siempre requerida -->
                <div>
                    <InputLabel for="email-password" :value="__('Current Password')" />
                    <div class="relative mt-1">
                        <TextInput
                            id="email-password"
                            :type="showPassword ? 'text' : 'password'"
                            class="block w-full pr-10 bg-white dark:bg-rapanel-navy-800"
                            v-model="emailForm.current_password"
                            required
                            autocomplete="new-password"
                            :placeholder="__('Current Password')"
                        />
                        <button
                            type="button"
                            @click="showPassword = !showPassword"
                            class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                            tabindex="-1"
                        >
                            <svg v-if="!showPassword" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                                <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
                            </svg>
                            <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M3.707 2.293a1 1 0 00-1.414 1.414l14 14a1 1 0 001.414-1.414l-1.473-1.473A10.014 10.014 0 0019.542 10C18.268 5.943 14.478 3 10 3a9.958 9.958 0 00-4.512 1.074l-1.78-1.781zm4.261 4.26l1.514 1.515a2.003 2.003 0 012.45 2.45l1.514 1.514a4 4 0 00-5.478-5.478z" clip-rule="evenodd" />
                                <path d="M12.454 16.697L9.75 13.992a4 4 0 01-3.742-3.741L2.335 6.578A9.98 9.98 0 00.458 10c1.274 4.057 5.064 7 9.542 7 .847 0 1.669-.105 2.454-.303z" />
                            </svg>
                        </button>
                    </div>
                    <InputError class="mt-1.5" :message="emailForm.errors.current_password" />
                </div>

                <!-- 2FA activo: también se requiere código TOTP -->
                <div v-if="hasTwoFactor">
                    <InputLabel for="email-totp" :value="__('Code from your authenticator app')" class="text-rapanel-blue font-bold uppercase text-xs" />
                    <TextInput
                        id="email-totp"
                        type="text"
                        inputmode="numeric"
                        autocomplete="one-time-code"
                        maxlength="6"
                        class="mt-1 block w-full bg-white dark:bg-rapanel-navy-800 border-rapanel-blue/30 focus:ring-rapanel-blue focus:border-rapanel-blue tracking-widest text-center"
                        v-model="emailForm.totp_code"
                        required
                        :placeholder="__('6-digit code')"
                    />
                    <InputError class="mt-1.5" :message="emailForm.errors.totp_code" />
                </div>

                <!-- Aviso de verificación si aplica -->
                <div
                    v-if="mustVerifyEmail"
                    class="flex items-start gap-2 rounded-lg bg-blue-50 dark:bg-blue-900/20
                           border border-blue-200 dark:border-blue-800/30 p-3"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 mt-0.5 text-rapanel-blue" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                    </svg>
                    <p class="text-xs text-rapanel-blue dark:text-blue-300">
                        {{ __('After changing your email you will need to verify the new address before accessing the panel.') }}
                    </p>
                </div>

                <!-- Botones -->
                <div class="flex justify-end gap-3 pt-2">
                    <button
                        type="button"
                        @click="closeEmailModal"
                        class="rounded-md px-4 py-2 text-sm font-medium text-gray-600 dark:text-gray-300
                               hover:bg-gray-100 dark:hover:bg-rapanel-navy-700 transition-colors"
                    >
                        {{ __('Cancel') }}
                    </button>
                    <PrimaryButton
                        :disabled="emailForm.processing"
                        :class="{ 'opacity-25': emailForm.processing }"
                    >
                        {{ __('Save Changes') }}
                    </PrimaryButton>
                </div>

            </form>
        </div>
    </Modal>
</template>

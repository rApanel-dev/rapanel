<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import Header from '@/Components/Header.vue';
import BgMain from '@/Components/BgMain.vue';
import Footer from '@/Components/Footer.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    qrCode:        { type: String,  default: null },
    secretKey:     { type: String,  default: null },
    enabled:       { type: Boolean, default: false },
    recoveryCodes: { type: Array,   default: () => [] },
});

const page   = usePage();
const __     = (key) => page.props.translations?.[key] || key;
const status = computed(() => page.props.flash?.status ?? null);

const forced = computed(() =>
    page.props.twoFactorForceAdmins &&
    page.props.auth?.user?.role === 'Admin' &&
    !props.enabled
);

const enableForm = useForm({ code: '' });
const submitEnable = () => {
    enableForm.post(route('two-factor.enable'), {
        preserveScroll: true,
        onSuccess: () => enableForm.reset(),
    });
};

const disableForm = useForm({ password: '', code: '', recovery_code: '' });
const useRecoveryDisable = ref(false);
const submitDisable = () => {
    disableForm.delete(route('two-factor.disable'), {
        preserveScroll: true,
        onSuccess: () => { disableForm.reset(); useRecoveryDisable.value = false; },
    });
};

const regenForm = useForm({});
const regenerateCodes = () => {
    regenForm.post(route('two-factor.recovery-codes'), { preserveScroll: true });
};

const showSecret = ref(false);
const copied = ref(false);
const copySecret = () => {
    navigator.clipboard.writeText(props.secretKey);
    copied.value = true;
    setTimeout(() => copied.value = false, 2000);
};
</script>

<template>
    <Head :title="__('Two Factor Authentication')" />

    <div class="min-h-screen text-rapanel-text-light dark:text-rapanel-text-dark font-sans antialiased">
        <BgMain />
        <Header />

        <main class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-6">

            <!-- Alerta de 2FA forzado -->
            <div v-if="forced"
                class="rounded-xl border border-amber-300 bg-amber-50 dark:bg-amber-900/20 dark:border-amber-700 p-4 flex gap-3">
                <svg class="w-5 h-5 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                </svg>
                <p class="text-sm text-amber-800 dark:text-amber-300">
                    {{ __('Two factor authentication is required for administrators. Please set it up before continuing.') }}
                </p>
            </div>

            <!-- Card principal -->
            <div class="bg-white dark:bg-rapanel-navy-900 border border-rapanel-navy-100 dark:border-white/10 rounded-xl shadow-xl dark:shadow-black/30 overflow-hidden">

                <!-- Header con botón volver -->
                <div class="px-6 py-5 border-b border-rapanel-navy-100 dark:border-white/10 bg-white dark:bg-rapanel-navy-900">
                    <div class="flex items-center gap-3">
                        <Link :href="route('profile.edit')"
                            class="flex items-center justify-center w-8 h-8 rounded-lg bg-rapanel-navy-100 dark:bg-rapanel-navy-700 hover:bg-rapanel-blue hover:text-white transition-all shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                            </svg>
                        </Link>
                        <div>
                            <p class="text-[10px] uppercase tracking-widest font-bold text-rapanel-text-light/40 dark:text-rapanel-text-dark/40">
                                {{ __('Master Account') }}
                            </p>
                            <h1 class="text-xl font-display font-bold text-rapanel-navy-900 dark:text-rapanel-text-dark">
                                {{ __('Two Factor Authentication') }}
                            </h1>
                        </div>
                        <!-- Badge estado -->
                        <span class="ml-auto inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold"
                            :class="enabled
                                ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                                : 'bg-rapanel-navy-100 text-rapanel-text-light/60 dark:bg-rapanel-navy-800 dark:text-rapanel-text-dark'">
                            <span class="w-1.5 h-1.5 rounded-full"
                                :class="enabled ? 'bg-green-500' : 'bg-rapanel-text-light/30 dark:bg-rapanel-text-dark/30'"></span>
                            {{ enabled ? __('Enabled') : __('Disabled') }}
                        </span>
                    </div>
                </div>

                <!-- Notificaciones flash -->
                <div v-if="status === '2fa-enabled'" class="mx-6 mt-5 rounded-lg border border-green-300 bg-green-50 dark:bg-green-900/20 dark:border-green-700 px-4 py-3 text-sm text-green-800 dark:text-green-300">
                    {{ __('Two factor authentication has been enabled.') }}
                </div>
                <div v-if="status === '2fa-disabled'" class="mx-6 mt-5 rounded-lg border border-rapanel-navy-200 bg-rapanel-navy-50 dark:bg-rapanel-navy-800 dark:border-rapanel-navy-700 px-4 py-3 text-sm text-rapanel-text-light dark:text-rapanel-text-dark">
                    {{ __('Two factor authentication has been disabled.') }}
                </div>
                <div v-if="status === '2fa-recovery-regenerated'" class="mx-6 mt-5 rounded-lg border border-blue-300 bg-blue-50 dark:bg-blue-900/20 dark:border-blue-700 px-4 py-3 text-sm text-blue-800 dark:text-blue-300">
                    {{ __('Recovery codes have been regenerated.') }}
                </div>

                <!-- Contenido -->
                <div class="p-6 space-y-6">

                    <!-- === AÚN NO HABILITADO: QR + confirmación === -->
                    <template v-if="!enabled">
                        <p class="text-sm text-rapanel-text-light/70 dark:text-rapanel-text-dark">
                            {{ __('Scan the QR code with your authenticator app (Google Authenticator, Authy, etc.), then enter the 6-digit code to confirm.') }}
                        </p>

                        <!-- QR -->
                        <div class="flex justify-center">
                            <div class="p-3 bg-white rounded-xl border border-rapanel-navy-100 dark:border-rapanel-navy-700 inline-block">
                                <img :src="qrCode" alt="QR 2FA" class="w-48 h-48" />
                            </div>
                        </div>

                        <!-- Clave manual -->
                        <div>
                            <button type="button" @click="showSecret = !showSecret"
                                class="text-sm text-rapanel-blue hover:underline">
                                {{ showSecret ? __('Hide manual key') : __('Cannot scan? Enter key manually') }}
                            </button>
                            <div v-if="showSecret" class="mt-2 flex items-center gap-2">
                                <code class="flex-1 bg-rapanel-navy-50 dark:bg-rapanel-navy-800 border border-rapanel-navy-100 dark:border-rapanel-navy-700 rounded-lg px-3 py-2 text-sm font-mono tracking-widest break-all text-rapanel-text-light dark:text-rapanel-text-dark">
                                    {{ secretKey }}
                                </code>
                                <button type="button" @click="copySecret"
                                    class="text-xs px-3 py-2 rounded-lg border border-rapanel-navy-200 dark:border-rapanel-navy-700 hover:bg-rapanel-navy-50 dark:hover:bg-rapanel-navy-800 transition text-rapanel-text-light dark:text-rapanel-text-dark shrink-0">
                                    {{ copied ? __('Copied!') : __('Copy') }}
                                </button>
                            </div>
                        </div>

                        <!-- Formulario confirmación -->
                        <form @submit.prevent="submitEnable" class="space-y-3">
                            <InputLabel :value="__('Authentication Code')" />
                            <TextInput
                                v-model="enableForm.code"
                                type="text"
                                inputmode="numeric"
                                maxlength="6"
                                autocomplete="one-time-code"
                                class="block w-full tracking-widest text-center text-lg"
                                :placeholder="__('000000')"
                            />
                            <InputError :message="enableForm.errors.code" />
                            <button type="submit" :disabled="enableForm.processing"
                                class="w-full py-2.5 px-4 rounded-xl bg-rapanel-success hover:bg-rapanel-success/90 text-white font-semibold text-sm transition disabled:opacity-50">
                                {{ __('Enable Two Factor Authentication') }}
                            </button>
                        </form>
                    </template>

                    <!-- === YA HABILITADO: códigos de recuperación + desactivar === -->
                    <template v-else>

                        <!-- Códigos de recuperación -->
                        <div v-if="recoveryCodes.length" class="space-y-3">
                            <div class="flex items-center justify-between">
                                <p class="text-sm font-semibold text-rapanel-navy-900 dark:text-white">
                                    {{ __('Recovery Codes') }}
                                </p>
                                <button type="button" @click="regenerateCodes" :disabled="regenForm.processing"
                                    class="text-xs text-rapanel-blue hover:underline disabled:opacity-50">
                                    {{ __('Regenerate') }}
                                </button>
                            </div>
                            <p class="text-xs text-rapanel-text-light/60 dark:text-rapanel-text-dark">
                                {{ __('Store these codes in a safe place. Each code can only be used once.') }}
                            </p>
                            <div class="grid grid-cols-2 gap-2 bg-rapanel-navy-50 dark:bg-rapanel-navy-800 border border-rapanel-navy-100 dark:border-rapanel-navy-700 rounded-xl p-4">
                                <code v-for="code in recoveryCodes" :key="code"
                                    class="text-xs font-mono text-rapanel-text-light dark:text-rapanel-text-dark text-center py-1">
                                    {{ code }}
                                </code>
                            </div>
                        </div>

                        <!-- Separador + Desactivar -->
                        <div class="border-t border-rapanel-navy-100 dark:border-white/10 pt-5 space-y-3">
                            <div>
                                <p class="text-sm font-semibold text-rapanel-danger">
                                    {{ __('Disable Two Factor Authentication') }}
                                </p>
                                <p class="text-xs text-rapanel-text-light/50 dark:text-rapanel-text-dark mt-0.5">
                                    {{ __('Your password and current authentication code are required to disable 2FA.') }}
                                </p>
                            </div>
                            <form @submit.prevent="submitDisable" class="space-y-3">
                                <!-- Contraseña -->
                                <div>
                                    <InputLabel :value="__('Current Password')" />
                                    <TextInput
                                        v-model="disableForm.password"
                                        type="password"
                                        autocomplete="current-password"
                                        class="mt-1 block w-full"
                                    />
                                    <InputError :message="disableForm.errors.password" />
                                </div>

                                <!-- Código TOTP -->
                                <div v-if="!useRecoveryDisable">
                                    <InputLabel :value="__('Authentication Code')" />
                                    <TextInput
                                        v-model="disableForm.code"
                                        type="text"
                                        inputmode="numeric"
                                        maxlength="6"
                                        autocomplete="one-time-code"
                                        class="mt-1 block w-full tracking-widest text-center text-lg"
                                        :placeholder="__('000000')"
                                    />
                                    <InputError :message="disableForm.errors.code" />
                                </div>

                                <!-- Código de recuperación -->
                                <div v-else>
                                    <InputLabel :value="__('Recovery Code')" />
                                    <TextInput
                                        v-model="disableForm.recovery_code"
                                        type="text"
                                        autocomplete="off"
                                        class="mt-1 block w-full font-mono"
                                        :placeholder="__('xxxxxxxxxx-xxxxxxxxxx')"
                                    />
                                    <InputError :message="disableForm.errors.code" />
                                </div>

                                <button type="button"
                                    @click="useRecoveryDisable = !useRecoveryDisable; disableForm.code = ''; disableForm.recovery_code = ''"
                                    class="text-xs text-rapanel-blue hover:underline">
                                    {{ useRecoveryDisable ? __('Use authentication code instead') : __('Use a recovery code instead') }}
                                </button>

                                <button type="submit" :disabled="disableForm.processing"
                                    class="w-full py-2.5 px-4 rounded-xl bg-rapanel-danger hover:bg-rapanel-danger/90 text-white font-semibold text-sm transition disabled:opacity-50">
                                    {{ __('Disable') }}
                                </button>
                            </form>
                        </div>
                    </template>
                </div>
            </div>

        </main>
    </div>

    <Footer />
</template>

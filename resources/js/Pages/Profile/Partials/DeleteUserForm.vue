<script setup>
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { nextTick, ref, watch } from 'vue';

const props = defineProps({
    status: { type: String, default: null },
});

const page = usePage();
const __ = (key) => page.props.translations?.[key] || key;

const confirmingUserDeletion = ref(false);
const emailSent              = ref(false);
const passwordInput          = ref(null);

const form = useForm({ password: '' });

const confirmUserDeletion = () => {
    emailSent.value = false;
    confirmingUserDeletion.value = true;
    nextTick(() => passwordInput.value?.focus());
};

const deleteUser = () => {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => {
            // Eliminación directa (RA_REQUIRE_EMAIL_VERIFY=false) → redirige a /
            // Si hubo email, el status ya fue detectado por el watcher
        },
        onError: () => passwordInput.value?.focus(),
        onFinish: () => form.reset(),
    });
};

const closeModal = () => {
    confirmingUserDeletion.value = false;
    emailSent.value = false;
    form.clearErrors();
    form.reset();
};

// Detecta cuando el backend responde con 'deletion-email-sent'
watch(
    () => page.props.status,
    (status) => {
        if (status === 'deletion-email-sent' && confirmingUserDeletion.value) {
            emailSent.value = true;
            form.reset();
        }
    }
);
</script>

<template>
    <DangerButton @click="confirmUserDeletion">
        {{ __('Delete Account') }}
    </DangerButton>

    <Modal :show="confirmingUserDeletion" @close="closeModal">
        <div class="p-6 bg-white dark:bg-rapanel-navy-900">

            <!-- ── Estado 1: Confirmar con contraseña ── -->
            <template v-if="!emailSent">
                <h2 class="text-lg font-bold text-rapanel-navy-900 dark:text-white mb-2
                            border-b border-rapanel-navy-100 dark:border-gray-700 pb-3
                            uppercase tracking-wider">
                    {{ __('Are you sure you want to delete your account?') }}
                </h2>

                <p class="text-sm text-rapanel-text-light/60 dark:text-rapanel-text-dark/60 mb-6 italic">
                    {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.') }}
                </p>

                <div class="space-y-4">
                    <div>
                        <InputLabel for="del_password" :value="__('Password')" class="sr-only" />
                        <TextInput
                            id="del_password"
                            ref="passwordInput"
                            v-model="form.password"
                            type="password"
                            class="block w-full bg-white dark:bg-rapanel-navy-800"
                            :placeholder="__('Password')"
                            autocomplete="new-password"
                            @keyup.enter="deleteUser"
                        />
                        <InputError :message="form.errors.password" class="mt-1.5" />
                    </div>

                    <div class="flex justify-end gap-3 pt-1">
                        <SecondaryButton @click="closeModal">{{ __('Cancel') }}</SecondaryButton>
                        <DangerButton
                            :class="{ 'opacity-25': form.processing }"
                            :disabled="form.processing"
                            @click="deleteUser"
                        >
                            {{ __('Delete Account') }}
                        </DangerButton>
                    </div>
                </div>
            </template>

            <!-- ── Estado 2: Email de confirmación enviado ── -->
            <template v-else>
                <div class="flex flex-col items-center text-center py-4 gap-4">
                    <!-- Icono email -->
                    <div class="w-16 h-16 rounded-full bg-rapanel-danger/10 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-rapanel-danger" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                    </div>

                    <div>
                        <h2 class="text-lg font-bold text-rapanel-navy-900 dark:text-white mb-2">
                            {{ __('Check your email') }}
                        </h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400 max-w-sm">
                            {{ __('account_deletion_email_sent') }}
                        </p>
                    </div>

                    <!-- Advertencia -->
                    <div class="w-full rounded-lg border border-rapanel-danger/30 bg-rapanel-danger/5 px-4 py-3 text-left">
                        <p class="text-xs font-semibold text-rapanel-danger">
                            ⚠ {{ __('account_deletion_link_expires') }}
                        </p>
                    </div>

                    <SecondaryButton @click="closeModal" class="mt-2">
                        {{ __('Close') }}
                    </SecondaryButton>
                </div>
            </template>

        </div>
    </Modal>
</template>

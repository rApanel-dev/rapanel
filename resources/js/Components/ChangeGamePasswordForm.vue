<script setup>
import { ref } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue'; 
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    account: Object, // Recibimos la cuenta de juego específica
});

// Implementación del helper de traducción
const page = usePage();
const __ = (key) => {
    return page.props.translations?.[key] || key;
};

const confirmingPasswordChange = ref(false);

// Preparamos el formulario con Inertia
const form = useForm({
    current_password: '', // Contraseña del Panel Web
    password: '',         // Nueva contraseña del juego
    password_confirmation: '',
});

const confirmPasswordChange = () => {
    confirmingPasswordChange.value = true;
};

const changePassword = () => {
    form.put(route('game-accounts.password.update', props.account.account_id), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => {
            // Si falla, limpiamos las contraseñas
            if (form.errors.password) {
                form.reset('password', 'password_confirmation');
            }
            if (form.errors.current_password) {
                form.reset('current_password');
            }
        },
    });
};

const closeModal = () => {
    confirmingPasswordChange.value = false;
    form.reset();
    form.clearErrors();
};
</script>

<template>
    <section class="space-y-6">
        <PrimaryButton @click="confirmPasswordChange">
            {{ __('Change Password') }}
        </PrimaryButton>

        <Modal :show="confirmingPasswordChange" @close="closeModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-rapanel-text-light dark:text-rapanel-text-dark">
                    {{ __('Change Password for') }} {{ account.userid }}
                </h2>

                <p class="mt-1 text-sm text-rapanel-text-light/70 dark:text-rapanel-text-dark/70">
                    {{ __('Enter a new password for this game account. For security, we request your main web panel account password.') }}
                </p>

                <div class="mt-6">
                    <InputLabel for="password" :value="__('New Game Password')" />
                    <TextInput
                        id="password"
                        v-model="form.password"
                        type="password"
                        class="mt-1 block w-full"
                        :placeholder="__('Minimum 4 characters')"
                    />
                    <InputError :message="form.errors.password" class="mt-2" />
                </div>

                <div class="mt-6">
                    <InputLabel for="password_confirmation" :value="__('Confirm New Password')" />
                    <TextInput
                        id="password_confirmation"
                        v-model="form.password_confirmation"
                        type="password"
                        class="mt-1 block w-full"
                    />
                    <InputError :message="form.errors.password_confirmation" class="mt-2" />
                </div>

                <div class="mt-6">
                    <InputLabel for="current_password" :value="__('Master Account Password')" />
                    <TextInput
                        id="current_password"
                        v-model="form.current_password"
                        type="password"
                        class="mt-1 block w-full"
                        :placeholder="__('Your master account password')"
                        @keyup.enter="changePassword"
                    />
                    <InputError :message="form.errors.current_password" class="mt-2" />
                </div>

                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="closeModal">
                        {{ __('Cancel') }}
                    </SecondaryButton>

                    <PrimaryButton
                        class="ms-3"
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                        @click="changePassword"
                    >
                        {{ __('Update Password') }}
                    </PrimaryButton>
                </div>
            </div>
        </Modal>
    </section>
</template>

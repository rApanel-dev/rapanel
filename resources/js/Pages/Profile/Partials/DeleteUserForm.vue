<script setup>
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';

const page = usePage();
const __ = (key) => page.props.translations?.[key] || key;

const confirmingUserDeletion = ref(false);
const passwordInput          = ref(null);

const form = useForm({ password: '' });

const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true;
    nextTick(() => passwordInput.value.focus());
};

const deleteUser = () => {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError:   () => passwordInput.value.focus(),
        onFinish:  () => form.reset(),
    });
};

const closeModal = () => {
    confirmingUserDeletion.value = false;
    form.clearErrors();
    form.reset();
};
</script>

<template>
    <DangerButton @click="confirmUserDeletion">
        {{ __('Delete Account') }}
    </DangerButton>

    <Modal :show="confirmingUserDeletion" @close="closeModal">
        <div class="p-6 bg-white dark:bg-rapanel-navy-900">
            <h2 class="text-lg font-bold text-rapanel-navy-900 dark:text-white mb-2 border-b border-rapanel-navy-100 dark:border-gray-700 pb-3 uppercase tracking-wider">
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
        </div>
    </Modal>
</template>

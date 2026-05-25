<script setup>
import { ref, computed } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import DangerButton from '@/Components/DangerButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    account:  Object,
    disabled: { type: Boolean, default: false },
});

const page = usePage();
const __ = (key) => page.props.translations?.[key] || key;

const isAdmin      = computed(() => page.props.auth?.user?.role === 'Admin');
const hasTwoFactor = computed(() => !!page.props.auth?.user?.two_factor_confirmed_at);

const confirmingAccountDeletion = ref(false);
const step = ref(1);

const form = useForm({
    password:  '',
    totp_code: '',
});

const openModal = () => {
    step.value = 1; // Reiniciamos al paso 1
    confirmingAccountDeletion.value = true;
};

const closeModal = () => {
    confirmingAccountDeletion.value = false;
    form.reset();
    form.clearErrors();
};

const nextStep = () => {
    step.value = 2;
};

const deleteAccount = () => {
    form.delete(route('game-accounts.destroy', props.account.account_id), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => form.reset(),
    });
};
</script>

<template>
    <div>
        <button
            @click="!disabled && openModal()"
            :disabled="disabled"
            class="flex items-center justify-center w-8 h-8 rounded-lg border transition-all"
            :class="disabled
                ? 'opacity-30 cursor-not-allowed bg-gray-100 dark:bg-gray-700 text-gray-400 border-gray-300 dark:border-gray-600'
                : 'bg-rapanel-danger/10 text-rapanel-danger border-rapanel-danger/20 hover:bg-rapanel-danger hover:text-white'"
            :title="disabled ? __('Character must be offline') : __('Delete Game Account')"
        >
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
        </button>

        <Modal :show="confirmingAccountDeletion" @close="closeModal">
            <div class="p-6 bg-white dark:bg-rapanel-navy-900 border border-rapanel-danger/30 rounded-lg text-left">
                
                <div v-if="step === 1">
                    <h2 class="text-lg font-medium text-rapanel-danger">
                        {{ __('Final Warning') }}
                    </h2>
                    <p class="mt-4 text-sm text-rapanel-text-light dark:text-rapanel-text-dark">
                        {{ __('You are about to delete the game account:') }} 
                        <span class="text-rapanel-navy-900 dark:text-white font-bold">{{ account.userid }}</span>.
                    </p>
                    <p class="mt-2 text-sm text-rapanel-text-light/70 dark:text-rapanel-text-dark/70">
                        {{ __('This will unlink all characters and progress from your master profile. Are you absolutely sure?') }}
                    </p>

                    <div class="mt-6 flex justify-end">
                        <SecondaryButton @click="closeModal">{{ __('Cancel') }}</SecondaryButton>
                        <DangerButton class="ms-3" @click="nextStep">
                            {{ __('Yes, I am sure') }}
                        </DangerButton>
                    </div>
                </div>

                <div v-else>
                    <h2 class="text-lg font-medium text-rapanel-danger">
                        {{ __('Identity Verification') }}
                    </h2>
                    
                    <div class="mt-6">
                        <InputLabel :value="__('Account to Delete')" class="text-rapanel-text-light dark:text-rapanel-text-dark" />
                        <div class="mt-1 block w-full px-3 py-2 bg-rapanel-navy-50 dark:bg-rapanel-navy-800 border border-rapanel-navy-100 dark:border-rapanel-navy-800 rounded-md text-rapanel-text-light/50 dark:text-rapanel-text-dark/50 select-none cursor-not-allowed">
                            {{ account.userid }}
                        </div>
                    </div>

                    <div class="mt-6">
                        <template v-if="hasTwoFactor">
                            <InputLabel for="delete_totp" :value="__('Code from your authenticator app')" class="text-rapanel-blue font-bold" />
                            <TextInput
                                id="delete_totp"
                                v-model="form.totp_code"
                                type="text" inputmode="numeric" autocomplete="one-time-code" maxlength="6"
                                class="mt-1 block w-full bg-white dark:bg-rapanel-navy-800 border-rapanel-blue/30 focus:ring-rapanel-danger focus:border-rapanel-danger tracking-widest text-center"
                                :placeholder="__('6-digit code')"
                                @keyup.enter="deleteAccount"
                            />
                            <InputError :message="form.errors.totp_code" class="mt-2" />
                        </template>
                        <template v-else>
                            <InputLabel for="password" :value="__('Master Account Password')" class="text-rapanel-text-light dark:text-rapanel-text-dark font-bold" />
                            <TextInput
                                id="password"
                                v-model="form.password"
                                type="password"
                                class="mt-1 block w-full bg-white dark:bg-rapanel-navy-800 border-rapanel-navy-100 dark:border-rapanel-navy-800 text-rapanel-text-light dark:text-white focus:ring-rapanel-danger focus:border-rapanel-danger"
                                :placeholder="__('Master Account Password')"
                                @keyup.enter="deleteAccount"
                            />
                            <InputError :message="form.errors.password" class="mt-2" />
                        </template>
                    </div>

                    <div class="mt-6 flex justify-end">
                        <SecondaryButton @click="step = 1">{{ __('Back') }}</SecondaryButton>
                        <DangerButton
                            class="ms-3"
                            :class="{ 'opacity-25': form.processing || (!hasTwoFactor && !form.password) || (hasTwoFactor && form.totp_code.length !== 6) }"
                            :disabled="form.processing || (!hasTwoFactor && !form.password) || (hasTwoFactor && form.totp_code.length !== 6)"
                            @click="deleteAccount"
                        >
                            {{ __('Confirm Deletion') }}
                        </DangerButton>
                    </div>
                </div>

            </div>
        </Modal>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import DangerButton from '@/Components/DangerButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    account: Object,
});

const page = usePage();
const __ = (key) => page.props.translations?.[key] || key;

const confirmingAccountDeletion = ref(false);
const step = ref(1); // Controlamos el paso actual

const form = useForm({
    password: '',
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
            @click="openModal" 
            class="text-red-500 hover:text-red-400 p-2 transition-colors"
            title="Delete Account"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
        </button>

        <Modal :show="confirmingAccountDeletion" @close="closeModal">
            <div class="p-6 bg-gray-900 border border-red-500/30 rounded-lg text-left">
                
                <div v-if="step === 1">
                    <h2 class="text-lg font-medium text-red-500">
                        {{ __('Final Warning') }}
                    </h2>
                    <p class="mt-4 text-sm text-gray-300">
                        {{ __('You are about to delete the game account:') }} 
                        <span class="text-white font-bold">{{ account.userid }}</span>.
                    </p>
                    <p class="mt-2 text-sm text-gray-400">
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
                    <h2 class="text-lg font-medium text-red-500">
                        {{ __('Identity Verification') }}
                    </h2>
                    
                    <div class="mt-6">
                        <InputLabel :value="__('Account to Delete')" class="text-gray-300" />
                        <div class="mt-1 block w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-md text-gray-500 select-none cursor-not-allowed">
                            {{ account.userid }}
                        </div>
                    </div>

                    <div class="mt-6">
                        <InputLabel for="password" :value="__('Master Account Password')" class="text-gray-300 font-bold" />
                        <TextInput
                            id="password"
                            v-model="form.password"
                            type="password"
                            class="mt-1 block w-full bg-gray-800 border-gray-700 text-white focus:ring-red-500"
                            :placeholder="__('Master Account Password')"
                            @keyup.enter="deleteAccount"
                        />
                        <InputError :message="form.errors.password" class="mt-2" />
                    </div>

                    <div class="mt-6 flex justify-end">
                        <SecondaryButton @click="step = 1">{{ __('Back') }}</SecondaryButton>
                        <DangerButton
                            class="ms-3"
                            :class="{ 'opacity-25': form.processing || !form.password }"
                            :disabled="form.processing || !form.password"
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

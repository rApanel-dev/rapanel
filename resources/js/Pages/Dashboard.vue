<script setup>
import { ref } from 'vue';
import { Head, usePage, useForm } from '@inertiajs/vue3';
import Header from '@/Components/Header.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

// 1. AÑADIMOS LAS PROPS PARA RECIBIR LOS DATOS DEL CONTROLADOR
const props = defineProps({
    gameAccountsCount: {
        type: Number,
        default: 0
    },
    gameAccounts: {
        type: Array,
        default: () => []
    }
});

const page = usePage();

// Helper de traducción
const __ = (key) => {
    return page.props.translations?.[key] || key;
};

// Control del estado del Modal
const creatingGameAccount = ref(false);

// Configuración del formulario
const form = useForm({
    userid: '',
    user_pass: '',
    sex: 'M', // Por defecto Masculino, requerido por rAthena
});

const openCreateModal = () => {
    creatingGameAccount.value = true;
};

const closeCreateModal = () => {
    creatingGameAccount.value = false;
    form.reset();
    form.clearErrors();
};

const createAccount = () => {
    form.post(route('game-accounts.store'), {
        preserveScroll: true,
        onSuccess: () => closeCreateModal(),
    });
};
</script>

<template>
    <Head :title="__('Master Account')" />

    <div class="min-h-screen bg-black text-gray-100 font-sans antialiased">
        
        <Header />

        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            
            <div class="bg-gray-900 border border-gray-800 rounded-lg p-6 md:p-10 shadow-2xl">
                
                <div class="flex justify-between items-center border-b border-gray-800 pb-4 mb-6">
                    <div>
                        <h1 class="text-2xl font-bold text-white uppercase tracking-widest">
                            {{ __('Master Account') }}
                        </h1>
                        <p class="text-gray-400 text-sm mt-1">
                            {{ __('Welcome to your central control panel,') }} <span class="text-blue-400 font-bold">{{ page.props.auth.user.name }}</span>.
                        </p>
                    </div>
                    <PrimaryButton @click="openCreateModal">
                        {{ __('Create Game Account') }}
                    </PrimaryButton>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-gray-800/50 border border-gray-700/50 rounded p-4">
                        <div class="text-xs text-gray-500 uppercase tracking-widest font-bold mb-2">{{ __('Game Accounts') }}</div>
                        <div class="text-3xl font-bold text-white">{{ gameAccountsCount }}</div>
                    </div>
                    
                    <div class="bg-gray-800/50 border border-gray-700/50 rounded p-4">
                        <div class="text-xs text-gray-500 uppercase tracking-widest font-bold mb-2">{{ __('Vote Points') }}</div>
                        <div class="text-3xl font-bold text-yellow-500">0</div>
                    </div>
                    
                    <div class="bg-gray-800/50 border border-gray-700/50 rounded p-4">
                        <div class="text-xs text-gray-500 uppercase tracking-widest font-bold mb-2">{{ __('Cash Points') }}</div>
                        <div class="text-3xl font-bold text-green-500">0</div>
                    </div>
                </div>
                
            </div>

            <div class="mt-8 bg-gray-900 border border-gray-800 rounded-lg shadow-xl overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-800 bg-gray-800/30">
                    <h3 class="text-lg font-semibold text-white tracking-wide uppercase">
                        {{ __('My Game Accounts') }}
                    </h3>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-black/50 text-gray-400 uppercase text-xs font-bold">
                            <tr>
                                <th class="px-6 py-4">{{ __('Account') }}</th>
                                <th class="px-6 py-4">{{ __('Gender') }}</th>
                                <th class="px-6 py-4 text-center">{{ __('Login Count') }}</th>
                                <th class="px-6 py-4">{{ __('Last Login') }}</th>
                                <th class="px-6 py-4">{{ __('Last IP') }}</th>
                                <th class="px-6 py-4">{{ __('Status') }}</th>
                                <th class="px-6 py-4 text-center">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-800">
                            <tr v-for="account in gameAccounts" :key="account.userid" class="hover:bg-gray-800/50 transition-colors">
                                <td class="px-6 py-4 font-medium text-blue-400">{{ account.userid }}</td>
                                <td class="px-6 py-4 text-gray-300">
                                    <span v-if="account.sex === 'M'" class="text-blue-500">♂ {{ __('Male') }}</span>
                                    <span v-else class="text-pink-500">♀ {{ __('Female') }}</span>
                                </td>
                                <td class="px-6 py-4 text-center text-gray-300">{{ account.logincount }}</td>
                                <td class="px-6 py-4 text-gray-400 text-xs italic">
                                    {{ account.lastlogin || __('Never') }}
                                </td>
                                <td class="px-6 py-4 text-gray-400 text-xs italic">
                                    {{ account.last_ip || __('Unknown') }}
                                </td>
                                <td class="px-6 py-4">
                                    <span v-if="account.state === 0" class="px-2 py-1 bg-green-900/30 text-green-500 rounded text-xs">
                                        ● {{ __('Active') }}
                                    </span>
                                    <span v-else class="px-2 py-1 bg-red-900/30 text-red-500 rounded text-xs">
                                        ● {{ __('Blocked') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 flex items-center justify-end space-x-2">
                                    <button 
                                        class="p-2 text-gray-400 hover:text-gray-200 hover:bg-gray-700/50 rounded-lg transition-all focus:outline-none" 
                                        :title="__('View Details')"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                    </button>
                                    
                                    <button 
                                        class="p-2 text-blue-400 hover:text-blue-300 hover:bg-gray-700/50 rounded-lg transition-all focus:outline-none" 
                                        :title="__('Change Password')"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z" />
                                        </svg>
                                    </button>
                                    
                                    <button 
                                        class="p-2 text-red-500 hover:text-red-400 hover:bg-red-500/10 rounded-lg transition-all focus:outline-none" 
                                        :title="__('Delete Account')"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="gameAccountsCount === 0">
                                <td colspan="5" class="px-6 py-10 text-center text-gray-500 italic">
                                    {{ __('No game accounts found. Create your first one above!') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            </main>

        <Modal :show="creatingGameAccount" @close="closeCreateModal">
            <div class="p-6 bg-gray-900">
                <h2 class="text-lg font-medium text-white mb-6 border-b border-gray-800 pb-2">
                    {{ __('Create New Game Account') }}
                </h2>

                <form @submit.prevent="createAccount" class="space-y-6">
                    <div>
                        <InputLabel for="userid" :value="__('Username')" class="text-gray-300" />
                        <TextInput
                            id="userid"
                            v-model="form.userid"
                            type="text"
                            class="mt-1 block w-full bg-gray-800 border-gray-700 text-white focus:ring-blue-500"
                            required
                            autofocus
                        />
                        <InputError class="mt-2" :message="form.errors.userid" />
                    </div>

                    <div>
                        <InputLabel for="user_pass" :value="__('Password')" class="text-gray-300" />
                        <TextInput
                            id="user_pass"
                            v-model="form.user_pass"
                            type="password"
                            class="mt-1 block w-full bg-gray-800 border-gray-700 text-white focus:ring-blue-500"
                            required
                        />
                        <InputError class="mt-2" :message="form.errors.user_pass" />
                    </div>

                    <div>
                        <InputLabel for="sex" :value="__('Gender')" class="text-gray-300" />
                        <select
                            id="sex"
                            v-model="form.sex"
                            class="mt-1 block w-full border-gray-700 bg-gray-800 text-white focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm"
                        >
                            <option value="M">{{ __('Male') }}</option>
                            <option value="F">{{ __('Female') }}</option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.sex" />
                    </div>

                    <div class="flex justify-end mt-6">
                        <SecondaryButton @click="closeCreateModal">
                            {{ __('Cancel') }}
                        </SecondaryButton>

                        <PrimaryButton
                            class="ms-3"
                            :class="{ 'opacity-25': form.processing }"
                            :disabled="form.processing"
                        >
                            {{ __('Create Account') }}
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

    </div>
</template>

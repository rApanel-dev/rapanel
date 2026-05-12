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
import ViewActivityLogs from '@/Components/ViewActivityLogs.vue';
import DeleteGameAccountForm from '@/Components/DeleteGameAccountForm.vue';

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

// --- ESTADO PARA CREAR CUENTA ---
const creatingGameAccount = ref(false);
const form = useForm({
    userid: '',
    user_pass: '',
    sex: 'M',
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

// --- ESTADO PARA CAMBIAR CONTRASEÑA ---
const changingPasswordFor = ref(null);

const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const openChangePasswordModal = (account) => {
    changingPasswordFor.value = account;
};

const closeChangePasswordModal = () => {
    changingPasswordFor.value = null;
    passwordForm.reset();
    passwordForm.clearErrors();
};

const updatePassword = () => {
    if (!changingPasswordFor.value) return;

    // Usamos account_id que es la llave primaria en rAthena
    passwordForm.put(route('game-accounts.password.update', changingPasswordFor.value.account_id), {
        preserveScroll: true,
        onSuccess: () => closeChangePasswordModal(),
        onError: () => {
            if (passwordForm.errors.password) {
                passwordForm.reset('password', 'password_confirmation');
            }
            if (passwordForm.errors.current_password) {
                passwordForm.reset('current_password');
            }
        },
    });
};

const showingLogsModal = ref(false);
const selectedAccountForLogs = ref(null);

const openLogsModal = (account) => {
    selectedAccountForLogs.value = account;
    showingLogsModal.value = true;
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
                                <th class="px-6 py-4">{{ __('created') }}</th>   
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
                                <td class="px-6 py-4 text-gray-400 text-xs italic">
                                    {{ account.created_at || __('Unknown') }}
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
                                    <button class="p-2 text-gray-400 hover:text-gray-200 hover:bg-gray-700/50 rounded-lg transition-all focus:outline-none" :title="__('View Details')">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                    </button>
                                    
                                    <button 
                                        @click="openChangePasswordModal(account)"
                                        class="p-2 text-blue-400 hover:text-blue-300 hover:bg-gray-700/50 rounded-lg transition-all focus:outline-none" 
                                        :title="__('Change Password')"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z" />
                                        </svg>
                                    </button>
                                    
                                    <button 
                                        @click="openLogsModal(account)"
                                        class="p-2 text-purple-400 hover:text-purple-300 hover:bg-gray-700/50 rounded-lg transition-all focus:outline-none" 
                                        :title="__('View Activity')"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                        </svg>
                                    </button>

                                    <DeleteGameAccountForm :account="account" />
                                </td>
                            </tr>
                            <tr v-if="gameAccountsCount === 0">
                                <td colspan="7" class="px-6 py-10 text-center text-gray-500 italic">
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
                        <TextInput id="userid" v-model="form.userid" type="text" class="mt-1 block w-full bg-gray-800 border-gray-700 text-white focus:ring-blue-500" required autofocus />
                        <InputError class="mt-2" :message="form.errors.userid" />
                    </div>
                    <div>
                        <InputLabel for="user_pass" :value="__('Password')" class="text-gray-300" />
                        <TextInput id="user_pass" v-model="form.user_pass" type="password" class="mt-1 block w-full bg-gray-800 border-gray-700 text-white focus:ring-blue-500" required />
                        <InputError class="mt-2" :message="form.errors.user_pass" />
                    </div>
                    <div>
                        <InputLabel for="sex" :value="__('Gender')" class="text-gray-300" />
                        <select id="sex" v-model="form.sex" class="mt-1 block w-full border-gray-700 bg-gray-800 text-white focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm">
                            <option value="M">{{ __('Male') }}</option>
                            <option value="F">{{ __('Female') }}</option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.sex" />
                    </div>
                    <div class="flex justify-end mt-6">
                        <SecondaryButton @click="closeCreateModal">{{ __('Cancel') }}</SecondaryButton>
                        <PrimaryButton class="ms-3" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">{{ __('Create Account') }}</PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <Modal :show="!!changingPasswordFor" @close="closeChangePasswordModal">
            <div class="p-6 bg-gray-900">
                <h2 class="text-lg font-medium text-white mb-2 border-b border-gray-800 pb-2">
                    {{ __('Change Password for') }} <span class="text-blue-400 font-bold">{{ changingPasswordFor?.userid }}</span>
                </h2>
                
                <p class="text-sm text-gray-400 mb-6 italic">
                    {{ __('For security reasons, please confirm your master account password.') }}
                </p>

                <form @submit.prevent="updatePassword" class="space-y-6">
                    <div>
                        <InputLabel for="new_password" :value="__('New Game Password')" class="text-gray-300" />
                        <TextInput
                            id="new_password"
                            v-model="passwordForm.password"
                            type="password"
                            class="mt-1 block w-full bg-gray-800 border-gray-700 text-white focus:ring-blue-500"
                            required
                        />
                        <InputError class="mt-2" :message="passwordForm.errors.password" />
                    </div>

                    <div>
                        <InputLabel for="password_confirmation" :value="__('Confirm New Password')" class="text-gray-300" />
                        <TextInput
                            id="password_confirmation"
                            v-model="passwordForm.password_confirmation"
                            type="password"
                            class="mt-1 block w-full bg-gray-800 border-gray-700 text-white focus:ring-blue-500"
                            required
                        />
                        <InputError class="mt-2" :message="passwordForm.errors.password_confirmation" />
                    </div>

                    <div class="pt-4 border-t border-gray-800">
                        <InputLabel for="current_password" :value="__('Master Account Password')" class="text-yellow-500 font-bold" />
                        <TextInput
                            id="current_password"
                            v-model="passwordForm.current_password"
                            type="password"
                            class="mt-1 block w-full bg-gray-800 border-yellow-700/50 text-white focus:ring-yellow-500"
                            required
                            :placeholder="__('Your web panel password')"
                        />
                        <InputError class="mt-2" :message="passwordForm.errors.current_password" />
                    </div>

                    <div class="flex justify-end mt-6">
                        <SecondaryButton @click="closeChangePasswordModal">
                            {{ __('Cancel') }}
                        </SecondaryButton>

                        <PrimaryButton
                            class="ms-3"
                            :class="{ 'opacity-25': passwordForm.processing }"
                            :disabled="passwordForm.processing"
                        >
                            {{ __('Update Password') }}
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>
        <ViewActivityLogs 
            :show="showingLogsModal" 
            :account="selectedAccountForLogs" 
            @close="showingLogsModal = false" 
            />
    </div>
</template>

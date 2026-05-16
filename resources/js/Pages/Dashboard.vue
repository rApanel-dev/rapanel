<script setup>
import { ref, computed } from 'vue';
import { Head, usePage, useForm, Link } from '@inertiajs/vue3';
import Header from '@/Components/Header.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import ClaimAccountModal from '@/Components/ClaimAccountModal.vue';

// 1. AÑADIMOS LAS PROPS PARA RECIBIR LOS DATOS DEL CONTROLADOR
const props = defineProps({
    gameAccountsCount: {
        type: Number,
        default: 0
    },
    gameAccounts: {
        type: Array,
        default: () => []
    },
    maxAccounts: Number,
});

// Lógica de validación
const isLimitReached = computed(() => {
    return props.gameAccountsCount >= props.maxAccounts;
});

const page = usePage();

// Helper de traducción
const __ = (key) => {
    return page.props.translations?.[key] || key;
};

const flashSuccess = computed(() => page.props.flash?.success);
const flashError   = computed(() => page.props.flash?.error);

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

// --- ESTADO PARA VINCULAR CUENTA ---
// En tu <script setup>
const isClaimModalOpen = ref(false);

const openClaimModal = () => {
    isClaimModalOpen.value = true;
};

</script>

<template>
    <Head :title="__('Master Account')" />

    <div class="min-h-screen bg-rapanel-navy-50 dark:bg-rapanel-navy-900 text-rapanel-text-light dark:text-rapanel-text-dark font-sans antialiased transition-colors duration-300">
        
        <Header />

        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 space-y-6">

            <div v-if="flashSuccess" class="flex items-center gap-3 px-5 py-3 rounded-xl bg-rapanel-success/10 border border-rapanel-success/30 text-rapanel-success text-sm font-medium">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                {{ flashSuccess }}
            </div>
            <div v-if="flashError" class="flex items-center gap-3 px-5 py-3 rounded-xl bg-rapanel-danger/10 border border-rapanel-danger/30 text-rapanel-danger text-sm font-medium">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9.303 3.376c.864 1.505-.15 3.374-1.95 3.374H2.647c-1.8 0-2.815-1.869-1.951-3.374L10.049 4.126c.9-1.56 3.002-1.56 3.902 0L21.303 16.126z"/></svg>
                {{ flashError }}
            </div>

            <div class="bg-white dark:bg-rapanel-navy-800 border border-rapanel-navy-100 dark:border-gray-700/50 rounded-xl p-6 md:p-10 shadow-xl">
                
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center border-b border-rapanel-navy-100 dark:border-gray-700 pb-6 mb-8 gap-6">
                    <div class="space-y-1">
                        <h1 class="text-2xl font-bold text-rapanel-navy-900 dark:text-white uppercase tracking-widest">
                            {{ __('Master Account') }}
                        </h1>
                        <p class="text-rapanel-text-light/70 dark:text-rapanel-text-dark/70 text-sm">
                            {{ __('Welcome to your central control panel,') }} 
                            <span class="text-rapanel-blue font-bold">{{ $page.props.auth.user.name }}</span>.
                        </p>
                    </div>
                    <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                        <PrimaryButton 
                            @click="!isLimitReached && openCreateModal()" 
                            :disabled="isLimitReached"
                            class="w-full sm:w-auto justify-center transition-all duration-300"
                            :class="{ 
                                'opacity-40 grayscale cursor-not-allowed border-gray-500 text-gray-500': isLimitReached 
                            }"
                            :title="isLimitReached ? __('Maximum account limit reached') : __('Create New Game Account')"
                        >
                            {{ __('Create Game Account') }}
                        </PrimaryButton>
                        <PrimaryButton
                            @click="!isLimitReached && openClaimModal()" 
                            :disabled="isLimitReached"
                            class="w-full sm:w-auto justify-center transition-all duration-300"
                            :class="{ 
                                'opacity-40 grayscale cursor-not-allowed border-gray-500 text-gray-500': isLimitReached 
                            }"
                            :title="isLimitReached ? __('Maximum account limit reached') : __('Link an existing account')"
                        >
                            {{ __('Claim Account') }}
                        </PrimaryButton>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-rapanel-navy-50/50 dark:bg-black/20 border border-rapanel-navy-100 dark:border-gray-700/30 rounded-xl p-5 transition-transform hover:scale-[1.02]">
                        <div class="text-xs text-rapanel-text-light/50 dark:text-rapanel-text-dark/50 uppercase tracking-widest font-bold mb-2">{{ __('Game Accounts') }}</div>
                        <div class="text-3xl font-bold text-rapanel-navy-900 dark:text-white">{{ gameAccountsCount }} / {{ maxAccounts }}</div>
                    </div>
                    <div class="bg-rapanel-navy-50/50 dark:bg-black/20 border border-rapanel-navy-100 dark:border-gray-700/30 rounded-xl p-5 transition-transform hover:scale-[1.02]">
                        <div class="text-xs text-rapanel-text-light/50 dark:text-rapanel-text-dark/50 uppercase tracking-widest font-bold mb-2">{{ __('Vote Points') }}</div>
                        <div class="text-3xl font-bold text-rapanel-gold">0</div>
                    </div>
                    <div class="bg-rapanel-navy-50/50 dark:bg-black/20 border border-rapanel-navy-100 dark:border-gray-700/30 rounded-xl p-5 transition-transform hover:scale-[1.02]">
                        <div class="text-xs text-rapanel-text-light/50 dark:text-rapanel-text-dark/50 uppercase tracking-widest font-bold mb-2">{{ __('Cash Points') }}</div>
                        <div class="text-3xl font-bold text-rapanel-success">0</div>
                    </div>
                </div>
                
            </div>

            <div class="mt-8 bg-white dark:bg-rapanel-navy-800 border border-rapanel-navy-100 dark:border-gray-700/50 rounded-xl shadow-xl overflow-hidden">
                <div class="px-6 py-5 border-b border-rapanel-navy-100 dark:border-gray-700 bg-rapanel-navy-50/30 dark:bg-black/10">
                    <h3 class="text-lg font-bold text-rapanel-navy-900 dark:text-white tracking-wide uppercase">
                        {{ __('My Game Accounts') }}
                    </h3>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-rapanel-navy-50 dark:bg-black/40 text-rapanel-text-light/80 dark:text-rapanel-text-dark uppercase text-xs font-bold">
                            <tr>
                                <th class="px-6 py-4">{{ __('Account') }}</th>
                                <th class="px-6 py-4">{{ __('Gender') }}</th>
                                <th class="px-6 py-4 text-center">{{ __('Login Count') }}</th>
                                <th class="px-6 py-4 text-center">{{ __('Last Login') }}</th>
                                <th class="px-6 py-4 text-center">{{ __('Last IP') }}</th>
                                <th class="px-6 py-4 text-center">{{ __('Created') }}</th>   
                                <th class="px-6 py-4 text-center">{{ __('Status') }}</th>
                                <th class="px-6 py-4 text-center">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-rapanel-navy-100 dark:divide-gray-700">
                            <tr v-for="account in gameAccounts" :key="account.userid" class="hover:bg-rapanel-navy-50/50 dark:hover:bg-gray-700/30 transition-colors">
                                <td class="px-6 py-4 font-bold text-rapanel-blue">{{ account.userid }}</td>
                                <td class="px-6 py-4 text-rapanel-text-light dark:text-rapanel-text-dark">
                                    <span v-if="account.sex === 'M'" class="flex items-center text-blue-500 font-medium">
                                        <span class="me-1.5 text-lg">♂</span> {{ __('Male') }}
                                    </span>
                                    <span v-else class="flex items-center text-pink-500 font-medium">
                                        <span class="me-1.5 text-lg">♀</span> {{ __('Female') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">{{ account.logincount }}</td>
                                <td class="px-6 py-4 text-center text-xs italic">
                                    {{ account.lastlogin || __('Never') }}
                                </td>
                                <td class="px-6 py-4 text-center text-xs italic">
                                    {{ account.last_ip || __('Unknown') }}
                                </td>
                                <td class="px-6 py-4 text-center text-xs italic">
                                    {{ account.created_at || __('Unknown') }}
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span v-if="account.state === 0" class="inline-flex items-center px-2.5 py-0.5 bg-rapanel-success/10 text-rapanel-success border border-rapanel-success/20 rounded-full text-xs font-bold uppercase tracking-tighter">
                                        <span class="w-1.5 h-1.5 bg-rapanel-success rounded-full me-1.5 animate-pulse"></span>
                                        {{ __('Active') }}
                                    </span>
                                    <span v-else class="inline-flex items-center px-2.5 py-0.5 bg-rapanel-danger/10 text-rapanel-danger border border-rapanel-danger/20 rounded-full text-xs font-bold uppercase tracking-tighter">
                                        <span class="w-1.5 h-1.5 bg-rapanel-danger rounded-full me-1.5"></span>
                                        {{ __('Blocked') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <Link
                                        :href="route('game-accounts.show', account.account_id)"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold bg-rapanel-blue/10 text-rapanel-blue border border-rapanel-blue/20 hover:bg-rapanel-blue hover:text-white transition-all"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3.5 h-3.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        {{ __('View Details') }}
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="gameAccountsCount === 0">
                                <td colspan="8" class="px-6 py-12 text-center text-rapanel-text-light/40 dark:text-gray-500 italic">
                                    <div class="flex flex-col items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 mb-2 opacity-20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                        </svg>
                                        {{ __('No game accounts found. Create or claim your first one above!') }}
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>

        <Modal :show="creatingGameAccount" @close="closeCreateModal">
            <div class="p-6 bg-white dark:bg-rapanel-navy-800 transition-colors duration-300">
                <h2 class="text-lg font-bold text-rapanel-navy-900 dark:text-white mb-6 border-b border-rapanel-navy-100 dark:border-gray-700 pb-3 uppercase tracking-wider">
                    {{ __('Create New Game Account') }}
                </h2>
                <form @submit.prevent="createAccount" class="space-y-5">
                    <div>
                        <InputLabel for="userid" :value="__('Username')" />
                        <TextInput id="userid" v-model="form.userid" type="text" class="mt-1 block w-full bg-white dark:bg-rapanel-navy-900 border-rapanel-navy-100 dark:border-gray-700" required autofocus />
                        <InputError class="mt-2" :message="form.errors.userid" />
                    </div>
                    <div>
                        <InputLabel for="user_pass" :value="__('Password')" />
                        <TextInput id="user_pass" v-model="form.user_pass" type="password" class="mt-1 block w-full bg-white dark:bg-rapanel-navy-900 border-rapanel-navy-100 dark:border-gray-700" required />
                        <InputError class="mt-2" :message="form.errors.user_pass" />
                    </div>
                    <div>
                        <InputLabel for="sex" :value="__('Gender')" />
                        <select id="sex" v-model="form.sex" class="mt-1 block w-full border-rapanel-navy-100 dark:border-gray-700 bg-white dark:bg-rapanel-navy-900 text-rapanel-text-light dark:text-rapanel-text-dark focus:border-rapanel-blue focus:ring-rapanel-blue rounded-md shadow-sm transition-colors">
                            <option value="M">{{ __('Male') }}</option>
                            <option value="F">{{ __('Female') }}</option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.sex" />
                    </div>
                    <div class="flex justify-end mt-8 gap-3">
                        <SecondaryButton @click="closeCreateModal">{{ __('Cancel') }}</SecondaryButton>
                        <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">{{ __('Create Account') }}</PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <ClaimAccountModal 
            :show="isClaimModalOpen" 
            @close="isClaimModalOpen = false" 
        />
    </div>
</template>

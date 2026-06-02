<script setup>
import { ref, computed } from 'vue';
import { playNowMobile } from '@/Composables/usePlayNow.js';
import { Head, usePage, useForm, Link } from '@inertiajs/vue3';
import Header from '@/Components/Header.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import ClaimAccountModal from '@/Components/ClaimAccountModal.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import StatsCard from '@/Components/StatsCard.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import EmptyState from '@/Components/EmptyState.vue';
import BgMain from '@/Components/BgMain.vue';
import Footer from '@/Components/Footer.vue';

// 1. AÑADIMOS LAS PROPS PARA RECIBIR LOS DATOS DEL CONTROLADOR
const props = defineProps({
    gameAccountsCount: { type: Number, default: 0 },
    gameAccounts:      { type: Array,  default: () => [] },
    maxAccounts:       Number,
    viewedUser:        { type: Object, default: null },
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

    <div class="min-h-screen text-rapanel-text-light dark:text-rapanel-text-dark font-sans antialiased">
        <BgMain />
        
        <Header />

        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 space-y-6">

            <FlashMessages :success="flashSuccess" :error="flashError" />

            <!-- Play Now banner -->
            <a v-if="$page.props.roBrowserUrl"
                :href="$page.props.roBrowserUrl" target="_blank" rel="noopener"
                class="group flex items-center justify-between gap-4
                       bg-white dark:bg-gradient-to-r dark:from-rapanel-navy-800 dark:to-[#0a1120]
                       border border-rapanel-gold/40 hover:border-rapanel-gold/70
                       rounded-xl px-6 py-5 shadow-sm dark:shadow-xl
                       transition-all duration-200 hover:shadow-rapanel-gold/10 hover:shadow-2xl"
                @click="(e) => playNowMobile(e, $page.props.roBrowserUrl)">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-rapanel-gold/15 border border-rapanel-gold/30 flex items-center justify-center shrink-0 group-hover:bg-rapanel-gold/25 transition">
                        <svg class="w-6 h-6 text-rapanel-gold" fill="currentColor" viewBox="0 0 20 20"><path d="M6.3 2.841A1.5 1.5 0 004 4.11V15.89a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.84z"/></svg>
                    </div>
                    <div>
                        <p class="text-rapanel-text-light dark:text-white font-bold text-base leading-tight">{{ __('Play Now in your Browser') }}</p>
                        <p class="text-rapanel-text-light/50 dark:text-white/50 text-xs mt-0.5">{{ __('No download required — play directly from this page') }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-3 shrink-0">
                    <div v-if="$page.props.serverStatus?.online" class="hidden sm:flex items-center gap-1.5 text-rapanel-success text-xs font-bold uppercase tracking-wider">
                        <span class="w-1.5 h-1.5 rounded-full bg-rapanel-success animate-pulse"></span>
                        {{ __('Online') }}
                    </div>
                    <span class="flex items-center gap-2 px-5 py-2.5 rounded-lg bg-rapanel-gold text-rapanel-navy-900 text-sm font-black uppercase tracking-wider group-hover:opacity-90 transition shadow">
                        {{ __('Play Now') }}
                        <svg class="w-4 h-4 group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                    </span>
                </div>
            </a>

            <div class="bg-white dark:bg-rapanel-navy-900 border border-rapanel-navy-100 dark:border-white/10 rounded-xl p-6 md:p-10 shadow-xl dark:shadow-black/30">
                
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center border-b border-rapanel-navy-100 dark:border-gray-700 pb-6 mb-8 gap-6">
                    <div class="space-y-1">
                        <div class="flex items-center gap-3">
                            <h1 class="text-2xl font-display font-bold text-rapanel-navy-900 dark:text-rapanel-text-dark uppercase tracking-widest">
                                {{ __('Master Account') }}
                            </h1>
                            <StatusBadge
                                :variant="viewedUser?.status ? 'success' : 'danger'"
                                :label="viewedUser?.status ? __('Active') : __('Banned')"
                                size="sm"
                            />
                        </div>
                        <p class="text-rapanel-text-light/70 dark:text-rapanel-text-dark/70 text-sm">
                            {{ __('Welcome to your central control panel,') }}
                            <span class="text-rapanel-blue font-bold">{{ viewedUser?.name ?? $page.props.auth.user.name }}</span>.
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
                    <StatsCard :label="__('Game Accounts')" :value="`${gameAccountsCount} / ${maxAccounts}`" muted />
                    <StatsCard :label="__('Vote Points')" value="0" value-class="text-rapanel-gold" muted />
                    <StatsCard :label="__('Cash Points')" value="0" value-class="text-rapanel-success" muted />
                </div>
                
            </div>

            <div class="mt-8 bg-white dark:bg-rapanel-navy-900 border border-rapanel-navy-100 dark:border-white/10 rounded-xl shadow-xl dark:shadow-black/30 overflow-hidden">
                <div class="px-6 py-5 border-b border-rapanel-navy-100 dark:border-white/10 bg-white dark:bg-rapanel-navy-900">
                    <h3 class="text-lg font-display font-bold text-rapanel-navy-900 dark:text-white tracking-wide uppercase">
                        {{ __('My Game Accounts') }}
                    </h3>
                </div>
                
                <!-- Tabla: md+ -->
                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-rapanel-navy-100/70 dark:bg-rapanel-navy-800 text-rapanel-text-light/80 dark:text-rapanel-text-dark uppercase text-xs font-bold">
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
                                <td class="px-6 py-4 text-center text-xs italic">{{ account.lastlogin || __('Never') }}</td>
                                <td class="px-6 py-4 text-center text-xs italic">{{ account.last_ip || __('Unknown') }}</td>
                                <td class="px-6 py-4 text-center text-xs italic">{{ account.created_at || __('Unknown') }}</td>
                                <td class="px-6 py-4 text-center">
                                    <StatusBadge
                                        :variant="account.state === 0 ? 'success' : 'danger'"
                                        :label="account.state === 0 ? __('Active') : __('Banned')"
                                        size="sm"
                                        dot
                                    />
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
                            <EmptyState
                                v-if="gameAccountsCount === 0"
                                :colspan="8"
                                :message="__('No game accounts found. Create or claim your first one above!')"
                            />
                        </tbody>
                    </table>
                </div>

                <!-- Cards: móvil -->
                <div class="md:hidden divide-y divide-rapanel-navy-100 dark:divide-white/10">
                    <div v-if="gameAccountsCount === 0" class="px-6 py-10 text-center text-sm text-rapanel-text-light/50 dark:text-white/30">
                        {{ __('No game accounts found. Create or claim your first one above!') }}
                    </div>
                    <div v-for="account in gameAccounts" :key="account.userid"
                         class="px-5 py-4 flex items-center justify-between gap-3 hover:bg-rapanel-navy-50/40 dark:hover:bg-white/[0.02] transition-colors">
                        <div class="min-w-0 flex-1">
                            <!-- Nombre + género -->
                            <div class="flex items-center gap-2 mb-1.5">
                                <span class="font-bold text-rapanel-blue truncate">{{ account.userid }}</span>
                                <span v-if="account.sex === 'M'" class="text-blue-400 text-base leading-none shrink-0">♂</span>
                                <span v-else class="text-pink-400 text-base leading-none shrink-0">♀</span>
                            </div>
                            <!-- Meta: último login + IP -->
                            <div class="flex flex-wrap gap-x-3 gap-y-0.5 text-xs text-rapanel-text-light/55 dark:text-white/35">
                                <span>{{ __('Last Login') }}: <span class="italic">{{ account.lastlogin || __('Never') }}</span></span>
                                <span>{{ __('Login Count') }}: {{ account.logincount }}</span>
                            </div>
                        </div>
                        <!-- Status + acción -->
                        <div class="flex flex-col items-end gap-2 shrink-0">
                            <StatusBadge
                                :variant="account.state === 0 ? 'success' : 'danger'"
                                :label="account.state === 0 ? __('Active') : __('Banned')"
                                size="sm"
                                dot
                            />
                            <Link
                                :href="route('game-accounts.show', account.account_id)"
                                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold bg-rapanel-blue/10 text-rapanel-blue border border-rapanel-blue/20 hover:bg-rapanel-blue hover:text-white transition-all"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3 h-3">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                {{ __('View') }}
                            </Link>
                        </div>
                    </div>
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
                        <TextInput id="userid" v-model="form.userid" type="text" class="mt-1 block w-full bg-white dark:bg-rapanel-navy-800 border-rapanel-navy-100 dark:border-gray-700" required autofocus />
                        <InputError class="mt-2" :message="form.errors.userid" />
                    </div>
                    <div>
                        <InputLabel for="user_pass" :value="__('Password')" />
                        <TextInput id="user_pass" v-model="form.user_pass" type="password" class="mt-1 block w-full bg-white dark:bg-rapanel-navy-800 border-rapanel-navy-100 dark:border-gray-700" required />
                        <InputError class="mt-2" :message="form.errors.user_pass" />
                    </div>
                    <div>
                        <InputLabel for="sex" :value="__('Gender')" />
                        <select id="sex" v-model="form.sex" class="mt-1 block w-full border-rapanel-navy-100 dark:border-gray-700 bg-white dark:bg-rapanel-navy-800 text-rapanel-text-light dark:text-rapanel-text-dark focus:border-rapanel-blue focus:ring-rapanel-blue rounded-md shadow-sm transition-colors">
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

    <Footer />
</template>

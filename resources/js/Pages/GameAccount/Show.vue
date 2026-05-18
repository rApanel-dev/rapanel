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
import CharacterDetail from '@/Pages/GameAccount/Partials/CharacterDetail.vue';
import ViewActivityLogs from '@/Components/ViewActivityLogs.vue';
import DeleteGameAccountForm from '@/Components/DeleteGameAccountForm.vue';
import { getJobName, formatNum, onImgError, itemLabel } from '@/Composables/useRoHelpers';
import FlashMessages from '@/Components/FlashMessages.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import BgMain from '@/Components/BgMain.vue';

const props = defineProps({
    gameAccount: Object,
    characters:  Array,
    storageItems: Array,
    cardNames:   Object,
    serverName:  String,
    vipEnabled:        Boolean,
    bankEnabled:       Boolean,
    cashPointsEnabled: Boolean,
    bankZeny:          Number,
    cashPoints:        Number,
});

const page = usePage();
const __ = (key) => page.props.translations?.[key] || key;

const flashSuccess = computed(() => page.props.flash?.success);
const flashError   = computed(() => page.props.flash?.error);

const totalZeny = computed(() => props.characters.reduce((sum, c) => sum + Number(c.zeny || 0), 0));

// Bloquea acciones si la cuenta o algún personaje está online
const isAccountOnline = computed(() => props.characters.some(c => c.online > 0));

const vipStatus = computed(() => {
    if (!props.vipEnabled) return null;
    const raw = Number(props.gameAccount.vip_time || 0);
    const now  = Math.floor(Date.now() / 1000);
    if (raw <= 0) return { active: false, label: __('No VIP'), expires: null };
    if (raw < now) return {
        active: false,
        label: __('VIP Expired'),
        expires: new Date(raw * 1000).toLocaleDateString(),
    };
    const diff  = raw - now;
    const days  = Math.floor(diff / 86400);
    const hours = Math.floor((diff % 86400) / 3600);
    const mins  = Math.floor((diff % 3600) / 60);
    return {
        active: true,
        label: `${days}d ${hours}h ${mins}m`,
        expires: new Date(raw * 1000).toLocaleString(),
    };
});

const getCardName = (id) => {
    if (!id || id <= 0 || id === 254 || id === 255) return null;
    return props.cardNames?.[id] || null;
};

const formatSex   = (s) => s === 'M' ? __('Male') : __('Female');
const formatState = (s) => s === 0 ? __('Active') : __('Blocked');

// --- MODALES ---

// Activity Logs
const logsModal = ref(false);

// Change Password
const changePasswordModal = ref(false);
const passwordForm = useForm({ current_password: '', password: '', password_confirmation: '' });
const openChangePasswordModal  = () => { changePasswordModal.value = true; };
const closeChangePasswordModal = () => {
    changePasswordModal.value = false;
    passwordForm.reset(); passwordForm.clearErrors();
};
const submitChangePassword = () => {
    passwordForm.put(route('game-accounts.password.update', props.gameAccount.account_id), {
        preserveScroll: true,
        onSuccess: () => closeChangePasswordModal(),
    });
};

// Change Gender
const genderModal = ref(false);
const genderForm  = useForm({ password: '' });
const openGenderModal  = () => { genderModal.value = true; };
const closeGenderModal = () => {
    genderModal.value = false;
    genderForm.reset(); genderForm.clearErrors();
};
const submitGender = () => {
    genderForm.put(route('game-accounts.gender', props.gameAccount.account_id), {
        preserveScroll: true,
        onSuccess: () => closeGenderModal(),
    });
};

// Character detail
const charModal = ref(null);
const openCharModal  = (char) => { charModal.value = char; };
const closeCharModal = () => { charModal.value = null; };

// Reset Position
const resetPosModal = ref(false);
const selectedPos   = ref(null);
const resetPosForm  = useForm({ password: '' });
const openResetPosModal = (char) => { selectedPos.value = char; resetPosModal.value = true; };
const closeResetPosModal = () => {
    resetPosModal.value = false; selectedPos.value = null;
    resetPosForm.reset(); resetPosForm.clearErrors();
};
const confirmResetPos = () => {
    resetPosForm.put(route('characters.reset-position', selectedPos.value.char_id), {
        preserveScroll: true,
        onSuccess: () => closeResetPosModal(),
    });
};

// Reset Look
const resetLookModal = ref(false);
const selectedLook   = ref(null);
const resetLookForm  = useForm({ password: '' });
const openResetLookModal = (char) => { selectedLook.value = char; resetLookModal.value = true; };
const closeResetLookModal = () => {
    resetLookModal.value = false; selectedLook.value = null;
    resetLookForm.reset(); resetLookForm.clearErrors();
};
const confirmResetLook = () => {
    resetLookForm.put(route('characters.reset-look', selectedLook.value.char_id), {
        preserveScroll: true,
        onSuccess: () => closeResetLookModal(),
    });
};
</script>

<template>
    <Head :title="`${gameAccount.userid} — ${__('Account Details')}`" />

    <div class="min-h-screen text-rapanel-text-light dark:text-rapanel-text-dark font-sans antialiased">
        <BgMain />
        <Header />

        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-6">

            <!-- Flash -->
            <FlashMessages :success="flashSuccess" :error="flashError" />

            <!-- ===== SECCIÓN 1: DETALLES DE CUENTA ===== -->
            <div class="bg-white dark:bg-rapanel-navy-800/60 dark:backdrop-blur-md border border-rapanel-navy-100 dark:border-white/10 rounded-xl shadow-xl dark:shadow-black/30 overflow-hidden">

                <!-- Header -->
                <div class="px-6 py-5 border-b border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50/30 dark:bg-black/20">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">

                        <!-- Título + botón eliminar (móvil: mismo row) -->
                        <div class="flex items-center justify-between sm:justify-start gap-3">
                            <div class="flex items-center gap-3">
                                <Link :href="route('dashboard')" class="flex items-center justify-center w-8 h-8 rounded-lg bg-rapanel-navy-100 dark:bg-gray-700 hover:bg-rapanel-blue hover:text-white transition-all">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
                                </Link>
                                <div>
                                    <p class="text-[10px] uppercase tracking-widest font-bold text-rapanel-text-light/40 dark:text-rapanel-text-dark/40">{{ __('Viewing Account') }}</p>
                                    <h1 class="text-xl font-display font-bold text-rapanel-navy-900 dark:text-rapanel-text-dark">{{ gameAccount.userid }}</h1>
                                </div>
                            </div>
                            <!-- Eliminar Cuenta: solo en móvil, esquina derecha del título -->
                            <div class="sm:hidden">
                                <DeleteGameAccountForm :account="gameAccount" :disabled="isAccountOnline" />
                            </div>
                        </div>

                        <!-- Menú de acciones -->
                        <div class="flex flex-col sm:flex-row sm:flex-wrap sm:items-center gap-2">

                            <!-- Ver Actividad -->
                            <button @click="logsModal = true"
                                class="flex items-center justify-center sm:justify-start gap-1.5 px-3 py-2 sm:py-1.5 rounded-lg text-xs font-bold border transition-all w-full sm:w-auto bg-rapanel-blue/10 text-rapanel-blue border-rapanel-blue/20 hover:bg-rapanel-blue hover:text-white"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V19.5a2.25 2.25 0 002.25 2.25h.75"/></svg>
                                {{ __('Actions') }}
                            </button>

                            <!-- Cambiar Contraseña -->
                            <button @click="openChangePasswordModal" :disabled="isAccountOnline"
                                class="flex items-center justify-center sm:justify-start gap-1.5 px-3 py-2 sm:py-1.5 rounded-lg text-xs font-bold border transition-all w-full sm:w-auto"
                                :class="isAccountOnline
                                    ? 'opacity-30 cursor-not-allowed bg-gray-100 dark:bg-gray-700 text-gray-400 border-gray-300 dark:border-gray-600'
                                    : 'bg-rapanel-gold/10 text-rapanel-gold border-rapanel-gold/20 hover:bg-rapanel-gold hover:text-rapanel-navy-900'"
                                :title="isAccountOnline ? __('Character must be offline') : ''"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z"/></svg>
                                {{ __('Change Password') }}
                            </button>

                            <!-- Cambiar Género -->
                            <button @click="openGenderModal" :disabled="isAccountOnline"
                                class="flex items-center justify-center sm:justify-start gap-1.5 px-3 py-2 sm:py-1.5 rounded-lg text-xs font-bold border transition-all w-full sm:w-auto"
                                :class="isAccountOnline
                                    ? 'opacity-30 cursor-not-allowed bg-gray-100 dark:bg-gray-700 text-gray-400 border-gray-300 dark:border-gray-600'
                                    : 'bg-purple-500/10 text-purple-400 border-purple-500/20 hover:bg-purple-500 hover:text-white'"
                                :title="isAccountOnline ? __('Character must be offline') : ''"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
                                {{ __('Change Gender') }}
                            </button>

                            <!-- Separador + Eliminar: solo en escritorio -->
                            <div class="hidden sm:block w-px h-6 bg-gray-300 dark:bg-gray-600 self-center mx-1"></div>
                            <div class="hidden sm:block">
                                <DeleteGameAccountForm :account="gameAccount" :disabled="isAccountOnline" />
                            </div>

                        </div>
                    </div>
                </div>

                <!-- Info Grid -->
                <div class="p-5 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
                    <div v-for="info in [
                        { label: __('Username'),    value: gameAccount.userid },
                        { label: __('Account ID'),  value: gameAccount.account_id },
                        { label: __('Email'),       value: gameAccount.email },
                        { label: __('Gender'),      value: formatSex(gameAccount.sex) },
                        { label: __('Group ID'),    value: gameAccount.group_id ?? 0 },
                        { label: __('State'),       value: formatState(gameAccount.state) },
                        { label: __('Login Count'), value: formatNum(gameAccount.logincount) },
                        { label: __('Last Login'),  value: gameAccount.lastlogin || __('Never') },
                        { label: __('Last IP'),     value: gameAccount.last_ip || __('Unknown') },
                        { label: __('Birthdate'),   value: gameAccount.birthdate || '—' },
                    ]" :key="info.label"
                        class="flex flex-col gap-1 bg-rapanel-navy-50 dark:bg-black/30 dark:backdrop-blur-sm rounded-xl px-4 py-3 border border-rapanel-navy-100 dark:border-white/10"
                    >
                        <span class="text-[9px] uppercase tracking-widest font-extrabold text-rapanel-text-light/40 dark:text-rapanel-text-dark/40">{{ info.label }}</span>
                        <span class="font-semibold text-rapanel-navy-900 dark:text-white text-sm leading-tight truncate">{{ info.value }}</span>
                    </div>
                </div>

                <!-- Bank Zeny + Cash Points + VIP (fila unificada, proporcional) -->
                <div v-if="bankEnabled || cashPointsEnabled || (vipEnabled && vipStatus)" class="px-5 pb-5 flex flex-col sm:flex-row gap-3">

                    <!-- Bank Zeny -->
                    <div v-if="bankEnabled"
                        class="flex-1 min-w-0 flex items-center gap-3 rounded-xl px-4 py-3 bg-rapanel-gold/10 border border-rapanel-gold/30"
                    >
                        <div class="w-8 h-8 flex items-center justify-center rounded-lg bg-rapanel-gold/20 shrink-0">
                            <img src="/data/gameaccount/bank.png" class="w-6 h-6 object-contain" alt="" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-[9px] uppercase tracking-widest font-extrabold text-rapanel-gold/70">{{ __('Bank Zeny') }}</p>
                            <p class="font-bold text-sm text-rapanel-gold truncate">{{ formatNum(bankZeny) }} z</p>
                        </div>
                    </div>

                    <!-- Cash Points -->
                    <div v-if="cashPointsEnabled"
                        class="flex-1 min-w-0 flex items-center gap-3 rounded-xl px-4 py-3 bg-rapanel-blue/10 border border-rapanel-blue/30"
                    >
                        <div class="w-8 h-8 flex items-center justify-center rounded-lg bg-rapanel-blue/20 shrink-0">
                            <img src="/data/gameaccount/cashpoints.png" class="w-6 h-6 object-contain" alt="" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-[9px] uppercase tracking-widest font-extrabold text-rapanel-blue/70">{{ __('Cash Points') }}</p>
                            <p class="font-bold text-sm text-rapanel-blue truncate">{{ formatNum(cashPoints) }}</p>
                        </div>
                    </div>

                    <!-- VIP -->
                    <div v-if="vipEnabled && vipStatus"
                        class="flex-1 min-w-0 flex items-center justify-between gap-3 rounded-xl px-4 py-3 bg-violet-500/10 border border-violet-500/30"
                    >
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-8 h-8 flex items-center justify-center rounded-lg bg-violet-500/20 shrink-0">
                                <img src="/data/gameaccount/vip.png" class="w-6 h-6 object-contain" alt="" />
                            </div>
                            <div class="min-w-0">
                                <p class="text-[9px] uppercase tracking-widest font-extrabold text-violet-400/70">VIP</p>
                                <p class="font-bold text-sm truncate"
                                    :class="vipStatus.active ? 'text-violet-400' : 'text-rapanel-text-light/50 dark:text-rapanel-text-dark/50'"
                                >{{ vipStatus.label }}</p>
                            </div>
                        </div>
                        <div v-if="vipStatus.expires" class="text-right shrink-0">
                            <p class="text-[9px] uppercase tracking-widest font-extrabold text-violet-400/60">
                                {{ vipStatus.active ? __('VIP Expires') : __('VIP Expired on') }}
                            </p>
                            <p class="text-xs font-semibold"
                                :class="vipStatus.active ? 'text-rapanel-success' : 'text-rapanel-danger'"
                            >{{ vipStatus.expires }}</p>
                        </div>
                    </div>

                </div>
            </div>

            <!-- ===== SECCIÓN 2: PERSONAJES ===== -->
            <div class="bg-white dark:bg-rapanel-navy-800/60 dark:backdrop-blur-md border border-rapanel-navy-100 dark:border-white/10 rounded-xl shadow-xl dark:shadow-black/30 overflow-hidden">
                <div class="px-6 py-4 border-b border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50/30 dark:bg-black/20">
                    <h3 class="text-sm font-display font-bold uppercase tracking-widest text-rapanel-navy-900 dark:text-white">
                        {{ __('Characters on') }} <span class="text-rapanel-blue">{{ serverName }}</span>
                    </h3>
                </div>

                <div v-if="characters.length === 0" class="px-6 py-12 text-center text-rapanel-text-light/40 dark:text-gray-500 italic text-sm">
                    {{ __('No characters found.') }}
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-rapanel-navy-100 dark:bg-black/30 text-[10px] uppercase tracking-widest font-bold text-rapanel-text-light/50 dark:text-rapanel-text-dark/50">
                            <tr>
                                <th class="px-4 py-3">{{ __('Slot') }}</th>
                                <th class="px-4 py-3">{{ __('Character Name') }}</th>
                                <th class="px-4 py-3">{{ __('Job Class') }}</th>
                                <th class="px-4 py-3 text-center">{{ __('Base Lv') }}</th>
                                <th class="px-4 py-3 text-center">{{ __('Job Lv') }}</th>
                                <th class="px-4 py-3 text-right">{{ __('Zeny') }}</th>
                                <th class="px-4 py-3 text-center">{{ __('Guild') }}</th>
                                <th class="px-4 py-3 text-center">{{ __('Status') }}</th>
                                <th class="px-4 py-3 text-center">{{ __('Reset Look') }}</th>
                                <th class="px-4 py-3 text-center">{{ __('Reset Position') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-rapanel-navy-100 dark:divide-gray-700/50">
                            <tr v-for="char in characters" :key="char.char_id" class="hover:bg-rapanel-navy-50/30 dark:hover:bg-gray-700/20 transition-colors">
                                <td class="px-4 py-3 text-rapanel-text-light/50 dark:text-rapanel-text-dark/50 font-mono">{{ char.char_num + 1 }}</td>
                                <td class="px-4 py-3">
                                    <button @click="openCharModal(char)" class="font-bold text-rapanel-blue hover:underline hover:text-rapanel-blue/80 transition-colors text-left">
                                        {{ char.name }}
                                    </button>
                                </td>
                                <td class="px-4 py-3 text-rapanel-text-light/70 dark:text-rapanel-text-dark/70">
                                    <div class="flex items-center gap-1.5">
                                        <img
                                            :src="`/data/gameaccount/job_icons/icon_jobs_${char.class}.png`"
                                            @error="onImgError"
                                            class="w-5 h-5 object-contain shrink-0"
                                            :alt="getJobName(char.class)"
                                        />
                                        {{ getJobName(char.class) }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-center font-bold text-rapanel-navy-900 dark:text-white">{{ char.base_level }}</td>
                                <td class="px-4 py-3 text-center font-bold text-rapanel-navy-900 dark:text-white">{{ char.job_level }}</td>
                                <td class="px-4 py-3 text-right font-mono text-rapanel-danger dark:text-rapanel-gold">{{ formatNum(char.zeny) }} z</td>
                                <td class="px-4 py-3 text-center text-xs text-rapanel-text-light/60 dark:text-rapanel-text-dark/60">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <img v-if="char.guild_id" :src="`/guild-emblem/${char.guild_id}`" @error="onImgError" class="w-5 h-5 object-contain shrink-0" :alt="char.guild_name" />
                                        <span>{{ char.guild_name || '—' }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <StatusBadge
                                        :variant="char.online > 0 ? 'success' : 'neutral'"
                                        :label="char.online > 0 ? __('Online') : __('Offline')"
                                        :dot="true"
                                    />
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <button @click="openResetLookModal(char)" :disabled="char.online > 0"
                                        class="text-xs px-2.5 py-1 rounded-lg font-bold transition-all"
                                        :class="char.online > 0 ? 'opacity-30 cursor-not-allowed bg-gray-100 dark:bg-gray-700 text-gray-400' : 'bg-rapanel-danger/10 text-rapanel-danger border border-rapanel-danger/20 hover:bg-rapanel-danger hover:text-white dark:bg-rapanel-gold/10 dark:text-rapanel-gold dark:border-rapanel-gold/20 dark:hover:bg-rapanel-gold dark:hover:text-rapanel-navy-900'"
                                    >{{ __('Reset Look') }}</button>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <button @click="openResetPosModal(char)" :disabled="char.online > 0"
                                        class="text-xs px-2.5 py-1 rounded-lg font-bold transition-all"
                                        :class="char.online > 0 ? 'opacity-30 cursor-not-allowed bg-gray-100 dark:bg-gray-700 text-gray-400' : 'bg-rapanel-blue/10 text-rapanel-blue border border-rapanel-blue/20 hover:bg-rapanel-blue hover:text-white'"
                                    >{{ __('Reset Position') }}</button>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot class="bg-rapanel-navy-50 dark:bg-black/30 border-t-2 border-rapanel-navy-100 dark:border-gray-700">
                            <tr>
                                <td colspan="5" class="px-4 py-2 text-right text-[10px] uppercase tracking-widest font-extrabold text-rapanel-text-light/50 dark:text-rapanel-text-dark/50">{{ __('Total Zeny') }}</td>
                                <td class="px-4 py-2 text-right font-mono font-bold text-rapanel-danger dark:text-rapanel-gold">{{ formatNum(totalZeny) }} z</td>
                                <td colspan="4"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- ===== SECCIÓN 3: STORAGE ITEMS ===== -->
            <div class="bg-white dark:bg-rapanel-navy-800/60 dark:backdrop-blur-md border border-rapanel-navy-100 dark:border-white/10 rounded-xl shadow-xl dark:shadow-black/30 overflow-hidden">
                <div class="px-6 py-4 border-b border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50/30 dark:bg-black/20">
                    <h3 class="text-sm font-display font-bold uppercase tracking-widest text-rapanel-navy-900 dark:text-white">
                        {{ __('Storage Items of') }} <span class="text-rapanel-blue uppercase">{{ gameAccount.userid }}</span>
                        <span class="ml-2 text-xs font-normal text-rapanel-text-light/40 dark:text-rapanel-text-dark/40">({{ storageItems.length }})</span>
                    </h3>
                </div>

                <div v-if="storageItems.length === 0" class="px-6 py-10 text-center text-rapanel-text-light/40 dark:text-gray-500 italic text-sm">
                    {{ __('No storage items found.') }}
                </div>

                <div v-else class="overflow-x-auto max-h-96 overflow-y-auto">
                    <table class="w-full text-xs text-left">
                        <thead class="sticky top-0 z-10 bg-rapanel-navy-100 dark:bg-black/30 dark:backdrop-blur-md text-[10px] uppercase tracking-widest font-bold text-rapanel-text-light/50 dark:text-rapanel-text-dark/50">
                            <tr>
                                <th class="px-3 py-2 text-center">ID</th>
                                <th class="px-3 py-2 w-10">{{ __('Icon') }}</th>
                                <th class="px-3 py-2">{{ __('Item Name') }}</th>
                                <th class="px-3 py-2 text-center">{{ __('Amount') }}</th>
                                <th class="px-3 py-2 text-center">{{ __('Identified') }}</th>
                                <th class="px-3 py-2 text-center">{{ __('Broken') }}</th>
                                <th class="px-3 py-2 text-center">{{ __('Slot 1') }}</th>
                                <th class="px-3 py-2 text-center">{{ __('Slot 2') }}</th>
                                <th class="px-3 py-2 text-center">{{ __('Slot 3') }}</th>
                                <th class="px-3 py-2 text-center">{{ __('Slot 4') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-rapanel-navy-100/50 dark:divide-gray-700/30">
                            <tr v-for="(item, idx) in storageItems" :key="idx" class="hover:bg-rapanel-navy-50/30 dark:hover:bg-gray-700/20">
                                <td class="px-3 py-1.5 text-center font-mono text-[11px] text-rapanel-text-light/50 dark:text-rapanel-text-dark/40">{{ item.nameid }}</td>
                                <td class="px-3 py-1.5 text-center">
                                    <img :src="`/data/items/icons/${item.nameid}.png`" @error="onImgError" class="w-7 h-7 object-contain mx-auto" :alt="item.name_english" />
                                </td>
                                <td class="px-3 py-1.5">
                                    <span v-if="item.refine > 0" class="font-bold text-rapanel-danger dark:text-rapanel-gold mr-1">+{{ item.refine }}</span>
                                    <span class="font-medium text-rapanel-navy-900 dark:text-white">{{ itemLabel(item) }}</span>
                                </td>
                                <td class="px-3 py-1.5 text-center text-rapanel-text-light/60 dark:text-rapanel-text-dark/60">{{ item.amount }}</td>
                                <td class="px-3 py-1.5 text-center">
                                    <span :class="item.identify ? 'text-rapanel-success' : 'text-rapanel-danger'" class="font-bold">{{ item.identify ? __('Yes') : __('No') }}</span>
                                </td>
                                <td class="px-3 py-1.5 text-center">
                                    <span v-if="item.attribute > 0" class="font-bold text-rapanel-danger">{{ __('Yes') }}</span>
                                    <span v-else class="text-rapanel-text-light/30 dark:text-rapanel-text-dark/30">{{ __('No') }}</span>
                                </td>
                                <td v-for="slot in ['card0','card1','card2','card3']" :key="slot" class="px-3 py-1.5 text-center text-rapanel-text-light/50 dark:text-rapanel-text-dark/40 italic">
                                    <span v-if="getCardName(item[slot])" class="text-rapanel-blue not-italic font-medium">{{ getCardName(item[slot]) }}</span>
                                    <span v-else>{{ __('None') }}</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>

        <!-- ===== CHARACTER DETAIL OVERLAY ===== -->
        <CharacterDetail
            :char="charModal"
            :card-names="cardNames"
            @close="closeCharModal"
            @reset-look="openResetLookModal"
            @reset-position="openResetPosModal"
        />

        <!-- ===== MODAL: ACTIVITY LOGS ===== -->
        <ViewActivityLogs :show="logsModal" :account="gameAccount" @close="logsModal = false" />

        <!-- ===== MODAL: CHANGE PASSWORD ===== -->
        <Modal :show="changePasswordModal" @close="closeChangePasswordModal">
            <div class="p-6 bg-white dark:bg-rapanel-navy-800">
                <h2 class="text-lg font-bold text-rapanel-navy-900 dark:text-white mb-2 border-b border-rapanel-navy-100 dark:border-gray-700 pb-3 uppercase tracking-wider">
                    {{ __('Change Password for') }} <span class="text-rapanel-gold">{{ gameAccount.userid }}</span>
                </h2>
                <p class="text-sm text-rapanel-text-light/60 dark:text-gray-400 mb-6 italic">
                    {{ __('Enter a new password for this game account. For security, we request your main web panel account password.') }}
                </p>
                <form @submit.prevent="submitChangePassword" class="space-y-4">
                    <div>
                        <InputLabel for="cp_new_password" :value="__('New Game Password')" />
                        <TextInput id="cp_new_password" v-model="passwordForm.password" type="password"
                            class="mt-1 block w-full bg-white dark:bg-rapanel-navy-900"
                            :placeholder="__('Minimum 4 characters')" required autofocus />
                        <InputError class="mt-1" :message="passwordForm.errors.password" />
                    </div>
                    <div>
                        <InputLabel for="cp_confirm_password" :value="__('Confirm New Password')" />
                        <TextInput id="cp_confirm_password" v-model="passwordForm.password_confirmation" type="password"
                            class="mt-1 block w-full bg-white dark:bg-rapanel-navy-900" required />
                        <InputError class="mt-1" :message="passwordForm.errors.password_confirmation" />
                    </div>
                    <div>
                        <InputLabel for="cp_current_password" :value="__('Master Account Password')" class="text-rapanel-gold font-bold uppercase text-xs" />
                        <TextInput id="cp_current_password" v-model="passwordForm.current_password" type="password"
                            class="mt-1 block w-full border-rapanel-gold/30 focus:ring-rapanel-gold focus:border-rapanel-gold bg-white dark:bg-rapanel-navy-900"
                            :placeholder="__('Your web panel password')" required />
                        <InputError class="mt-1" :message="passwordForm.errors.current_password" />
                    </div>
                    <div class="flex justify-end gap-3 pt-2">
                        <SecondaryButton @click="closeChangePasswordModal">{{ __('Cancel') }}</SecondaryButton>
                        <PrimaryButton :disabled="passwordForm.processing" :class="{ 'opacity-25': passwordForm.processing }">
                            {{ __('Save') }}
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- ===== MODAL: CHANGE GENDER ===== -->
        <Modal :show="genderModal" @close="closeGenderModal">
            <div class="p-6 bg-white dark:bg-rapanel-navy-800">
                <h2 class="text-lg font-bold text-rapanel-navy-900 dark:text-white mb-2 border-b border-rapanel-navy-100 dark:border-gray-700 pb-3 uppercase tracking-wider">
                    {{ __('Change Gender') }}
                </h2>
                <p class="text-sm text-rapanel-text-light/60 dark:text-gray-400 mb-1">
                    {{ __('Account') }}: <span class="font-bold text-rapanel-blue">{{ gameAccount.userid }}</span>
                    &nbsp;|&nbsp; {{ __('Current Gender') }}: <span class="font-bold text-purple-400">{{ formatSex(gameAccount.sex) }}</span>
                    &nbsp;→&nbsp; <span class="font-bold text-rapanel-gold">{{ gameAccount.sex === 'M' ? __('Female') : __('Male') }}</span>
                </p>
                <p class="text-sm text-rapanel-text-light/60 dark:text-gray-400 mb-6 italic">
                    {{ __('All characters must be offline. Confirm with your master password.') }}
                </p>
                <form @submit.prevent="submitGender" class="space-y-5">
                    <div>
                        <InputLabel for="gender_password" :value="__('Master Account Password')" class="text-rapanel-gold font-bold uppercase text-xs" />
                        <TextInput id="gender_password" v-model="genderForm.password" type="password"
                            class="mt-1 block w-full border-rapanel-gold/30 focus:ring-rapanel-gold focus:border-rapanel-gold bg-white dark:bg-rapanel-navy-900"
                            required autofocus />
                        <InputError class="mt-2" :message="genderForm.errors.password" />
                        <InputError class="mt-2" :message="genderForm.errors.error" />
                    </div>
                    <div class="flex justify-end gap-3 mt-6">
                        <SecondaryButton @click="closeGenderModal">{{ __('Cancel') }}</SecondaryButton>
                        <PrimaryButton :disabled="genderForm.processing" :class="{ 'opacity-25': genderForm.processing }">
                            {{ __('Confirm Gender Change') }}
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- ===== MODAL: RESET POSITION ===== -->
        <Modal :show="resetPosModal" @close="closeResetPosModal">
            <div class="p-6 bg-white dark:bg-rapanel-navy-800">
                <h2 class="text-lg font-bold text-rapanel-navy-900 dark:text-white mb-2 border-b border-rapanel-navy-100 dark:border-gray-700 pb-3 uppercase tracking-wider">{{ __('Reset Position') }}</h2>
                <p class="text-sm text-rapanel-text-light/60 dark:text-gray-400 mb-1">{{ __('Character') }}: <span class="font-bold text-rapanel-blue">{{ selectedPos?.name }}</span></p>
                <p class="text-sm text-rapanel-text-light/60 dark:text-gray-400 mb-6 italic">{{ __('This will teleport the character to the default spawn point. Confirm with your master password.') }}</p>
                <form @submit.prevent="confirmResetPos" class="space-y-5">
                    <div>
                        <InputLabel for="rpos_pw" :value="__('Master Account Password')" class="text-rapanel-gold font-bold uppercase text-xs" />
                        <TextInput id="rpos_pw" v-model="resetPosForm.password" type="password" class="mt-1 block w-full border-rapanel-gold/30 focus:ring-rapanel-gold focus:border-rapanel-gold bg-white dark:bg-rapanel-navy-900" required autofocus />
                        <InputError class="mt-2" :message="resetPosForm.errors.password" />
                        <InputError class="mt-2" :message="resetPosForm.errors.error" />
                    </div>
                    <div class="flex justify-end gap-3">
                        <SecondaryButton @click="closeResetPosModal">{{ __('Cancel') }}</SecondaryButton>
                        <PrimaryButton :disabled="resetPosForm.processing" :class="{ 'opacity-25': resetPosForm.processing }">{{ __('Confirm Reset') }}</PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- ===== MODAL: RESET LOOK ===== -->
        <Modal :show="resetLookModal" @close="closeResetLookModal">
            <div class="p-6 bg-white dark:bg-rapanel-navy-800">
                <h2 class="text-lg font-bold text-rapanel-navy-900 dark:text-white mb-2 border-b border-rapanel-navy-100 dark:border-gray-700 pb-3 uppercase tracking-wider">{{ __('Reset Look') }}</h2>
                <p class="text-sm text-rapanel-text-light/60 dark:text-gray-400 mb-1">{{ __('Character') }}: <span class="font-bold text-rapanel-gold">{{ selectedLook?.name }}</span></p>
                <p class="text-sm text-rapanel-text-light/60 dark:text-gray-400 mb-6 italic">{{ __('This will reset the character\'s appearance (hair, color, outfit) to default values. Confirm with your master password.') }}</p>
                <form @submit.prevent="confirmResetLook" class="space-y-5">
                    <div>
                        <InputLabel for="rlook_pw" :value="__('Master Account Password')" class="text-rapanel-gold font-bold uppercase text-xs" />
                        <TextInput id="rlook_pw" v-model="resetLookForm.password" type="password" class="mt-1 block w-full border-rapanel-gold/30 focus:ring-rapanel-gold focus:border-rapanel-gold bg-white dark:bg-rapanel-navy-900" required autofocus />
                        <InputError class="mt-2" :message="resetLookForm.errors.password" />
                        <InputError class="mt-2" :message="resetLookForm.errors.error" />
                    </div>
                    <div class="flex justify-end gap-3">
                        <SecondaryButton @click="closeResetLookModal">{{ __('Cancel') }}</SecondaryButton>
                        <PrimaryButton :disabled="resetLookForm.processing" :class="{ 'opacity-25': resetLookForm.processing }">{{ __('Confirm Reset') }}</PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

    </div>
</template>

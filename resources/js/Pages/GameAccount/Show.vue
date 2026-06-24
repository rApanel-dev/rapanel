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
import { useItemDbModal } from '@/Composables/useItemDbModal';
import FlashMessages from '@/Components/FlashMessages.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import BgMain from '@/Components/BgMain.vue';
import ActionButton from '@/Components/ActionButton.vue';
import Footer from '@/Components/Footer.vue';
import ItemDbModal from '@/Components/ItemDbModal.vue';
import MobDbModal  from '@/Components/MobDbModal.vue';

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
    charPreferences:   { type: Object, default: () => ({}) },
});

const page = usePage();
const __ = (key) => page.props.translations?.[key] || key;

const isAdmin      = computed(() => page.props.auth?.user?.role === 'Admin');
const hasTwoFactor = computed(() => !!page.props.auth?.user?.two_factor_confirmed_at);

const backHref = computed(() => {
    if (!isAdmin.value) return route('dashboard');
    const myId = page.props.auth?.user?.id;
    // Admin viendo su propia cuenta de juego
    if (props.gameAccount.master_id === myId) return route('dashboard');
    // Admin viendo la cuenta de juego de otro usuario → volver a su dashboard
    if (props.gameAccount.master_id) return route('dashboard') + '?as=' + props.gameAccount.master_id;
    // Cuenta sin master_id (desvinculada) → listado admin
    return route('admin.game-accounts.index');
});

const flashSuccess = computed(() => page.props.flash?.success);
const flashError   = computed(() => page.props.flash?.error);

const totalZeny = computed(() => props.characters.reduce((sum, c) => sum + Number(c.zeny || 0), 0));

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
const formatState = (s) => s === 0 ? __('Active') : __('Banned');

// --- MODALES ---

// Activity Logs
const logsModal = ref(false);

// Change Password
const changePasswordModal = ref(false);
const passwordForm = useForm({ current_password: '', totp_code: '', password: '', password_confirmation: '' });
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
const genderForm  = useForm({ password: '', totp_code: '' });
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

// Change Slot
const slotModal      = ref(false);
const selectedSlotChar = ref(null);
const targetSlot     = ref(null);
const slotForm       = useForm({ password: '', slot: 0 });

// character_slots en login ya tiene el valor correcto (9 normal, 15 VIP, etc.)
const effectiveMaxSlots = computed(() => props.gameAccount?.character_slots ?? 9);

const slotOccupancy = computed(() => {
    const map = {};
    for (const c of props.characters) {
        map[c.char_num] = c;
    }
    return map;
});

const openSlotModal = (char) => {
    selectedSlotChar.value = char;
    targetSlot.value = null;
    slotForm.reset(); slotForm.clearErrors();
    slotModal.value = true;
};
const closeSlotModal = () => {
    slotModal.value = false;
    selectedSlotChar.value = null;
    targetSlot.value = null;
    slotForm.reset(); slotForm.clearErrors();
};
const selectTargetSlot = (slot) => {
    if (slot === selectedSlotChar.value?.char_num) return;
    const occupant = slotOccupancy.value[slot];
    if (occupant && occupant.online > 0) return; // cannot swap with an online character
    targetSlot.value = slot;
    slotForm.slot = slot;
};
const confirmSlotChange = () => {
    const newSlot = slotForm.slot;
    slotForm.put(route('characters.change-slot', selectedSlotChar.value.char_id), {
        preserveScroll: true,
        onSuccess: () => {
            if (charModal.value?.char_id === selectedSlotChar.value?.char_id) {
                charModal.value.char_num = newSlot;
            }
            closeSlotModal();
        },
    });
};

const slotButtonClass = (slot) => {
    const isCurrent       = slot === selectedSlotChar.value?.char_num;
    const isSelected      = slot === targetSlot.value;
    const occupant        = slotOccupancy.value[slot];
    const isOccupied      = occupant && !isCurrent;
    const occupantOnline  = isOccupied && occupant.online > 0;

    if (isCurrent)       return 'border-2 border-rapanel-blue/60 bg-rapanel-blue/20 dark:bg-rapanel-blue/20 text-rapanel-blue cursor-default';
    if (isSelected)      return 'border-2 border-rapanel-blue bg-rapanel-blue text-white shadow-md';
    if (occupantOnline)  return 'border-2 border-rapanel-danger/40 bg-rapanel-danger/10 text-rapanel-danger/50 cursor-not-allowed opacity-60';
    if (isOccupied)      return 'border-2 border-rapanel-danger/60 bg-rapanel-danger/20 dark:bg-rapanel-danger/15 text-rapanel-danger hover:bg-rapanel-danger hover:text-white hover:border-rapanel-danger cursor-pointer transition-all';
    return 'border-2 border-rapanel-success/50 bg-rapanel-success/20 dark:bg-rapanel-success/15 text-rapanel-success hover:bg-rapanel-success hover:text-rapanel-success-dark hover:border-rapanel-success cursor-pointer transition-all';
};

// Character Preferences
const prefModal     = ref(false);
const selectedPrefChar = ref(null);
const prefForm      = useForm({ password: '', hide_from_rankings: false });

const openPrefModal = (char) => {
    selectedPrefChar.value = char;
    const pref = props.charPreferences?.[char.char_id];
    prefForm.hide_from_rankings = pref?.hide_from_rankings ?? false;
    prefForm.clearErrors();
    prefModal.value = true;
};
const closePrefModal = () => {
    prefModal.value = false;
    selectedPrefChar.value = null;
    prefForm.reset(); prefForm.clearErrors();
};
const confirmPrefUpdate = () => {
    prefForm.put(route('characters.preferences', selectedPrefChar.value.char_id), {
        preserveScroll: true,
        onSuccess: () => closePrefModal(),
    });
};

const getCharPref = (charId) => props.charPreferences?.[charId] ?? null;

// Item DB modal
const { openItemDb } = useItemDbModal();

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
            <div class="bg-white dark:bg-rapanel-navy-900 border border-rapanel-navy-100 dark:border-white/10 rounded-xl shadow-xl dark:shadow-black/30 overflow-hidden">

                <!-- Header -->
                <div class="px-6 py-5 border-b border-rapanel-navy-100 dark:border-white/10 bg-white dark:bg-rapanel-navy-900">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">

                        <!-- Título + botón eliminar (móvil: mismo row) -->
                        <div class="flex items-center justify-between sm:justify-start gap-3">
                            <div class="flex items-center gap-3">
                                <Link :href="backHref" class="flex items-center justify-center w-8 h-8 rounded-lg bg-rapanel-navy-100 dark:bg-rapanel-navy-700 hover:bg-rapanel-blue hover:text-white transition-all">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
                                </Link>
                                <div>
                                    <p class="text-[10px] uppercase tracking-widest font-bold text-rapanel-text-light/40 dark:text-rapanel-text-dark/40">{{ __('Viewing Account') }}</p>
                                    <div class="flex items-center gap-2">
                                        <h1 class="text-xl font-display font-bold text-rapanel-navy-900 dark:text-rapanel-text-dark">{{ gameAccount.userid }}</h1>
                                        <StatusBadge
                                            :variant="gameAccount.state === 0 ? 'success' : 'danger'"
                                            :label="gameAccount.state === 0 ? __('Active') : __('Banned')"
                                            size="sm"
                                        />
                                    </div>
                                </div>
                            </div>
                            <!-- Eliminar Cuenta: solo en móvil, esquina derecha del título -->
                            <div class="sm:hidden">
                                <DeleteGameAccountForm :account="gameAccount" :disabled="isAccountOnline" />
                            </div>
                        </div>

                        <!-- Menú de acciones — colores por posición: gold, blue, purple, danger -->
                        <div class="flex flex-col sm:flex-row sm:flex-wrap sm:items-center gap-2">

                            <!-- Ver Actividad (gold — 1°) -->
                            <ActionButton variant="gold" @click="logsModal = true" class="w-full sm:w-auto justify-center sm:justify-start py-2 sm:py-1.5">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V19.5a2.25 2.25 0 002.25 2.25h.75"/></svg>
                                {{ __('Activity') }}
                            </ActionButton>

                            <!-- Cambiar Contraseña (blue — 2°) -->
                            <ActionButton variant="blue" @click="openChangePasswordModal"
                                :disabled="isAccountOnline"
                                :title="isAccountOnline ? __('Character must be offline') : ''"
                                class="w-full sm:w-auto justify-center sm:justify-start py-2 sm:py-1.5"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z"/></svg>
                                {{ __('Change Password') }}
                            </ActionButton>

                            <!-- Cambiar Género (purple — 3°) -->
                            <ActionButton variant="purple" @click="openGenderModal"
                                :disabled="isAccountOnline"
                                :title="isAccountOnline ? __('Character must be offline') : ''"
                                class="w-full sm:w-auto justify-center sm:justify-start py-2 sm:py-1.5"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
                                {{ __('Change Gender') }}
                            </ActionButton>

                            <!-- Separador + Eliminar (danger): solo en escritorio -->
                            <div class="hidden sm:block w-px h-6 bg-rapanel-navy-100 dark:bg-white/10 self-center mx-1"></div>
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
                        class="flex flex-col gap-1 bg-rapanel-navy-100/70 dark:bg-rapanel-navy-800 rounded-xl px-4 py-3 border border-rapanel-navy-100 dark:border-white/10"
                    >
                        <span class="text-[9px] uppercase tracking-widest font-extrabold text-rapanel-text-light/40 dark:text-rapanel-text-dark/40">{{ info.label }}</span>
                        <span class="font-semibold text-rapanel-navy-900 dark:text-white text-sm leading-tight truncate">{{ info.value }}</span>
                    </div>
                </div>

                <!-- Bank Zeny + Cash Points + VIP -->
                <div v-if="bankEnabled || cashPointsEnabled || (vipEnabled && vipStatus)" class="px-5 pb-5 flex flex-col sm:flex-row gap-3">

                    <div v-if="bankEnabled"
                        class="flex-1 min-w-0 flex items-center gap-3 rounded-xl px-4 py-3 bg-rapanel-gold/20 dark:bg-rapanel-gold/10 border border-rapanel-gold/40 dark:border-rapanel-gold/30"
                    >
                        <div class="w-8 h-8 flex items-center justify-center rounded-lg bg-rapanel-gold/20 shrink-0">
                            <img src="/data/gameaccount/bank.png" class="w-6 h-6 object-contain" alt="" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-[9px] uppercase tracking-widest font-extrabold text-rapanel-gold/70">{{ __('Bank Zeny') }}</p>
                            <p class="font-bold text-sm text-rapanel-gold truncate">{{ formatNum(bankZeny) }} z</p>
                        </div>
                    </div>

                    <div v-if="cashPointsEnabled"
                        class="flex-1 min-w-0 flex items-center gap-3 rounded-xl px-4 py-3 bg-rapanel-blue/20 dark:bg-rapanel-blue/10 border border-rapanel-blue/40 dark:border-rapanel-blue/30"
                    >
                        <div class="w-8 h-8 flex items-center justify-center rounded-lg bg-rapanel-blue/20 shrink-0">
                            <img src="/data/gameaccount/cashpoints.png" class="w-6 h-6 object-contain" alt="" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-[9px] uppercase tracking-widest font-extrabold text-rapanel-blue/70">{{ __('Cash Points') }}</p>
                            <p class="font-bold text-sm text-rapanel-blue truncate">{{ formatNum(cashPoints) }}</p>
                        </div>
                    </div>

                    <div v-if="vipEnabled && vipStatus"
                        class="flex-1 min-w-0 flex items-center justify-between gap-3 rounded-xl px-4 py-3 bg-rapanel-purple/20 dark:bg-rapanel-purple/10 border border-rapanel-purple/40 dark:border-rapanel-purple/30"
                    >
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-8 h-8 flex items-center justify-center rounded-lg bg-rapanel-purple/20 shrink-0">
                                <img src="/data/gameaccount/vip.png" class="w-6 h-6 object-contain" alt="" />
                            </div>
                            <div class="min-w-0">
                                <p class="text-[9px] uppercase tracking-widest font-extrabold text-rapanel-purple/70">VIP</p>
                                <p class="font-bold text-sm truncate"
                                    :class="vipStatus.active ? 'text-rapanel-purple' : 'text-rapanel-text-light/50 dark:text-rapanel-text-dark/50'"
                                >{{ vipStatus.label }}</p>
                            </div>
                        </div>
                        <div v-if="vipStatus.expires" class="text-right shrink-0">
                            <p class="text-[9px] uppercase tracking-widest font-extrabold text-rapanel-purple/60">
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
            <div class="bg-white dark:bg-rapanel-navy-900 border border-rapanel-navy-100 dark:border-white/10 rounded-xl shadow-xl dark:shadow-black/30 overflow-hidden">
                <div class="px-6 py-4 border-b border-rapanel-navy-100 dark:border-white/10 bg-white dark:bg-rapanel-navy-900">
                    <h3 class="text-base font-display font-bold uppercase tracking-widest text-rapanel-navy-900 dark:text-white">
                        {{ __('Characters on') }} <span class="text-rapanel-blue">{{ serverName }}</span>
                    </h3>
                </div>

                <div v-if="characters.length === 0" class="px-6 py-12 text-center text-rapanel-text-light/40 dark:text-rapanel-text-dark/45 italic text-sm">
                    {{ __('No characters found.') }}
                </div>

                <!-- Tabla: md+ -->
                <div v-else class="hidden md:block overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-rapanel-navy-100/70 dark:bg-rapanel-navy-800 text-[10px] uppercase tracking-widest font-bold text-rapanel-text-light/50 dark:text-rapanel-text-dark/50">
                            <tr>
                                <th class="px-4 py-3">{{ __('Slot') }}</th>
                                <th class="px-4 py-3">{{ __('Character Name') }}</th>
                                <th class="px-4 py-3">{{ __('Job Class') }}</th>
                                <th class="px-4 py-3 text-center">{{ __('Base Lv') }}</th>
                                <th class="px-4 py-3 text-center">{{ __('Job Lv') }}</th>
                                <th class="px-4 py-3 text-right">{{ __('Zeny') }}</th>
                                <th class="px-4 py-3 text-center">{{ __('Guild') }}</th>
                                <th class="px-4 py-3 text-center">{{ __('Status') }}</th>
                                <th class="px-4 py-3 text-center">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-rapanel-navy-100 dark:divide-white/[0.06]">
                            <tr v-for="char in characters" :key="char.char_id" class="hover:bg-rapanel-navy-50/30 dark:hover:bg-white/[0.05] transition-colors">
                                <td class="px-4 py-3 text-rapanel-text-light/50 dark:text-rapanel-text-dark/50 font-mono">{{ char.char_num + 1 }}</td>
                                <td class="px-4 py-3">
                                    <button @click="openCharModal(char)" class="font-bold text-rapanel-blue hover:underline hover:text-rapanel-blue/80 transition-colors text-left">
                                        {{ char.name }}
                                    </button>
                                </td>
                                <td class="px-4 py-3 text-rapanel-text-light/70 dark:text-rapanel-text-dark/70">
                                    <div class="flex items-center gap-1.5">
                                        <img :src="`/data/icon_jobs/icon_jobs_${char.class}.png`" @error="onImgError" class="w-5 h-5 object-contain shrink-0" :alt="getJobName(char.class)" />
                                        {{ getJobName(char.class) }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-center font-bold text-rapanel-navy-900 dark:text-white">{{ char.base_level }}</td>
                                <td class="px-4 py-3 text-center font-bold text-rapanel-navy-900 dark:text-white">{{ char.job_level }}</td>
                                <td class="px-4 py-3 text-right font-mono text-rapanel-danger dark:text-rapanel-gold">{{ formatNum(char.zeny) }} z</td>
                                <td class="px-4 py-3 text-center text-xs text-rapanel-text-light/60 dark:text-rapanel-text-dark/60">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <img v-if="char.guild_id" :src="`/guild-emblem/${char.guild_id}?v=${char.guild_emblem_id ?? 0}`" @error="onImgError" class="w-5 h-5 object-contain shrink-0" :alt="char.guild_name" />
                                        <span>{{ char.guild_name || '—' }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <StatusBadge
                                        :variant="char.online > 0 ? 'success' : 'neutral'"
                                        :label="char.online > 0 ? __('Online') : __('Offline')"
                                        :dot="true"
                                        size="sm"
                                    />
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-1">
                                        <ActionButton variant="gold" size="sm" @click="openPrefModal(char)" :title="__('Character Preferences')" :class="getCharPref(char.char_id)?.hide_from_rankings ? 'ring-1 ring-rapanel-gold' : ''">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        </ActionButton>
                                        <ActionButton variant="blue" size="sm" @click="openSlotModal(char)" :disabled="char.online > 0" :title="__('Change Slot')">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5"/></svg>
                                        </ActionButton>
                                        <ActionButton variant="purple" size="sm" @click="openResetLookModal(char)" :disabled="char.online > 0" :title="__('Reset Look')">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.098 19.902a3.75 3.75 0 005.304 0l6.401-6.402M6.75 21A3.75 3.75 0 013 17.25V4.125C3 3.504 3.504 3 4.125 3h5.25c.621 0 1.125.504 1.125 1.125v4.072M6.75 21a3.75 3.75 0 003.75-3.75V8.197M6.75 21h13.125c.621 0 1.125-.504 1.125-1.125v-5.25c0-.621-.504-1.125-1.125-1.125h-4.072M10.5 8.197l2.88-2.88c.438-.439 1.15-.439 1.59 0l3.712 3.713c.44.44.44 1.152 0 1.59l-2.879 2.88M6.75 17.25h.008v.008H6.75v-.008z"/></svg>
                                        </ActionButton>
                                        <ActionButton variant="navy" size="sm" @click="openResetPosModal(char)" :disabled="char.online > 0" :title="__('Reset Position')">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
                                        </ActionButton>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot class="bg-rapanel-navy-100/70 dark:bg-rapanel-navy-800 border-t-2 border-rapanel-navy-100 dark:border-white/[0.08]">
                            <tr>
                                <td colspan="5" class="px-4 py-2 text-right text-[10px] uppercase tracking-widest font-extrabold text-rapanel-text-light/50 dark:text-rapanel-text-dark/50">{{ __('Total Zeny') }}</td>
                                <td class="px-4 py-2 text-right font-mono font-bold text-rapanel-danger dark:text-rapanel-gold">{{ formatNum(totalZeny) }} z</td>
                                <td colspan="3"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Cards: móvil -->
                <div v-if="characters.length > 0" class="md:hidden divide-y divide-rapanel-navy-100 dark:divide-white/[0.06]">
                    <div v-for="char in characters" :key="char.char_id"
                         class="px-4 py-4 hover:bg-rapanel-navy-50/30 dark:hover:bg-white/[0.05] transition-colors">
                        <!-- Fila principal: nombre + dot estado -->
                        <div class="flex items-start justify-between gap-2 mb-2">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <span :class="char.online > 0 ? 'bg-rapanel-success' : 'bg-rapanel-text-light/25 dark:bg-white/20'" class="w-2 h-2 rounded-full shrink-0 mt-0.5"></span>
                                    <button @click="openCharModal(char)" class="font-bold text-rapanel-blue hover:underline text-left text-sm leading-tight">
                                        {{ char.name }}
                                    </button>
                                    <span class="text-[10px] font-mono text-rapanel-text-light/40 dark:text-white/25">·{{ char.char_num + 1 }}</span>
                                </div>
                                <!-- Job + nivel -->
                                <div class="flex items-center gap-1.5 mt-1">
                                    <img :src="`/data/icon_jobs/icon_jobs_${char.class}.png`" @error="onImgError" class="w-4 h-4 object-contain shrink-0" />
                                    <span class="text-xs text-rapanel-text-light/70 dark:text-rapanel-text-dark/70">{{ getJobName(char.class) }}</span>
                                    <span class="text-rapanel-text-light/30 dark:text-white/20 text-xs">·</span>
                                    <span class="text-xs font-bold text-rapanel-navy-900 dark:text-white">{{ char.base_level }}<span class="text-rapanel-text-light/40 dark:text-white/30 font-normal">/</span>{{ char.job_level }}</span>
                                </div>
                                <!-- Zeny + guild -->
                                <div class="flex items-center gap-3 mt-1">
                                    <span class="text-xs font-mono text-rapanel-danger dark:text-rapanel-gold">{{ formatNum(char.zeny) }} z</span>
                                    <span v-if="char.guild_name" class="flex items-center gap-1 text-xs text-rapanel-text-light/60 dark:text-rapanel-text-dark/60">
                                        <img v-if="char.guild_id" :src="`/guild-emblem/${char.guild_id}?v=${char.guild_emblem_id ?? 0}`" @error="onImgError" class="w-4 h-4 object-contain shrink-0" />
                                        {{ char.guild_name }}
                                    </span>
                                </div>
                            </div>
                            <!-- Botones acción 2×2 -->
                            <div class="grid grid-cols-2 gap-1 shrink-0">
                                <ActionButton variant="gold" size="sm" @click="openPrefModal(char)" :title="__('Character Preferences')" :class="getCharPref(char.char_id)?.hide_from_rankings ? 'ring-1 ring-rapanel-gold' : ''">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                </ActionButton>
                                <ActionButton variant="blue" size="sm" @click="openSlotModal(char)" :disabled="char.online > 0" :title="__('Change Slot')">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5"/></svg>
                                </ActionButton>
                                <ActionButton variant="purple" size="sm" @click="openResetLookModal(char)" :disabled="char.online > 0" :title="__('Reset Look')">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.098 19.902a3.75 3.75 0 005.304 0l6.401-6.402M6.75 21A3.75 3.75 0 013 17.25V4.125C3 3.504 3.504 3 4.125 3h5.25c.621 0 1.125.504 1.125 1.125v4.072M6.75 21a3.75 3.75 0 003.75-3.75V8.197M6.75 21h13.125c.621 0 1.125-.504 1.125-1.125v-5.25c0-.621-.504-1.125-1.125-1.125h-4.072M10.5 8.197l2.88-2.88c.438-.439 1.15-.439 1.59 0l3.712 3.713c.44.44.44 1.152 0 1.59l-2.879 2.88M6.75 17.25h.008v.008H6.75v-.008z"/></svg>
                                </ActionButton>
                                <ActionButton variant="navy" size="sm" @click="openResetPosModal(char)" :disabled="char.online > 0" :title="__('Reset Position')">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
                                </ActionButton>
                            </div>
                        </div>
                    </div>
                    <!-- Total Zeny móvil -->
                    <div class="px-4 py-3 bg-rapanel-navy-100/70 dark:bg-rapanel-navy-800 flex items-center justify-between">
                        <span class="text-[10px] uppercase tracking-widest font-extrabold text-rapanel-text-light/50 dark:text-rapanel-text-dark/50">{{ __('Total Zeny') }}</span>
                        <span class="font-mono font-bold text-sm text-rapanel-danger dark:text-rapanel-gold">{{ formatNum(totalZeny) }} z</span>
                    </div>
                </div>
            </div>

            <!-- ===== SECCIÓN 3: STORAGE ITEMS ===== -->
            <div class="bg-white dark:bg-rapanel-navy-900 border border-rapanel-navy-100 dark:border-white/10 rounded-xl shadow-xl dark:shadow-black/30 overflow-hidden">
                <div class="px-6 py-4 border-b border-rapanel-navy-100 dark:border-white/10 bg-white dark:bg-rapanel-navy-900">
                    <h3 class="text-base font-display font-bold uppercase tracking-widest text-rapanel-navy-900 dark:text-white">
                        {{ __('Storage Items of') }} <span class="text-rapanel-blue uppercase">{{ gameAccount.userid }}</span>
                        <span class="ml-2 text-xs font-normal text-rapanel-text-light/40 dark:text-rapanel-text-dark/40">({{ storageItems.length }})</span>
                    </h3>
                </div>

                <div v-if="storageItems.length === 0" class="px-6 py-10 text-center text-rapanel-text-light/40 dark:text-rapanel-text-dark/45 italic text-sm">
                    {{ __('No storage items found.') }}
                </div>

                <!-- Tabla: md+ -->
                <div v-else class="hidden md:block overflow-x-auto max-h-96 overflow-y-auto">
                    <table class="w-full text-xs text-left">
                        <thead class="sticky top-0 z-10 bg-rapanel-navy-100/70 dark:bg-rapanel-navy-800 text-[10px] uppercase tracking-widest font-bold text-rapanel-text-light/50 dark:text-rapanel-text-dark/50">
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
                        <tbody class="divide-y divide-rapanel-navy-100/50 dark:divide-white/[0.04]">
                            <tr v-for="(item, idx) in storageItems" :key="idx"
                                class="hover:bg-rapanel-navy-50/30 dark:hover:bg-white/[0.05] transition-colors">
                                <td class="px-3 py-1.5 text-center font-mono text-[11px] text-rapanel-text-light/50 dark:text-rapanel-text-dark/40 cursor-pointer hover:text-rapanel-blue"
                                    @click="openItemDb(item.nameid, item)">{{ item.nameid }}</td>
                                <td class="px-3 py-1.5 text-center cursor-pointer"
                                    @click="openItemDb(item.nameid, item)">
                                    <img :src="`/data/items/item/${item.nameid}.png`" @error="onImgError" class="w-7 h-7 object-contain mx-auto hover:scale-110 transition-transform" :alt="item.name_english" />
                                </td>
                                <td class="px-3 py-1.5 cursor-pointer"
                                    @click="openItemDb(item.nameid, item)">
                                    <span v-if="item.refine > 0" class="font-bold text-rapanel-danger dark:text-rapanel-gold mr-1">+{{ item.refine }}</span>
                                    <span class="font-medium text-rapanel-navy-900 dark:text-white hover:text-rapanel-blue dark:hover:text-rapanel-blue transition-colors">{{ itemLabel(item) }}</span>
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
                                    <span v-if="getCardName(item[slot])"
                                        class="text-rapanel-blue not-italic font-medium cursor-pointer hover:underline"
                                        @click="openItemDb(item[slot], { nameid: item[slot] })">{{ getCardName(item[slot]) }}</span>
                                    <span v-else>{{ __('None') }}</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Cards storage: móvil -->
                <div v-if="storageItems.length > 0" class="md:hidden divide-y divide-rapanel-navy-100/50 dark:divide-white/[0.04] max-h-96 overflow-y-auto">
                    <div v-for="(item, idx) in storageItems" :key="idx"
                         class="px-3 py-3 flex items-center gap-3 hover:bg-rapanel-navy-50/30 dark:hover:bg-white/[0.05] transition-colors cursor-pointer"
                         @click="openItemDb(item.nameid, item)">
                        <div class="flex flex-col items-center gap-0.5 shrink-0 w-10">
                            <img :src="`/data/items/item/${item.nameid}.png`" @error="onImgError" class="w-9 h-9 object-contain" :alt="item.name_english" />
                            <span class="font-mono text-[9px] text-rapanel-text-light/40 dark:text-rapanel-text-dark/30 mt-0.5 whitespace-nowrap">ID: {{ item.nameid }}</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <span v-if="item.refine > 0" class="text-xs font-bold text-rapanel-danger dark:text-rapanel-gold shrink-0">+{{ item.refine }}</span>
                                <span class="text-sm font-medium text-rapanel-navy-900 dark:text-white truncate">{{ itemLabel(item) }}</span>
                                <span v-if="item.attribute > 0" class="text-rapanel-danger font-bold text-[10px] shrink-0">[{{ __('Broken') }}]</span>
                            </div>
                            <div v-if="['card0','card1','card2','card3'].some(s => getCardName(item[s]))" class="grid grid-cols-2 gap-1 mt-1">
                                <template v-for="slot in ['card0','card1','card2','card3']" :key="slot">
                                    <span v-if="getCardName(item[slot])"
                                          class="text-[10px] font-medium text-rapanel-blue bg-rapanel-blue/10 border border-rapanel-blue/20 px-1.5 py-0.5 rounded truncate cursor-pointer"
                                          @click.stop="openItemDb(item[slot], { nameid: item[slot] })">{{ getCardName(item[slot]) }}</span>
                                </template>
                            </div>
                        </div>
                        <span class="text-xs font-bold text-rapanel-text-light/60 dark:text-rapanel-text-dark/60 shrink-0">×{{ item.amount }}</span>
                    </div>
                </div>
            </div>

        </main>

        <!-- ===== ITEM DB MODAL ===== -->

        <!-- ===== CHARACTER DETAIL OVERLAY ===== -->
        <CharacterDetail
            :char="charModal"
            :card-names="cardNames"
            @close="closeCharModal"
            @reset-look="openResetLookModal"
            @reset-position="openResetPosModal"
            @open-preferences="openPrefModal"
            @change-slot="openSlotModal"
        />

        <!-- ===== MODAL: ACTIVITY LOGS ===== -->
        <ViewActivityLogs :show="logsModal" :account="gameAccount" @close="logsModal = false" />

        <!-- ===== MODAL: CHANGE PASSWORD ===== -->
        <Modal :show="changePasswordModal" @close="closeChangePasswordModal">
            <div class="p-6 bg-white dark:bg-rapanel-navy-900">
                <h2 class="text-lg font-bold text-rapanel-navy-900 dark:text-white mb-2 border-b border-rapanel-navy-100 dark:border-white/10 pb-3 uppercase tracking-wider">
                    {{ __('Change Password for') }} <span class="text-rapanel-blue">{{ gameAccount.userid }}</span>
                </h2>
                <p class="text-sm text-rapanel-text-light/60 dark:text-rapanel-text-dark/55 mb-4 italic">
                    {{ __('Enter a new password for this game account. For security, we request your master account password.') }}
                </p>
                <!-- Password rules -->
                <ul class="mb-4 space-y-1 text-xs text-rapanel-text-light/60 dark:text-rapanel-text-dark/60 list-none">
                    <li class="flex items-center gap-1.5"><span class="text-rapanel-gold font-bold">•</span> {{ __('pwd_rule_length') }}</li>
                    <li class="flex items-center gap-1.5"><span class="text-rapanel-gold font-bold">•</span> {{ __('pwd_rule_uppercase') }}</li>
                    <li class="flex items-center gap-1.5"><span class="text-rapanel-gold font-bold">•</span> {{ __('pwd_rule_lowercase') }}</li>
                    <li class="flex items-center gap-1.5"><span class="text-rapanel-gold font-bold">•</span> {{ __('pwd_rule_number') }}</li>
                    <li class="flex items-center gap-1.5"><span class="text-rapanel-gold font-bold">•</span> {{ __('pwd_rule_username') }}</li>
                </ul>
                <form @submit.prevent="submitChangePassword" class="space-y-4">
                    <div>
                        <InputLabel for="cp_new_password" :value="__('New Game Password')" />
                        <TextInput id="cp_new_password" v-model="passwordForm.password" type="password"
                            class="mt-1 block w-full bg-white dark:bg-rapanel-navy-800"
                            required autofocus />
                        <InputError class="mt-1" :message="passwordForm.errors.password" />
                    </div>
                    <div>
                        <InputLabel for="cp_confirm_password" :value="__('Confirm New Password')" />
                        <TextInput id="cp_confirm_password" v-model="passwordForm.password_confirmation" type="password"
                            class="mt-1 block w-full bg-white dark:bg-rapanel-navy-800" required />
                        <InputError class="mt-1" :message="passwordForm.errors.password_confirmation" />
                    </div>
                    <!-- 2FA activo: pide código TOTP; sin 2FA: pide contraseña maestra -->
                    <div v-if="hasTwoFactor">
                        <InputLabel for="cp_totp" :value="__('Code from your authenticator app')" class="text-rapanel-blue font-bold uppercase text-xs" />
                        <TextInput id="cp_totp" v-model="passwordForm.totp_code"
                            type="text" inputmode="numeric" autocomplete="one-time-code" maxlength="6"
                            class="mt-1 block w-full border-rapanel-blue/30 focus:ring-rapanel-blue focus:border-rapanel-blue bg-white dark:bg-rapanel-navy-800 tracking-widest text-center"
                            :placeholder="__('6-digit code')" required />
                        <InputError class="mt-1" :message="passwordForm.errors.totp_code" />
                    </div>
                    <div v-else>
                        <InputLabel for="cp_current_password" :value="__('Master Account Password')" class="text-rapanel-gold font-bold uppercase text-xs" />
                        <TextInput id="cp_current_password" v-model="passwordForm.current_password" type="password"
                            class="mt-1 block w-full border-rapanel-gold/30 focus:ring-rapanel-gold focus:border-rapanel-gold bg-white dark:bg-rapanel-navy-800"
                            required />
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
            <div class="p-6 bg-white dark:bg-rapanel-navy-900">
                <h2 class="text-lg font-bold text-rapanel-navy-900 dark:text-white mb-2 border-b border-rapanel-navy-100 dark:border-white/10 pb-3 uppercase tracking-wider">
                    {{ __('Change Gender') }}
                </h2>
                <p class="text-sm text-rapanel-text-light/60 dark:text-rapanel-text-dark/55 mb-1">
                    {{ __('Account') }}: <span class="font-bold text-rapanel-blue">{{ gameAccount.userid }}</span>
                    &nbsp;|&nbsp; {{ __('Current Gender') }}: <span class="font-bold text-rapanel-purple">{{ formatSex(gameAccount.sex) }}</span>
                    &nbsp;→&nbsp; <span class="font-bold text-rapanel-gold">{{ gameAccount.sex === 'M' ? __('Female') : __('Male') }}</span>
                </p>
                <p class="text-sm text-rapanel-text-light/60 dark:text-rapanel-text-dark/55 mb-6 italic">
                    {{ __('All characters must be offline. Confirm with your master password.') }}
                </p>
                <form @submit.prevent="submitGender" class="space-y-5">
                    <div v-if="hasTwoFactor">
                        <InputLabel for="gender_totp" :value="__('Code from your authenticator app')" class="text-rapanel-blue font-bold uppercase text-xs" />
                        <TextInput id="gender_totp" v-model="genderForm.totp_code"
                            type="text" inputmode="numeric" autocomplete="one-time-code" maxlength="6"
                            class="mt-1 block w-full border-rapanel-blue/30 focus:ring-rapanel-blue focus:border-rapanel-blue bg-white dark:bg-rapanel-navy-800 tracking-widest text-center"
                            :placeholder="__('6-digit code')" required autofocus />
                        <InputError class="mt-2" :message="genderForm.errors.totp_code" />
                    </div>
                    <div v-else>
                        <InputLabel for="gender_password" :value="__('Master Account Password')" class="text-rapanel-gold font-bold uppercase text-xs" />
                        <TextInput id="gender_password" v-model="genderForm.password" type="password"
                            class="mt-1 block w-full border-rapanel-gold/30 focus:ring-rapanel-gold focus:border-rapanel-gold bg-white dark:bg-rapanel-navy-800"
                            required autofocus />
                        <InputError class="mt-2" :message="genderForm.errors.password" />
                    </div>
                    <InputError class="mt-2" :message="genderForm.errors.error" />
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
            <div class="p-6 bg-white dark:bg-rapanel-navy-900">
                <h2 class="text-lg font-bold text-rapanel-navy-900 dark:text-white mb-2 border-b border-rapanel-navy-100 dark:border-white/10 pb-3 uppercase tracking-wider">{{ __('Reset Position') }}</h2>
                <p class="text-sm text-rapanel-text-light/60 dark:text-rapanel-text-dark/55 mb-1">{{ __('Character') }}: <span class="font-bold text-rapanel-blue">{{ selectedPos?.name }}</span></p>
                <p class="text-sm text-rapanel-text-light/60 dark:text-rapanel-text-dark/55 mb-6 italic">{{ __('This will teleport the character to the default spawn point. Confirm with your master password.') }}</p>
                <form @submit.prevent="confirmResetPos" class="space-y-5">
                    <div>
                        <InputLabel for="rpos_pw" :value="__('Master Account Password')" class="text-rapanel-gold font-bold uppercase text-xs" />
                        <TextInput id="rpos_pw" v-model="resetPosForm.password" type="password" class="mt-1 block w-full border-rapanel-gold/30 focus:ring-rapanel-gold focus:border-rapanel-gold bg-white dark:bg-rapanel-navy-800" required autofocus />
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
            <div class="p-6 bg-white dark:bg-rapanel-navy-900">
                <h2 class="text-lg font-bold text-rapanel-navy-900 dark:text-white mb-2 border-b border-rapanel-navy-100 dark:border-white/10 pb-3 uppercase tracking-wider">{{ __('Reset Look') }}</h2>
                <p class="text-sm text-rapanel-text-light/60 dark:text-rapanel-text-dark/55 mb-1">{{ __('Character') }}: <span class="font-bold text-rapanel-blue">{{ selectedLook?.name }}</span></p>
                <p class="text-sm text-rapanel-text-light/60 dark:text-rapanel-text-dark/55 mb-6 italic">{{ __('This will reset the character\'s appearance (hair, color, outfit) to default values. Confirm with your master password.') }}</p>
                <form @submit.prevent="confirmResetLook" class="space-y-5">
                    <div>
                        <InputLabel for="rlook_pw" :value="__('Master Account Password')" class="text-rapanel-gold font-bold uppercase text-xs" />
                        <TextInput id="rlook_pw" v-model="resetLookForm.password" type="password" class="mt-1 block w-full border-rapanel-gold/30 focus:ring-rapanel-gold focus:border-rapanel-gold bg-white dark:bg-rapanel-navy-800" required autofocus />
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

        <!-- ===== MODAL: CHANGE SLOT ===== -->
        <Modal :show="slotModal" @close="closeSlotModal">
            <div class="p-6 bg-white dark:bg-rapanel-navy-900">
                <h2 class="text-lg font-bold text-rapanel-navy-900 dark:text-white mb-2 border-b border-rapanel-navy-100 dark:border-white/10 pb-3 uppercase tracking-wider">{{ __('Change Slot') }}</h2>
                <p class="text-sm text-rapanel-text-light/60 dark:text-rapanel-text-dark/55 mb-1">
                    {{ __('Character') }}: <span class="font-bold text-rapanel-blue">{{ selectedSlotChar?.name }}</span>
                    &nbsp;|&nbsp; {{ __('Current Slot') }}: <span class="font-bold text-rapanel-blue">{{ (selectedSlotChar?.char_num ?? 0) + 1 }}</span>
                </p>
                <p class="text-sm text-rapanel-text-light/60 dark:text-rapanel-text-dark/55 mb-4 italic">{{ __('Select a slot for this character. If the target slot is occupied, the characters will be swapped.') }}</p>

                <!-- Slot picker -->
                <div class="grid grid-cols-3 gap-2 mb-5">
                    <button
                        v-for="n in effectiveMaxSlots"
                        :key="n - 1"
                        type="button"
                        @click="selectTargetSlot(n - 1)"
                        :disabled="(n - 1) === selectedSlotChar?.char_num || (slotOccupancy[n - 1] && slotOccupancy[n - 1].online > 0)"
                        :class="['rounded-lg px-3 py-2 flex flex-col items-center gap-0.5 text-center', slotButtonClass(n - 1)]"
                    >
                        <span class="text-xs font-bold leading-none">{{ __('Slot') }} {{ n }}</span>
                        <span v-if="(n - 1) === selectedSlotChar?.char_num" class="text-[10px] leading-none opacity-70">{{ __('Current') }}</span>
                        <span v-else-if="slotOccupancy[n - 1] && slotOccupancy[n - 1].online > 0" class="text-[10px] leading-none text-rapanel-danger/70">{{ __('Online') }}</span>
                        <span v-else-if="slotOccupancy[n - 1]" class="text-[10px] leading-none truncate max-w-full">{{ slotOccupancy[n - 1].name }}</span>
                        <span v-else class="text-[10px] leading-none opacity-50">{{ __('Empty') }}</span>
                    </button>
                </div>

                <form v-if="targetSlot !== null" @submit.prevent="confirmSlotChange" class="space-y-4">
                    <p class="text-xs text-rapanel-text-light/60 dark:text-rapanel-text-dark/60">
                        {{ __('Moving to slot') }} <span class="font-bold text-rapanel-blue">{{ targetSlot + 1 }}</span>
                        <span v-if="slotOccupancy[targetSlot]">
                            &nbsp;— {{ __('Swapping with') }} <span class="font-bold text-rapanel-gold">{{ slotOccupancy[targetSlot].name }}</span>
                        </span>
                    </p>
                    <div>
                        <InputLabel for="slot_pw" :value="__('Master Account Password')" class="text-rapanel-gold font-bold uppercase text-xs" />
                        <TextInput id="slot_pw" v-model="slotForm.password" type="password"
                            class="mt-1 block w-full border-rapanel-gold/30 focus:ring-rapanel-gold focus:border-rapanel-gold bg-white dark:bg-rapanel-navy-800"
                            required autofocus />
                        <InputError class="mt-2" :message="slotForm.errors.password" />
                        <InputError class="mt-2" :message="slotForm.errors.error" />
                    </div>
                    <div class="flex justify-end gap-3">
                        <SecondaryButton @click="closeSlotModal">{{ __('Cancel') }}</SecondaryButton>
                        <PrimaryButton :disabled="slotForm.processing" :class="{ 'opacity-25': slotForm.processing }">{{ __('Confirm Slot Change') }}</PrimaryButton>
                    </div>
                </form>
                <div v-else class="flex justify-end">
                    <SecondaryButton @click="closeSlotModal">{{ __('Cancel') }}</SecondaryButton>
                </div>
            </div>
        </Modal>

        <!-- ===== MODAL: CHARACTER PREFERENCES ===== -->
        <Modal :show="prefModal" @close="closePrefModal">
            <div class="p-6 bg-white dark:bg-rapanel-navy-900">
                <h2 class="text-lg font-bold text-rapanel-navy-900 dark:text-white mb-2 border-b border-rapanel-navy-100 dark:border-white/10 pb-3 uppercase tracking-wider">{{ __('Character Preferences') }}</h2>
                <p class="text-sm text-rapanel-text-light/60 dark:text-rapanel-text-dark/55 mb-4">
                    {{ __('Character') }}: <span class="font-bold text-rapanel-blue">{{ selectedPrefChar?.name }}</span>
                </p>
                <form @submit.prevent="confirmPrefUpdate" class="space-y-5">
                    <!-- Hide from Rankings toggle -->
                    <label class="flex items-start gap-3 cursor-pointer group">
                        <div class="relative mt-0.5 shrink-0">
                            <input type="checkbox" v-model="prefForm.hide_from_rankings" class="sr-only peer" />
                            <div class="w-10 h-6 rounded-full border-2 transition-all
                                border-rapanel-navy-100 dark:border-white/20 bg-rapanel-navy-100/50 dark:bg-rapanel-navy-700
                                peer-checked:bg-rapanel-blue peer-checked:border-rapanel-blue"></div>
                            <div class="absolute left-1 top-1 w-4 h-4 rounded-full bg-white shadow transition-transform peer-checked:translate-x-4"></div>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-rapanel-navy-900 dark:text-white">{{ __('Hide from Rankings') }}</p>
                            <p class="text-xs text-rapanel-text-light/60 dark:text-rapanel-text-dark/60 mt-0.5">{{ __('This character will not appear in public ranking pages.') }}</p>
                        </div>
                    </label>

                    <div>
                        <InputLabel for="pref_pw" :value="__('Master Account Password')" class="text-rapanel-gold font-bold uppercase text-xs" />
                        <TextInput id="pref_pw" v-model="prefForm.password" type="password"
                            class="mt-1 block w-full border-rapanel-gold/30 focus:ring-rapanel-gold focus:border-rapanel-gold bg-white dark:bg-rapanel-navy-800"
                            required autofocus />
                        <InputError class="mt-2" :message="prefForm.errors.password" />
                        <InputError class="mt-2" :message="prefForm.errors.error" />
                    </div>
                    <div class="flex justify-end gap-3">
                        <SecondaryButton @click="closePrefModal">{{ __('Cancel') }}</SecondaryButton>
                        <PrimaryButton :disabled="prefForm.processing" :class="{ 'opacity-25': prefForm.processing }">{{ __('Save Preferences') }}</PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

    </div>

    <Footer />
    <ItemDbModal />
    <MobDbModal />
</template>

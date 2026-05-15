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

const props = defineProps({
    gameAccount: Object,
    characters: Array,
});

const page = usePage();
const __ = (key) => page.props.translations?.[key] || key;

const flashSuccess = computed(() => page.props.flash?.success);
const flashError   = computed(() => page.props.flash?.error);

// --- MAPA DE CLASES DE rAthena ---
const JOB_NAMES = {
    0: 'Novice', 1: 'Swordman', 2: 'Mage', 3: 'Archer',
    4: 'Acolyte', 5: 'Merchant', 6: 'Thief',
    7: 'Knight', 8: 'Priest', 9: 'Wizard',
    10: 'Blacksmith', 11: 'Hunter', 12: 'Assassin',
    14: 'Crusader', 15: 'Monk', 16: 'Sage',
    17: 'Rogue', 18: 'Alchemist', 19: 'Bard', 20: 'Dancer',
    22: 'Super Novice', 23: 'Gunslinger', 24: 'Ninja',
    25: 'Novice High', 26: 'Swordman High', 27: 'Mage High',
    28: 'Archer High', 29: 'Acolyte High', 30: 'Merchant High', 31: 'Thief High',
    32: 'Lord Knight', 33: 'High Priest', 34: 'High Wizard',
    35: 'Whitesmith', 36: 'Sniper', 37: 'Assassin Cross',
    39: 'Paladin', 40: 'Champion', 41: 'Professor',
    42: 'Stalker', 43: 'Creator', 44: 'Clown', 45: 'Gypsy',
    4054: 'Rune Knight', 4055: 'Warlock', 4056: 'Ranger',
    4057: 'Archbishop', 4058: 'Mechanic', 4059: 'Guillotine Cross',
    4066: 'Royal Guard', 4067: 'Sorcerer', 4068: 'Minstrel',
    4069: 'Wanderer', 4070: 'Sura', 4071: 'Genetic', 4072: 'Shadow Chaser',
    4096: 'Doram', 4211: 'Summoner',
};

const getJobName = (classId) => JOB_NAMES[classId] ?? `Class ${classId}`;

// --- INVENTARIO EXPANDIDO ---
const expandedInventory = ref({});
const toggleInventory = (charId) => {
    expandedInventory.value[charId] = !expandedInventory.value[charId];
};

// --- MODAL RESET POSICIÓN ---
const resetPosModal    = ref(false);
const selectedCharPos  = ref(null);
const resetPosForm     = useForm({ password: '' });

const openResetPosModal = (char) => {
    selectedCharPos.value = char;
    resetPosModal.value   = true;
};
const closeResetPosModal = () => {
    resetPosModal.value = false;
    selectedCharPos.value = null;
    resetPosForm.reset();
    resetPosForm.clearErrors();
};
const confirmResetPosition = () => {
    resetPosForm.put(route('characters.reset-position', selectedCharPos.value.char_id), {
        preserveScroll: true,
        onSuccess: () => closeResetPosModal(),
    });
};

// --- MODAL RESET LOOK ---
const resetLookModal   = ref(false);
const selectedCharLook = ref(null);
const resetLookForm    = useForm({ password: '' });

const openResetLookModal = (char) => {
    selectedCharLook.value = char;
    resetLookModal.value   = true;
};
const closeResetLookModal = () => {
    resetLookModal.value = false;
    selectedCharLook.value = null;
    resetLookForm.reset();
    resetLookForm.clearErrors();
};
const confirmResetLook = () => {
    resetLookForm.put(route('characters.reset-look', selectedCharLook.value.char_id), {
        preserveScroll: true,
        onSuccess: () => closeResetLookModal(),
    });
};

// --- HELPERS ---
const formatZeny = (n) => Number(n || 0).toLocaleString();
const barPercent = (cur, max) => max > 0 ? Math.min(100, Math.round((cur / max) * 100)) : 0;
const formatMap  = (map) => map ? map.replace('.gat', '') : '—';
</script>

<template>
    <Head :title="__('Account Details')" />

    <div class="min-h-screen bg-rapanel-navy-50 dark:bg-rapanel-navy-900 text-rapanel-text-light dark:text-rapanel-text-dark font-sans antialiased transition-colors duration-300">

        <Header />

        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 space-y-6">

            <!-- Flash messages -->
            <div v-if="flashSuccess" class="flex items-center gap-3 px-5 py-4 rounded-xl bg-rapanel-success/10 border border-rapanel-success/30 text-rapanel-success text-sm font-medium">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                {{ flashSuccess }}
            </div>
            <div v-if="flashError" class="flex items-center gap-3 px-5 py-4 rounded-xl bg-rapanel-danger/10 border border-rapanel-danger/30 text-rapanel-danger text-sm font-medium">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                </svg>
                {{ flashError }}
            </div>

            <!-- Encabezado de la cuenta -->
            <div class="bg-white dark:bg-rapanel-navy-800 border border-rapanel-navy-100 dark:border-gray-700/50 rounded-xl p-6 shadow-xl">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <Link
                            :href="route('dashboard')"
                            class="flex items-center justify-center w-9 h-9 rounded-lg bg-rapanel-navy-100 dark:bg-gray-700 hover:bg-rapanel-blue hover:text-white dark:hover:bg-rapanel-blue text-rapanel-text-light dark:text-rapanel-text-dark transition-all"
                            :title="__('Back to Dashboard')"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                            </svg>
                        </Link>
                        <div>
                            <p class="text-xs text-rapanel-text-light/50 dark:text-rapanel-text-dark/50 uppercase tracking-widest font-bold mb-0.5">{{ __('Account Details') }}</p>
                            <h1 class="text-2xl font-bold text-rapanel-blue tracking-wide">{{ gameAccount.userid }}</h1>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-3 text-xs">
                        <div class="bg-rapanel-navy-50 dark:bg-black/20 border border-rapanel-navy-100 dark:border-gray-700/30 rounded-lg px-4 py-2">
                            <span class="text-rapanel-text-light/50 dark:text-rapanel-text-dark/50 uppercase tracking-widest font-bold">ID</span>
                            <p class="font-mono font-bold text-rapanel-navy-900 dark:text-white mt-0.5">{{ gameAccount.account_id }}</p>
                        </div>
                        <div class="bg-rapanel-navy-50 dark:bg-black/20 border border-rapanel-navy-100 dark:border-gray-700/30 rounded-lg px-4 py-2">
                            <span class="text-rapanel-text-light/50 dark:text-rapanel-text-dark/50 uppercase tracking-widest font-bold">{{ __('Last Login') }}</span>
                            <p class="font-mono font-bold text-rapanel-navy-900 dark:text-white mt-0.5">{{ gameAccount.lastlogin || __('Never') }}</p>
                        </div>
                        <div class="bg-rapanel-navy-50 dark:bg-black/20 border border-rapanel-navy-100 dark:border-gray-700/30 rounded-lg px-4 py-2">
                            <span class="text-rapanel-text-light/50 dark:text-rapanel-text-dark/50 uppercase tracking-widest font-bold">{{ __('Status') }}</span>
                            <div class="mt-0.5">
                                <span v-if="gameAccount.state === 0" class="inline-flex items-center gap-1 text-rapanel-success font-bold">
                                    <span class="w-1.5 h-1.5 bg-rapanel-success rounded-full animate-pulse"></span>{{ __('Active') }}
                                </span>
                                <span v-else class="inline-flex items-center gap-1 text-rapanel-danger font-bold">
                                    <span class="w-1.5 h-1.5 bg-rapanel-danger rounded-full"></span>{{ __('Blocked') }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sección personajes -->
            <div class="bg-white dark:bg-rapanel-navy-800 border border-rapanel-navy-100 dark:border-gray-700/50 rounded-xl shadow-xl overflow-hidden">
                <div class="px-6 py-5 border-b border-rapanel-navy-100 dark:border-gray-700 bg-rapanel-navy-50/30 dark:bg-black/10">
                    <h3 class="text-lg font-bold text-rapanel-navy-900 dark:text-white tracking-wide uppercase">
                        {{ __('Characters') }}
                        <span class="ml-2 text-sm font-normal text-rapanel-text-light/50 dark:text-rapanel-text-dark/50">({{ characters.length }})</span>
                    </h3>
                </div>

                <!-- Sin personajes -->
                <div v-if="characters.length === 0" class="px-6 py-16 text-center text-rapanel-text-light/40 dark:text-gray-500 italic">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 mx-auto mb-2 opacity-20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                    </svg>
                    {{ __('No characters found.') }}
                </div>

                <!-- Lista de personajes -->
                <div v-else class="divide-y divide-rapanel-navy-100 dark:divide-gray-700">
                    <div v-for="char in characters" :key="char.char_id" class="p-6">

                        <!-- Cabecera del personaje -->
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                            <div class="flex items-center gap-3">
                                <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-rapanel-blue/10 border border-rapanel-blue/20 font-bold text-rapanel-blue text-lg">
                                    {{ char.char_num + 1 }}
                                </div>
                                <div>
                                    <h4 class="text-lg font-bold text-rapanel-navy-900 dark:text-white">{{ char.name }}</h4>
                                    <p class="text-xs text-rapanel-text-light/60 dark:text-rapanel-text-dark/60">{{ getJobName(char.class) }}</p>
                                </div>
                                <span v-if="char.online > 0" class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-rapanel-success/10 text-rapanel-success border border-rapanel-success/20">
                                    <span class="w-1.5 h-1.5 bg-rapanel-success rounded-full animate-pulse"></span> {{ __('Online') }}
                                </span>
                                <span v-else class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-gray-500/10 text-gray-400 border border-gray-500/20">
                                    <span class="w-1.5 h-1.5 bg-gray-400 rounded-full"></span> {{ __('Offline') }}
                                </span>
                            </div>
                            <!-- Acciones -->
                            <div class="flex gap-2">
                                <button
                                    @click="openResetPosModal(char)"
                                    :disabled="char.online > 0"
                                    class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold transition-all focus:outline-none"
                                    :class="char.online > 0
                                        ? 'opacity-40 cursor-not-allowed bg-gray-100 dark:bg-gray-700 text-gray-400'
                                        : 'bg-rapanel-blue/10 text-rapanel-blue border border-rapanel-blue/20 hover:bg-rapanel-blue hover:text-white'"
                                    :title="char.online > 0 ? __('Character must be offline') : __('Reset Position')"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                                    </svg>
                                    {{ __('Reset Position') }}
                                </button>
                                <button
                                    @click="openResetLookModal(char)"
                                    :disabled="char.online > 0"
                                    class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold transition-all focus:outline-none"
                                    :class="char.online > 0
                                        ? 'opacity-40 cursor-not-allowed bg-gray-100 dark:bg-gray-700 text-gray-400'
                                        : 'bg-rapanel-gold/10 text-rapanel-gold border border-rapanel-gold/20 hover:bg-rapanel-gold hover:text-rapanel-navy-900'"
                                    :title="char.online > 0 ? __('Character must be offline') : __('Reset Look')"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.53 16.122a3 3 0 00-5.78 1.128 2.25 2.25 0 01-2.4 2.245 4.5 4.5 0 008.4-2.245c0-.399-.078-.78-.22-1.128zm0 0a15.998 15.998 0 003.388-1.62m-5.043-.025a15.994 15.994 0 011.622-3.395m3.42 3.42a15.995 15.995 0 004.764-4.648l3.876-5.814a1.151 1.151 0 00-1.597-1.597L14.146 6.32a15.996 15.996 0 00-4.649 4.763m3.42 3.42a6.776 6.776 0 00-3.42-3.42" />
                                    </svg>
                                    {{ __('Reset Look') }}
                                </button>
                            </div>
                        </div>

                        <!-- Cuerpo: stats + info + inventario -->
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

                            <!-- Stats -->
                            <div class="bg-rapanel-navy-50/50 dark:bg-black/20 border border-rapanel-navy-100 dark:border-gray-700/30 rounded-xl p-4 space-y-3">
                                <p class="text-xs font-bold uppercase tracking-widest text-rapanel-text-light/50 dark:text-rapanel-text-dark/50">{{ __('Stats') }}</p>
                                <div class="grid grid-cols-3 gap-2 text-center">
                                    <div v-for="stat in [
                                        { label: 'STR', value: char.str },
                                        { label: 'AGI', value: char.agi },
                                        { label: 'VIT', value: char.vit },
                                        { label: 'INT', value: char.int },
                                        { label: 'DEX', value: char.dex },
                                        { label: 'LUK', value: char.luk },
                                    ]" :key="stat.label"
                                        class="bg-white dark:bg-rapanel-navy-800 rounded-lg py-2 px-1 border border-rapanel-navy-100 dark:border-gray-700/30"
                                    >
                                        <p class="text-[10px] font-bold uppercase tracking-widest text-rapanel-text-light/50 dark:text-rapanel-text-dark/50">{{ stat.label }}</p>
                                        <p class="text-base font-bold text-rapanel-navy-900 dark:text-white">{{ stat.value ?? 0 }}</p>
                                    </div>
                                </div>
                                <!-- HP/SP -->
                                <div class="space-y-2 pt-1">
                                    <div>
                                        <div class="flex justify-between text-xs mb-1">
                                            <span class="font-bold text-rapanel-danger">HP</span>
                                            <span class="text-rapanel-text-light/60 dark:text-rapanel-text-dark/60">{{ (char.hp || 0).toLocaleString() }} / {{ (char.max_hp || 0).toLocaleString() }}</span>
                                        </div>
                                        <div class="h-2 bg-rapanel-navy-100 dark:bg-black/30 rounded-full overflow-hidden">
                                            <div class="h-full bg-rapanel-danger rounded-full transition-all" :style="{ width: barPercent(char.hp, char.max_hp) + '%' }"></div>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="flex justify-between text-xs mb-1">
                                            <span class="font-bold text-rapanel-blue">SP</span>
                                            <span class="text-rapanel-text-light/60 dark:text-rapanel-text-dark/60">{{ (char.sp || 0).toLocaleString() }} / {{ (char.max_sp || 0).toLocaleString() }}</span>
                                        </div>
                                        <div class="h-2 bg-rapanel-navy-100 dark:bg-black/30 rounded-full overflow-hidden">
                                            <div class="h-full bg-rapanel-blue rounded-full transition-all" :style="{ width: barPercent(char.sp, char.max_sp) + '%' }"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Info general -->
                            <div class="bg-rapanel-navy-50/50 dark:bg-black/20 border border-rapanel-navy-100 dark:border-gray-700/30 rounded-xl p-4 space-y-3">
                                <p class="text-xs font-bold uppercase tracking-widest text-rapanel-text-light/50 dark:text-rapanel-text-dark/50">{{ __('General') }}</p>
                                <div class="space-y-2 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-rapanel-text-light/60 dark:text-rapanel-text-dark/60">{{ __('Base Level') }}</span>
                                        <span class="font-bold text-rapanel-navy-900 dark:text-white">{{ char.base_level }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-rapanel-text-light/60 dark:text-rapanel-text-dark/60">{{ __('Job Level') }}</span>
                                        <span class="font-bold text-rapanel-navy-900 dark:text-white">{{ char.job_level }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-rapanel-text-light/60 dark:text-rapanel-text-dark/60">{{ __('Status Points') }}</span>
                                        <span class="font-bold text-rapanel-navy-900 dark:text-white">{{ char.status_point ?? 0 }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-rapanel-text-light/60 dark:text-rapanel-text-dark/60">{{ __('Skill Points') }}</span>
                                        <span class="font-bold text-rapanel-navy-900 dark:text-white">{{ char.skill_point ?? 0 }}</span>
                                    </div>
                                    <div class="border-t border-rapanel-navy-100 dark:border-gray-700/30 pt-2 flex justify-between">
                                        <span class="text-rapanel-text-light/60 dark:text-rapanel-text-dark/60">{{ __('Zeny') }}</span>
                                        <span class="font-bold text-rapanel-gold">{{ formatZeny(char.zeny) }} z</span>
                                    </div>
                                    <div class="border-t border-rapanel-navy-100 dark:border-gray-700/30 pt-2">
                                        <span class="text-rapanel-text-light/60 dark:text-rapanel-text-dark/60 text-xs block mb-1">{{ __('Location') }}</span>
                                        <span class="font-mono font-bold text-rapanel-navy-900 dark:text-white text-sm">{{ formatMap(char.last_map) }}</span>
                                        <span class="text-xs text-rapanel-text-light/50 dark:text-rapanel-text-dark/50 ml-1">({{ char.last_x }}, {{ char.last_y }})</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Inventario -->
                            <div class="bg-rapanel-navy-50/50 dark:bg-black/20 border border-rapanel-navy-100 dark:border-gray-700/30 rounded-xl overflow-hidden">
                                <button
                                    @click="toggleInventory(char.char_id)"
                                    class="w-full flex items-center justify-between px-4 py-3 text-left hover:bg-rapanel-navy-100/50 dark:hover:bg-black/20 transition-colors"
                                >
                                    <p class="text-xs font-bold uppercase tracking-widest text-rapanel-text-light/50 dark:text-rapanel-text-dark/50">
                                        {{ __('Inventory') }}
                                        <span class="ml-1 text-rapanel-blue">({{ char.inventory.length }})</span>
                                    </p>
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"
                                        class="w-4 h-4 text-rapanel-text-light/40 dark:text-rapanel-text-dark/40 transition-transform"
                                        :class="expandedInventory[char.char_id] ? 'rotate-180' : ''"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                    </svg>
                                </button>

                                <div v-if="expandedInventory[char.char_id]" class="max-h-64 overflow-y-auto">
                                    <div v-if="char.inventory.length === 0" class="px-4 py-6 text-center text-xs text-rapanel-text-light/40 dark:text-gray-500 italic">
                                        {{ __('No items found.') }}
                                    </div>
                                    <table v-else class="w-full text-xs">
                                        <thead class="sticky top-0 bg-rapanel-navy-100 dark:bg-black/40">
                                            <tr>
                                                <th class="px-3 py-2 text-left font-bold uppercase tracking-widest text-rapanel-text-light/50 dark:text-rapanel-text-dark/50">{{ __('Item') }}</th>
                                                <th class="px-3 py-2 text-center font-bold uppercase tracking-widest text-rapanel-text-light/50 dark:text-rapanel-text-dark/50">x</th>
                                                <th class="px-3 py-2 text-center font-bold uppercase tracking-widest text-rapanel-text-light/50 dark:text-rapanel-text-dark/50">{{ __('Slot') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-rapanel-navy-100/50 dark:divide-gray-700/30">
                                            <tr v-for="(item, idx) in char.inventory" :key="idx"
                                                class="hover:bg-rapanel-navy-100/30 dark:hover:bg-black/20"
                                            >
                                                <td class="px-3 py-2">
                                                    <span class="font-medium text-rapanel-navy-900 dark:text-white">{{ item.name_english }}</span>
                                                    <span v-if="item.refine > 0" class="ml-1 text-rapanel-gold font-bold">+{{ item.refine }}</span>
                                                    <span v-if="!item.identify" class="ml-1 text-gray-400 italic">({{ __('Unidentified') }})</span>
                                                </td>
                                                <td class="px-3 py-2 text-center text-rapanel-text-light/70 dark:text-rapanel-text-dark/70">{{ item.amount }}</td>
                                                <td class="px-3 py-2 text-center">
                                                    <span v-if="item.equip > 0" class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-rapanel-blue/10 text-rapanel-blue border border-rapanel-blue/20">
                                                        {{ __('Equipped') }}
                                                    </span>
                                                    <span v-else class="text-rapanel-text-light/40 dark:text-rapanel-text-dark/40">{{ __('Bag') }}</span>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

        </main>

        <!-- Modal: Reset Posición -->
        <Modal :show="resetPosModal" @close="closeResetPosModal">
            <div class="p-6 bg-white dark:bg-rapanel-navy-800 transition-colors duration-300">
                <h2 class="text-lg font-bold text-rapanel-navy-900 dark:text-white mb-2 border-b border-rapanel-navy-100 dark:border-gray-700 pb-3 uppercase tracking-wider">
                    {{ __('Reset Position') }}
                </h2>
                <p class="text-sm text-rapanel-text-light/60 dark:text-gray-400 mb-1">
                    {{ __('Character') }}: <span class="font-bold text-rapanel-blue">{{ selectedCharPos?.name }}</span>
                </p>
                <p class="text-sm text-rapanel-text-light/60 dark:text-gray-400 mb-6 italic">
                    {{ __('This will teleport the character to the default spawn point. Confirm with your master password.') }}
                </p>
                <form @submit.prevent="confirmResetPosition" class="space-y-5">
                    <div>
                        <InputLabel for="reset_pos_password" :value="__('Master Account Password')" class="text-rapanel-gold font-bold uppercase text-xs" />
                        <TextInput
                            id="reset_pos_password"
                            v-model="resetPosForm.password"
                            type="password"
                            class="mt-1 block w-full border-rapanel-gold/30 focus:ring-rapanel-gold focus:border-rapanel-gold bg-white dark:bg-rapanel-navy-900"
                            required autofocus
                        />
                        <InputError class="mt-2" :message="resetPosForm.errors.password" />
                        <InputError class="mt-2" :message="resetPosForm.errors.error" />
                    </div>
                    <div class="flex justify-end gap-3 mt-6">
                        <SecondaryButton @click="closeResetPosModal">{{ __('Cancel') }}</SecondaryButton>
                        <PrimaryButton :disabled="resetPosForm.processing" :class="{ 'opacity-25': resetPosForm.processing }">
                            {{ __('Confirm Reset') }}
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Modal: Reset Look -->
        <Modal :show="resetLookModal" @close="closeResetLookModal">
            <div class="p-6 bg-white dark:bg-rapanel-navy-800 transition-colors duration-300">
                <h2 class="text-lg font-bold text-rapanel-navy-900 dark:text-white mb-2 border-b border-rapanel-navy-100 dark:border-gray-700 pb-3 uppercase tracking-wider">
                    {{ __('Reset Look') }}
                </h2>
                <p class="text-sm text-rapanel-text-light/60 dark:text-gray-400 mb-1">
                    {{ __('Character') }}: <span class="font-bold text-rapanel-gold">{{ selectedCharLook?.name }}</span>
                </p>
                <p class="text-sm text-rapanel-text-light/60 dark:text-gray-400 mb-6 italic">
                    {{ __('This will reset the character\'s appearance (hair, color, outfit) to default values. Confirm with your master password.') }}
                </p>
                <form @submit.prevent="confirmResetLook" class="space-y-5">
                    <div>
                        <InputLabel for="reset_look_password" :value="__('Master Account Password')" class="text-rapanel-gold font-bold uppercase text-xs" />
                        <TextInput
                            id="reset_look_password"
                            v-model="resetLookForm.password"
                            type="password"
                            class="mt-1 block w-full border-rapanel-gold/30 focus:ring-rapanel-gold focus:border-rapanel-gold bg-white dark:bg-rapanel-navy-900"
                            required autofocus
                        />
                        <InputError class="mt-2" :message="resetLookForm.errors.password" />
                        <InputError class="mt-2" :message="resetLookForm.errors.error" />
                    </div>
                    <div class="flex justify-end gap-3 mt-6">
                        <SecondaryButton @click="closeResetLookModal">{{ __('Cancel') }}</SecondaryButton>
                        <PrimaryButton :disabled="resetLookForm.processing" :class="{ 'opacity-25': resetLookForm.processing }">
                            {{ __('Confirm Reset') }}
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

    </div>
</template>

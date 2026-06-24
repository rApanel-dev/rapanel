<script setup>
import { ref, watch, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import WhoSellShopModal from '@/Components/WhoSellShopModal.vue';
import { useItemDbModal } from '@/Composables/useItemDbModal.js';
import { useMobDbModal }  from '@/Composables/useMobDbModal.js';
import { useModalStack }  from '@/Composables/useModalStack.js';
import { useSafeHtml }    from '@/Composables/useSafeHtml.js';

const page = usePage();
const __   = (key) => page.props.translations?.[key] || key;

const { itemDbItem, itemDbCount, closeItemDb } = useItemDbModal();
const { openMobDb } = useMobDbModal();

const { acquire } = useModalStack();
const { sanitizeItemDesc } = useSafeHtml();
const safeItemDesc = computed(() => sanitizeItemDesc(itemDbItem.value?.description_html));
const modalZ = ref(60);

const activeTab = ref('detail');
watch(itemDbItem, (val, oldVal) => {
    if (val) {
        modalZ.value = acquire();
        const idChanged = val.item_id !== oldVal?.item_id;
        if (idChanged) {
            activeTab.value             = 'detail';
            illustrationAvailable.value = false;
            showIllustration.value      = false;
            monstersData.value          = null;
            tradeData.value             = null;
            loadMonsters();
            loadTrade();
        }
    }
});

const imageSrc   = (item) => `/data/items/collection/${item.item_id}.png`;
const onImgError = (e)    => { e.target.style.display = 'none'; };

// ── Card illustration ─────────────────────────────────────────────────
const illustrationAvailable = ref(false);
const showIllustration      = ref(false);
const cardIllustrationSrc   = (item) => `/data/items/cards/${item.item_id}.png`;
const onIllustrationLoad    = () => { illustrationAvailable.value = true; };
const onIllustrationError   = () => { illustrationAvailable.value = false; };

// ── Type badge ────────────────────────────────────────────────────────
const typeBadge = (t) => {
    const map = {
        Weapon:     'bg-rapanel-danger/10 text-rapanel-danger border-rapanel-danger/20',
        Armor:      'bg-rapanel-blue/10 text-rapanel-blue border-rapanel-blue/20',
        Consumable: 'bg-rapanel-success/10 text-rapanel-success border-rapanel-success/20',
        Card:       'bg-purple-500/10 text-purple-500 border-purple-500/20',
        Ammo:       'bg-amber-500/10 text-amber-500 border-amber-500/20',
        Healing:    'bg-rapanel-success/10 text-rapanel-success border-rapanel-success/20',
        Usable:     'bg-rapanel-success/10 text-rapanel-success border-rapanel-success/20',
    };
    return map[t] ?? 'bg-white/5 text-white/50 border-white/10';
};

// ── Stat labels ───────────────────────────────────────────────────────
const statLabels = {
    Weight: 'Weight', Attack: 'ATK', MagicAttack: 'MATK', Defense: 'DEF',
    Range: 'Range', WeaponLevel: 'Weapon Lv', ArmorLevel: 'Armor Lv',
    EquipLevelMin: 'Min Level', EquipLevelMax: 'Max Level',
    Refineable: 'Refineable', Gradable: 'Gradable',
};

const attrStats = (p) => {
    if (!p) return [];
    const skip = new Set(['Script','OnEquipScript','OnUnequipScript','EquipScript','UnEquipScript','Buy','Sell','View','Gender']);
    return Object.entries(p)
        .filter(([k, v]) => !skip.has(k) && typeof v !== 'object' && v !== null && v !== false)
        .map(([k, v]) => ({ label: statLabels[k] ?? k, value: k === 'Weight' ? `${v / 10}` : typeof v === 'boolean' ? '✓' : v }));
};

const scriptValue = (p) => p?.Script ?? p?.OnEquipScript ?? null;

// ── Jobs ──────────────────────────────────────────────────────────────
const allowedJobs = (p) => {
    if (!p?.Jobs || typeof p.Jobs !== 'object') return null;
    const jobs = Object.entries(p.Jobs).filter(([, v]) => v === true).map(([k]) => k);
    return jobs.length ? jobs : null;
};

const jobBadge = (job) => {
    const map = {
        Novice:'bg-gray-500/15 text-gray-300 border-gray-500/25', SuperNovice:'bg-gray-500/15 text-gray-300 border-gray-500/25',
        Swordman:'bg-rose-500/15 text-rose-400 border-rose-500/25', Knight:'bg-rose-500/15 text-rose-400 border-rose-500/25', Crusader:'bg-rose-500/15 text-rose-400 border-rose-500/25',
        Mage:'bg-blue-500/15 text-blue-400 border-blue-500/25', Wizard:'bg-blue-500/15 text-blue-400 border-blue-500/25', Sage:'bg-blue-500/15 text-blue-400 border-blue-500/25',
        Archer:'bg-emerald-500/15 text-emerald-400 border-emerald-500/25', Hunter:'bg-emerald-500/15 text-emerald-400 border-emerald-500/25', Bard:'bg-emerald-500/15 text-emerald-400 border-emerald-500/25', Dancer:'bg-emerald-500/15 text-emerald-400 border-emerald-500/25',
        Merchant:'bg-amber-500/15 text-amber-400 border-amber-500/25', Blacksmith:'bg-amber-500/15 text-amber-400 border-amber-500/25', Alchemist:'bg-amber-500/15 text-amber-400 border-amber-500/25',
        Thief:'bg-purple-500/15 text-purple-400 border-purple-500/25', Assassin:'bg-purple-500/15 text-purple-400 border-purple-500/25', Rogue:'bg-purple-500/15 text-purple-400 border-purple-500/25',
        Monk:'bg-sky-500/15 text-sky-400 border-sky-500/25', Priest:'bg-sky-500/15 text-sky-400 border-sky-500/25',
        TaeKwon:'bg-orange-500/15 text-orange-400 border-orange-500/25', StarGladiator:'bg-orange-500/15 text-orange-400 border-orange-500/25', SoulLinker:'bg-orange-500/15 text-orange-400 border-orange-500/25',
        Gunslinger:'bg-yellow-500/15 text-yellow-300 border-yellow-500/25', Rebellion:'bg-yellow-500/15 text-yellow-300 border-yellow-500/25',
        Ninja:'bg-cyan-500/15 text-cyan-400 border-cyan-500/25', Kagerou:'bg-cyan-500/15 text-cyan-400 border-cyan-500/25', Oboro:'bg-cyan-500/15 text-cyan-400 border-cyan-500/25',
        SpiritHandler:'bg-teal-500/15 text-teal-400 border-teal-500/25', Summoner:'bg-pink-500/15 text-pink-400 border-pink-500/25',
        All:'bg-rapanel-gold/15 text-rapanel-gold border-rapanel-gold/30',
    };
    return map[job] ?? 'bg-white/5 text-white/60 border-white/10';
};

// ── Locations ─────────────────────────────────────────────────────────
const locationLabel = { Head_Top:'Head Top', Head_Mid:'Head Mid', Head_Low:'Head Low', Right_Hand:'Right Hand', Left_Hand:'Left Hand', Both_Hand:'Both Hands', Both_Accessory:'Accessory', Armor:'Armor', Garment:'Garment', Shoes:'Shoes', Ammo:'Ammo', Costume_Head_Top:'Costume Top', Costume_Head_Mid:'Costume Mid', Costume_Head_Low:'Costume Low', Costume_Garment:'Costume Garment' };

const locationBadge = (loc) => {
    if (loc.startsWith('Costume_')) return 'bg-teal-500/10 text-teal-400 border-teal-500/20';
    const map = { Head_Top:'bg-sky-500/15 text-sky-400 border-sky-500/25', Head_Mid:'bg-sky-500/15 text-sky-400 border-sky-500/25', Head_Low:'bg-sky-500/15 text-sky-400 border-sky-500/25', Right_Hand:'bg-amber-500/15 text-amber-400 border-amber-500/25', Left_Hand:'bg-amber-500/15 text-amber-400 border-amber-500/25', Both_Hand:'bg-amber-500/15 text-amber-400 border-amber-500/25', Both_Accessory:'bg-rose-500/15 text-rose-400 border-rose-500/25', Armor:'bg-rapanel-blue/15 text-rapanel-blue border-rapanel-blue/25', Garment:'bg-purple-500/15 text-purple-400 border-purple-500/25', Shoes:'bg-emerald-500/15 text-emerald-400 border-emerald-500/25', Ammo:'bg-orange-500/15 text-orange-400 border-orange-500/25' };
    return map[loc] ?? 'bg-white/5 text-white/60 border-white/10';
};

const equipLocations = (p) => {
    if (!p?.Locations || typeof p.Locations !== 'object') return null;
    const locs = Object.entries(p.Locations).filter(([, v]) => v === true).map(([k]) => k);
    return locs.length ? locs : null;
};

// ── Classes (renewal) ─────────────────────────────────────────────────
const classLabel = { Normal:'Normal', Upper:'Trans', Baby:'Baby', Third:'3rd Class', Third_Upper:'Trans 3rd', Third_Baby:'Baby 3rd', Fourth:'4th Class' };

const classBadge = (cls) => {
    const map = { Normal:'bg-gray-500/15 text-gray-300 border-gray-500/25', Upper:'bg-rapanel-gold/15 text-rapanel-gold border-rapanel-gold/25', Baby:'bg-pink-500/15 text-pink-400 border-pink-500/25', Third:'bg-purple-500/15 text-purple-400 border-purple-500/25', Third_Upper:'bg-orange-500/15 text-orange-400 border-orange-500/25', Third_Baby:'bg-pink-500/10 text-pink-300 border-pink-500/20', Fourth:'bg-red-500/15 text-red-400 border-red-500/25' };
    return map[cls] ?? 'bg-white/5 text-white/60 border-white/10';
};

const classRestrictions = (p) => {
    if (!p?.Classes || typeof p.Classes !== 'object') return null;
    const cls = Object.entries(p.Classes).filter(([, v]) => v === true).map(([k]) => k);
    return cls.length ? cls : null;
};

// ── Misc ──────────────────────────────────────────────────────────────
const itemGender = (p) => { const g = p?.Gender; return (!g || g === 'Both') ? null : g; };

const tradeLabels = { NoDrop:'No Drop', NoTrade:'No Trade', NoSell:'No Sell', NoCart:'No Cart', NoStorage:'No Storage', NoGuildStorage:'No Guild Storage', NoMail:'No Mail', NoAuction:'No Auction' };
const tradeRestrictions = (p) => {
    if (!p?.Trade || typeof p.Trade !== 'object') return [];
    return Object.entries(p.Trade).filter(([, v]) => v === true).map(([k]) => tradeLabels[k] ?? k);
};

const hasBuyingStore = (p) => p?.Flags?.BuyingStore === true;

// ── Shop modal (Trade tab) ────────────────────────────────────────────
const shopModal = ref(null);

// ── Monsters tab ──────────────────────────────────────────────────────
const monstersData    = ref(null);
const monstersLoading = ref(false);
const monstersSort    = ref('desc');
const sortedMonsters  = computed(() => {
    if (!monstersData.value?.length) return monstersData.value ?? [];
    const val = (m) => m.adjusted_rate ?? m.rate / 100;
    return [...monstersData.value].sort((a, b) =>
        monstersSort.value === 'desc' ? val(b) - val(a) : val(a) - val(b)
    );
});

// ── Trade tab ─────────────────────────────────────────────────────────
const tradeData    = ref(null);
const tradeLoading = ref(false);
const tradeSort    = ref('asc');
const sortedTrade  = computed(() => {
    if (!tradeData.value?.length) return tradeData.value ?? [];
    return [...tradeData.value].sort((a, b) =>
        tradeSort.value === 'asc' ? a.price - b.price : b.price - a.price
    );
});

const loadMonsters = async () => {
    if (monstersData.value !== null || monstersLoading.value) return;
    monstersLoading.value = true;
    try {
        const res          = await fetch(route('info.item-db.monsters', itemDbItem.value.item_id));
        monstersData.value = await res.json();
    } catch { monstersData.value = []; }
    finally  { monstersLoading.value = false; }
};

const loadTrade = async () => {
    if (tradeData.value !== null || tradeLoading.value) return;
    tradeLoading.value = true;
    try {
        const res       = await fetch(route('info.item-db.trade', itemDbItem.value.item_id));
        tradeData.value = await res.json();
    } catch { tradeData.value = []; }
    finally  { tradeLoading.value = false; }
};

const switchToMonsters = () => { activeTab.value = 'monsters'; loadMonsters(); };
const switchToTrade    = () => { activeTab.value = 'trade';    loadTrade(); };

// ── Drop rate ─────────────────────────────────────────────────────────
const fmtRate = (r) => {
    if (!r) return '?';
    if (r >= 10000) return '100%';
    const p = r / 100;
    return (Number.isInteger(p) ? p : parseFloat(p.toFixed(2))) + '%';
};

const fmtAdjusted = (r) => {
    if (r === null || r === undefined) return null;
    return parseFloat(r.toFixed(2)) + '%';
};

// ── Price tiers (Trade tab) ───────────────────────────────────────────
const PRICE_TIERS = [
    { min: 1,            max: 99,          color: '#000080', shadow: '#FF00FF' },
    { min: 100,          max: 999,         color: '#0000FF', shadow: '#00FFFF' },
    { min: 1_000,        max: 9_999,       color: '#FF0000', shadow: '#FFFF00' },
    { min: 10_000,       max: 99_999,      color: '#FF00FF', shadow: null      },
    { min: 100_000,      max: 999_999,     color: '#0000FF', shadow: null      },
    { min: 1_000_000,    max: 9_999_999,   color: '#000000', shadow: '#00FF00' },
    { min: 10_000_000,   max: 99_999_999,  color: '#FF0000', shadow: null      },
    { min: 100_000_000,  max: 999_999_999, color: '#000000', shadow: '#BDB76B' },
    { min: 1_000_000_000,max: Infinity,    color: '#FF0000', shadow: '#FF00FF' },
];
const priceStyle = (n) => {
    const tier = PRICE_TIERS.find(t => n >= t.min && n <= t.max);
    if (!tier) return {};
    return { color: tier.color, textShadow: tier.shadow ? `1px 0 0 ${tier.shadow}` : undefined };
};

// ── Mob bar / badge helpers (monsters list) ──────────────────────────
const mobBarClass = (mob) => {
    if (mob.is_mvp)           return 'bg-rapanel-gold';
    if (mob.class === 'Boss') return 'bg-orange-500';
    return 'bg-rapanel-navy-300 dark:bg-rapanel-navy-600';
};

// ── Element badge (for monsters list) ────────────────────────────────
const elementBadge = (el) => {
    const map = {
        Neutral: 'bg-white/5 text-gray-400 border-white/10',
        Water:   'bg-blue-400/10 text-blue-400 border-blue-400/20',
        Earth:   'bg-amber-700/10 text-amber-500 border-amber-700/20',
        Fire:    'bg-red-500/10 text-red-400 border-red-500/20',
        Wind:    'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
        Poison:  'bg-purple-500/10 text-purple-400 border-purple-500/20',
        Holy:    'bg-yellow-400/10 text-yellow-300 border-yellow-400/20',
        Dark:    'bg-violet-900/20 text-violet-400 border-violet-900/30',
        Ghost:   'bg-slate-400/10 text-slate-400 border-slate-400/20',
        Undead:  'bg-zinc-700/20 text-zinc-400 border-zinc-700/30',
    };
    return map[el] ?? 'bg-white/5 text-white/50 border-white/10';
};
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95">

            <div v-if="itemDbItem"
                class="fixed inset-0 flex items-end sm:items-center justify-center sm:p-4 bg-rapanel-navy-900/80 backdrop-blur-sm"
                :style="{ zIndex: modalZ }"
                @click.self="closeItemDb()">

                <div class="relative w-full sm:max-w-4xl max-h-[95vh] sm:max-h-[90vh] flex flex-col overflow-hidden
                            rounded-t-2xl sm:rounded-2xl shadow-2xl bg-white dark:bg-rapanel-navy-900 ring-1 ring-rapanel-navy-200 dark:ring-white/10">

                    <!-- Gold accent line -->
                    <div class="h-[3px] shrink-0 bg-gradient-to-r from-rapanel-gold via-rapanel-gold/50 to-transparent" />

                    <!-- ── Header ── -->
                    <div class="flex items-center gap-4 px-5 py-4 shrink-0 bg-white dark:bg-rapanel-navy-900">
                        <img :src="imageSrc(itemDbItem)"
                             :alt="itemDbItem.display_name || itemDbItem.name"
                             class="shrink-0 w-[75px] h-[100px] object-contain rounded-xl"
                             @error="onImgError" />

                        <!-- preload silencioso para detectar si existe la ilustración -->
                        <img v-if="itemDbItem.type === 'Card'"
                             :src="cardIllustrationSrc(itemDbItem)"
                             class="hidden"
                             @load="onIllustrationLoad"
                             @error="onIllustrationError" />

                        <div class="flex-1 min-w-0">
                            <h2 class="font-bold text-rapanel-navy-900 dark:text-white text-xl leading-tight">
                                {{ itemDbItem.display_name || itemDbItem.name }}<template v-if="itemDbItem.slots > 0"> [{{ itemDbItem.slots }}]</template>
                            </h2>
                            <p v-if="itemDbItem.aegis_name" class="font-mono text-[11px] text-rapanel-text-light/50 dark:text-white/35 mt-0.5">
                                {{ itemDbItem.aegis_name }}
                            </p>
                            <div class="flex items-center gap-2 mt-2 flex-wrap">
                                <span class="text-[11px] font-bold px-2.5 py-0.5 rounded-full bg-rapanel-navy-100 text-rapanel-text-light dark:bg-white/10 dark:text-white/70">
                                    ID: {{ itemDbItem.item_id }}
                                </span>
                                <span v-if="itemDbItem.type" :class="['text-[10px] font-black uppercase px-2 py-0.5 rounded-full border tracking-wide', typeBadge(itemDbItem.type)]">
                                    {{ itemDbItem.type }}
                                </span>
                                <button v-if="illustrationAvailable"
                                    @click="showIllustration = true"
                                    class="inline-flex items-center gap-1 text-[10px] font-black uppercase px-2 py-0.5 rounded-full border tracking-wide transition
                                           bg-rapanel-purple/10 text-rapanel-purple border-rapanel-purple/20 hover:bg-rapanel-purple/20">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3 21h18M3.75 3h16.5M4.5 3v18M19.5 3v18"/>
                                    </svg>
                                    {{ __('Illustration') }}
                                </button>
                            </div>
                        </div>

                        <button @click="closeItemDb()"
                            class="shrink-0 w-8 h-8 flex items-center justify-center rounded-lg cursor-pointer
                                   text-rapanel-text-light/40 dark:text-white/40 hover:text-rapanel-navy-900 dark:hover:text-white hover:bg-rapanel-navy-100 dark:hover:bg-white/10 transition duration-150">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- ── Tab bar ── -->
                    <div class="flex shrink-0 bg-white dark:bg-rapanel-navy-900 border-b border-rapanel-navy-100 dark:border-white/10">

                        <button @click="activeTab = 'detail'"
                            :class="activeTab === 'detail' ? 'bg-rapanel-gold text-rapanel-navy-900' : 'text-rapanel-text-light/60 dark:text-white/50 hover:text-rapanel-navy-900 dark:hover:text-white hover:bg-rapanel-navy-50 dark:hover:bg-white/5'"
                            class="flex-1 flex items-start gap-2.5 px-3 py-2.5 rounded-t-xl text-left transition-all duration-150 min-w-0">
                            <svg class="w-4 h-4 mt-0.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <div class="min-w-0">
                                <div class="text-sm font-bold leading-tight truncate">{{ __('Item Detail') }}</div>
                                <div :class="activeTab === 'detail' ? 'text-rapanel-navy-900/60' : 'text-rapanel-text-light/40 dark:text-white/30'"
                                     class="text-[10px] mt-0.5 leading-tight truncate">{{ __('Stats, NPC prices and requirements') }}</div>
                            </div>
                        </button>

                        <button @click="switchToMonsters()"
                            :class="activeTab === 'monsters' ? 'bg-rapanel-gold text-rapanel-navy-900' : 'text-rapanel-text-light/60 dark:text-white/50 hover:text-rapanel-navy-900 dark:hover:text-white hover:bg-rapanel-navy-50 dark:hover:bg-white/5'"
                            class="flex-1 flex items-start gap-2.5 px-3 py-2.5 rounded-t-xl text-left transition-all duration-150 min-w-0">
                            <svg class="w-4 h-4 mt-0.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.121 14.121L19 19m-7-7l7-7m-7 7l-2.879 2.879M12 12L9.121 9.121m0 5.758a3 3 0 10-4.243-4.243 3 3 0 004.243 4.243zm0 0L12 17.743" />
                            </svg>
                            <div class="min-w-0">
                                <div class="flex items-center gap-1.5 text-sm font-bold leading-tight">
                                    <span class="truncate">{{ __('Monsters') }}</span>
                                    <span v-if="monstersData !== null"
                                        :class="activeTab === 'monsters' ? 'bg-rapanel-navy-900/30 text-rapanel-navy-900' : 'bg-rapanel-navy-100 text-rapanel-text-light dark:bg-white/10 dark:text-white/50'"
                                        class="text-[10px] rounded-full min-w-[16px] h-4 px-1 flex items-center justify-center shrink-0 font-bold">
                                        {{ monstersData.length }}
                                    </span>
                                    <svg v-else-if="monstersLoading" class="w-3 h-3 animate-spin shrink-0" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                                    </svg>
                                </div>
                                <div :class="activeTab === 'monsters' ? 'text-rapanel-navy-900/60' : 'text-rapanel-text-light/30 dark:text-white/20'"
                                     class="text-[10px] mt-0.5 leading-tight truncate">{{ __('Where this item drops from') }}</div>
                            </div>
                        </button>

                        <button @click="switchToTrade()"
                            :class="activeTab === 'trade' ? 'bg-rapanel-gold text-rapanel-navy-900' : 'text-rapanel-text-light/60 dark:text-white/50 hover:text-rapanel-navy-900 dark:hover:text-white hover:bg-rapanel-navy-50 dark:hover:bg-white/5'"
                            class="flex-1 flex items-start gap-2.5 px-3 py-2.5 rounded-t-xl text-left transition-all duration-150 min-w-0">
                            <svg class="w-4 h-4 mt-0.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            <div class="min-w-0">
                                <div class="flex items-center gap-1.5 text-sm font-bold leading-tight">
                                    <span class="truncate">{{ __('Trade') }}</span>
                                    <span v-if="tradeData !== null"
                                        :class="activeTab === 'trade' ? 'bg-rapanel-navy-900/30 text-rapanel-navy-900' : 'bg-rapanel-navy-100 text-rapanel-text-light dark:bg-white/10 dark:text-white/50'"
                                        class="text-[10px] rounded-full min-w-[16px] h-4 px-1 flex items-center justify-center shrink-0 font-bold">
                                        {{ tradeData.length }}
                                    </span>
                                    <svg v-else-if="tradeLoading" class="w-3 h-3 animate-spin shrink-0" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                                    </svg>
                                </div>
                                <div :class="activeTab === 'trade' ? 'text-rapanel-navy-900/60' : 'text-rapanel-text-light/30 dark:text-white/20'"
                                     class="text-[10px] mt-0.5 leading-tight truncate">{{ __('Active player shops') }}</div>
                            </div>
                        </button>

                        <div class="flex-1 flex items-start gap-2.5 px-3 py-2.5 rounded-t-xl text-left min-w-0 text-rapanel-text-light/30 dark:text-white/25 cursor-default">
                            <svg class="w-4 h-4 mt-0.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />
                            </svg>
                            <div class="min-w-0">
                                <div class="text-sm font-bold leading-tight truncate">{{ __('Price History') }}</div>
                                <div class="text-[10px] text-rapanel-text-light/25 dark:text-white/20 mt-0.5 leading-tight truncate">{{ __('Recent price trends') }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- ── Body ── -->
                    <div class="overflow-y-auto flex-1 p-5 bg-rapanel-navy-50 dark:bg-rapanel-navy-800/60">

                        <div v-if="activeTab === 'detail'" class="grid grid-cols-1 md:grid-cols-5 gap-4">

                            <!-- Columna izquierda -->
                            <div class="md:col-span-3 space-y-4">

                                <!-- Descripción -->
                                <div class="bg-white dark:bg-rapanel-navy-900 rounded-xl border border-rapanel-navy-100 dark:border-white/10 overflow-hidden">
                                    <div class="flex items-center gap-2 px-4 py-3 border-b border-rapanel-navy-100 dark:border-white/10">
                                        <svg class="w-4 h-4 text-rapanel-gold shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        <span class="font-semibold text-sm text-rapanel-navy-900 dark:text-white">{{ __('Game Description') }}</span>
                                    </div>
                                    <div class="px-4 py-4">
                                        <div v-if="itemDbItem.description_html"
                                             class="font-mono text-sm leading-relaxed text-rapanel-navy-900/90 dark:text-white/85"
                                             v-html="safeItemDesc" />
                                        <p v-else class="text-sm text-rapanel-text-light/50 dark:text-white/40 italic">{{ __('No description available.') }}</p>
                                    </div>
                                </div>

                                <!-- Clases Permitidas -->
                                <div v-if="allowedJobs(itemDbItem.properties) || classRestrictions(itemDbItem.properties)"
                                     class="bg-white dark:bg-rapanel-navy-900 rounded-xl border border-rapanel-navy-100 dark:border-white/10 overflow-hidden">
                                    <div class="flex items-center gap-2 px-4 py-3 border-b border-rapanel-navy-100 dark:border-white/10">
                                        <svg class="w-4 h-4 text-rapanel-gold shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        <span class="font-semibold text-sm text-rapanel-navy-900 dark:text-white">{{ __('Allowed Classes') }}</span>
                                    </div>
                                    <div v-if="allowedJobs(itemDbItem.properties)" class="px-4 pt-3 pb-2 flex flex-wrap gap-1.5">
                                        <span v-for="job in allowedJobs(itemDbItem.properties)" :key="job"
                                              :class="['text-[10px] font-bold px-2 py-0.5 rounded-full border', jobBadge(job)]">
                                            {{ job }}
                                        </span>
                                    </div>
                                    <div v-if="classRestrictions(itemDbItem.properties)"
                                         :class="allowedJobs(itemDbItem.properties) ? 'border-t border-rapanel-navy-100 dark:border-white/[0.06]' : ''"
                                         class="px-4 pt-2 pb-3">
                                        <p class="text-[9px] font-black uppercase tracking-widest text-rapanel-text-light/40 dark:text-white/30 mb-1.5">{{ __('Class Tier') }}</p>
                                        <div class="flex flex-wrap gap-1.5">
                                            <span v-for="cls in classRestrictions(itemDbItem.properties)" :key="cls"
                                                  :class="['text-[10px] font-bold px-2 py-0.5 rounded-full border', classBadge(cls)]">
                                                {{ classLabel[cls] ?? cls.replace(/_/g, ' ') }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Script -->
                                <div v-if="scriptValue(itemDbItem.properties)"
                                     class="bg-white dark:bg-rapanel-navy-900 rounded-xl border border-rapanel-navy-100 dark:border-white/10 overflow-hidden">
                                    <div class="flex items-center gap-2 px-4 py-3 border-b border-rapanel-navy-100 dark:border-white/10">
                                        <svg class="w-4 h-4 text-rapanel-gold shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                                        </svg>
                                        <span class="font-semibold text-sm text-rapanel-navy-900 dark:text-white">{{ __('Script') }}</span>
                                    </div>
                                    <pre class="px-4 py-4 text-xs font-mono text-emerald-400/80 overflow-x-auto whitespace-pre-wrap leading-relaxed">{{ scriptValue(itemDbItem.properties) }}</pre>
                                </div>
                            </div>

                            <!-- Columna derecha -->
                            <div class="md:col-span-2 space-y-3">

                                <!-- Atributos -->
                                <div v-if="attrStats(itemDbItem.properties).length"
                                     class="bg-white dark:bg-rapanel-navy-900 rounded-xl border border-rapanel-navy-100 dark:border-white/10 overflow-hidden">
                                    <div class="flex items-center gap-2 px-4 py-3 border-b border-rapanel-navy-100 dark:border-white/10">
                                        <svg class="w-4 h-4 text-rapanel-gold shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                        </svg>
                                        <span class="font-semibold text-sm text-rapanel-navy-900 dark:text-white">{{ __('Attributes') }}</span>
                                    </div>
                                    <div class="divide-y divide-rapanel-navy-100 dark:divide-white/[0.06]">
                                        <div v-for="stat in attrStats(itemDbItem.properties)" :key="stat.label"
                                             class="flex justify-between items-center px-4 py-2.5">
                                            <span class="text-[11px] font-black uppercase tracking-wider text-rapanel-text-light/60 dark:text-white/40">{{ __(stat.label) }}</span>
                                            <span class="text-sm font-bold text-rapanel-navy-900 dark:text-white tabular-nums">{{ stat.value }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Precios NPC -->
                                <div v-if="itemDbItem.properties?.Buy !== undefined || itemDbItem.properties?.Sell !== undefined"
                                     class="bg-white dark:bg-rapanel-navy-900 rounded-xl border border-rapanel-navy-100 dark:border-white/10 overflow-hidden">
                                    <div class="flex items-center gap-2 px-4 py-3 border-b border-rapanel-navy-100 dark:border-white/10">
                                        <svg class="w-4 h-4 text-rapanel-gold shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                                        </svg>
                                        <span class="font-semibold text-sm text-rapanel-navy-900 dark:text-white">{{ __('NPC Prices') }}</span>
                                    </div>
                                    <div class="divide-y divide-rapanel-navy-100 dark:divide-white/[0.06]">
                                        <div class="flex justify-between items-center px-4 py-2.5">
                                            <span class="text-[11px] font-black uppercase tracking-wider text-rapanel-text-light/60 dark:text-white/40">{{ __('Buy') }}</span>
                                            <span class="text-sm font-bold text-rapanel-navy-900 dark:text-white tabular-nums">{{ (itemDbItem.properties.Buy ?? 0).toLocaleString() }}z</span>
                                        </div>
                                        <div class="flex justify-between items-center px-4 py-2.5">
                                            <span class="text-[11px] font-black uppercase tracking-wider text-rapanel-text-light/60 dark:text-white/40">{{ __('Sell') }}</span>
                                            <span class="text-sm font-bold text-rapanel-gold tabular-nums">{{ (itemDbItem.properties.Sell ?? 0).toLocaleString() }}z</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Información -->
                                <div class="bg-white dark:bg-rapanel-navy-900 rounded-xl border border-rapanel-navy-100 dark:border-white/10 overflow-hidden">
                                    <div class="flex items-center gap-2 px-4 py-3 border-b border-rapanel-navy-100 dark:border-white/10">
                                        <svg class="w-4 h-4 text-rapanel-gold shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span class="font-semibold text-sm text-rapanel-navy-900 dark:text-white">{{ __('Information') }}</span>
                                    </div>
                                    <div class="divide-y divide-rapanel-navy-100 dark:divide-white/[0.06]">
                                        <div v-if="itemDbItem.type" class="flex justify-between items-center px-4 py-2.5">
                                            <span class="text-[11px] font-black uppercase tracking-wider text-rapanel-text-light/60 dark:text-white/40">{{ __('Type') }}</span>
                                            <span class="text-sm font-bold text-rapanel-navy-900 dark:text-white">{{ itemDbItem.type }}</span>
                                        </div>
                                        <div v-if="itemDbItem.subtype" class="flex justify-between items-center px-4 py-2.5">
                                            <span class="text-[11px] font-black uppercase tracking-wider text-rapanel-text-light/60 dark:text-white/40">{{ __('Subtype') }}</span>
                                            <span class="text-sm font-bold text-rapanel-navy-900 dark:text-white">{{ itemDbItem.subtype }}</span>
                                        </div>
                                        <div v-if="itemDbItem.slots > 0" class="flex justify-between items-center px-4 py-2.5">
                                            <span class="text-[11px] font-black uppercase tracking-wider text-rapanel-text-light/60 dark:text-white/40">{{ __('Slots') }}</span>
                                            <span class="text-sm font-bold text-rapanel-navy-900 dark:text-white">{{ itemDbItem.slots }}</span>
                                        </div>
                                        <div v-if="equipLocations(itemDbItem.properties)" class="flex justify-between items-start gap-2 px-4 py-2.5">
                                            <span class="text-[11px] font-black uppercase tracking-wider text-white/40 shrink-0 mt-0.5">{{ __('Equip Location') }}</span>
                                            <div class="flex flex-wrap gap-1 justify-end">
                                                <span v-for="loc in equipLocations(itemDbItem.properties)" :key="loc"
                                                      :class="['text-[10px] font-bold px-2 py-0.5 rounded-full border', locationBadge(loc)]">
                                                    {{ locationLabel[loc] ?? loc.replace(/_/g, ' ') }}
                                                </span>
                                            </div>
                                        </div>
                                        <div v-if="itemDbItem.is_custom" class="flex justify-between items-center px-4 py-2.5">
                                            <span class="text-[11px] font-black uppercase tracking-wider text-rapanel-text-light/60 dark:text-white/40">{{ __('Custom') }}</span>
                                            <span class="text-sm font-bold text-rapanel-gold">✓</span>
                                        </div>
                                        <div v-if="itemGender(itemDbItem.properties)" class="flex justify-between items-center px-4 py-2.5">
                                            <span class="text-[11px] font-black uppercase tracking-wider text-rapanel-text-light/60 dark:text-white/40">{{ __('Gender') }}</span>
                                            <span :class="['text-xs font-bold px-2 py-0.5 rounded-full border', itemDbItem.properties.Gender === 'Male' ? 'bg-rapanel-blue/15 text-rapanel-blue border-rapanel-blue/25' : 'bg-pink-500/15 text-pink-400 border-pink-500/25']">
                                                {{ __(itemDbItem.properties.Gender) }}
                                            </span>
                                        </div>
                                        <div v-if="hasBuyingStore(itemDbItem.properties)" class="flex justify-between items-center px-4 py-2.5">
                                            <span class="text-[11px] font-black uppercase tracking-wider text-rapanel-text-light/60 dark:text-white/40">{{ __('Buying Store') }}</span>
                                            <span class="text-xs font-bold px-2 py-0.5 rounded-full border bg-rapanel-success/15 text-rapanel-success border-rapanel-success/25">✓</span>
                                        </div>
                                        <div class="flex justify-between items-center px-4 py-2.5">
                                            <span class="text-[11px] font-black uppercase tracking-wider text-rapanel-text-light/60 dark:text-white/40">{{ __('On Server') }}</span>
                                            <span v-if="itemDbCount !== null" class="text-sm font-bold text-rapanel-navy-900 dark:text-white tabular-nums">{{ itemDbCount.toLocaleString() }}</span>
                                            <span v-else class="w-16 h-4 rounded bg-rapanel-navy-100 dark:bg-white/10 animate-pulse inline-block" />
                                        </div>
                                    </div>
                                </div>

                                <!-- Restricciones de Comercio -->
                                <div v-if="tradeRestrictions(itemDbItem.properties).length"
                                     class="bg-white dark:bg-rapanel-navy-900 rounded-xl border border-rapanel-navy-100 dark:border-white/10 overflow-hidden">
                                    <div class="flex items-center gap-2 px-4 py-3 border-b border-rapanel-navy-100 dark:border-white/10">
                                        <svg class="w-4 h-4 text-rapanel-danger shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                        </svg>
                                        <span class="font-semibold text-sm text-rapanel-navy-900 dark:text-white">{{ __('Trade Restrictions') }}</span>
                                    </div>
                                    <div class="px-4 py-3 flex flex-wrap gap-1.5">
                                        <span v-for="r in tradeRestrictions(itemDbItem.properties)" :key="r"
                                              class="text-[10px] font-bold px-2 py-0.5 rounded-full border bg-rapanel-danger/10 text-rapanel-danger border-rapanel-danger/20">
                                            {{ r }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ── Monsters tab ── -->
                        <div v-else-if="activeTab === 'monsters'">
                            <div v-if="monstersLoading" class="flex items-center justify-center py-12">
                                <svg class="animate-spin w-6 h-6 text-rapanel-gold" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                                </svg>
                            </div>
                            <template v-else-if="monstersData !== null">
                                <div v-if="!monstersData.length" class="flex flex-col items-center justify-center py-16 text-center gap-3">
                                    <svg class="w-10 h-10 text-rapanel-navy-200 dark:text-white/10" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.121 14.121L19 19m-7-7l7-7m-7 7l-2.879 2.879M12 12L9.121 9.121m0 5.758a3 3 0 10-4.243-4.243 3 3 0 004.243 4.243zm0 0L12 17.743" />
                                    </svg>
                                    <p class="text-rapanel-text-light/40 dark:text-white/30 text-sm">{{ __('No monsters drop this item.') }}</p>
                                </div>
                                <template v-else>
                                    <!-- Sort toggle -->
                                    <div class="flex items-center justify-end mb-3">
                                        <div class="inline-flex items-center gap-0.5 bg-rapanel-navy-100/60 dark:bg-white/5 rounded-lg p-0.5 border border-rapanel-navy-100 dark:border-white/[0.08]">
                                            <button @click="monstersSort = 'desc'"
                                                :class="monstersSort === 'desc'
                                                    ? 'bg-white dark:bg-rapanel-navy-700 text-rapanel-navy-900 dark:text-white shadow-sm'
                                                    : 'text-rapanel-text-light/50 dark:text-white/35 hover:text-rapanel-navy-900 dark:hover:text-white/70'"
                                                class="flex items-center gap-1 px-2.5 py-1 rounded-md text-[10px] font-semibold transition-all duration-150">
                                                <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 4.5h14.25M3 9h9.75M3 13.5h5.25m5.25-.75L17.25 9m0 0L21 12.75M17.25 9v12"/>
                                                </svg>
                                                {{ __('Drop Rate') }}
                                            </button>
                                            <button @click="monstersSort = 'asc'"
                                                :class="monstersSort === 'asc'
                                                    ? 'bg-white dark:bg-rapanel-navy-700 text-rapanel-navy-900 dark:text-white shadow-sm'
                                                    : 'text-rapanel-text-light/50 dark:text-white/35 hover:text-rapanel-navy-900 dark:hover:text-white/70'"
                                                class="flex items-center gap-1 px-2.5 py-1 rounded-md text-[10px] font-semibold transition-all duration-150">
                                                <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 4.5h14.25M3 9h9.75M3 13.5h9.75m4.5-4.5v12m0 0l-3.75-3.75M17.25 21l3.75-3.75"/>
                                                </svg>
                                                {{ __('Drop Rate') }}
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Grid 3 columnas -->
                                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                                        <button v-for="mob in sortedMonsters" :key="mob.id"
                                            @click="openMobDb(mob)"
                                            class="bg-white dark:bg-rapanel-navy-900 rounded-xl border border-rapanel-navy-100 dark:border-white/10 flex items-center gap-2.5 px-3 py-2.5 text-left w-full transition hover:border-rapanel-blue/40 dark:hover:border-rapanel-blue/40 hover:shadow-sm">
                                            <!-- Barra de acento vertical -->
                                            <div :class="mobBarClass(mob)" class="w-1 self-stretch rounded-full shrink-0" />
                                            <!-- GIF del mob -->
                                            <div class="shrink-0 w-10 h-10 flex items-center justify-center overflow-hidden">
                                                <img :src="`/data/monsters/${mob.id}.gif`"
                                                     :alt="mob.name"
                                                     class="max-w-full max-h-full object-contain"
                                                     @error="$event.target.style.display = 'none'" />
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center gap-1.5 flex-wrap">
                                                    <span class="text-xs font-bold text-rapanel-navy-900 dark:text-white leading-tight truncate">{{ mob.name }}</span>
                                                    <!-- Badge MVP (el mob es MVP) -->
                                                    <span v-if="mob.is_mvp"
                                                        class="text-[9px] font-black uppercase px-1 py-0.5 rounded-full border bg-rapanel-gold/15 text-rapanel-gold border-rapanel-gold/30 shrink-0">
                                                        MVP
                                                    </span>
                                                    <!-- Badge Boss -->
                                                    <span v-else-if="mob.class === 'Boss'"
                                                        class="text-[9px] font-black uppercase px-1 py-0.5 rounded-full border bg-orange-500/15 text-orange-400 border-orange-500/30 shrink-0">
                                                        Boss
                                                    </span>
                                                    <!-- Indicador: item cae en tabla MVP drops -->
                                                    <span v-if="mob.is_mvp_drop"
                                                        class="text-[8px] font-black uppercase px-1 py-px rounded border bg-amber-500/10 text-amber-500 border-amber-500/20 shrink-0">
                                                        ★ MVP Drop
                                                    </span>
                                                </div>
                                                <div class="flex items-center gap-1.5 mt-0.5 text-[10px] text-rapanel-text-light/60 dark:text-white/40 flex-wrap">
                                                    <span class="font-mono">Lv.{{ mob.level }}</span>
                                                    <span :class="['px-1 py-0 rounded border text-[9px] font-bold leading-4', elementBadge(mob.element)]">{{ mob.element }}</span>
                                                </div>
                                            </div>
                                            <div class="text-right shrink-0">
                                                <div :class="['text-sm font-bold tabular-nums', (mob.adjusted_rate ?? mob.rate / 100) >= 50 ? 'text-rapanel-success' : (mob.adjusted_rate ?? mob.rate / 100) >= 5 ? 'text-rapanel-gold' : 'text-rapanel-text-light dark:text-white/60']">
                                                    {{ fmtAdjusted(mob.adjusted_rate) ?? fmtRate(mob.rate) }}
                                                </div>
                                                <div v-if="mob.adjusted_rate !== null" class="text-[10px] text-rapanel-text-light/40 dark:text-white/30 tabular-nums">
                                                    {{ fmtRate(mob.rate) }}
                                                </div>
                                            </div>
                                        </button>
                                    </div>
                                </template>
                            </template>
                        </div>

                        <!-- ── Trade tab ── -->
                        <div v-else-if="activeTab === 'trade'">
                            <div v-if="tradeLoading" class="flex items-center justify-center py-12">
                                <svg class="animate-spin w-6 h-6 text-rapanel-gold" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                                </svg>
                            </div>
                            <template v-else-if="tradeData !== null">
                                <div v-if="!tradeData.length" class="flex flex-col items-center justify-center py-16 text-center gap-3">
                                    <svg class="w-10 h-10 text-rapanel-navy-200 dark:text-white/10" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                    <p class="text-rapanel-text-light/40 dark:text-white/30 text-sm">{{ __('No active shops selling this item.') }}</p>
                                </div>
                                <template v-else>
                                    <!-- Sort toggle -->
                                    <div class="flex items-center justify-end mb-3">
                                        <div class="inline-flex items-center gap-0.5 bg-rapanel-navy-100/60 dark:bg-white/5 rounded-lg p-0.5 border border-rapanel-navy-100 dark:border-white/[0.08]">
                                            <button @click="tradeSort = 'asc'"
                                                :class="tradeSort === 'asc'
                                                    ? 'bg-white dark:bg-rapanel-navy-700 text-rapanel-navy-900 dark:text-white shadow-sm'
                                                    : 'text-rapanel-text-light/50 dark:text-white/35 hover:text-rapanel-navy-900 dark:hover:text-white/70'"
                                                class="flex items-center gap-1 px-2.5 py-1 rounded-md text-[10px] font-semibold transition-all duration-150">
                                                <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 4.5h14.25M3 9h9.75M3 13.5h5.25m5.25-.75L17.25 9m0 0L21 12.75M17.25 9v12"/>
                                                </svg>
                                                {{ __('Price') }}
                                            </button>
                                            <button @click="tradeSort = 'desc'"
                                                :class="tradeSort === 'desc'
                                                    ? 'bg-white dark:bg-rapanel-navy-700 text-rapanel-navy-900 dark:text-white shadow-sm'
                                                    : 'text-rapanel-text-light/50 dark:text-white/35 hover:text-rapanel-navy-900 dark:hover:text-white/70'"
                                                class="flex items-center gap-1 px-2.5 py-1 rounded-md text-[10px] font-semibold transition-all duration-150">
                                                <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 4.5h14.25M3 9h9.75M3 13.5h9.75m4.5-4.5v12m0 0l-3.75-3.75M17.25 21l3.75-3.75"/>
                                                </svg>
                                                {{ __('Price') }}
                                            </button>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                    <div v-for="shop in sortedTrade" :key="`${shop.vending_id}-${shop.price}-${shop.amount}`"
                                        class="bg-white dark:bg-rapanel-navy-900 rounded-xl border border-rapanel-navy-100 dark:border-white/10 px-4 py-3">
                                        <div class="flex items-center justify-between gap-3">
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center gap-2 flex-wrap">
                                                    <span class="text-sm font-semibold text-rapanel-navy-900 dark:text-white truncate">{{ shop.shop_title }}</span>
                                                    <span v-if="shop.refine > 0"
                                                        class="text-[9px] font-black px-1.5 py-0.5 rounded-full border bg-rapanel-blue/10 text-rapanel-blue border-rapanel-blue/20 shrink-0">
                                                        +{{ shop.refine }}
                                                    </span>
                                                </div>
                                                <div class="flex items-center gap-1.5 mt-1 text-[10px] text-rapanel-text-light/60 dark:text-white/40 flex-wrap">
                                                    <span>{{ shop.char_name }}</span>
                                                    <span>·</span>
                                                    <span class="font-mono">{{ shop.map }} ({{ shop.x }},{{ shop.y }})</span>
                                                </div>
                                            </div>
                                            <div class="shrink-0 flex items-center gap-3">
                                                <div class="text-right">
                                                    <div class="text-sm font-bold tabular-nums" :style="priceStyle(shop.price)">{{ Number(shop.price).toLocaleString() }}z</div>
                                                    <div class="text-[10px] text-rapanel-text-light/60 dark:text-white/40">×{{ shop.amount }}</div>
                                                </div>
                                                <button @click="shopModal?.open(shop.vending_id)"
                                                    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-md text-[11px] font-semibold border transition-all duration-150 select-none cursor-pointer
                                                           bg-rapanel-blue/8 text-rapanel-blue border-rapanel-blue/25 hover:bg-rapanel-blue/15 hover:border-rapanel-blue/40
                                                           dark:bg-rapanel-gold/8 dark:text-rapanel-gold dark:border-rapanel-gold/25 dark:hover:bg-rapanel-gold/15 dark:hover:border-rapanel-gold/40">
                                                    <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.65V9.35m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 002.25 1.016c.896 0 1.7-.393 2.25-1.016a3.001 3.001 0 003.75.614m-16.5 0a3.004 3.004 0 01-.621-4.72L4.318 3.44A1.5 1.5 0 015.378 3h13.243a1.5 1.5 0 011.06.44l1.19 2.189a3.004 3.004 0 01-.621 4.72m-13.5 8.65h3.75a.75.75 0 00.75-.75V13.5a.75.75 0 00-.75-.75H6.75a.75.75 0 00-.75.75v3.75c0 .415.336.75.75.75z"/>
                                                    </svg>
                                                    {{ __('View Shop') }}
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    </div>
                                </template>
                            </template>
                        </div>

                        <!-- ── Price History (coming soon) ── -->
                        <div v-else class="flex flex-col items-center justify-center py-16 text-center gap-3">
                            <svg class="w-10 h-10 text-rapanel-navy-200 dark:text-white/10" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <p class="text-rapanel-text-light/40 dark:text-white/30 text-sm">{{ __('Coming soon') }}</p>
                        </div>
                    </div>
                </div>

                <!-- ── Illustration overlay ── -->
                <Transition
                    enter-active-class="transition duration-150 ease-out"
                    enter-from-class="opacity-0"
                    enter-to-class="opacity-100"
                    leave-active-class="transition duration-100 ease-in"
                    leave-from-class="opacity-100"
                    leave-to-class="opacity-0">
                    <div v-if="showIllustration"
                        class="absolute inset-0 z-10 flex items-center justify-center bg-white/95 dark:bg-rapanel-navy-900/95 rounded-t-2xl sm:rounded-2xl"
                        @click.self="showIllustration = false">
                        <button @click="showIllustration = false"
                            class="absolute top-3 right-3 w-8 h-8 flex items-center justify-center rounded-lg text-rapanel-text-light/40 dark:text-white/40 hover:text-rapanel-navy-900 dark:hover:text-white hover:bg-rapanel-navy-100 dark:hover:bg-white/10 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                        <img :src="cardIllustrationSrc(itemDbItem)"
                             :alt="itemDbItem.display_name || itemDbItem.name"
                             class="max-h-[80vh] max-w-[90%] object-contain drop-shadow-2xl" />
                    </div>
                </Transition>

            </div>
        </Transition>
    </Teleport>
    <WhoSellShopModal ref="shopModal" />
</template>

<style scoped>
:deep(span[style*="color"]) { display: inline; }
</style>

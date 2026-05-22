<script setup>
import { ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
    item:        { type: Object, default: null },
    serverCount: { type: Number, default: null },
});

const emit = defineEmits(['close']);

const page = usePage();
const __   = (key) => page.props.translations?.[key] || key;

const activeTab = ref('detail');
watch(() => props.item, (val) => {
    if (val) {
        activeTab.value = 'detail';
        illustrationAvailable.value = false;
        showIllustration.value      = false;
    }
});

const imageSrc    = (item) => `/data/items/images/${item.item_id}.png`;
const onImgError  = (e)    => { e.target.style.display = 'none'; };

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

            <div v-if="item"
                class="fixed inset-0 z-50 flex items-end sm:items-center justify-center sm:p-4 bg-rapanel-navy-900/80 backdrop-blur-sm"
                @click.self="emit('close')">

                <div class="relative w-full sm:max-w-4xl max-h-[95vh] sm:max-h-[90vh] flex flex-col overflow-hidden
                            rounded-t-2xl sm:rounded-2xl shadow-2xl bg-rapanel-navy-900 ring-1 ring-white/10">

                    <!-- Gold accent line -->
                    <div class="h-[3px] shrink-0 bg-gradient-to-r from-rapanel-gold via-rapanel-gold/50 to-transparent" />

                    <!-- ── Header ── -->
                    <div class="flex items-center gap-4 px-5 py-4 shrink-0 bg-rapanel-navy-900">
                        <img :src="imageSrc(item)"
                             :alt="item.display_name || item.name"
                             class="shrink-0 w-[75px] h-[100px] object-contain rounded-xl"
                             @error="onImgError" />

                        <!-- preload silencioso para detectar si existe la ilustración -->
                        <img v-if="item.type === 'Card'"
                             :src="cardIllustrationSrc(item)"
                             class="hidden"
                             @load="onIllustrationLoad"
                             @error="onIllustrationError" />

                        <div class="flex-1 min-w-0">
                            <h2 class="font-bold text-white text-xl leading-tight">
                                {{ item.display_name || item.name }}<template v-if="item.slots > 0"> [{{ item.slots }}]</template>
                            </h2>
                            <p v-if="item.aegis_name" class="font-mono text-[11px] text-white/35 mt-0.5">
                                {{ item.aegis_name }}
                            </p>
                            <div class="flex items-center gap-2 mt-2 flex-wrap">
                                <span class="text-[11px] font-bold px-2.5 py-0.5 rounded-full bg-white/10 text-white/70">
                                    ID: {{ item.item_id }}
                                </span>
                                <span v-if="item.type" :class="['text-[10px] font-black uppercase px-2 py-0.5 rounded-full border tracking-wide', typeBadge(item.type)]">
                                    {{ item.type }}
                                </span>
                                <button v-if="illustrationAvailable"
                                    @click="showIllustration = true"
                                    class="inline-flex items-center gap-1 text-[10px] font-black uppercase px-2 py-0.5 rounded-full border tracking-wide transition
                                           bg-purple-500/10 text-purple-400 border-purple-500/20 hover:bg-purple-500/20">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3 21h18M3.75 3h16.5M4.5 3v18M19.5 3v18"/>
                                    </svg>
                                    {{ __('Illustration') }}
                                </button>
                            </div>
                        </div>

                        <button @click="emit('close')"
                            class="shrink-0 w-8 h-8 flex items-center justify-center rounded-lg cursor-pointer
                                   text-white/40 hover:text-white hover:bg-white/10 transition duration-150">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- ── Tab bar ── -->
                    <div class="flex shrink-0 gap-1.5 px-4 pt-3 bg-rapanel-navy-900 border-b border-white/10 overflow-x-auto">

                        <button @click="activeTab = 'detail'"
                            :class="activeTab === 'detail' ? 'bg-rapanel-gold text-rapanel-navy-900' : 'text-white/50 hover:text-white hover:bg-white/5'"
                            class="flex items-start gap-2.5 px-4 py-2.5 rounded-t-xl text-left transition-all duration-150 shrink-0">
                            <svg class="w-4 h-4 mt-0.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <div>
                                <div class="text-sm font-bold leading-tight whitespace-nowrap">{{ __('Item Detail') }}</div>
                                <div :class="activeTab === 'detail' ? 'text-rapanel-navy-900/60' : 'text-white/30'"
                                     class="text-[10px] mt-0.5 leading-tight max-w-[160px] truncate">Stats, precios NPC y requisitos</div>
                            </div>
                        </button>

                        <div class="flex items-start gap-2.5 px-4 py-2.5 rounded-t-xl text-left shrink-0 text-white/25 cursor-default">
                            <svg class="w-4 h-4 mt-0.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.121 14.121L19 19m-7-7l7-7m-7 7l-2.879 2.879M12 12L9.121 9.121m0 5.758a3 3 0 10-4.243-4.243 3 3 0 004.243 4.243zm0 0L12 17.743" />
                            </svg>
                            <div>
                                <div class="flex items-center gap-1.5 text-sm font-bold leading-tight whitespace-nowrap">
                                    {{ __('Monsters') }}
                                    <span class="text-[10px] bg-white/10 text-white/40 rounded-full w-4 h-4 flex items-center justify-center shrink-0">0</span>
                                </div>
                                <div class="text-[10px] text-white/20 mt-0.5 leading-tight max-w-[160px] truncate">Donde cae este ítem y probabilidades</div>
                            </div>
                        </div>

                        <div class="flex items-start gap-2.5 px-4 py-2.5 rounded-t-xl text-left shrink-0 text-white/25 cursor-default">
                            <svg class="w-4 h-4 mt-0.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            <div>
                                <div class="flex items-center gap-1.5 text-sm font-bold leading-tight whitespace-nowrap">
                                    {{ __('Trade') }}
                                    <span class="text-[10px] bg-white/10 text-white/40 rounded-full w-4 h-4 flex items-center justify-center shrink-0">0</span>
                                </div>
                                <div class="text-[10px] text-white/20 mt-0.5 leading-tight max-w-[160px] truncate">Mercado y tiendas del servidor</div>
                            </div>
                        </div>

                        <div class="flex items-start gap-2.5 px-4 py-2.5 rounded-t-xl text-left shrink-0 text-white/25 cursor-default">
                            <svg class="w-4 h-4 mt-0.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />
                            </svg>
                            <div>
                                <div class="text-sm font-bold leading-tight whitespace-nowrap">{{ __('Price History') }}</div>
                                <div class="text-[10px] text-white/20 mt-0.5 leading-tight max-w-[160px] truncate">Tendencias recientes de precios</div>
                            </div>
                        </div>
                    </div>

                    <!-- ── Body ── -->
                    <div class="overflow-y-auto flex-1 p-5 bg-rapanel-navy-800/60">

                        <div v-if="activeTab === 'detail'" class="grid grid-cols-1 md:grid-cols-5 gap-4">

                            <!-- Columna izquierda -->
                            <div class="md:col-span-3 space-y-4">

                                <!-- Descripción -->
                                <div class="bg-rapanel-navy-900 rounded-xl border border-white/10 overflow-hidden">
                                    <div class="flex items-center gap-2 px-4 py-3 border-b border-white/10">
                                        <svg class="w-4 h-4 text-rapanel-gold shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        <span class="font-semibold text-sm text-white">{{ __('Game Description') }}</span>
                                    </div>
                                    <div class="px-4 py-4">
                                        <div v-if="item.description_html"
                                             class="font-mono text-sm leading-relaxed text-white/85"
                                             v-html="item.description_html" />
                                        <p v-else class="text-sm text-white/40 italic">{{ __('No description available.') }}</p>
                                    </div>
                                </div>

                                <!-- Clases Permitidas -->
                                <div v-if="allowedJobs(item.properties) || classRestrictions(item.properties)"
                                     class="bg-rapanel-navy-900 rounded-xl border border-white/10 overflow-hidden">
                                    <div class="flex items-center gap-2 px-4 py-3 border-b border-white/10">
                                        <svg class="w-4 h-4 text-rapanel-gold shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        <span class="font-semibold text-sm text-white">{{ __('Allowed Classes') }}</span>
                                    </div>
                                    <div v-if="allowedJobs(item.properties)" class="px-4 pt-3 pb-2 flex flex-wrap gap-1.5">
                                        <span v-for="job in allowedJobs(item.properties)" :key="job"
                                              :class="['text-[10px] font-bold px-2 py-0.5 rounded-full border', jobBadge(job)]">
                                            {{ job }}
                                        </span>
                                    </div>
                                    <div v-if="classRestrictions(item.properties)"
                                         :class="allowedJobs(item.properties) ? 'border-t border-white/[0.06]' : ''"
                                         class="px-4 pt-2 pb-3">
                                        <p class="text-[9px] font-black uppercase tracking-widest text-white/30 mb-1.5">{{ __('Class Tier') }}</p>
                                        <div class="flex flex-wrap gap-1.5">
                                            <span v-for="cls in classRestrictions(item.properties)" :key="cls"
                                                  :class="['text-[10px] font-bold px-2 py-0.5 rounded-full border', classBadge(cls)]">
                                                {{ classLabel[cls] ?? cls.replace(/_/g, ' ') }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Script -->
                                <div v-if="scriptValue(item.properties)"
                                     class="bg-rapanel-navy-900 rounded-xl border border-white/10 overflow-hidden">
                                    <div class="flex items-center gap-2 px-4 py-3 border-b border-white/10">
                                        <svg class="w-4 h-4 text-rapanel-gold shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                                        </svg>
                                        <span class="font-semibold text-sm text-white">{{ __('Script') }}</span>
                                    </div>
                                    <pre class="px-4 py-4 text-xs font-mono text-emerald-400/80 overflow-x-auto whitespace-pre-wrap leading-relaxed">{{ scriptValue(item.properties) }}</pre>
                                </div>
                            </div>

                            <!-- Columna derecha -->
                            <div class="md:col-span-2 space-y-3">

                                <!-- Atributos -->
                                <div v-if="attrStats(item.properties).length"
                                     class="bg-rapanel-navy-900 rounded-xl border border-white/10 overflow-hidden">
                                    <div class="flex items-center gap-2 px-4 py-3 border-b border-white/10">
                                        <svg class="w-4 h-4 text-rapanel-gold shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                        </svg>
                                        <span class="font-semibold text-sm text-white">{{ __('Attributes') }}</span>
                                    </div>
                                    <div class="divide-y divide-white/[0.06]">
                                        <div v-for="stat in attrStats(item.properties)" :key="stat.label"
                                             class="flex justify-between items-center px-4 py-2.5">
                                            <span class="text-[11px] font-black uppercase tracking-wider text-white/40">{{ __(stat.label) }}</span>
                                            <span class="text-sm font-bold text-white tabular-nums">{{ stat.value }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Precios NPC -->
                                <div v-if="item.properties?.Buy !== undefined || item.properties?.Sell !== undefined"
                                     class="bg-rapanel-navy-900 rounded-xl border border-white/10 overflow-hidden">
                                    <div class="flex items-center gap-2 px-4 py-3 border-b border-white/10">
                                        <svg class="w-4 h-4 text-rapanel-gold shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                                        </svg>
                                        <span class="font-semibold text-sm text-white">{{ __('NPC Prices') }}</span>
                                    </div>
                                    <div class="divide-y divide-white/[0.06]">
                                        <div class="flex justify-between items-center px-4 py-2.5">
                                            <span class="text-[11px] font-black uppercase tracking-wider text-white/40">{{ __('Buy') }}</span>
                                            <span class="text-sm font-bold text-white tabular-nums">{{ (item.properties.Buy ?? 0).toLocaleString() }}z</span>
                                        </div>
                                        <div class="flex justify-between items-center px-4 py-2.5">
                                            <span class="text-[11px] font-black uppercase tracking-wider text-white/40">{{ __('Sell') }}</span>
                                            <span class="text-sm font-bold text-rapanel-gold tabular-nums">{{ (item.properties.Sell ?? 0).toLocaleString() }}z</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Información -->
                                <div class="bg-rapanel-navy-900 rounded-xl border border-white/10 overflow-hidden">
                                    <div class="flex items-center gap-2 px-4 py-3 border-b border-white/10">
                                        <svg class="w-4 h-4 text-rapanel-gold shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span class="font-semibold text-sm text-white">{{ __('Information') }}</span>
                                    </div>
                                    <div class="divide-y divide-white/[0.06]">
                                        <div v-if="item.type" class="flex justify-between items-center px-4 py-2.5">
                                            <span class="text-[11px] font-black uppercase tracking-wider text-white/40">{{ __('Type') }}</span>
                                            <span class="text-sm font-bold text-white">{{ item.type }}</span>
                                        </div>
                                        <div v-if="item.subtype" class="flex justify-between items-center px-4 py-2.5">
                                            <span class="text-[11px] font-black uppercase tracking-wider text-white/40">{{ __('Subtype') }}</span>
                                            <span class="text-sm font-bold text-white">{{ item.subtype }}</span>
                                        </div>
                                        <div v-if="item.slots > 0" class="flex justify-between items-center px-4 py-2.5">
                                            <span class="text-[11px] font-black uppercase tracking-wider text-white/40">{{ __('Slots') }}</span>
                                            <span class="text-sm font-bold text-white">{{ item.slots }}</span>
                                        </div>
                                        <div v-if="equipLocations(item.properties)" class="flex justify-between items-start gap-2 px-4 py-2.5">
                                            <span class="text-[11px] font-black uppercase tracking-wider text-white/40 shrink-0 mt-0.5">{{ __('Equip Location') }}</span>
                                            <div class="flex flex-wrap gap-1 justify-end">
                                                <span v-for="loc in equipLocations(item.properties)" :key="loc"
                                                      :class="['text-[10px] font-bold px-2 py-0.5 rounded-full border', locationBadge(loc)]">
                                                    {{ locationLabel[loc] ?? loc.replace(/_/g, ' ') }}
                                                </span>
                                            </div>
                                        </div>
                                        <div v-if="item.is_custom" class="flex justify-between items-center px-4 py-2.5">
                                            <span class="text-[11px] font-black uppercase tracking-wider text-white/40">{{ __('Custom') }}</span>
                                            <span class="text-sm font-bold text-rapanel-gold">✓</span>
                                        </div>
                                        <div v-if="itemGender(item.properties)" class="flex justify-between items-center px-4 py-2.5">
                                            <span class="text-[11px] font-black uppercase tracking-wider text-white/40">{{ __('Gender') }}</span>
                                            <span :class="['text-xs font-bold px-2 py-0.5 rounded-full border', item.properties.Gender === 'Male' ? 'bg-rapanel-blue/15 text-rapanel-blue border-rapanel-blue/25' : 'bg-pink-500/15 text-pink-400 border-pink-500/25']">
                                                {{ __(item.properties.Gender) }}
                                            </span>
                                        </div>
                                        <div v-if="hasBuyingStore(item.properties)" class="flex justify-between items-center px-4 py-2.5">
                                            <span class="text-[11px] font-black uppercase tracking-wider text-white/40">{{ __('Buying Store') }}</span>
                                            <span class="text-xs font-bold px-2 py-0.5 rounded-full border bg-rapanel-success/15 text-rapanel-success border-rapanel-success/25">✓</span>
                                        </div>
                                        <div class="flex justify-between items-center px-4 py-2.5">
                                            <span class="text-[11px] font-black uppercase tracking-wider text-white/40">{{ __('On Server') }}</span>
                                            <span v-if="serverCount !== null" class="text-sm font-bold text-white tabular-nums">{{ serverCount.toLocaleString() }}</span>
                                            <span v-else class="w-16 h-4 rounded bg-white/10 animate-pulse inline-block" />
                                        </div>
                                    </div>
                                </div>

                                <!-- Restricciones de Comercio -->
                                <div v-if="tradeRestrictions(item.properties).length"
                                     class="bg-rapanel-navy-900 rounded-xl border border-white/10 overflow-hidden">
                                    <div class="flex items-center gap-2 px-4 py-3 border-b border-white/10">
                                        <svg class="w-4 h-4 text-rapanel-danger shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                        </svg>
                                        <span class="font-semibold text-sm text-white">{{ __('Trade Restrictions') }}</span>
                                    </div>
                                    <div class="px-4 py-3 flex flex-wrap gap-1.5">
                                        <span v-for="r in tradeRestrictions(item.properties)" :key="r"
                                              class="text-[10px] font-bold px-2 py-0.5 rounded-full border bg-rapanel-danger/10 text-rapanel-danger border-rapanel-danger/20">
                                            {{ r }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tabs futuros -->
                        <div v-else class="flex flex-col items-center justify-center py-16 text-center gap-3">
                            <svg class="w-10 h-10 text-white/10" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <p class="text-white/30 text-sm">{{ __('Coming soon') }}</p>
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
                        class="absolute inset-0 z-10 flex items-center justify-center bg-rapanel-navy-900/95 rounded-t-2xl sm:rounded-2xl"
                        @click.self="showIllustration = false">
                        <button @click="showIllustration = false"
                            class="absolute top-3 right-3 w-8 h-8 flex items-center justify-center rounded-lg text-white/40 hover:text-white hover:bg-white/10 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                        <img :src="cardIllustrationSrc(item)"
                             :alt="item.display_name || item.name"
                             class="max-h-[80vh] max-w-[90%] object-contain drop-shadow-2xl" />
                    </div>
                </Transition>

            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
:deep(span[style*="color"]) { display: inline; }
</style>

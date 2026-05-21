<script setup>
import { ref, computed, watch } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';

const props = defineProps({
    items:   Object,
    types:   Array,
    filters: Object,
});

const page = usePage();
const __   = (key) => page.props.translations?.[key] || key;

// ── Filters (debounced search) ───────────────────────────────────────
const search = ref(props.filters.search ?? '');
const type   = ref(props.filters.type ?? '');

let debounceTimer = null;

const applyFilters = () => {
    router.get(route('info.item-db'), {
        search: search.value || undefined,
        type:   type.value   || undefined,
    }, {
        preserveState: true,
        replace: true,
    });
};

watch(search, () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(applyFilters, 350);
});

watch([type], applyFilters);

// ── Detail modal ─────────────────────────────────────────────────────
const selectedItem = ref(null);

const openDetail = (item) => { selectedItem.value = item; };
const closeDetail = () => { selectedItem.value = null; };

// ── Icon path resolution ─────────────────────────────────────────────
const iconSrc  = (item) => `/data/items/icons/${item.item_id}.png`;
const imageSrc = (item) => `/data/items/images/${item.item_id}.png`;

const onIconError = (e) => {
    e.target.style.display = 'none';
};

// ── Type badge ───────────────────────────────────────────────────────
const typeBadge = (t) => {
    const map = {
        Weapon:     'bg-rapanel-danger/10 text-rapanel-danger border-rapanel-danger/20',
        Armor:      'bg-rapanel-blue/10 text-rapanel-blue border-rapanel-blue/20',
        Consumable: 'bg-rapanel-success/10 text-rapanel-success border-rapanel-success/20',
        Card:       'bg-purple-500/10 text-purple-500 border-purple-500/20',
        Ammo:       'bg-amber-500/10 text-amber-500 border-amber-500/20',
    };
    return map[t] ?? 'bg-rapanel-navy-100 text-rapanel-text-light border-transparent dark:bg-white/5 dark:text-rapanel-text-dark';
};

// ── Stat labels mapped from YAML keys ───────────────────────────────
const statLabels = {
    Weight:        'Weight',
    Buy:           'Buy Price',
    Sell:          'Sell Price',
    Attack:        'ATK',
    MagicAttack:   'MATK',
    Defense:       'DEF',
    Range:         'Range',
    Slots:         'Slots',
    WeaponLevel:   'Weapon Lv',
    ArmorLevel:    'Armor Lv',
    EquipLevelMin: 'Min Level',
    EquipLevelMax: 'Max Level',
    Refineable:    'Refineable',
    Gradable:      'Gradable',
};

const QUICK_KEYS = ['Attack', 'MagicAttack', 'Defense', 'Range', 'WeaponLevel', 'ArmorLevel', 'EquipLevelMin'];

// Quick-strip: only the most important combat stats
const quickStats = (props_obj) => {
    if (!props_obj) return [];
    return QUICK_KEYS
        .filter(k => props_obj[k] !== undefined && props_obj[k] !== null && props_obj[k] !== false && props_obj[k] !== 0)
        .map(k => ({ label: statLabels[k] ?? k, value: props_obj[k] }));
};

// Body grid: everything else (excluding scripts and complex objects)
const visibleStats = (props_obj) => {
    if (!props_obj) return [];
    const skip = new Set([...QUICK_KEYS, 'Script', 'OnEquipScript', 'OnUnequipScript']);
    return Object.entries(props_obj)
        .filter(([k, v]) => !skip.has(k) && typeof v !== 'object' && v !== null && v !== false)
        .map(([k, v]) => ({
            label: statLabels[k] ?? k,
            value: k === 'Weight' ? `${v / 10}` : typeof v === 'boolean' ? (v ? '✓' : '✗') : v,
        }));
};

const scriptValue = (props_obj) => props_obj?.Script ?? props_obj?.OnEquipScript ?? null;

// Type accent line color for modal top border
const typeAccentLine = (type) => {
    const map = {
        Weapon:     'bg-gradient-to-r from-rose-500 via-rose-500/40 to-transparent',
        Armor:      'bg-gradient-to-r from-rapanel-blue via-rapanel-blue/40 to-transparent',
        Consumable: 'bg-gradient-to-r from-emerald-500 via-emerald-500/40 to-transparent',
        Card:       'bg-gradient-to-r from-purple-500 via-purple-500/40 to-transparent',
        Ammo:       'bg-gradient-to-r from-amber-500 via-amber-500/40 to-transparent',
    };
    return map[type] ?? 'bg-gradient-to-r from-rapanel-blue/60 via-rapanel-blue/20 to-transparent';
};
</script>

<template>
    <Head :title="__('Item DB')" />

    <MainLayout :show-bg="true">

        <!-- ── Hero / Filters ── -->
        <div class="bg-white dark:bg-rapanel-navy-900 border-b border-rapanel-navy-100 dark:border-white/10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <h1 class="text-3xl font-display font-bold text-rapanel-navy-900 dark:text-white tracking-wide mb-6">
                    {{ __('Item DB') }}
                </h1>

                <!-- Filter bar -->
                <div class="flex flex-wrap gap-3">

                    <!-- Search -->
                    <div class="relative flex-1 min-w-[200px]">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-rapanel-text-light dark:text-rapanel-text-dark"
                            fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M11 19a8 8 0 100-16 8 8 0 000 16z" />
                        </svg>
                        <input
                            v-model="search"
                            type="text"
                            :placeholder="`${__('Search')}…`"
                            class="w-full pl-9 pr-4 py-2 rounded-lg border border-rapanel-navy-100 dark:border-white/10 bg-white dark:bg-rapanel-navy-800 text-rapanel-navy-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-rapanel-blue/50 transition" />
                    </div>

                    <!-- Type filter -->
                    <select v-model="type"
                        class="px-3 py-2 rounded-lg border border-rapanel-navy-100 dark:border-white/10 bg-white dark:bg-rapanel-navy-800 text-rapanel-navy-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-rapanel-blue/50 transition">
                        <option value="">{{ __('All Types') }}</option>
                        <option v-for="t in types" :key="t" :value="t">{{ t }}</option>
                    </select>

                    <!-- Result count -->
                    <div class="flex items-center text-sm text-rapanel-text-light dark:text-rapanel-text-dark">
                        {{ items.total.toLocaleString() }} {{ __('Total items') }}
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Grid ── -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

            <div v-if="items.data.length"
                class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3">

                <button v-for="item in items.data" :key="item.id"
                    @click="openDetail(item)"
                    class="group bg-white dark:bg-rapanel-navy-900 rounded-2xl border border-rapanel-navy-100 dark:border-white/10 shadow-sm overflow-hidden flex flex-col items-center p-3 gap-2 hover:shadow-md hover:border-rapanel-blue/40 dark:hover:border-rapanel-blue/40 transition-all duration-200 text-left w-full">

                    <!-- Icon -->
                    <div class="rounded-xl bg-rapanel-navy-50 dark:bg-rapanel-navy-800 flex items-center justify-center shrink-0 p-1">
                        <img
                            :src="iconSrc(item)"
                            :alt="item.display_name || item.name"
                            class="object-contain group-hover:scale-110 transition-transform duration-300"
                            @error="onIconError" />
                    </div>

                    <!-- Name -->
                    <p class="text-center text-[11px] font-semibold text-rapanel-navy-900 dark:text-white leading-tight line-clamp-2 w-full">
                        {{ item.display_name || item.name }}
                    </p>

                    <!-- Type badge + slots -->
                    <div class="flex items-center gap-1.5 flex-wrap justify-center mt-auto">
                        <span :class="['text-[9px] font-black uppercase px-1.5 py-0.5 rounded-full border', typeBadge(item.type)]">
                            {{ item.type }}
                        </span>
                        <span v-if="item.slots > 0"
                            class="text-[9px] font-black uppercase px-1.5 py-0.5 rounded-full border bg-rapanel-gold/10 text-rapanel-gold border-rapanel-gold/30">
                            [{{ item.slots }}]
                        </span>
                    </div>

                    <!-- Quick stats -->
                    <div class="flex items-center gap-2 text-[10px] text-rapanel-text-light dark:text-rapanel-text-dark w-full justify-center">
                        <span v-if="item.properties?.Attack">
                            {{ __('ATK') }} {{ item.properties.Attack }}
                        </span>
                        <span v-if="item.properties?.Defense">
                            {{ __('DEF') }} {{ item.properties.Defense }}
                        </span>
                        <span v-if="item.properties?.Weight">
                            {{ item.properties.Weight / 10 }}oz
                        </span>
                    </div>
                </button>
            </div>

            <!-- Empty state -->
            <div v-else class="flex flex-col items-center justify-center py-24 text-center">
                <svg class="w-12 h-12 text-rapanel-navy-100 dark:text-white/10 mb-4" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0H4" />
                </svg>
                <p class="text-rapanel-text-light dark:text-rapanel-text-dark text-sm">{{ __('No items found.') }}</p>
            </div>

            <!-- ── Pagination ── -->
            <div v-if="items.last_page > 1" class="flex justify-center mt-8 gap-1.5 flex-wrap">
                <Link
                    v-for="link in items.links"
                    :key="link.label"
                    :href="link.url ?? '#'"
                    :class="[
                        'px-3 py-1.5 rounded-lg text-xs font-semibold border transition-all',
                        link.active
                            ? 'bg-rapanel-blue text-white border-rapanel-blue'
                            : link.url
                                ? 'bg-white dark:bg-rapanel-navy-900 text-rapanel-text-light dark:text-rapanel-text-dark border-rapanel-navy-100 dark:border-white/10 hover:border-rapanel-blue hover:text-rapanel-blue'
                                : 'bg-transparent text-rapanel-text-light/40 dark:text-white/20 border-transparent cursor-default',
                    ]"
                    v-html="link.label"
                    :preserve-scroll="true"
                />
            </div>
        </div>

        <!-- ── Detail Modal ── -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0 scale-95"
                enter-to-class="opacity-100 scale-100"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100 scale-100"
                leave-to-class="opacity-0 scale-95">

                <div v-if="selectedItem"
                    class="fixed inset-0 z-50 flex items-end sm:items-center justify-center sm:p-4 bg-rapanel-navy-900/80 backdrop-blur-sm"
                    @click.self="closeDetail">

                    <div class="w-full sm:max-w-2xl max-h-[92vh] sm:max-h-[85vh] flex flex-col overflow-hidden
                                rounded-t-2xl sm:rounded-2xl
                                bg-white dark:bg-rapanel-navy-900
                                ring-1 ring-rapanel-navy-100 dark:ring-white/10 shadow-2xl">

                        <!-- Type accent line -->
                        <div class="h-[3px] shrink-0" :class="typeAccentLine(selectedItem.type)" />

                        <!-- Header -->
                        <div class="flex items-start gap-4 px-5 pt-5 pb-4 shrink-0">

                            <!-- Item image -->
                            <div class="shrink-0 w-[88px] h-[88px] rounded-xl flex items-center justify-center
                                        bg-rapanel-navy-50 dark:bg-rapanel-navy-800
                                        border border-rapanel-navy-100 dark:border-white/10 overflow-hidden">
                                <img :src="imageSrc(selectedItem)"
                                     :alt="selectedItem.display_name || selectedItem.name"
                                     class="max-w-full max-h-full object-contain"
                                     @error="onIconError" />
                            </div>

                            <!-- Name + meta -->
                            <div class="flex-1 min-w-0 pt-1">
                                <h2 class="font-bold text-rapanel-navy-900 dark:text-white text-xl leading-tight">
                                    {{ selectedItem.display_name || selectedItem.name }}
                                </h2>
                                <div class="flex items-center gap-1.5 mt-1 flex-wrap">
                                    <span class="font-mono text-[11px] text-rapanel-text-light dark:text-rapanel-text-dark">#{{ selectedItem.item_id }}</span>
                                    <span class="text-rapanel-navy-200 dark:text-white/15 text-xs">·</span>
                                    <span class="font-mono text-[11px] text-rapanel-text-light dark:text-rapanel-text-dark">{{ selectedItem.aegis_name }}</span>
                                </div>
                                <div class="flex items-center gap-1.5 mt-2.5 flex-wrap">
                                    <span :class="['text-[10px] font-black uppercase px-2 py-0.5 rounded-full border tracking-wide', typeBadge(selectedItem.type)]">
                                        {{ selectedItem.type }}<span v-if="selectedItem.subtype"> · {{ selectedItem.subtype }}</span>
                                    </span>
                                    <span v-if="selectedItem.slots > 0"
                                        class="text-[10px] font-black uppercase px-2 py-0.5 rounded-full border bg-rapanel-gold/10 text-rapanel-gold border-rapanel-gold/30 tracking-wide">
                                        [{{ selectedItem.slots }}] {{ __('Slots') }}
                                    </span>
                                </div>
                            </div>

                            <!-- Close button -->
                            <button @click="closeDetail"
                                class="shrink-0 w-8 h-8 flex items-center justify-center rounded-lg cursor-pointer
                                       text-rapanel-text-light dark:text-rapanel-text-dark
                                       hover:text-rapanel-navy-900 dark:hover:text-white
                                       hover:bg-rapanel-navy-50 dark:hover:bg-white/10
                                       transition duration-150">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- Quick stats strip -->
                        <div v-if="quickStats(selectedItem.properties).length"
                            class="flex gap-2 px-5 pb-4 shrink-0 overflow-x-auto">
                            <div v-for="stat in quickStats(selectedItem.properties)" :key="stat.label"
                                class="flex items-center gap-2 shrink-0 px-3 py-1.5 rounded-lg
                                       bg-rapanel-navy-50 dark:bg-white/[0.04]
                                       border border-rapanel-navy-100 dark:border-white/[0.07]">
                                <span class="text-[9px] font-black uppercase tracking-[0.12em] text-rapanel-text-light/70 dark:text-white/35">{{ __(stat.label) }}</span>
                                <span class="text-sm font-bold text-rapanel-navy-900 dark:text-white tabular-nums">{{ stat.value }}</span>
                            </div>
                        </div>

                        <!-- Divider -->
                        <div class="h-px bg-rapanel-navy-100 dark:bg-white/[0.07] mx-5 shrink-0" />

                        <!-- Body (scrollable) -->
                        <div class="overflow-y-auto flex-1 p-5 space-y-5">

                            <!-- Description -->
                            <div v-if="selectedItem.description_html">
                                <p class="text-[9px] font-black uppercase tracking-[0.15em] text-rapanel-text-light/50 dark:text-white/25 mb-2">{{ __('Description') }}</p>
                                <div class="rounded-xl border border-rapanel-navy-100 dark:border-white/[0.07]
                                            bg-rapanel-navy-50 dark:bg-black/30
                                            px-4 py-3 font-mono text-sm leading-relaxed
                                            text-rapanel-navy-900 dark:text-white/80"
                                     v-html="selectedItem.description_html" />
                            </div>
                            <p v-else class="text-sm text-rapanel-text-light dark:text-rapanel-text-dark italic">{{ __('No description available.') }}</p>

                            <!-- Properties grid -->
                            <div v-if="visibleStats(selectedItem.properties).length">
                                <p class="text-[9px] font-black uppercase tracking-[0.15em] text-rapanel-text-light/50 dark:text-white/25 mb-2">{{ __('Properties') }}</p>
                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                                    <div v-for="stat in visibleStats(selectedItem.properties)" :key="stat.label"
                                        class="flex flex-col gap-0.5 rounded-xl px-3 py-2.5
                                               bg-rapanel-navy-50 dark:bg-white/[0.03]
                                               border border-rapanel-navy-100 dark:border-white/[0.05]">
                                        <span class="text-[9px] font-bold uppercase tracking-wider text-rapanel-text-light/60 dark:text-white/30">{{ __(stat.label) }}</span>
                                        <span class="text-sm font-bold text-rapanel-navy-900 dark:text-white tabular-nums">{{ stat.value }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Script -->
                            <div v-if="scriptValue(selectedItem.properties)">
                                <p class="text-[9px] font-black uppercase tracking-[0.15em] text-rapanel-text-light/50 dark:text-white/25 mb-2">{{ __('Script') }}</p>
                                <pre class="text-xs font-mono text-rapanel-navy-900 dark:text-emerald-400/80
                                            bg-rapanel-navy-50 dark:bg-black/40
                                            border border-rapanel-navy-100 dark:border-white/[0.07]
                                            rounded-xl px-4 py-3 overflow-x-auto whitespace-pre-wrap leading-relaxed">{{ scriptValue(selectedItem.properties) }}</pre>
                            </div>

                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

    </MainLayout>
</template>

<style scoped>
/* Allow v-html description colors to show through */
:deep(span[style*="color"]) {
    display: inline;
}
</style>

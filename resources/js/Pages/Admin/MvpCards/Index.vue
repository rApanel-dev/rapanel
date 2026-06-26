<script setup>
import { ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';

const props = defineProps({
    rows:        Array,
    counts:      Object,
    classFilter: String,
    mobDbEmpty:  Boolean,
});

const page = usePage();
const __ = (key) => page.props.translations?.[key] || key;

// ── Filtro de clase ──────────────────────────────────────────────────
const setFilter = (cls) => {
    router.get(route('admin.mvp-cards.index'), { class: cls }, { preserveScroll: true, preserveState: false });
};

// ── Toggle visibilidad pública ────────────────────────────────────────
const toggle = (row) => {
    router.patch(
        route('admin.mvp-cards.toggle', row.card_item_id),
        { mob_id: row.mob_id },
        { preserveScroll: true, preserveState: false }
    );
};

// ── Modal Holders ────────────────────────────────────────────────────
const showHoldersModal = ref(false);
const holdersRow       = ref(null);
const holders          = ref([]);
const holdersLoading   = ref(false);

const openHolders = async (row) => {
    holdersRow.value     = row;
    holders.value        = [];
    holdersLoading.value = true;
    showHoldersModal.value = true;

    try {
        const res  = await fetch(route('admin.mvp-cards.holders', row.card_item_id));
        const data = await res.json();
        holders.value = data.holders ?? [];
    } finally {
        holdersLoading.value = false;
    }
};

// ── Preview de imagen ────────────────────────────────────────────────
const previewSrc = ref(null);
const previewPos = ref({ x: 0, y: 0 });

const showPreview = (e, src) => {
    previewSrc.value = src;
    previewPos.value = { x: e.clientX + 16, y: e.clientY - 90 };
};
const movePreview = (e) => {
    previewPos.value = { x: e.clientX + 16, y: e.clientY - 90 };
};
const hidePreview = () => { previewSrc.value = null; };

const locationBadgeClass = (loc) => {
    if (loc.includes('equipped') || loc.includes('item')) return 'bg-amber-500/10 text-amber-500 border-amber-500/20';
    if (loc.includes('storage')) return 'bg-rapanel-blue/10 text-rapanel-blue border-rapanel-blue/20';
    if (loc.includes('guild'))   return 'bg-purple-500/10 text-purple-500 border-purple-500/20';
    if (loc.includes('cart'))    return 'bg-teal-500/10 text-teal-500 border-teal-500/20';
    return 'bg-rapanel-success/10 text-rapanel-success border-rapanel-success/20';
};
</script>

<template>
    <AdminLayout>

        <PageHeader :title="__('MvP Cards')"
            :description="`${counts.active} ${__('visible to public')}`"
            class="mb-6" />

        <!-- ── Filtros de clase ── -->
        <div class="flex items-center gap-2 mb-6 flex-wrap">
            <button v-for="(label, cls) in { mvp: 'MVP', boss: 'Boss', normal: __('Normal') }" :key="cls"
                @click="setFilter(cls)"
                :class="[
                    'inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-bold border transition',
                    classFilter === cls
                        ? 'bg-rapanel-navy-900 dark:bg-white text-white dark:text-rapanel-navy-900 border-rapanel-navy-900 dark:border-white'
                        : 'bg-white dark:bg-transparent text-rapanel-text-light dark:text-rapanel-text-dark border-rapanel-navy-100 dark:border-white/10 hover:border-rapanel-btn-blue hover:text-rapanel-blue'
                ]">
                {{ label }}
                <span :class="[
                    'text-[10px] font-black px-1.5 py-0.5 rounded-full',
                    classFilter === cls
                        ? 'bg-white/20 dark:bg-black/20'
                        : 'bg-rapanel-navy-100 dark:bg-white/10'
                ]">{{ counts[cls] }}</span>
            </button>
        </div>

        <!-- ── Aviso: mob_db vacío ── -->
        <div v-if="mobDbEmpty"
            class="flex gap-4 p-5 rounded-xl bg-amber-500/8 border border-amber-500/25 mb-6">
            <svg class="w-5 h-5 shrink-0 mt-0.5 text-amber-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
            </svg>
            <div>
                <p class="font-bold text-amber-700 dark:text-amber-400 text-sm">{{ __('Mob DB not imported') }}</p>
                <p class="text-xs text-amber-600/80 dark:text-amber-400/70 mt-0.5">
                    {{ __('Import') }} <span class="font-mono font-bold">mob_db.yml</span> {{ __('from the Mob DB section to populate this page automatically.') }}
                </p>
            </div>
        </div>

        <!-- ── Aviso: sin resultados (pero mob_db importada) ── -->
        <div v-else-if="!rows.length"
            class="flex gap-4 p-5 rounded-xl bg-rapanel-navy-50 dark:bg-white/[0.04] border border-rapanel-navy-100 dark:border-white/[0.07] mb-6">
            <svg class="w-5 h-5 shrink-0 mt-0.5 text-rapanel-text-light dark:text-rapanel-text-dark" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
            </svg>
            <p class="text-sm text-rapanel-text-light dark:text-rapanel-text-dark">
                {{ __('No cards found for this class. Make sure item_db.yml is also imported.') }}
            </p>
        </div>

        <!-- ── Tabla ── -->
        <div v-else
            class="bg-white dark:bg-rapanel-surface rounded-xl border border-rapanel-navy-100 dark:border-white/[0.07] shadow-[0_4px_20px_rgba(0,0,0,0.22)] dark:shadow-[0_4px_28px_rgba(0,0,0,0.5)] overflow-hidden">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-white/5">
                        <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40 w-14">{{ __('Image') }}</th>
                        <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40">{{ __('Monster') }}</th>
                        <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40">{{ __('Card') }}</th>
                        <th class="px-4 py-3 text-center text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40 w-28">{{ __('Public') }}</th>
                        <th class="px-4 py-3 text-center text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40 w-20">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-rapanel-navy-100 dark:divide-white/5">
                    <tr v-for="row in rows" :key="row.card_item_id"
                        class="hover:bg-rapanel-navy-50 dark:hover:bg-white/5 transition-colors">

                        <!-- Imagen -->
                        <td class="px-4 py-3 w-12">
                            <img v-if="row.image_exists"
                                :src="`/data/items/cards/${row.card_item_id}.png`"
                                :alt="row.card_name"
                                class="w-8 h-10 object-contain cursor-default"
                                @mouseenter="showPreview($event, `/data/items/cards/${row.card_item_id}.png`)"
                                @mousemove="movePreview($event)"
                                @mouseleave="hidePreview" />
                            <span v-else class="block w-8 h-10" />
                        </td>

                        <!-- Monstruo -->
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="font-medium text-rapanel-navy-900 dark:text-white">{{ row.mob_name }}</span>
                                <span v-if="row.is_mvp"
                                    class="text-[9px] font-black uppercase px-1.5 py-0.5 rounded border bg-rapanel-gold/10 text-rapanel-gold border-rapanel-gold/30">
                                    MVP
                                </span>
                                <span v-else-if="row.mob_class === 'Boss'"
                                    class="text-[9px] font-black uppercase px-1.5 py-0.5 rounded border bg-rapanel-danger/10 text-rapanel-danger border-rapanel-danger/20">
                                    Boss
                                </span>
                            </div>
                            <span class="text-[11px] font-mono text-rapanel-text-light/50 dark:text-white/30">#{{ row.mob_id }}</span>
                        </td>

                        <!-- Carta -->
                        <td class="px-4 py-3">
                            <span class="font-medium text-rapanel-navy-900 dark:text-white">{{ row.card_name }}</span>
                            <br>
                            <span class="text-[11px] font-mono text-rapanel-text-light/50 dark:text-white/30">#{{ row.card_item_id }}</span>
                            <span v-if="!row.image_exists"
                                class="ml-2 text-[9px] font-black uppercase px-1 py-0.5 rounded border bg-amber-500/10 text-amber-500 border-amber-500/20">
                                {{ __('No image') }}
                            </span>
                        </td>

                        <!-- Toggle público -->
                        <td class="px-4 py-3 text-center">
                            <button @click="toggle(row)"
                                :title="row.is_active ? __('Hide from public') : __('Show to public')"
                                :class="[
                                    'relative inline-flex h-5 w-9 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 focus:outline-none',
                                    row.is_active ? 'bg-rapanel-success' : 'bg-rapanel-navy-100 dark:bg-white/10'
                                ]">
                                <span :class="[
                                    'pointer-events-none inline-block h-4 w-4 rounded-full bg-white shadow-sm transform transition-transform duration-200',
                                    row.is_active ? 'translate-x-4' : 'translate-x-0'
                                ]" />
                            </button>
                        </td>

                        <!-- Acciones -->
                        <td class="px-4 py-3 text-center">
                            <button @click="openHolders(row)"
                                :title="__('Holders')"
                                class="inline-flex items-center justify-center w-7 h-7 rounded-lg border border-rapanel-gold/60 text-rapanel-gold bg-rapanel-gold/10 hover:bg-rapanel-gold/25 transition">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                                </svg>
                            </button>
                        </td>

                    </tr>
                </tbody>
            </table>
        </div>

        <!-- ══════════════════════════════════════
             Modal: Holders
        ══════════════════════════════════════ -->
        <Teleport to="body">
            <div v-if="showHoldersModal"
                role="dialog" aria-modal="true"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
                @click.self="showHoldersModal = false">

                <div class="bg-white dark:bg-rapanel-navy-900 rounded-2xl shadow-xl border border-rapanel-navy-100 dark:border-white/[0.07] w-full max-w-3xl max-h-[80vh] flex flex-col overflow-hidden">

                    <div class="shrink-0">
                        <div class="h-[3px] bg-gradient-to-r from-rapanel-gold/70 via-rapanel-gold/30 to-transparent" />
                        <div class="flex items-center justify-between px-6 py-4 border-b border-rapanel-navy-100 dark:border-white/[0.07]">
                            <div class="flex items-center gap-3">
                                <div v-if="holdersRow" class="w-10 h-10 rounded-lg overflow-hidden bg-rapanel-navy-50 dark:bg-rapanel-surface">
                                    <img v-if="holdersRow.image_exists"
                                        :src="`/data/items/cards/${holdersRow.card_item_id}.png`"
                                        class="w-full h-full object-contain" />
                                </div>
                                <div>
                                    <h2 class="font-bold text-rapanel-navy-900 dark:text-white">{{ __('Holders') }}</h2>
                                    <p class="text-xs text-rapanel-text-light dark:text-rapanel-text-dark">{{ holdersRow?.card_name }}</p>
                                </div>
                            </div>
                            <button @click="showHoldersModal = false"
                                class="text-rapanel-text-light dark:text-rapanel-text-dark hover:text-rapanel-navy-900 dark:hover:text-white transition text-xl leading-none">×</button>
                        </div>
                    </div>

                    <div class="overflow-y-auto flex-1">
                        <div v-if="holdersLoading" class="flex items-center justify-center py-16 text-sm text-rapanel-text-light dark:text-rapanel-text-dark">
                            <svg class="animate-spin w-5 h-5 mr-2 text-rapanel-blue" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                            </svg>
                            {{ __('Loading...') }}
                        </div>

                        <div v-else-if="!holders.length" class="flex flex-col items-center justify-center py-16 text-sm text-rapanel-text-light dark:text-rapanel-text-dark">
                            <span class="text-3xl mb-3">🃏</span>
                            {{ __('No holders found.') }}
                        </div>

                        <table v-else class="min-w-full text-sm">
                            <thead class="sticky top-0">
                                <tr class="border-b border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-white/5">
                                    <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40">{{ __('Character') }}</th>
                                    <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40">{{ __('Account') }}</th>
                                    <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40">{{ __('Location') }}</th>
                                    <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40 hidden md:table-cell">{{ __('Master Account') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-rapanel-navy-100 dark:divide-white/5">
                                <tr v-for="(h, i) in holders" :key="i"
                                    class="hover:bg-rapanel-navy-50 dark:hover:bg-white/5 transition-colors">
                                    <td class="px-4 py-3 font-medium text-rapanel-navy-900 dark:text-white">{{ h.char_name ?? '—' }}</td>
                                    <td class="px-4 py-3">
                                        <a v-if="h.account_id" :href="route('game-accounts.show', h.account_id)" target="_blank"
                                            class="inline-flex items-center gap-1 font-mono text-xs text-rapanel-blue hover:underline">
                                            {{ h.userid }}
                                            <svg class="w-3 h-3 opacity-60 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                                            </svg>
                                        </a>
                                        <span v-else class="text-rapanel-text-light dark:text-rapanel-text-dark">—</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex flex-col gap-1">
                                            <span :class="['text-[10px] font-bold uppercase px-2 py-0.5 rounded border w-fit', locationBadgeClass(h.location)]">{{ h.location }}</span>
                                            <span v-if="h.in_item" class="text-xs text-rapanel-text-light dark:text-rapanel-text-dark italic">{{ h.in_item }}</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 hidden md:table-cell">
                                        <span v-if="h.master_account" class="text-sm font-medium text-rapanel-navy-900 dark:text-white">{{ h.master_account }}</span>
                                        <span v-else class="text-rapanel-text-light dark:text-rapanel-text-dark">—</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-if="!holdersLoading && holders.length"
                        class="px-6 py-3 border-t border-rapanel-navy-100 dark:border-white/10 text-xs text-rapanel-text-light dark:text-rapanel-text-dark shrink-0">
                        {{ holders.length }} {{ __('result(s)') }}
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- ── Preview flotante de imagen ── -->
        <Teleport to="body">
            <div v-if="previewSrc"
                :style="{ position: 'fixed', left: previewPos.x + 'px', top: previewPos.y + 'px', zIndex: 9999 }"
                class="pointer-events-none">
                <img :src="previewSrc"
                    class="w-28 h-36 object-contain rounded-xl shadow-2xl border border-white/10 bg-rapanel-navy-900/90" />
            </div>
        </Teleport>

    </AdminLayout>
</template>

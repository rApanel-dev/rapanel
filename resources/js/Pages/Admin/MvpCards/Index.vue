<script setup>
import { ref, computed } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';

const props = defineProps({
    cards: Array,
    stats: Object,
});

const page = usePage();
const __ = (key) => page.props.translations?.[key] || key;

// ── Sorting ─────────────────────────────────────────────────────────
const sortKey = ref('item_id');
const sortDir = ref('asc');

const setSort = (key) => {
    if (sortKey.value === key) {
        sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortKey.value = key;
        sortDir.value = key === 'total' ? 'desc' : 'asc';
    }
};

const sortedCards = computed(() => {
    return [...props.cards].sort((a, b) => {
        let va = a[sortKey.value];
        let vb = b[sortKey.value];
        if (typeof va === 'string') va = va.toLowerCase();
        if (typeof vb === 'string') vb = vb.toLowerCase();
        if (va < vb) return sortDir.value === 'asc' ? -1 : 1;
        if (va > vb) return sortDir.value === 'asc' ? 1 : -1;
        return 0;
    });
});

const sortIcon = (key) => {
    if (sortKey.value !== key) return '↕';
    return sortDir.value === 'asc' ? '↑' : '↓';
};

// ── Add card modal ──────────────────────────────────────────────────
const showAddModal   = ref(false);
const imagePreview   = ref(null);

const addForm = useForm({
    item_id:       '',
    name_override: '',
    image:         null,
});

const onImageChange = (e) => {
    addForm.image = e.target.files[0] ?? null;
    imagePreview.value = addForm.image ? URL.createObjectURL(addForm.image) : null;
};

const submitAdd = () => {
    addForm.post(route('admin.mvp-cards.store'), {
        forceFormData: true,
        onSuccess: () => {
            showAddModal.value = false;
            addForm.reset();
            imagePreview.value = null;
        },
    });
};

// ── Holders modal ───────────────────────────────────────────────────
const showHoldersModal = ref(false);
const holdersCard      = ref(null);
const holders          = ref([]);
const holdersLoading   = ref(false);

const openHolders = async (card) => {
    holdersCard.value    = card;
    holders.value        = [];
    holdersLoading.value = true;
    showHoldersModal.value = true;

    try {
        const res = await fetch(route('admin.mvp-cards.holders', card.id));
        const data = await res.json();
        holders.value = data.holders ?? [];
    } finally {
        holdersLoading.value = false;
    }
};

// ── Actions ─────────────────────────────────────────────────────────
const toggle = (card) => router.patch(
    route('admin.mvp-cards.toggle', card.id),
    {},
    { preserveScroll: true, preserveState: false }
);

const destroy = (card) => {
    if (confirm(__('Are you sure you want to delete this card?'))) {
        router.delete(route('admin.mvp-cards.destroy', card.id));
    }
};

const sync = () => router.post(route('admin.mvp-cards.sync'));

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

        <PageHeader :title="__('MvP Cards')" :description="`${stats.total} total · ${stats.active} active · ${stats.inactive} inactive`" class="mb-6">
            <div class="flex items-center gap-2 shrink-0">
                <button @click="sync"
                    class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-rapanel-navy-100 dark:border-white/10 text-sm font-semibold text-rapanel-text-light dark:text-white/70 hover:border-rapanel-blue hover:text-rapanel-blue transition">
                    ↻ {{ __('Import from folder') }}
                </button>
                <button @click="showAddModal = true"
                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-rapanel-blue text-white text-sm font-bold hover:opacity-90 transition">
                    + {{ __('Add Card') }}
                </button>
            </div>
        </PageHeader>

        <!-- ── Table ── -->
        <div class="bg-white dark:bg-[#0f1829] rounded-xl border border-rapanel-navy-100 dark:border-white/[0.07] shadow-[0_4px_20px_rgba(0,0,0,0.22)] dark:shadow-[0_4px_28px_rgba(0,0,0,0.5)] overflow-hidden">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-white/5">
                        <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40 w-14">{{ __('Image') }}</th>
                        <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40 w-24 cursor-pointer select-none hover:text-rapanel-blue transition" @click="setSort('item_id')">
                            {{ __('Item ID') }} <span class="opacity-60">{{ sortIcon('item_id') }}</span>
                        </th>
                        <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40 cursor-pointer select-none hover:text-rapanel-blue transition" @click="setSort('name')">
                            {{ __('Name') }} <span class="opacity-60">{{ sortIcon('name') }}</span>
                        </th>
                        <th class="px-4 py-3 text-center text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40 w-24 cursor-pointer select-none hover:text-rapanel-blue transition" @click="setSort('is_active')">
                            {{ __('Status') }} <span class="opacity-60">{{ sortIcon('is_active') }}</span>
                        </th>
                        <th class="px-4 py-3 text-center text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40 w-24 cursor-pointer select-none hover:text-rapanel-blue transition" @click="setSort('total')">
                            {{ __('Count') }} <span class="opacity-60">{{ sortIcon('total') }}</span>
                        </th>
                        <th class="px-4 py-3 text-right text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40 w-36">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-rapanel-navy-100 dark:divide-white/5">
                    <tr v-for="card in sortedCards" :key="card.id"
                        class="hover:bg-rapanel-navy-50 dark:hover:bg-white/5 transition-colors">

                        <!-- Thumbnail -->
                        <td class="px-4 py-3">
                            <div class="w-10 h-10 rounded-lg overflow-hidden bg-rapanel-navy-50 dark:bg-rapanel-navy-800 flex items-center justify-center shrink-0">
                                <img :src="`/data/mvpcards/${card.image_path}`"
                                    :alt="card.name"
                                    class="w-full h-full object-contain"
                                    @error="$event.target.style.display='none'" />
                            </div>
                        </td>

                        <!-- Item ID -->
                        <td class="px-4 py-3 font-mono text-xs text-rapanel-text-light dark:text-rapanel-text-dark">
                            #{{ card.item_id }}
                        </td>

                        <!-- Name -->
                        <td class="px-4 py-3 font-medium text-rapanel-navy-900 dark:text-white">
                            {{ card.name }}
                        </td>

                        <!-- Status -->
                        <td class="px-4 py-3 text-center">
                            <span :class="[
                                'text-[10px] font-black uppercase px-2 py-0.5 rounded border',
                                card.is_active
                                    ? 'bg-rapanel-success/10 text-rapanel-success border-rapanel-success/20'
                                    : 'bg-rapanel-danger/10 text-rapanel-danger border-rapanel-danger/20'
                            ]">{{ card.is_active ? __('Active') : __('Inactive') }}</span>
                        </td>

                        <!-- Count -->
                        <td class="px-4 py-3 text-center">
                            <span :class="[
                                'font-bold tabular-nums text-sm',
                                card.total > 0
                                    ? 'text-rapanel-navy-900 dark:text-white'
                                    : 'text-rapanel-text-light dark:text-rapanel-text-dark opacity-40'
                            ]">{{ card.total.toLocaleString() }}</span>
                        </td>

                        <!-- Actions -->
                        <td class="px-4 py-3 text-right">
                            <div class="inline-flex items-center gap-3">

                                <!-- Toggle switch -->
                                <button
                                    @click="toggle(card)"
                                    :title="card.is_active ? __('Deactivate') : __('Activate')"
                                    :class="[
                                        'relative inline-flex h-5 w-9 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 focus:outline-none',
                                        card.is_active ? 'bg-rapanel-success' : 'bg-rapanel-navy-100 dark:bg-white/10'
                                    ]">
                                    <span :class="[
                                        'pointer-events-none inline-block h-4 w-4 rounded-full bg-white shadow-sm transform transition-transform duration-200',
                                        card.is_active ? 'translate-x-4' : 'translate-x-0'
                                    ]" />
                                </button>

                                <!-- Holders — gold -->
                                <button @click="openHolders(card)"
                                    :title="__('Holders')"
                                    class="inline-flex items-center justify-center w-7 h-7 rounded-lg border border-rapanel-gold/60 text-rapanel-gold bg-rapanel-gold/10 hover:bg-rapanel-gold/25 transition">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                                    </svg>
                                </button>

                                <!-- Delete -->
                                <button @click="destroy(card)"
                                    :title="__('Delete')"
                                    class="inline-flex items-center justify-center w-7 h-7 rounded-lg border border-rapanel-danger/40 text-rapanel-danger bg-rapanel-danger/10 hover:bg-rapanel-danger/25 transition">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                    </svg>
                                </button>

                            </div>
                        </td>
                    </tr>

                    <tr v-if="!cards.length">
                        <td colspan="6" class="px-4 py-8 text-center text-sm text-rapanel-text-light dark:text-rapanel-text-dark">
                            {{ __('No cards found. Use "Import from folder" or add one manually.') }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- ══════════════════════════════════════
             Modal: Add Card
        ══════════════════════════════════════ -->
        <Teleport to="body">
            <div v-if="showAddModal"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
                @click.self="showAddModal = false">

                <div class="bg-white dark:bg-rapanel-navy-900 rounded-2xl shadow-xl border border-rapanel-navy-100 dark:border-white/[0.07] w-full max-w-md overflow-hidden">
                    <div class="h-[3px] bg-gradient-to-r from-rapanel-blue/70 via-rapanel-blue/30 to-transparent" />
                    <div class="flex items-center justify-between px-6 py-4 border-b border-rapanel-navy-100 dark:border-white/[0.07]">
                        <h2 class="font-bold text-rapanel-navy-900 dark:text-white">{{ __('Add Card') }}</h2>
                        <button @click="showAddModal = false"
                            class="text-rapanel-text-light dark:text-rapanel-text-dark hover:text-rapanel-navy-900 dark:hover:text-white transition text-xl leading-none">×</button>
                    </div>

                    <form @submit.prevent="submitAdd" class="px-6 py-5 space-y-4">

                        <!-- Item ID -->
                        <div>
                            <label class="block text-xs font-bold text-rapanel-text-light dark:text-rapanel-text-dark uppercase tracking-wide mb-1.5">
                                {{ __('Item ID') }} <span class="text-rapanel-danger">*</span>
                            </label>
                            <input v-model="addForm.item_id" type="number" min="1" required
                                class="w-full px-3 py-2 rounded-lg border border-rapanel-navy-100 dark:border-white/10 bg-white dark:bg-rapanel-navy-800 text-rapanel-navy-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-rapanel-blue/50 transition" />
                            <p v-if="addForm.errors.item_id" class="mt-1 text-xs text-rapanel-danger">{{ addForm.errors.item_id }}</p>
                        </div>

                        <!-- Custom name -->
                        <div>
                            <label class="block text-xs font-bold text-rapanel-text-light dark:text-rapanel-text-dark uppercase tracking-wide mb-1.5">
                                {{ __('Custom Name') }} <span class="text-rapanel-text-light dark:text-rapanel-text-dark font-normal normal-case">({{ __('optional') }})</span>
                            </label>
                            <input v-model="addForm.name_override" type="text" maxlength="255"
                                :placeholder="__('Leave blank to use item_db name')"
                                class="w-full px-3 py-2 rounded-lg border border-rapanel-navy-100 dark:border-white/10 bg-white dark:bg-rapanel-navy-800 text-rapanel-navy-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-rapanel-blue/50 transition" />
                        </div>

                        <!-- Image -->
                        <div>
                            <label class="block text-xs font-bold text-rapanel-text-light dark:text-rapanel-text-dark uppercase tracking-wide mb-1.5">
                                {{ __('Card Image') }} <span class="text-rapanel-danger">*</span>
                            </label>
                            <input type="file" accept=".jpg,.jpeg,.png,.webp" required @change="onImageChange"
                                class="w-full text-sm text-rapanel-text-light dark:text-rapanel-text-dark file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-rapanel-blue/10 file:text-rapanel-blue hover:file:bg-rapanel-blue/20 transition" />
                            <p v-if="addForm.errors.image" class="mt-1 text-xs text-rapanel-danger">{{ addForm.errors.image }}</p>

                            <!-- Preview -->
                            <div v-if="imagePreview" class="mt-3 w-20 h-20 rounded-xl overflow-hidden border border-rapanel-navy-100 dark:border-white/10">
                                <img :src="imagePreview" class="w-full h-full object-contain" />
                            </div>
                        </div>

                        <!-- Buttons -->
                        <div class="flex justify-end gap-3 pt-2">
                            <button type="button" @click="showAddModal = false"
                                class="px-4 py-2 rounded-lg border border-rapanel-navy-100 dark:border-white/10 text-sm font-semibold text-rapanel-text-light dark:text-rapanel-text-dark hover:border-rapanel-navy-300 transition">
                                {{ __('Cancel') }}
                            </button>
                            <button type="submit" :disabled="addForm.processing"
                                class="px-4 py-2 rounded-lg bg-rapanel-blue text-white text-sm font-bold hover:opacity-90 transition disabled:opacity-60">
                                {{ addForm.processing ? __('Saving...') : __('Save') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- ══════════════════════════════════════
             Modal: Holders
        ══════════════════════════════════════ -->
        <Teleport to="body">
            <div v-if="showHoldersModal"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
                @click.self="showHoldersModal = false">

                <div class="bg-white dark:bg-rapanel-navy-900 rounded-2xl shadow-xl border border-rapanel-navy-100 dark:border-white/[0.07] w-full max-w-3xl max-h-[80vh] flex flex-col overflow-hidden">

                    <!-- Header -->
                    <div class="shrink-0">
                    <div class="h-[3px] bg-gradient-to-r from-rapanel-gold/70 via-rapanel-gold/30 to-transparent" />
                    <div class="flex items-center justify-between px-6 py-4 border-b border-rapanel-navy-100 dark:border-white/[0.07]">
                        <div class="flex items-center gap-3">
                            <div v-if="holdersCard" class="w-10 h-10 rounded-lg overflow-hidden bg-rapanel-navy-50 dark:bg-rapanel-navy-800">
                                <img :src="`/data/mvpcards/${holdersCard.image_path}`" class="w-full h-full object-contain" @error="$event.target.style.display='none'" />
                            </div>
                            <div>
                                <h2 class="font-bold text-rapanel-navy-900 dark:text-white">{{ __('Holders') }}</h2>
                                <p class="text-xs text-rapanel-text-light dark:text-rapanel-text-dark">{{ holdersCard?.name }}</p>
                            </div>
                        </div>
                        <button @click="showHoldersModal = false"
                            class="text-rapanel-text-light dark:text-rapanel-text-dark hover:text-rapanel-navy-900 dark:hover:text-white transition text-xl leading-none">×</button>
                    </div>
                    </div><!-- /shrink-0 -->

                    <!-- Body -->
                    <div class="overflow-y-auto flex-1">

                        <!-- Loading -->
                        <div v-if="holdersLoading" class="flex items-center justify-center py-16 text-sm text-rapanel-text-light dark:text-rapanel-text-dark">
                            <svg class="animate-spin w-5 h-5 mr-2 text-rapanel-blue" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                            </svg>
                            {{ __('Loading...') }}
                        </div>

                        <!-- Empty -->
                        <div v-else-if="!holders.length" class="flex flex-col items-center justify-center py-16 text-sm text-rapanel-text-light dark:text-rapanel-text-dark">
                            <span class="text-3xl mb-3">🃏</span>
                            {{ __('No holders found.') }}
                        </div>

                        <!-- Table -->
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

                                    <!-- Character -->
                                    <td class="px-4 py-3 font-medium text-rapanel-navy-900 dark:text-white">
                                        {{ h.char_name ?? '—' }}
                                    </td>

                                    <!-- Game Account — link directo al perfil de la cuenta -->
                                    <td class="px-4 py-3">
                                        <a v-if="h.account_id"
                                            :href="route('game-accounts.show', h.account_id)"
                                            target="_blank"
                                            class="inline-flex items-center gap-1 font-mono text-xs text-rapanel-blue hover:underline">
                                            {{ h.userid }}
                                            <svg class="w-3 h-3 opacity-60 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                                            </svg>
                                        </a>
                                        <span v-if="!h.account_id" class="text-rapanel-text-light dark:text-rapanel-text-dark">—</span>
                                    </td>

                                    <!-- Location -->
                                    <td class="px-4 py-3">
                                        <div class="flex flex-col gap-1">
                                            <span :class="['text-[10px] font-bold uppercase px-2 py-0.5 rounded border w-fit', locationBadgeClass(h.location)]">
                                                {{ h.location }}
                                            </span>
                                            <span v-if="h.in_item" class="text-xs text-rapanel-text-light dark:text-rapanel-text-dark italic">
                                                {{ h.in_item }}
                                            </span>
                                        </div>
                                    </td>

                                    <!-- Master Account -->
                                    <td class="px-4 py-3 hidden md:table-cell">
                                        <span v-if="h.master_account" class="text-sm font-medium text-rapanel-navy-900 dark:text-white">
                                            {{ h.master_account }}
                                        </span>
                                        <span v-else class="text-rapanel-text-light dark:text-rapanel-text-dark">—</span>
                                    </td>

                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Footer count -->
                    <div v-if="!holdersLoading && holders.length"
                        class="px-6 py-3 border-t border-rapanel-navy-100 dark:border-white/10 text-xs text-rapanel-text-light dark:text-rapanel-text-dark shrink-0">
                        {{ holders.length }} {{ __('result(s)') }}
                    </div>
                </div>
            </div>
        </Teleport>

    </AdminLayout>
</template>

<script setup>
import { ref, watch } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import {
    ArrowUpTrayIcon,
    ExclamationTriangleIcon,
    TrashIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    dropRates: { type: Object, default: () => ({}) },
});

const page = usePage();
const __ = (key, rep = {}) => {
    let t = page.props.translations?.[key] || key;
    Object.entries(rep).forEach(([k, v]) => { t = t.replace(`:${k}`, v); });
    return t;
};

const categories = [
    {
        key: 'common',
        label: 'Common / ETC',
        description: 'The rate the common items are dropped (items in the ETC tab, besides cards).',
        fields: [
            { key: 'item_rate_common',      label: 'Normal' },
            { key: 'item_rate_common_boss', label: 'Boss' },
            { key: 'item_rate_common_mvp',  label: 'MVP' },
            { key: 'item_drop_common_min',  label: 'Min' },
            { key: 'item_drop_common_max',  label: 'Max' },
        ],
    },
    {
        key: 'heal',
        label: 'Healing',
        description: 'The rate healing items are dropped (HP/SP recovery items in the Usable tab).',
        fields: [
            { key: 'item_rate_heal',      label: 'Normal' },
            { key: 'item_rate_heal_boss', label: 'Boss' },
            { key: 'item_rate_heal_mvp',  label: 'MVP' },
            { key: 'item_drop_heal_min',  label: 'Min' },
            { key: 'item_drop_heal_max',  label: 'Max' },
        ],
    },
    {
        key: 'use',
        label: 'Usable',
        description: 'The rate usable items are dropped (Usable tab, excluding healing items).',
        fields: [
            { key: 'item_rate_use',      label: 'Normal' },
            { key: 'item_rate_use_boss', label: 'Boss' },
            { key: 'item_rate_use_mvp',  label: 'MVP' },
            { key: 'item_drop_use_min',  label: 'Min' },
            { key: 'item_drop_use_max',  label: 'Max' },
        ],
    },
    {
        key: 'equip',
        label: 'Equipment',
        description: 'The rate equipment items are dropped.',
        fields: [
            { key: 'item_rate_equip',      label: 'Normal' },
            { key: 'item_rate_equip_boss', label: 'Boss' },
            { key: 'item_rate_equip_mvp',  label: 'MVP' },
            { key: 'item_drop_equip_min',  label: 'Min' },
            { key: 'item_drop_equip_max',  label: 'Max' },
        ],
    },
    {
        key: 'card',
        label: 'Cards',
        description: 'The rate cards are dropped.',
        fields: [
            { key: 'item_rate_card',      label: 'Normal' },
            { key: 'item_rate_card_boss', label: 'Boss' },
            { key: 'item_rate_card_mvp',  label: 'MVP' },
            { key: 'item_drop_card_min',  label: 'Min' },
            { key: 'item_drop_card_max',  label: 'Max' },
        ],
    },
    {
        key: 'adddrop',
        label: 'Card-granted',
        description: 'The rate items granted by card bonuses (item_bonus baddrop) are dropped.',
        fields: [
            { key: 'item_rate_adddrop', label: 'Rate' },
            { key: 'item_drop_add_min', label: 'Min' },
            { key: 'item_drop_add_max', label: 'Max' },
        ],
    },
    {
        key: 'treasure',
        label: 'Treasure Box',
        description: 'The rate items from treasure boxes are dropped (used by the monster skill "treasure").',
        fields: [
            { key: 'item_rate_treasure',     label: 'Rate' },
            { key: 'item_drop_treasure_min', label: 'Min' },
            { key: 'item_drop_treasure_max', label: 'Max' },
        ],
    },
    {
        key: 'mvp',
        label: 'MVP Drops',
        description: 'The rate items from the MVP drop list are dropped (first item on the MVP drop list).',
        fields: [
            { key: 'item_rate_mvp',      label: 'Rate' },
            { key: 'item_drop_mvp_min',  label: 'Min' },
            { key: 'item_drop_mvp_max',  label: 'Max' },
            { key: 'item_drop_mvp_mode', label: 'Mode' },
        ],
    },
    {
        key: 'caps',
        label: 'Rate Caps',
        description: 'Maximum drop rate regardless of all multipliers above. Set to 0 to disable.',
        fields: [
            { key: 'drop_rate_cap',     label: 'Rate Cap' },
            { key: 'drop_rate_cap_vip', label: 'VIP Rate Cap' },
        ],
    },
];

const allFields = categories.flatMap(c => c.fields);

const dropRateValues = ref({});
const hasDropRates   = ref(false);

const syncDropValues = (rates) => {
    const values = {};
    allFields.forEach(f => { values[f.key] = rates[f.key] ?? ''; });
    dropRateValues.value = values;
    hasDropRates.value   = Object.keys(rates).length > 0;
};

syncDropValues(props.dropRates);
watch(() => props.dropRates, syncDropValues);

// --- Import form ---
const importForm  = useForm({ drops_conf: null });
const onConfFile  = (e) => { importForm.drops_conf = e.target.files[0] ?? null; };
const submitImport = () => {
    importForm.post(route('admin.drop-rates.import'), {
        forceFormData: true,
        onFinish: () => { importForm.drops_conf = null; },
    });
};

// --- Edit form ---
const editForm = useForm({});
const submitEdit = () => {
    editForm
        .transform(() => ({ drop_rates: dropRateValues.value }))
        .post(route('admin.drop-rates.update'));
};

// --- Clear form ---
const confirmClear = ref(false);
const clearForm    = useForm({});
const executeClear = () => {
    clearForm.post(route('admin.drop-rates.clear'), {
        onSuccess: () => { confirmClear.value = false; },
    });
};
</script>

<template>
    <AdminLayout>

        <PageHeader :title="__('Drop Rates')" :description="__('Configure item, MVP and card drop multipliers.')" class="mb-6">
            <div v-if="hasDropRates" class="flex items-center gap-2">
                <button type="submit" form="drop-rates-form" :disabled="editForm.processing"
                    class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-rapanel-blue/40 text-rapanel-blue text-sm font-semibold hover:bg-rapanel-blue/10 disabled:opacity-50 transition">
                    {{ editForm.processing ? __('Saving...') : __('Save Changes') }}
                </button>
                <button type="button" @click="confirmClear = true"
                    class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-rapanel-danger/40 text-rapanel-danger text-sm font-semibold hover:bg-rapanel-danger/10 transition">
                    <TrashIcon class="w-4 h-4" />
                    {{ __('Clear All') }}
                </button>
            </div>
        </PageHeader>

        <!-- Import card -->
        <div class="bg-white dark:bg-rapanel-navy-900 rounded-xl border border-rapanel-navy-100 dark:border-white/10 shadow-sm p-6 mb-6">
            <h2 class="text-sm font-bold text-rapanel-navy-900 dark:text-white uppercase tracking-wider mb-1">
                {{ __('Import drops.conf') }}
            </h2>
            <p class="text-xs text-rapanel-text-light dark:text-rapanel-text-dark opacity-60 mb-4">
                {{ __('Upload your rAthena drops.conf file. Values are automatically saved — no extra step needed.') }}
            </p>
            <form @submit.prevent="submitImport" class="flex flex-wrap items-center gap-3">
                <input type="file" accept=".conf,.txt" @change="onConfFile"
                    class="text-sm text-rapanel-text-light dark:text-rapanel-text-dark file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-rapanel-blue/10 file:text-rapanel-blue hover:file:bg-rapanel-blue/20 cursor-pointer" />
                <button type="submit" :disabled="!importForm.drops_conf || importForm.processing"
                    class="flex items-center gap-2 px-4 py-2 bg-rapanel-blue hover:bg-rapanel-blue/90 disabled:opacity-40 text-white text-sm font-bold rounded-lg transition-colors">
                    <ArrowUpTrayIcon class="w-4 h-4" />
                    {{ importForm.processing ? __('Importing...') : __('Import & Save') }}
                </button>
            </form>
        </div>

        <!-- Category cards -->
        <template v-if="hasDropRates">
            <form id="drop-rates-form" @submit.prevent="submitEdit" class="space-y-5">

                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
                    <div v-for="cat in categories" :key="cat.key"
                        class="bg-white dark:bg-rapanel-navy-900 rounded-xl border border-rapanel-navy-100 dark:border-white/10 shadow-sm overflow-hidden flex flex-col"
                        :class="cat.key === 'caps' ? 'border-rapanel-gold/40 dark:border-rapanel-gold/20' : ''">

                        <!-- Card header -->
                        <div class="px-4 pt-4 pb-3 border-b border-rapanel-navy-100 dark:border-white/5"
                            :class="cat.key === 'caps' ? 'bg-rapanel-gold/5 dark:bg-rapanel-gold/5' : 'bg-rapanel-navy-50/50 dark:bg-rapanel-surface/30'">
                            <p class="text-sm font-bold text-rapanel-navy-900 dark:text-white mb-1">{{ __(cat.label) }}</p>
                            <p class="text-xs text-rapanel-text-light dark:text-rapanel-text-dark opacity-60 leading-relaxed">
                                {{ __(cat.description) }}
                            </p>
                        </div>

                        <!-- Fields -->
                        <div class="px-4 py-3 space-y-2.5 flex-1">
                            <div v-for="field in cat.fields" :key="field.key"
                                class="flex items-center justify-between gap-3">
                                <span class="text-xs font-semibold text-rapanel-navy-900 dark:text-white/70 w-20 shrink-0">
                                    {{ __(field.label) }}
                                </span>
                                <input v-model="dropRateValues[field.key]"
                                    type="number" min="0" max="100000"
                                    class="flex-1 rounded-lg border border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-white/[0.05] px-3 py-1.5 text-sm text-right font-mono text-rapanel-navy-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-rapanel-blue" />
                            </div>
                        </div>

                    </div>
                </div>

                <p class="text-xs text-rapanel-text-light dark:text-rapanel-text-dark opacity-50">
                    {{ __('Values match drops.conf format: 100 = 1x, 1000 = 10x, 10000 = 100x.') }}
                </p>

            </form>
        </template>

        <!-- Empty state -->
        <div v-else class="bg-rapanel-navy-50 dark:bg-rapanel-surface/50 rounded-xl border border-dashed border-rapanel-navy-200 dark:border-white/10 p-10 text-center">
            <ChartBarIcon class="w-10 h-10 mx-auto mb-3 text-rapanel-text-light dark:text-rapanel-text-dark opacity-30" />
            <p class="text-sm font-semibold text-rapanel-navy-900 dark:text-white mb-1">{{ __('No drop rates imported yet') }}</p>
            <p class="text-xs text-rapanel-text-light dark:text-rapanel-text-dark opacity-60">
                {{ __('Upload your drops.conf file above to get started.') }}
            </p>
        </div>

        <!-- Clear confirm modal -->
        <Teleport to="body">
            <div v-if="confirmClear" role="dialog" aria-modal="true" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm">
                <div class="bg-white dark:bg-rapanel-navy-900 rounded-2xl shadow-2xl p-6 max-w-sm w-full mx-4 border border-rapanel-navy-100 dark:border-white/10">
                    <div class="flex items-center gap-3 mb-3">
                        <ExclamationTriangleIcon class="w-6 h-6 text-rapanel-danger shrink-0" />
                        <h3 class="text-base font-bold text-rapanel-navy-900 dark:text-white">{{ __('Are you sure?') }}</h3>
                    </div>
                    <p class="text-sm text-rapanel-text-light dark:text-rapanel-text-dark mb-5">
                        {{ __('This will delete all imported drop rate values. This cannot be undone.') }}
                    </p>
                    <div class="flex gap-3 justify-end">
                        <button type="button" @click="confirmClear = false"
                            class="px-4 py-2 rounded-lg text-sm font-bold border border-rapanel-navy-100 dark:border-white/10 text-rapanel-navy-900 dark:text-white hover:bg-rapanel-navy-50 dark:hover:bg-rapanel-navy-800 transition-colors">
                            {{ __('Cancel') }}
                        </button>
                        <button type="button" @click="executeClear" :disabled="clearForm.processing"
                            class="px-4 py-2 rounded-lg text-sm font-bold bg-rapanel-danger hover:bg-rapanel-danger/90 disabled:opacity-60 text-white transition-colors">
                            {{ clearForm.processing ? __('Clearing...') : __('Confirm') }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

    </AdminLayout>
</template>

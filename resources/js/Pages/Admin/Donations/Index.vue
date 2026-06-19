<script setup>
import { ref, watch } from 'vue';
import { router, Link, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { MagnifyingGlassIcon, PlusIcon, EyeIcon, CheckIcon, XMarkIcon, ChartBarIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    donations: Object,
    filters:   Object,
    totals:    Object,
});

const page = usePage();
const __ = (key, rep = {}) => {
    let t = page.props.translations?.[key] || key;
    Object.entries(rep).forEach(([k, v]) => { t = t.replace(`:${k}`, v); });
    return t;
};

const search    = ref(props.filters?.search ?? '');
const status    = ref(props.filters?.status ?? '');
const platform  = ref(props.filters?.platform ?? '');
const dateFrom  = ref(props.filters?.date_from ?? '');
const dateTo    = ref(props.filters?.date_to ?? '');

let debounce = null;
function applyFilters() {
    router.get(route('admin.donations.index'), {
        search:    search.value || undefined,
        status:    status.value || undefined,
        platform:  platform.value || undefined,
        date_from: dateFrom.value || undefined,
        date_to:   dateTo.value || undefined,
    }, { preserveState: true, replace: true });
}

watch(search, () => { clearTimeout(debounce); debounce = setTimeout(applyFilters, 350); });
watch([status, platform, dateFrom, dateTo], applyFilters);

const statusBadge = (s) => ({
    pending:   'bg-rapanel-gold/10 text-rapanel-gold border-rapanel-gold/20',
    completed: 'bg-rapanel-success/10 text-rapanel-success border-rapanel-success/20',
    failed:    'bg-rapanel-danger/10 text-rapanel-danger border-rapanel-danger/20',
    refunded:  'bg-rapanel-navy-100 dark:bg-rapanel-navy-800 text-rapanel-text-light/70 dark:text-rapanel-text-dark/70 border-transparent',
})[s] ?? '';

function approve(id) {
    router.post(route('admin.donations.approve', id), {}, { preserveScroll: true });
}
function reject(id) {
    if (!confirm(__('Are you sure you want to reject this donation?'))) return;
    router.post(route('admin.donations.reject', id), {}, { preserveScroll: true });
}
</script>

<template>
    <AdminLayout>
        <div class="space-y-6">
            <PageHeader :title="__('Donations')">
                <div class="flex gap-2">
                    <Link :href="route('admin.donations.analytics')"
                        class="inline-flex items-center gap-2 px-3 py-2 border border-rapanel-navy-200 dark:border-white/10 rounded-lg text-sm font-semibold text-rapanel-text-light dark:text-white hover:bg-rapanel-navy-50 dark:hover:bg-white/5 transition">
                        <ChartBarIcon class="w-4 h-4" />
                        {{ __('Analytics') }}
                    </Link>
                    <Link :href="route('admin.donations.create')"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-rapanel-blue text-white rounded-lg text-sm font-semibold hover:opacity-90 transition">
                        <PlusIcon class="w-4 h-4" />
                        {{ __('Add manual donation') }}
                    </Link>
                </div>
            </PageHeader>

            <!-- Summary pills -->
            <div class="flex flex-wrap gap-3">
                <span class="px-3 py-1.5 rounded-full text-xs font-semibold bg-rapanel-navy-100 dark:bg-rapanel-navy-800 text-rapanel-text-light dark:text-rapanel-text-dark">{{ __('All') }}: {{ totals.all }}</span>
                <span class="px-3 py-1.5 rounded-full text-xs font-semibold bg-rapanel-gold/10 text-rapanel-gold">{{ __('Pending') }}: {{ totals.pending }}</span>
                <span class="px-3 py-1.5 rounded-full text-xs font-semibold bg-rapanel-success/10 text-rapanel-success">{{ __('Completed') }}: {{ totals.completed }}</span>
                <span class="px-3 py-1.5 rounded-full text-xs font-semibold bg-rapanel-danger/10 text-rapanel-danger">{{ __('Failed') }}: {{ totals.failed }}</span>
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap gap-3">
                <div class="relative">
                    <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-rapanel-text-light/40 dark:text-rapanel-text-dark/40" />
                    <input v-model="search" type="text" :placeholder="__('Search user...')"
                        class="pl-9 pr-4 py-2 text-sm rounded-lg border border-rapanel-navy-200 dark:border-white/10 bg-white dark:bg-rapanel-navy-900 text-rapanel-text-light dark:text-white focus:outline-none focus:ring-2 focus:ring-rapanel-blue/50 w-52" />
                </div>
                <select v-model="status"
                    class="px-3 py-2 text-sm rounded-lg border border-rapanel-navy-200 dark:border-white/10 bg-white dark:bg-rapanel-navy-900 text-rapanel-text-light dark:text-white focus:outline-none focus:ring-2 focus:ring-rapanel-blue/50">
                    <option value="">{{ __('All statuses') }}</option>
                    <option value="pending">{{ __('Pending') }}</option>
                    <option value="completed">{{ __('Completed') }}</option>
                    <option value="failed">{{ __('Failed') }}</option>
                    <option value="refunded">{{ __('Refunded') }}</option>
                </select>
                <select v-model="platform"
                    class="px-3 py-2 text-sm rounded-lg border border-rapanel-navy-200 dark:border-white/10 bg-white dark:bg-rapanel-navy-900 text-rapanel-text-light dark:text-white focus:outline-none focus:ring-2 focus:ring-rapanel-blue/50">
                    <option value="">{{ __('All platforms') }}</option>
                    <option value="paypal">PayPal</option>
                    <option value="stripe">Stripe</option>
                    <option value="manual">{{ __('Manual') }}</option>
                </select>
                <input v-model="dateFrom" type="date"
                    class="px-3 py-2 text-sm rounded-lg border border-rapanel-navy-200 dark:border-white/10 bg-white dark:bg-rapanel-navy-900 text-rapanel-text-light dark:text-white focus:outline-none focus:ring-2 focus:ring-rapanel-blue/50" />
                <input v-model="dateTo" type="date"
                    class="px-3 py-2 text-sm rounded-lg border border-rapanel-navy-200 dark:border-white/10 bg-white dark:bg-rapanel-navy-900 text-rapanel-text-light dark:text-white focus:outline-none focus:ring-2 focus:ring-rapanel-blue/50" />
            </div>

            <!-- Desktop table -->
            <div class="hidden md:block bg-white dark:bg-rapanel-navy-900 border border-rapanel-navy-100 dark:border-white/10 rounded-xl shadow overflow-hidden">
                <table class="min-w-full divide-y divide-rapanel-navy-100 dark:divide-white/10 text-sm">
                    <thead class="bg-rapanel-navy-100 dark:bg-rapanel-navy-800">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-rapanel-navy-700 dark:text-rapanel-text-dark">#</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-rapanel-navy-700 dark:text-rapanel-text-dark">{{ __('User') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-rapanel-navy-700 dark:text-rapanel-text-dark">{{ __('Package') }}</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-rapanel-navy-700 dark:text-rapanel-text-dark">{{ __('Platform') }}</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-rapanel-navy-700 dark:text-rapanel-text-dark">{{ __('Amount (USD)') }}</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-rapanel-navy-700 dark:text-rapanel-text-dark">CP</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-rapanel-navy-700 dark:text-rapanel-text-dark">{{ __('Status') }}</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-rapanel-navy-700 dark:text-rapanel-text-dark">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-rapanel-navy-50 dark:divide-white/5">
                        <tr v-for="d in donations.data" :key="d.id"
                            class="hover:bg-rapanel-navy-50 dark:hover:bg-white/5 transition">
                            <td class="px-4 py-3 text-rapanel-text-light/50 dark:text-rapanel-text-dark/50 text-xs">#{{ d.id }}</td>
                            <td class="px-4 py-3">
                                <p class="font-medium text-rapanel-navy-900 dark:text-white">{{ d.user_name ?? '—' }}</p>
                                <p class="text-xs text-rapanel-text-light/50 dark:text-rapanel-text-dark/50">{{ d.user_email }}</p>
                            </td>
                            <td class="px-4 py-3 text-rapanel-text-light dark:text-rapanel-text-dark text-xs">{{ d.package_title ?? '—' }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="text-xs font-mono px-2 py-0.5 rounded bg-rapanel-navy-100 dark:bg-rapanel-navy-800 text-rapanel-text-light dark:text-rapanel-text-dark capitalize">{{ d.platform }}</span>
                            </td>
                            <td class="px-4 py-3 text-center font-bold text-rapanel-gold">${{ d.amount_usd.toFixed(2) }}</td>
                            <td class="px-4 py-3 text-center text-rapanel-navy-700 dark:text-white font-semibold">{{ d.cashpoints_awarded.toLocaleString() }}</td>
                            <td class="px-4 py-3 text-center">
                                <span :class="statusBadge(d.status)" class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold border capitalize">
                                    {{ __(d.status.charAt(0).toUpperCase() + d.status.slice(1)) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <Link :href="route('admin.donations.show', d.id)" class="p-1.5 rounded text-rapanel-blue hover:bg-rapanel-blue/10 transition" :title="__('View')">
                                        <EyeIcon class="w-4 h-4" />
                                    </Link>
                                    <button v-if="d.status === 'pending'" @click="approve(d.id)" class="p-1.5 rounded text-rapanel-success hover:bg-rapanel-success/10 transition" :title="__('Approve')">
                                        <CheckIcon class="w-4 h-4" />
                                    </button>
                                    <button v-if="d.status === 'pending'" @click="reject(d.id)" class="p-1.5 rounded text-rapanel-danger hover:bg-rapanel-danger/10 transition" :title="__('Reject')">
                                        <XMarkIcon class="w-4 h-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!donations.data.length">
                            <td colspan="8" class="px-4 py-12 text-center text-rapanel-text-light/50 dark:text-rapanel-text-dark/50">{{ __('No donations yet.') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Mobile cards -->
            <div class="md:hidden space-y-3">
                <div v-for="d in donations.data" :key="d.id"
                    class="bg-white dark:bg-rapanel-navy-900 border border-rapanel-navy-100 dark:border-white/10 rounded-xl p-4 shadow-sm">
                    <div class="flex items-start justify-between gap-2 mb-2">
                        <div>
                            <p class="font-bold text-rapanel-navy-900 dark:text-white text-sm">{{ d.user_name ?? '—' }}</p>
                            <p class="text-xs text-rapanel-text-light/50 dark:text-rapanel-text-dark/50">{{ d.package_title ?? d.platform }}</p>
                        </div>
                        <span :class="statusBadge(d.status)" class="px-2 py-0.5 rounded-full text-xs font-semibold border capitalize flex-shrink-0">
                            {{ __(d.status.charAt(0).toUpperCase() + d.status.slice(1)) }}
                        </span>
                    </div>
                    <div class="flex items-center gap-3 text-sm mb-3">
                        <span class="font-bold text-rapanel-gold">${{ d.amount_usd.toFixed(2) }}</span>
                        <span class="text-rapanel-text-light/40 dark:text-rapanel-text-dark/40">·</span>
                        <span class="text-rapanel-navy-700 dark:text-white font-semibold">{{ d.cashpoints_awarded.toLocaleString() }} CP</span>
                        <span class="text-rapanel-text-light/40 dark:text-rapanel-text-dark/40">·</span>
                        <span class="text-xs text-rapanel-text-light/50 dark:text-rapanel-text-dark/50 capitalize">{{ d.platform }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-rapanel-text-light/40 dark:text-rapanel-text-dark/40">{{ d.created_ago }}</span>
                        <div class="flex gap-2">
                            <Link :href="route('admin.donations.show', d.id)" class="text-rapanel-blue text-xs font-semibold">{{ __('View') }}</Link>
                            <button v-if="d.status === 'pending'" @click="approve(d.id)" class="text-rapanel-success text-xs font-semibold">{{ __('Approve') }}</button>
                            <button v-if="d.status === 'pending'" @click="reject(d.id)" class="text-rapanel-danger text-xs font-semibold">{{ __('Reject') }}</button>
                        </div>
                    </div>
                </div>
                <p v-if="!donations.data.length" class="text-center py-12 text-rapanel-text-light/50 dark:text-rapanel-text-dark/50 text-sm">{{ __('No donations yet.') }}</p>
            </div>

            <!-- Pagination -->
            <div v-if="donations.last_page > 1" class="flex justify-center gap-2">
                <Link v-for="link in donations.links" :key="link.label"
                    :href="link.url ?? '#'"
                    :class="['px-3 py-1.5 text-sm rounded-lg transition',
                        link.active ? 'bg-rapanel-blue text-white font-semibold' : 'text-rapanel-text-light dark:text-rapanel-text-dark hover:bg-rapanel-navy-100 dark:hover:bg-white/10',
                        !link.url ? 'opacity-40 pointer-events-none' : '']"
                    v-html="link.label" />
            </div>
        </div>
    </AdminLayout>
</template>

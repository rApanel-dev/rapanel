<script setup>
import { router, Link, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { ArrowLeftIcon, CheckIcon, XMarkIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    donation: { type: Object, required: true },
});

const page = usePage();
const __ = (key, rep = {}) => {
    let t = page.props.translations?.[key] || key;
    Object.entries(rep).forEach(([k, v]) => { t = t.replace(`:${k}`, v); });
    return t;
};

const statusBadge = (s) => ({
    pending:   'bg-rapanel-gold/10 text-rapanel-gold border-rapanel-gold/20',
    completed: 'bg-rapanel-success/10 text-rapanel-success border-rapanel-success/20',
    failed:    'bg-rapanel-danger/10 text-rapanel-danger border-rapanel-danger/20',
    refunded:  'bg-rapanel-navy-100 dark:bg-rapanel-navy-800 text-rapanel-text-light/70 dark:text-rapanel-text-dark/70 border-transparent',
})[s] ?? '';

function approve() {
    router.post(route('admin.donations.approve', props.donation.id), {}, { preserveScroll: true });
}
function reject() {
    if (!confirm(__('Are you sure you want to reject this donation?'))) return;
    router.post(route('admin.donations.reject', props.donation.id), {}, { preserveScroll: true });
}
</script>

<template>
    <AdminLayout>
        <div class="max-w-2xl space-y-6">
            <!-- Header -->
            <div class="flex items-center gap-4 pb-5 border-b border-rapanel-navy-100 dark:border-white/[0.055]">
                <Link :href="route('admin.donations.index')"
                      class="p-2 rounded-lg hover:bg-rapanel-navy-100 dark:hover:bg-white/[0.07] text-rapanel-text-light/55 dark:text-white/45 transition-colors">
                    <ArrowLeftIcon class="w-5 h-5" />
                </Link>
                <div class="flex-1">
                    <h1 class="text-2xl font-display font-bold tracking-wide text-rapanel-text-light dark:text-white">
                        {{ __('Donation Details') }} #{{ donation.id }}
                    </h1>
                </div>
                <span :class="statusBadge(donation.status)" class="px-3 py-1 rounded-full text-xs font-bold border capitalize">
                    {{ __(donation.status.charAt(0).toUpperCase() + donation.status.slice(1)) }}
                </span>
            </div>

            <!-- Fields -->
            <div class="bg-white dark:bg-rapanel-surface rounded-xl border border-rapanel-navy-100 dark:border-white/10 p-5 shadow-sm">
                <dl class="divide-y divide-rapanel-navy-50 dark:divide-white/5">
                    <div v-for="(value, label) in {
                        [__('User')]:           donation.user_name + (donation.user_email ? ` (${donation.user_email})` : ''),
                        [__('Package')]:        donation.package_title ?? '—',
                        [__('Platform')]:       donation.platform,
                        [__('Transaction ID')]: donation.transaction_id ?? '—',
                        [__('Amount (USD)')]:   `$${donation.amount_usd.toFixed(2)}`,
                        [__('Donation Points awarded')]: donation.cashpoints_awarded.toLocaleString() + ' DP',
                        [__('Approved by')]:    donation.approved_by_name ?? '—',
                        [__('Approved at')]:    donation.approved_at ?? '—',
                        [__('Date')]:           donation.created_at,
                    }" :key="label" class="py-3 flex items-start justify-between gap-4 text-sm">
                        <dt class="text-rapanel-text-light/60 dark:text-rapanel-text-dark/60 font-medium w-36 flex-shrink-0">{{ label }}</dt>
                        <dd class="text-rapanel-navy-900 dark:text-white font-mono text-right break-all">{{ value }}</dd>
                    </div>
                </dl>
            </div>

            <!-- Notes -->
            <div v-if="donation.notes" class="bg-white dark:bg-rapanel-surface rounded-xl border border-rapanel-navy-100 dark:border-white/10 p-5 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wider text-rapanel-text-light/50 dark:text-white/40 mb-2">{{ __('Notes') }}</p>
                <p class="text-sm text-rapanel-text-light dark:text-rapanel-text-dark">{{ donation.notes }}</p>
            </div>

            <!-- Metadata (raw) -->
            <div v-if="donation.metadata" class="bg-white dark:bg-rapanel-surface rounded-xl border border-rapanel-navy-100 dark:border-white/10 p-5 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wider text-rapanel-text-light/50 dark:text-white/40 mb-2">Metadata</p>
                <pre class="text-xs text-rapanel-text-light/80 dark:text-rapanel-text-dark/80 overflow-auto max-h-48 bg-rapanel-navy-50 dark:bg-rapanel-navy-800 p-3 rounded-lg">{{ JSON.stringify(donation.metadata, null, 2) }}</pre>
            </div>

            <!-- Actions -->
            <div v-if="donation.status === 'pending'" class="flex gap-3">
                <button @click="approve"
                    class="flex items-center gap-2 px-5 py-2.5 rounded-xl bg-rapanel-success text-white font-semibold text-sm hover:opacity-90 transition">
                    <CheckIcon class="w-4 h-4" />
                    {{ __('Approve') }}
                </button>
                <button @click="reject"
                    class="flex items-center gap-2 px-5 py-2.5 rounded-xl bg-rapanel-danger text-white font-semibold text-sm hover:opacity-90 transition">
                    <XMarkIcon class="w-4 h-4" />
                    {{ __('Reject') }}
                </button>
            </div>
        </div>
    </AdminLayout>
</template>

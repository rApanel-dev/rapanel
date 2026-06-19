<script setup>
import { ref, computed, onMounted, watch, nextTick } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import {
    Chart,
    LineElement, BarElement, ArcElement, PointElement,
    CategoryScale, LinearScale,
    Tooltip, Legend, Filler,
    LineController, BarController, DoughnutController,
} from 'chart.js';

Chart.register(
    LineElement, BarElement, ArcElement, PointElement,
    CategoryScale, LinearScale,
    Tooltip, Legend, Filler,
    LineController, BarController, DoughnutController,
);

const props = defineProps({
    kpis:           Object,
    monthlyRevenue: Array,
    byPlatform:     Array,
    byPackage:      Array,
    byStatus:       Object,
    topDonors:      Array,
    range:          String,
});

const page = usePage();
const __ = (key, rep = {}) => {
    let t = page.props.translations?.[key] || key;
    Object.entries(rep).forEach(([k, v]) => { t = t.replace(`:${k}`, v); });
    return t;
};

const currentRange = ref(props.range ?? 'year');
function changeRange(r) {
    currentRange.value = r;
    router.get(route('admin.donations.analytics'), { range: r }, { preserveState: true, replace: true });
}

// ── Dark mode helper ──────────────────────────────────────────────────────
const isDark = computed(() => document.documentElement.classList.contains('dark'));
const gridColor  = () => isDark.value ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.06)';
const textColor  = () => isDark.value ? '#94a3b8' : '#64748b';

// ── Chart refs ─────────────────────────────────────────────────────────────
const revenueRef  = ref(null);
const platformRef = ref(null);
const packageRef  = ref(null);
const statusRef   = ref(null);

let revenueChart = null, platformChart = null, packageChart = null, statusChart = null;

function destroyCharts() {
    [revenueChart, platformChart, packageChart, statusChart].forEach(c => c?.destroy());
    revenueChart = platformChart = packageChart = statusChart = null;
}

function buildCharts() {
    destroyCharts();

    // 1. Revenue line chart
    if (revenueRef.value) {
        revenueChart = new Chart(revenueRef.value, {
            type: 'line',
            data: {
                labels: props.monthlyRevenue.map(r => r.month),
                datasets: [{
                    label: __('Revenue (USD)'),
                    data: props.monthlyRevenue.map(r => r.revenue),
                    borderColor: '#F59E0B',
                    backgroundColor: 'rgba(245,158,11,0.12)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointBackgroundColor: '#F59E0B',
                }],
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { color: gridColor() }, ticks: { color: textColor(), maxTicksLimit: 6 } },
                    y: { grid: { color: gridColor() }, ticks: { color: textColor(), callback: v => '$' + v } },
                },
            },
        });
    }

    // 2. Platform doughnut
    const platformColors = { paypal: '#003087', stripe: '#635BFF', manual: '#10B981' };
    if (platformRef.value && props.byPlatform.length) {
        platformChart = new Chart(platformRef.value, {
            type: 'doughnut',
            data: {
                labels: props.byPlatform.map(p => p.platform),
                datasets: [{
                    data: props.byPlatform.map(p => p.revenue),
                    backgroundColor: props.byPlatform.map(p => platformColors[p.platform] ?? '#94a3b8'),
                    borderWidth: 2,
                    borderColor: isDark.value ? '#0f1f3a' : '#ffffff',
                }],
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom', labels: { color: textColor(), padding: 12, font: { size: 12 } } },
                    tooltip: { callbacks: { label: ctx => ` $${ctx.parsed.toFixed(2)}` } },
                },
            },
        });
    }

    // 3. Packages bar (horizontal)
    if (packageRef.value && props.byPackage.length) {
        packageChart = new Chart(packageRef.value, {
            type: 'bar',
            data: {
                labels: props.byPackage.map(p => p.title),
                datasets: [{
                    label: __('Donations'),
                    data: props.byPackage.map(p => p.count),
                    backgroundColor: '#3B82F6',
                    borderRadius: 6,
                }],
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { color: gridColor() }, ticks: { color: textColor() } },
                    y: { grid: { display: false }, ticks: { color: textColor() } },
                },
            },
        });
    }

    // 4. Status doughnut
    const statusColors = { completed: '#10B981', pending: '#F59E0B', failed: '#EF4444', refunded: '#94a3b8' };
    const statusLabels = Object.keys(props.byStatus);
    if (statusRef.value && statusLabels.length) {
        statusChart = new Chart(statusRef.value, {
            type: 'doughnut',
            data: {
                labels: statusLabels.map(s => __(s.charAt(0).toUpperCase() + s.slice(1))),
                datasets: [{
                    data: statusLabels.map(s => props.byStatus[s]),
                    backgroundColor: statusLabels.map(s => statusColors[s] ?? '#94a3b8'),
                    borderWidth: 2,
                    borderColor: isDark.value ? '#0f1f3a' : '#ffffff',
                }],
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom', labels: { color: textColor(), padding: 12, font: { size: 12 } } },
                },
            },
        });
    }
}

onMounted(() => nextTick(buildCharts));
watch(() => props.monthlyRevenue, () => nextTick(buildCharts));

const revChange = computed(() => {
    const pct = props.kpis.revenue_change_pct;
    if (pct === null) return null;
    return { value: Math.abs(pct), up: pct >= 0 };
});
</script>

<template>
    <AdminLayout>
        <div class="space-y-6">
            <PageHeader :title="__('Donation Analytics')">
                <!-- Range selector -->
                <div class="flex gap-1 rounded-xl bg-rapanel-navy-100 dark:bg-rapanel-navy-800 p-1">
                    <button v-for="r in [['month', 'Month'], ['quarter', '3M'], ['year', '1Y'], ['all', 'All']]"
                        :key="r[0]" @click="changeRange(r[0])"
                        :class="['px-3 py-1 rounded-lg text-xs font-semibold transition',
                            currentRange === r[0]
                                ? 'bg-white dark:bg-rapanel-navy-700 text-rapanel-navy-900 dark:text-white shadow'
                                : 'text-rapanel-text-light/60 dark:text-rapanel-text-dark/60 hover:text-rapanel-navy-900 dark:hover:text-white']">
                        {{ __(r[1]) }}
                    </button>
                </div>
            </PageHeader>

            <!-- KPI cards -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Revenue this month -->
                <div class="bg-white dark:bg-rapanel-navy-900 border border-rapanel-navy-100 dark:border-white/10 rounded-xl p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wider text-rapanel-text-light/50 dark:text-rapanel-text-dark/50 mb-1">{{ __('Total revenue') }}</p>
                    <p class="text-2xl font-display font-bold text-rapanel-gold">${{ kpis.revenue_this_month.toFixed(2) }}</p>
                    <p v-if="revChange" :class="['text-xs mt-1 font-semibold', revChange.up ? 'text-rapanel-success' : 'text-rapanel-danger']">
                        {{ revChange.up ? '↑' : '↓' }} {{ revChange.value }}% {{ __('vs last month') }}
                    </p>
                </div>

                <!-- Total CP awarded -->
                <div class="bg-white dark:bg-rapanel-navy-900 border border-rapanel-navy-100 dark:border-white/10 rounded-xl p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wider text-rapanel-text-light/50 dark:text-rapanel-text-dark/50 mb-1">{{ __('Total CashPoints awarded') }}</p>
                    <p class="text-2xl font-display font-bold text-rapanel-navy-900 dark:text-white">{{ kpis.total_cp_awarded.toLocaleString() }}</p>
                    <p class="text-xs text-rapanel-text-light/40 dark:text-rapanel-text-dark/40 mt-1">CP {{ __('total') }}</p>
                </div>

                <!-- Completed this month -->
                <div class="bg-white dark:bg-rapanel-navy-900 border border-rapanel-navy-100 dark:border-white/10 rounded-xl p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wider text-rapanel-text-light/50 dark:text-rapanel-text-dark/50 mb-1">{{ __('Completed donations') }}</p>
                    <p class="text-2xl font-display font-bold text-rapanel-success">{{ kpis.completed_this_month }}</p>
                    <p class="text-xs text-rapanel-text-light/40 dark:text-rapanel-text-dark/40 mt-1">{{ kpis.completed_prev_month }} {{ __('last month') }}</p>
                </div>

                <!-- Average -->
                <div class="bg-white dark:bg-rapanel-navy-900 border border-rapanel-navy-100 dark:border-white/10 rounded-xl p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wider text-rapanel-text-light/50 dark:text-rapanel-text-dark/50 mb-1">{{ __('Average donation') }}</p>
                    <p class="text-2xl font-display font-bold text-rapanel-blue">${{ kpis.avg_donation }}</p>
                    <p class="text-xs text-rapanel-text-light/40 dark:text-rapanel-text-dark/40 mt-1">USD {{ __('average') }}</p>
                </div>
            </div>

            <!-- Charts row 1: Revenue + Platform -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 bg-white dark:bg-rapanel-navy-900 border border-rapanel-navy-100 dark:border-white/10 rounded-xl p-5 shadow-sm">
                    <p class="text-xs font-bold uppercase tracking-wider text-rapanel-text-light/50 dark:text-white/40 mb-4">{{ __('Revenue last 12 months') }}</p>
                    <canvas ref="revenueRef" class="max-h-56" />
                </div>
                <div class="bg-white dark:bg-rapanel-navy-900 border border-rapanel-navy-100 dark:border-white/10 rounded-xl p-5 shadow-sm">
                    <p class="text-xs font-bold uppercase tracking-wider text-rapanel-text-light/50 dark:text-white/40 mb-4">{{ __('Donations by platform') }}</p>
                    <canvas ref="platformRef" class="max-h-48" />
                    <p v-if="!byPlatform.length" class="text-center text-sm text-rapanel-text-light/30 dark:text-rapanel-text-dark/30 py-8">—</p>
                </div>
            </div>

            <!-- Charts row 2: Packages + Status -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 bg-white dark:bg-rapanel-navy-900 border border-rapanel-navy-100 dark:border-white/10 rounded-xl p-5 shadow-sm">
                    <p class="text-xs font-bold uppercase tracking-wider text-rapanel-text-light/50 dark:text-white/40 mb-4">{{ __('Most popular packages') }}</p>
                    <canvas ref="packageRef" class="max-h-48" />
                    <p v-if="!byPackage.length" class="text-center text-sm text-rapanel-text-light/30 dark:text-rapanel-text-dark/30 py-8">—</p>
                </div>
                <div class="bg-white dark:bg-rapanel-navy-900 border border-rapanel-navy-100 dark:border-white/10 rounded-xl p-5 shadow-sm">
                    <p class="text-xs font-bold uppercase tracking-wider text-rapanel-text-light/50 dark:text-white/40 mb-4">{{ __('Transaction status') }}</p>
                    <canvas ref="statusRef" class="max-h-48" />
                </div>
            </div>

            <!-- Top donors table -->
            <div class="bg-white dark:bg-rapanel-navy-900 border border-rapanel-navy-100 dark:border-white/10 rounded-xl shadow overflow-hidden">
                <div class="px-5 py-4 border-b border-rapanel-navy-100 dark:border-white/10">
                    <p class="text-xs font-bold uppercase tracking-wider text-rapanel-text-light/50 dark:text-white/40">{{ __('Top donors') }}</p>
                </div>
                <table class="min-w-full divide-y divide-rapanel-navy-50 dark:divide-white/5 text-sm">
                    <thead class="bg-rapanel-navy-100 dark:bg-rapanel-navy-800">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-rapanel-navy-700 dark:text-rapanel-text-dark">#</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-rapanel-navy-700 dark:text-rapanel-text-dark">{{ __('User') }}</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-rapanel-navy-700 dark:text-rapanel-text-dark">{{ __('Total') }} USD</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-rapanel-navy-700 dark:text-rapanel-text-dark">{{ __('Total') }} CP</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-rapanel-navy-700 dark:text-rapanel-text-dark">{{ __('Last donation') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-rapanel-navy-50 dark:divide-white/5">
                        <tr v-for="(d, i) in topDonors" :key="i" class="hover:bg-rapanel-navy-50 dark:hover:bg-white/5 transition">
                            <td class="px-4 py-3 text-rapanel-text-light/50 dark:text-rapanel-text-dark/50 font-bold">{{ i + 1 }}</td>
                            <td class="px-4 py-3 font-semibold text-rapanel-navy-900 dark:text-white">{{ d.user_name }}</td>
                            <td class="px-4 py-3 text-center font-bold text-rapanel-gold">${{ d.total_usd.toFixed(2) }}</td>
                            <td class="px-4 py-3 text-center text-rapanel-navy-700 dark:text-white">{{ d.total_cp.toLocaleString() }}</td>
                            <td class="px-4 py-3 text-right text-rapanel-text-light/50 dark:text-rapanel-text-dark/50 text-xs">{{ d.last_donation }}</td>
                        </tr>
                        <tr v-if="!topDonors.length">
                            <td colspan="5" class="px-4 py-10 text-center text-rapanel-text-light/40 dark:text-rapanel-text-dark/40">{{ __('No donations yet.') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AdminLayout>
</template>

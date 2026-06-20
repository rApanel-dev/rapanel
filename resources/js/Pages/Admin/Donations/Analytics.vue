<script setup>
import { ref, computed, onMounted, watch, nextTick } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import {
    ShieldCheckIcon, ExclamationTriangleIcon, ArrowTrendingUpIcon,
    ArrowTrendingDownIcon, FireIcon,
} from '@heroicons/vue/24/outline';
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

// ── Financial helpers ─────────────────────────────────────────────────────
const hasFinancial = computed(() => props.kpis.monthly_cost > 0 || props.kpis.monthly_goal > 0);

const bannerColor = computed(() => {
    const pct = props.kpis.cost_covered_pct;
    if (pct === null) return 'blue';
    if (pct >= 100) return 'success';
    if (pct >= 50)  return 'gold';
    return 'danger';
});

const bannerClasses = computed(() => ({
    success: 'bg-rapanel-success/8 border-rapanel-success',
    gold:    'bg-rapanel-gold/8 border-rapanel-gold',
    danger:  'bg-rapanel-danger/8 border-rapanel-danger',
    blue:    'bg-rapanel-blue/8 border-rapanel-blue',
}[bannerColor.value]));

const progressBarColor = computed(() => ({
    success: 'bg-rapanel-success',
    gold:    'bg-rapanel-gold',
    danger:  'bg-rapanel-danger',
    blue:    'bg-rapanel-blue',
}[bannerColor.value]));

const projectionStatus = computed(() => {
    const k = props.kpis;
    if (!k.effective_goal) return null;
    if (k.projected_total >= k.effective_goal) return 'exceeds';
    if (k.will_cover_cost) return 'on_track';
    return 'at_risk';
});

const confidenceLabel = computed(() => ({
    high:   __('High confidence'),
    medium: __('Medium confidence'),
    low:    __('Low confidence — early in cycle'),
}[props.kpis.proj_confidence] ?? ''));

const confidenceColor = computed(() => ({
    high:   'text-rapanel-success',
    medium: 'text-rapanel-gold',
    low:    'text-rapanel-text-light/40 dark:text-rapanel-text-dark/40',
}[props.kpis.proj_confidence] ?? ''));

// 3M vs 6M trend arrow
const trendDir = computed(() => {
    if (props.kpis.avg_3m > props.kpis.avg_6m) return 'up';
    if (props.kpis.avg_3m < props.kpis.avg_6m) return 'down';
    return 'flat';
});

const revChange = computed(() => {
    const pct = props.kpis.revenue_change_pct;
    if (pct === null) return null;
    return { value: Math.abs(pct), up: pct >= 0 };
});

// ── Dark mode ─────────────────────────────────────────────────────────────
const isDark    = computed(() => document.documentElement.classList.contains('dark'));
const gridColor = () => isDark.value ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.06)';
const textColor = () => isDark.value ? '#94a3b8' : '#64748b';

// ── Callout label plugin (doughnuts) ──────────────────────────────────────
function rrPath(ctx, x, y, w, h, r) {
    ctx.beginPath();
    ctx.moveTo(x + r, y); ctx.lineTo(x + w - r, y);
    ctx.arcTo(x + w, y, x + w, y + r, r); ctx.lineTo(x + w, y + h - r);
    ctx.arcTo(x + w, y + h, x + w - r, y + h, r); ctx.lineTo(x + r, y + h);
    ctx.arcTo(x, y + h, x, y + h - r, r); ctx.lineTo(x, y + r);
    ctx.arcTo(x, y, x + r, y, r); ctx.closePath();
}

function makeCalloutPlugin(dark) {
    return {
        id: 'calloutLabels',
        afterDraw(chart) {
            const { ctx, data } = chart;
            const dataset = data.datasets[0];
            const meta    = chart.getDatasetMeta(0);
            const total   = dataset.data.reduce((s, v) => s + v, 0);
            if (!total) return;
            meta.data.forEach((arc, i) => {
                const value = dataset.data[i];
                if (!value || value / total < 0.03) return;
                const pct = ((value / total) * 100).toFixed(1);
                const label = String(data.labels[i]);
                const color = dataset.backgroundColor[i];
                const cx = arc.x, cy = arc.y;
                const mid = (arc.startAngle + arc.endAngle) / 2;
                const cos = Math.cos(mid), sin = Math.sin(mid);
                const r1 = arc.outerRadius;
                const sx = cx + cos * r1, sy = cy + sin * r1;
                const kx = cx + cos * (r1 + 22), ky = cy + sin * (r1 + 22);
                const right = cos >= 0;
                const ex = kx + (right ? 18 : -18), ey = ky;
                ctx.save();
                ctx.font = 'bold 9px system-ui,sans-serif';
                const lw = ctx.measureText(label.toUpperCase()).width;
                ctx.font = 'bold 15px system-ui,sans-serif';
                const pw = ctx.measureText(`${pct}%`).width;
                ctx.font = '9px system-ui,sans-serif';
                const nw = ctx.measureText(`${value}`).width;
                const boxW = Math.max(lw, pw, nw) + 22;
                const boxH = 50;
                const bx = right ? ex : ex - boxW;
                const by = ey - boxH / 2;
                ctx.strokeStyle = color; ctx.lineWidth = 1.5;
                ctx.beginPath(); ctx.moveTo(sx, sy); ctx.lineTo(kx, ky); ctx.lineTo(ex, ey); ctx.stroke();
                ctx.fillStyle = color; ctx.beginPath(); ctx.arc(sx, sy, 3, 0, Math.PI * 2); ctx.fill();
                rrPath(ctx, bx, by, boxW, boxH, 7);
                ctx.fillStyle = dark ? '#0d1f3c' : '#ffffff'; ctx.fill();
                ctx.strokeStyle = color; ctx.lineWidth = 1.5; ctx.stroke();
                const mx = bx + boxW / 2;
                ctx.textAlign = 'center';
                ctx.fillStyle = dark ? 'rgba(255,255,255,0.4)' : 'rgba(15,31,58,0.4)';
                ctx.font = 'bold 8px system-ui,sans-serif'; ctx.textBaseline = 'top';
                ctx.fillText(label.toUpperCase(), mx, by + 7);
                ctx.fillStyle = color; ctx.font = 'bold 15px system-ui,sans-serif'; ctx.textBaseline = 'top';
                ctx.fillText(`${pct}%`, mx, by + 18);
                ctx.fillStyle = dark ? 'rgba(255,255,255,0.4)' : 'rgba(15,31,58,0.4)';
                ctx.font = '9px system-ui,sans-serif'; ctx.textBaseline = 'top';
                ctx.fillText(`${value}`, mx, by + 37);
                ctx.restore();
            });
        },
    };
}

// ── Cost/Goal reference lines plugin ──────────────────────────────────────
function makeCostLinePlugin(cost, goal, costLabel, goalLabel) {
    return {
        id: 'costLine',
        afterDraw(chart) {
            const { ctx, chartArea, scales } = chart;
            if (!scales.y) return;
            const drawLine = (value, color, label) => {
                if (!value) return;
                const y = scales.y.getPixelForValue(value);
                if (y < chartArea.top || y > chartArea.bottom) return;
                ctx.save();
                ctx.setLineDash([6, 4]);
                ctx.strokeStyle = color;
                ctx.lineWidth = 1.5;
                ctx.beginPath();
                ctx.moveTo(chartArea.left, y);
                ctx.lineTo(chartArea.right, y);
                ctx.stroke();
                ctx.setLineDash([]);
                ctx.fillStyle = color;
                ctx.font = 'bold 10px system-ui,sans-serif';
                ctx.textBaseline = 'bottom';
                ctx.fillText(label, chartArea.left + 6, y - 2);
                ctx.restore();
            };
            if (cost > 0) drawLine(cost, '#EF4444', `${costLabel} $${cost}`);
            if (goal > cost) drawLine(goal, '#10B981', `${goalLabel} $${goal}`);
        },
    };
}

// ── Chart refs ────────────────────────────────────────────────────────────
const plRef       = ref(null);
const platformRef = ref(null);
const packageRef  = ref(null);
const statusRef   = ref(null);

let plChart = null, platformChart = null, packageChart = null, statusChart = null;

function destroyCharts() {
    [plChart, platformChart, packageChart, statusChart].forEach(c => c?.destroy());
    plChart = platformChart = packageChart = statusChart = null;
}

function buildCharts() {
    destroyCharts();
    const dark = isDark.value;
    const cost = props.kpis.monthly_cost;
    const goal = props.kpis.monthly_goal;

    // 1. Revenue bar chart — segmentos apilados por zona (rojo/naranja/verde)
    if (plRef.value) {
        const labels      = props.monthlyRevenue.map(r => r.month);
        const revenues    = props.monthlyRevenue.map(r => r.revenue);
        const effectiveG  = goal > cost ? goal : cost;   // meta efectiva
        const hasCost     = cost > 0 || goal > 0;
        const hasOrange   = goal > cost && cost > 0;     // zona naranja solo si goal > cost

        // Suma real de cada barra para el tooltip
        const totalByIndex = revenues;

        let datasets;
        if (!hasCost) {
            datasets = [{
                label: __('Revenue (USD)'),
                data: revenues,
                backgroundColor: 'rgba(59,130,246,0.75)',
                borderRadius: 5,
            }];
        } else {
            // Segmento rojo: de $0 hasta min(revenue, cost)  [base]
            // Segmento naranja: de cost hasta min(revenue, goal)  [solo si goal > cost]
            // Segmento verde: de effectiveG hasta revenue  [punta]
            const redBase   = revenues.map(r => Math.min(r, cost > 0 ? cost : effectiveG));
            const orangeMid = hasOrange
                ? revenues.map(r => Math.max(0, Math.min(r, goal) - cost))
                : null;
            const greenTop  = revenues.map(r => Math.max(0, r - effectiveG));

            datasets = [
                {
                    label: `≤ $${cost} ${__('Cost')}`,
                    data: redBase,
                    backgroundColor: 'rgba(239,68,68,0.72)',
                    stack: 'rev',
                },
                ...(orangeMid ? [{
                    label: `$${cost}–$${goal}`,
                    data: orangeMid,
                    backgroundColor: 'rgba(245,158,11,0.80)',
                    stack: 'rev',
                }] : []),
                {
                    label: `> $${effectiveG} ${__('Goal')}`,
                    data: greenTop,
                    backgroundColor: 'rgba(16,185,129,0.80)',
                    stack: 'rev',
                },
            ];
        }

        plChart = new Chart(plRef.value, {
            type: 'bar',
            plugins: [makeCostLinePlugin(cost, goal, __('Cost'), __('Goal'))],
            data: { labels, datasets },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            title: items => items[0].label,
                            label: () => null,
                            afterBody: items => {
                                const idx = items[0].dataIndex;
                                const rev = totalByIndex[idx];
                                const lines = [`  ${__('Revenue')}: $${rev.toFixed(2)}`];
                                if (hasCost) lines.push(`  ${__('P&L')}: ${rev >= cost ? '+' : ''}$${(rev - cost).toFixed(2)}`);
                                return lines;
                            },
                        },
                    },
                },
                scales: {
                    x: { stacked: true, grid: { color: gridColor() }, ticks: { color: textColor(), maxTicksLimit: 6 } },
                    y: { stacked: true, min: 0, grid: { color: gridColor() }, ticks: { color: textColor(), callback: v => '$' + v } },
                },
            },
        });
    }

    // 2. Platform doughnut
    const platformColors = { paypal: '#003087', stripe: '#635BFF', manual: '#10B981' };
    if (platformRef.value && props.byPlatform.length) {
        const platformTotal = props.byPlatform.reduce((s, p) => s + p.count, 0);
        platformChart = new Chart(platformRef.value, {
            type: 'doughnut',
            plugins: [makeCalloutPlugin(dark)],
            data: {
                labels: props.byPlatform.map(p => p.platform === 'manual' ? __('Manual') : p.platform),
                datasets: [{
                    data: props.byPlatform.map(p => p.count),
                    backgroundColor: props.byPlatform.map(p => platformColors[p.platform] ?? '#94a3b8'),
                    borderWidth: 2,
                    borderColor: dark ? '#0f1f3a' : '#ffffff',
                }],
            },
            options: {
                layout: { padding: 55 },
                responsive: true,
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: ctx => ` ${ctx.label}: ${ctx.parsed} (${((ctx.parsed / platformTotal) * 100).toFixed(1)}%)` } },
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
    const statusLabels  = Object.keys(props.byStatus);
    const statusTotal   = statusLabels.reduce((s, k) => s + props.byStatus[k], 0);
    if (statusRef.value && statusLabels.length) {
        statusChart = new Chart(statusRef.value, {
            type: 'doughnut',
            plugins: [makeCalloutPlugin(dark)],
            data: {
                labels: statusLabels.map(s => __(s.charAt(0).toUpperCase() + s.slice(1))),
                datasets: [{
                    data: statusLabels.map(s => props.byStatus[s]),
                    backgroundColor: statusLabels.map(s => statusColors[s] ?? '#94a3b8'),
                    borderWidth: 2,
                    borderColor: dark ? '#0f1f3a' : '#ffffff',
                }],
            },
            options: {
                layout: { padding: 55 },
                responsive: true,
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: ctx => ` ${ctx.label}: ${ctx.parsed} (${((ctx.parsed / statusTotal) * 100).toFixed(1)}%)` } },
                },
            },
        });
    }
}

onMounted(() => nextTick(buildCharts));
watch(() => props.monthlyRevenue, () => nextTick(buildCharts));
</script>

<template>
    <AdminLayout>
        <div class="space-y-6">
            <PageHeader :title="__('Donation Analytics')">
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

            <!-- ── BLOQUE 0: Banner financiero ─────────────────────────────── -->
            <div v-if="hasFinancial"
                :class="['rounded-xl border p-5 shadow-sm', bannerClasses]">
                <!-- Header row -->
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-2">
                        <ShieldCheckIcon v-if="bannerColor === 'success'" class="w-5 h-5 text-rapanel-success" />
                        <ExclamationTriangleIcon v-else class="w-5 h-5"
                            :class="bannerColor === 'gold' ? 'text-rapanel-gold' : 'text-rapanel-danger'" />
                        <span class="text-xs font-bold uppercase tracking-wider text-rapanel-navy-900 dark:text-white">
                            {{ __('Financial Status') }}
                        </span>
                    </div>
                    <span class="text-xs font-semibold text-rapanel-text-light/60 dark:text-rapanel-text-dark/60">
                        {{ kpis.days_left }} {{ __('days left') }}
                    </span>
                </div>

                <!-- Amounts row -->
                <div class="flex items-baseline gap-2 mb-3 flex-wrap">
                    <span class="text-2xl font-display font-bold text-rapanel-navy-900 dark:text-white">
                        ${{ kpis.revenue_this_month.toFixed(2) }}
                    </span>
                    <span class="text-sm text-rapanel-text-light/60 dark:text-rapanel-text-dark/60">{{ __('raised') }}</span>
                    <span class="text-sm text-rapanel-text-light/40 dark:text-rapanel-text-dark/40">·</span>
                    <span class="text-sm font-semibold text-rapanel-navy-900 dark:text-white">${{ kpis.effective_goal.toFixed(2) }}</span>
                    <span class="text-sm text-rapanel-text-light/60 dark:text-rapanel-text-dark/60">goal</span>
                    <span class="text-sm text-rapanel-text-light/40 dark:text-rapanel-text-dark/40">·</span>
                    <span class="font-bold text-sm"
                        :class="bannerColor === 'success' ? 'text-rapanel-success' : bannerColor === 'gold' ? 'text-rapanel-gold' : 'text-rapanel-danger'">
                        {{ kpis.goal_covered_pct ?? kpis.cost_covered_pct ?? 0 }}%
                    </span>
                </div>

                <!-- Progress bar -->
                <div class="relative h-3 bg-rapanel-navy-100 dark:bg-white/10 rounded-full overflow-hidden mb-3">
                    <div :class="['h-full rounded-full transition-all duration-500', progressBarColor]"
                        :style="{ width: `${Math.min(100, kpis.goal_covered_pct ?? kpis.cost_covered_pct ?? 0)}%` }" />
                    <!-- Cost marker (when goal > cost) -->
                    <div v-if="kpis.monthly_goal > kpis.monthly_cost && kpis.monthly_cost > 0"
                        class="absolute top-0 h-full w-0.5 bg-rapanel-danger/70"
                        :style="{ left: `${Math.min(100, (kpis.monthly_cost / kpis.monthly_goal) * 100)}%` }" />
                </div>

                <!-- Projection row -->
                <div class="flex items-center gap-3 flex-wrap text-xs">
                    <span class="text-rapanel-text-light/60 dark:text-rapanel-text-dark/60">
                        {{ __('Projected Revenue') }}: <strong class="text-rapanel-navy-900 dark:text-white">${{ kpis.projected_total.toFixed(2) }}</strong>
                    </span>
                    <span class="text-rapanel-text-light/40 dark:text-rapanel-text-dark/40">·</span>
                    <span v-if="kpis.will_cover_cost" class="text-rapanel-success font-semibold">✓ {{ __('Will cover costs') }}</span>
                    <span v-else class="text-rapanel-danger font-semibold">✗ {{ __('At risk') }}</span>
                    <template v-if="kpis.monthly_goal > kpis.monthly_cost">
                        <span class="text-rapanel-text-light/40 dark:text-rapanel-text-dark/40">·</span>
                        <span v-if="kpis.will_cover_goal" class="text-rapanel-success font-semibold">✓ {{ __('Will reach goal') }}</span>
                        <span v-else class="text-rapanel-gold font-semibold">${{ kpis.goal_remaining.toFixed(2) }} {{ __('to go') }}</span>
                    </template>
                    <span class="text-rapanel-text-light/40 dark:text-rapanel-text-dark/40">·</span>
                    <span :class="['font-medium', confidenceColor]">{{ confidenceLabel }}</span>
                </div>
            </div>

            <!-- ── BLOQUE 1: KPI cards ──────────────────────────────────────── -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Card 1: Revenue -->
                <div class="bg-white dark:bg-rapanel-navy-900 border border-rapanel-navy-100 dark:border-white/10 rounded-xl p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wider text-rapanel-text-light/50 dark:text-rapanel-text-dark/50 mb-1">{{ __('Total revenue') }}</p>
                    <p class="text-2xl font-display font-bold text-rapanel-gold">${{ kpis.revenue_this_month.toFixed(2) }}</p>
                    <p v-if="revChange" :class="['text-xs mt-1 font-semibold', revChange.up ? 'text-rapanel-success' : 'text-rapanel-danger']">
                        {{ revChange.up ? '↑' : '↓' }} {{ revChange.value }}% {{ __('vs last month') }}
                    </p>
                    <p class="text-xs text-rapanel-text-light/40 dark:text-rapanel-text-dark/40 mt-0.5">
                        ${{ kpis.daily_rate.toFixed(2) }} {{ __('daily avg') }}
                    </p>
                </div>

                <!-- Card 2: P&L (con meta) o Total DP (sin meta) -->
                <div class="bg-white dark:bg-rapanel-navy-900 border border-rapanel-navy-100 dark:border-white/10 rounded-xl p-4 shadow-sm">
                    <template v-if="hasFinancial">
                        <p class="text-xs font-semibold uppercase tracking-wider text-rapanel-text-light/50 dark:text-rapanel-text-dark/50 mb-1">{{ __('P&L this month') }}</p>
                        <p :class="['text-2xl font-display font-bold', kpis.pl_this_month >= 0 ? 'text-rapanel-success' : 'text-rapanel-danger']">
                            {{ kpis.pl_this_month >= 0 ? '+' : '' }}${{ kpis.pl_this_month.toFixed(2) }}
                        </p>
                        <p :class="['text-xs mt-1 font-semibold', kpis.pl_this_month >= 0 ? 'text-rapanel-success' : 'text-rapanel-danger']">
                            {{ kpis.pl_this_month >= 0 ? __('Surplus') : __('Shortfall') }}
                        </p>
                        <p class="text-xs text-rapanel-text-light/40 dark:text-rapanel-text-dark/40 mt-0.5">
                            {{ __('vs. monthly cost') }} ${{ kpis.monthly_cost.toFixed(2) }}
                        </p>
                    </template>
                    <template v-else>
                        <p class="text-xs font-semibold uppercase tracking-wider text-rapanel-text-light/50 dark:text-rapanel-text-dark/50 mb-1">{{ __('Total Donation Points awarded') }}</p>
                        <p class="text-2xl font-display font-bold text-rapanel-navy-900 dark:text-white">{{ kpis.total_cp_awarded.toLocaleString() }}</p>
                        <p class="text-xs text-rapanel-text-light/40 dark:text-rapanel-text-dark/40 mt-1">DP {{ __('total') }}</p>
                    </template>
                </div>

                <!-- Card 3: Cobertura (con meta) o Completadas (sin meta) -->
                <div class="bg-white dark:bg-rapanel-navy-900 border border-rapanel-navy-100 dark:border-white/10 rounded-xl p-4 shadow-sm">
                    <template v-if="hasFinancial">
                        <p class="text-xs font-semibold uppercase tracking-wider text-rapanel-text-light/50 dark:text-rapanel-text-dark/50 mb-1">{{ __('Cost Coverage') }}</p>
                        <div class="flex items-center gap-3">
                            <!-- Mini ring SVG -->
                            <svg class="w-12 h-12 flex-shrink-0 -rotate-90" viewBox="0 0 36 36">
                                <circle cx="18" cy="18" r="14" fill="none" stroke="currentColor"
                                    class="text-rapanel-navy-100 dark:text-white/10" stroke-width="3.5" />
                                <circle cx="18" cy="18" r="14" fill="none"
                                    :stroke="kpis.cost_covered_pct >= 100 ? '#10B981' : kpis.cost_covered_pct >= 50 ? '#F59E0B' : '#EF4444'"
                                    stroke-width="3.5" stroke-linecap="round"
                                    :stroke-dasharray="`${(kpis.cost_covered_pct ?? 0) * 0.879} 87.96`" />
                            </svg>
                            <div>
                                <p class="text-2xl font-display font-bold text-rapanel-navy-900 dark:text-white leading-none">
                                    {{ kpis.cost_covered_pct ?? 0 }}%
                                </p>
                                <p class="text-xs text-rapanel-text-light/40 dark:text-rapanel-text-dark/40 mt-1">
                                    ${{ kpis.revenue_this_month.toFixed(0) }} / ${{ kpis.monthly_cost.toFixed(0) }}
                                </p>
                            </div>
                        </div>
                    </template>
                    <template v-else>
                        <p class="text-xs font-semibold uppercase tracking-wider text-rapanel-text-light/50 dark:text-rapanel-text-dark/50 mb-1">{{ __('Completed donations') }}</p>
                        <p class="text-2xl font-display font-bold text-rapanel-success">{{ kpis.completed_this_month }}</p>
                        <p class="text-xs text-rapanel-text-light/40 dark:text-rapanel-text-dark/40 mt-1">{{ kpis.completed_prev_month }} {{ __('last month') }}</p>
                    </template>
                </div>

                <!-- Card 4: Proyección (con meta) o Avg donation (sin meta) -->
                <div class="bg-white dark:bg-rapanel-navy-900 border border-rapanel-navy-100 dark:border-white/10 rounded-xl p-4 shadow-sm">
                    <template v-if="hasFinancial">
                        <p class="text-xs font-semibold uppercase tracking-wider text-rapanel-text-light/50 dark:text-rapanel-text-dark/50 mb-1">{{ __('Projected end-of-month') }}</p>
                        <p class="text-2xl font-display font-bold text-rapanel-blue">${{ kpis.projected_total.toFixed(2) }}</p>
                        <p :class="['text-xs mt-1 font-semibold', confidenceColor]">{{ confidenceLabel }}</p>
                        <p class="text-xs text-rapanel-text-light/40 dark:text-rapanel-text-dark/40 mt-0.5">
                            {{ kpis.days_left }} {{ __('days left') }}
                        </p>
                    </template>
                    <template v-else>
                        <p class="text-xs font-semibold uppercase tracking-wider text-rapanel-text-light/50 dark:text-rapanel-text-dark/50 mb-1">{{ __('Average donation') }}</p>
                        <p class="text-2xl font-display font-bold text-rapanel-blue">${{ kpis.avg_donation }}</p>
                        <p class="text-xs text-rapanel-text-light/40 dark:text-rapanel-text-dark/40 mt-1">USD {{ __('average') }}</p>
                    </template>
                </div>
            </div>

            <!-- ── BLOQUE 2: P&L chart + platform doughnut ─────────────────── -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 bg-white dark:bg-rapanel-navy-900 border border-rapanel-navy-100 dark:border-white/10 rounded-xl p-5 shadow-sm">
                    <p class="text-xs font-bold uppercase tracking-wider text-rapanel-text-light/50 dark:text-white/40 mb-4">
                        {{ hasFinancial ? __('P&L History (12 months)') : __('Revenue last 12 months') }}
                    </p>
                    <canvas ref="plRef" class="max-h-56" />
                </div>
                <div class="bg-white dark:bg-rapanel-navy-900 border border-rapanel-navy-100 dark:border-white/10 rounded-xl p-5 shadow-sm">
                    <p class="text-xs font-bold uppercase tracking-wider text-rapanel-text-light/50 dark:text-white/40 mb-4">{{ __('Donations by platform') }}</p>
                    <canvas ref="platformRef" class="max-h-48" />
                    <p v-if="!byPlatform.length" class="text-center text-sm text-rapanel-text-light/30 dark:text-rapanel-text-dark/30 py-8">—</p>
                </div>
            </div>

            <!-- ── BLOQUE 3: Cards de contexto financiero ──────────────────── -->
            <div v-if="kpis.monthly_cost > 0" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Card A: Proyección vs meta -->
                <div class="bg-white dark:bg-rapanel-navy-900 border border-rapanel-navy-100 dark:border-white/10 rounded-xl p-4 shadow-sm space-y-3">
                    <p class="text-xs font-semibold uppercase tracking-wider text-rapanel-text-light/50 dark:text-rapanel-text-dark/50">{{ __('Projected Revenue') }} vs Meta</p>
                    <div>
                        <div class="flex justify-between text-xs mb-1.5">
                            <span class="font-semibold text-rapanel-navy-900 dark:text-white">${{ kpis.projected_total.toFixed(2) }}</span>
                            <span class="text-rapanel-text-light/50 dark:text-rapanel-text-dark/50">/ ${{ kpis.effective_goal.toFixed(2) }}</span>
                        </div>
                        <div class="h-2 bg-rapanel-navy-100 dark:bg-white/10 rounded-full overflow-hidden">
                            <div class="h-full rounded-full transition-all"
                                :class="projectionStatus === 'exceeds' ? 'bg-rapanel-success' : projectionStatus === 'on_track' ? 'bg-rapanel-gold' : 'bg-rapanel-danger'"
                                :style="{ width: `${Math.min(100, kpis.effective_goal > 0 ? (kpis.projected_total / kpis.effective_goal) * 100 : 0)}%` }" />
                        </div>
                    </div>
                    <p :class="['text-xs font-semibold', projectionStatus === 'exceeds' ? 'text-rapanel-success' : projectionStatus === 'on_track' ? 'text-rapanel-gold' : 'text-rapanel-danger']">
                        {{ projectionStatus === 'exceeds' ? __('Exceeds goal') : projectionStatus === 'on_track' ? __('On track') : __('At risk') }}
                    </p>
                </div>

                <!-- Card B: Promedio 3M / 6M -->
                <div class="bg-white dark:bg-rapanel-navy-900 border border-rapanel-navy-100 dark:border-white/10 rounded-xl p-4 shadow-sm space-y-3">
                    <p class="text-xs font-semibold uppercase tracking-wider text-rapanel-text-light/50 dark:text-rapanel-text-dark/50">{{ __('3M Average') }} / {{ __('6M Average') }}</p>
                    <div class="flex items-center gap-3">
                        <div class="text-center">
                            <p class="text-xl font-display font-bold text-rapanel-navy-900 dark:text-white">${{ kpis.avg_3m.toFixed(0) }}</p>
                            <p class="text-xs text-rapanel-text-light/40 dark:text-rapanel-text-dark/40">3M</p>
                        </div>
                        <div class="flex-1 text-center text-lg">
                            <ArrowTrendingUpIcon v-if="trendDir === 'up'" class="w-6 h-6 text-rapanel-success mx-auto" />
                            <ArrowTrendingDownIcon v-else-if="trendDir === 'down'" class="w-6 h-6 text-rapanel-danger mx-auto" />
                            <span v-else class="text-rapanel-text-light/30">→</span>
                        </div>
                        <div class="text-center">
                            <p class="text-xl font-display font-bold text-rapanel-navy-900 dark:text-white">${{ kpis.avg_6m.toFixed(0) }}</p>
                            <p class="text-xs text-rapanel-text-light/40 dark:text-rapanel-text-dark/40">6M</p>
                        </div>
                    </div>
                    <p :class="['text-xs font-semibold', kpis.avg_3m >= kpis.monthly_cost ? 'text-rapanel-success' : 'text-rapanel-danger']">
                        {{ kpis.avg_3m >= kpis.monthly_cost ? '✓' : '✗' }}
                        {{ __('vs. monthly cost') }} ${{ kpis.monthly_cost.toFixed(2) }}
                    </p>
                </div>

                <!-- Card C: Racha de cobertura -->
                <div class="bg-white dark:bg-rapanel-navy-900 border border-rapanel-navy-100 dark:border-white/10 rounded-xl p-4 shadow-sm space-y-2">
                    <p class="text-xs font-semibold uppercase tracking-wider text-rapanel-text-light/50 dark:text-rapanel-text-dark/50">{{ __('Coverage Streak') }}</p>
                    <div class="flex items-center gap-3">
                        <FireIcon v-if="kpis.coverage_streak > 0" class="w-8 h-8 text-rapanel-gold flex-shrink-0" />
                        <ShieldCheckIcon v-else class="w-8 h-8 text-rapanel-text-light/20 flex-shrink-0" />
                        <div>
                            <p class="text-3xl font-display font-bold text-rapanel-navy-900 dark:text-white leading-none">
                                {{ kpis.coverage_streak }}
                            </p>
                            <p class="text-xs text-rapanel-text-light/40 dark:text-rapanel-text-dark/40 mt-0.5">
                                {{ kpis.coverage_streak === 1 ? __('month') : __('months') }}
                            </p>
                        </div>
                    </div>
                    <p class="text-xs text-rapanel-text-light/50 dark:text-rapanel-text-dark/50">
                        {{ kpis.coverage_streak > 0 ? __('consecutive months covering costs') : __('No consecutive months') }}
                    </p>
                </div>
            </div>

            <!-- ── BLOQUE 4: Packages + Status ─────────────────────────────── -->
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

            <!-- ── BLOQUE 5: Top donors ─────────────────────────────────────── -->
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
                            <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-rapanel-navy-700 dark:text-rapanel-text-dark">{{ __('Total') }} DP</th>
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

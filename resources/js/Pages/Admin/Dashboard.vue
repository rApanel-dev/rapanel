<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import WorldMap from '@/Components/Admin/WorldMap.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { ArrowTopRightOnSquareIcon } from '@heroicons/vue/24/outline';
import PageHeader from '@/Components/PageHeader.vue';

const props = defineProps({
    stats:        Object,
    mapSeries:    Object,
    topCountries: Array,
});

const page       = usePage();
const __ = (key, rep = {}) => {
    let t = page.props.translations?.[key] || key;
    Object.entries(rep).forEach(([k, v]) => { t = t.replace(`:${k}`, v); });
    return t;
};

const safeRoute = (name) => { try { return route(name); } catch { return '#'; } };

const sv         = computed(() => page.props.serverStatus ?? {});
const serverName = computed(() => page.props.serverName ?? 'rApanel');

const servers = computed(() => [
    { label: 'Login', ok: sv.value.login },
    { label: 'Char',  ok: sv.value.char  },
    { label: 'Map',   ok: sv.value.map   },
    { label: 'Web',   ok: sv.value.web   },
]);

const countryNames = {
    AF:'Afghanistan',AR:'Argentina',AU:'Australia',AT:'Austria',BE:'Belgium',BR:'Brazil',CA:'Canada',
    CL:'Chile',CN:'China',CO:'Colombia',CR:'Costa Rica',CZ:'Czech Republic',DK:'Denmark',DO:'Dominican Republic',
    EC:'Ecuador',EG:'Egypt',FI:'Finland',FR:'France',DE:'Germany',GR:'Greece',GT:'Guatemala',
    HK:'Hong Kong',HU:'Hungary',IN:'India',ID:'Indonesia',IE:'Ireland',IL:'Israel',IT:'Italy',
    JP:'Japan',MY:'Malaysia',MX:'Mexico',MA:'Morocco',NL:'Netherlands',NZ:'New Zealand',NG:'Nigeria',
    NO:'Norway',PA:'Panama',PE:'Peru',PH:'Philippines',PL:'Poland',PT:'Portugal',PR:'Puerto Rico',
    RO:'Romania',RU:'Russia',SA:'Saudi Arabia',SG:'Singapore',ZA:'South Africa',KR:'South Korea',
    ES:'Spain',SE:'Sweden',CH:'Switzerland',TW:'Taiwan',TH:'Thailand',TR:'Turkey',UA:'Ukraine',
    AE:'United Arab Emirates',GB:'United Kingdom',US:'United States',UY:'Uruguay',VE:'Venezuela',VN:'Vietnam',
};
const countryName = (code) => countryNames[code] ?? code;
const barWidth    = (count) => Math.round((count / (props.topCountries?.[0]?.total || 1)) * 100);
</script>

<template>
    <AdminLayout>
        <div class="space-y-5">

            <PageHeader :title="__('Admin Dashboard')" :description="__('Server overview and player distribution')" />

            <!-- ── Status bar ────────────────────────────────────────────────── -->
            <div :class="[
                'flex flex-col sm:flex-row sm:items-center justify-between gap-3 rounded-lg border px-4 py-3',
                sv.online
                    ? 'border-rapanel-success/25 bg-rapanel-success/[0.05]'
                    : 'border-rapanel-danger/30  bg-rapanel-danger/[0.06]',
            ]">
                <div class="flex items-center gap-3">
                    <!-- Pulse dot -->
                    <span class="relative flex h-2.5 w-2.5 shrink-0">
                        <span :class="['motion-safe:animate-ping absolute inset-0 rounded-full opacity-50',
                                       sv.online ? 'bg-rapanel-success' : 'bg-rapanel-danger']" />
                        <span :class="['relative inline-flex h-2.5 w-2.5 rounded-full',
                                       sv.online ? 'bg-rapanel-success' : 'bg-rapanel-danger']" />
                    </span>

                    <div class="flex items-center gap-3 flex-wrap">
                        <span :class="['text-sm font-semibold', sv.online ? 'text-rapanel-success' : 'text-rapanel-danger']">
                            {{ sv.online ? __('Server Online') : __('Server Offline') }}
                        </span>
                        <span class="text-xs text-rapanel-text-light/40 dark:text-white/30">{{ serverName }}</span>

                        <!-- Per-process indicators -->
                        <span class="flex items-center gap-2.5">
                            <span v-for="s in servers" :key="s.label"
                                  :class="['flex items-center gap-1 text-[11px] font-medium',
                                           s.ok ? 'text-rapanel-success/75' : 'text-rapanel-danger/75']">
                                <span :class="['inline-block w-1.5 h-1.5 rounded-full shrink-0',
                                               s.ok ? 'bg-rapanel-success' : 'bg-rapanel-danger']" />
                                {{ s.label }}
                            </span>
                        </span>
                    </div>
                </div>

                <div class="flex items-center gap-5 shrink-0">
                    <div class="flex items-baseline gap-1.5">
                        <span class="text-xl font-bold tabular-nums text-rapanel-text-light dark:text-white">{{ sv.players ?? 0 }}</span>
                        <span class="text-xs text-rapanel-text-light/40 dark:text-white/35">{{ __('online') }}</span>
                        <template v-if="sv.peak">
                            <span class="text-rapanel-text-light/20 dark:text-white/15 text-xs mx-0.5">·</span>
                            <span class="text-sm font-medium tabular-nums text-rapanel-text-light/40 dark:text-white/30">{{ sv.peak }}</span>
                            <span class="text-xs text-rapanel-text-light/30 dark:text-white/25">{{ __('peak') }}</span>
                        </template>
                    </div>
                    <Link :href="safeRoute('console.index')"
                          class="flex items-center gap-1 text-xs font-medium text-rapanel-blue hover:text-rapanel-blue/75 transition-colors whitespace-nowrap">
                        {{ __('Live Console') }}
                        <ArrowTopRightOnSquareIcon class="w-3.5 h-3.5" aria-hidden="true" />
                    </Link>
                </div>
            </div>

            <!-- ── Unified stats panel ────────────────────────────────────────── -->
            <div class="grid grid-cols-2 lg:grid-cols-4 border border-rapanel-navy-100 dark:border-white/[0.08] rounded-xl overflow-hidden bg-white dark:bg-rapanel-navy-800 shadow-sm divide-x divide-y lg:divide-y-0 divide-rapanel-navy-100 dark:divide-white/[0.08]">

                <div class="px-5 py-4">
                    <div class="text-[11px] font-medium text-rapanel-text-light/45 dark:text-white/35 mb-2">{{ __('Master Accounts') }}</div>
                    <div class="text-2xl font-bold tabular-nums text-rapanel-text-light dark:text-white leading-none">{{ stats.total_users }}</div>
                    <div class="text-xs text-rapanel-text-light/40 dark:text-white/30 mt-1.5 tabular-nums">
                        <span class="text-rapanel-success font-medium">{{ stats.active_users }} {{ __('active') }}</span>
                        <span class="mx-1 opacity-40">·</span>
                        <span :class="stats.banned_users > 0 ? 'text-rapanel-danger font-medium' : ''">{{ stats.banned_users }} {{ __('banned') }}</span>
                    </div>
                </div>

                <div class="px-5 py-4">
                    <div class="text-[11px] font-medium text-rapanel-text-light/45 dark:text-white/35 mb-2">{{ __('Game Accounts') }}</div>
                    <div class="text-2xl font-bold tabular-nums text-rapanel-text-light dark:text-white leading-none">{{ stats.total_game_accounts }}</div>
                    <div class="text-xs text-rapanel-text-light/40 dark:text-white/30 mt-1.5 tabular-nums">
                        <span class="font-medium">{{ stats.active_game_accounts }} {{ __('active') }}</span>
                        <span class="mx-1 opacity-40">·</span>
                        {{ stats.total_game_accounts - stats.active_game_accounts }} {{ __('inactive') }}
                    </div>
                </div>

                <div class="px-5 py-4">
                    <div class="text-[11px] font-medium text-rapanel-text-light/45 dark:text-white/35 mb-2">{{ __('Panel Admins') }}</div>
                    <div class="text-2xl font-bold tabular-nums text-rapanel-text-light dark:text-white leading-none">{{ stats.admin_users }}</div>
                    <div class="text-xs text-rapanel-text-light/40 dark:text-white/30 mt-1.5">
                        {{ __('of :n master accounts', { n: stats.total_users }) }}
                    </div>
                </div>

                <div class="px-5 py-4">
                    <div class="text-[11px] font-medium text-rapanel-text-light/45 dark:text-white/35 mb-2">{{ __('Log Activity') }}</div>
                    <div class="text-2xl font-bold tabular-nums text-rapanel-text-light dark:text-white leading-none">{{ stats.logs_today }}</div>
                    <div class="text-xs text-rapanel-text-light/40 dark:text-white/30 mt-1.5 tabular-nums">
                        {{ __('today') }}
                        <span class="mx-1 opacity-40">·</span>
                        {{ stats.logs_total }} {{ __('total') }}
                    </div>
                </div>

            </div>

            <!-- ── Map + Countries ────────────────────────────────────────────── -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

                <!-- Map -->
                <div class="lg:col-span-2 bg-white dark:bg-rapanel-navy-800 rounded-xl border border-rapanel-navy-100 dark:border-white/[0.08] shadow-sm overflow-hidden flex flex-col">
                    <div class="flex items-center justify-between px-5 py-3 border-b border-rapanel-navy-100 dark:border-white/[0.07] shrink-0">
                        <h2 class="text-sm font-semibold text-rapanel-text-light dark:text-white">{{ __('Player Distribution') }}</h2>
                        <span class="text-xs text-rapanel-text-light/35 dark:text-white/25 tabular-nums">
                            {{ stats.total_users }} {{ __('users') }} · {{ topCountries?.length ?? 0 }} {{ __('countries') }}
                        </span>
                    </div>
                    <div class="p-2 flex-1 min-h-0">
                        <WorldMap :series="mapSeries" />
                    </div>
                </div>

                <!-- Right column -->
                <div class="flex flex-col gap-5">

                    <!-- Top Countries -->
                    <div class="flex-1 bg-white dark:bg-rapanel-navy-800 rounded-xl border border-rapanel-navy-100 dark:border-white/[0.08] shadow-sm overflow-hidden">
                        <div class="px-5 py-3 border-b border-rapanel-navy-100 dark:border-white/[0.07]">
                            <h2 class="text-sm font-semibold text-rapanel-text-light dark:text-white">{{ __('Top Countries') }}</h2>
                        </div>
                        <div class="px-5 py-3.5 space-y-2.5">
                            <div v-if="!topCountries?.length" class="text-sm text-rapanel-text-light/40 dark:text-white/35 py-2">
                                {{ __('No country data available.') }}
                            </div>
                            <div v-for="(country, i) in topCountries" :key="country.code">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="text-[11px] tabular-nums text-rapanel-text-light/25 dark:text-white/20 w-4 text-right shrink-0">{{ i + 1 }}</span>
                                    <div class="flex-1 min-w-0 flex items-center gap-1.5">
                                        <span class="text-xs font-medium text-rapanel-text-light dark:text-white truncate">{{ countryName(country.code) }}</span>
                                        <span class="text-[10px] text-rapanel-text-light/30 dark:text-white/25 font-mono shrink-0">{{ country.code }}</span>
                                    </div>
                                    <span class="text-xs font-semibold tabular-nums text-rapanel-blue shrink-0">{{ country.total }}</span>
                                </div>
                                <div class="ml-6 h-px bg-rapanel-navy-100 dark:bg-white/[0.05] rounded-full overflow-hidden">
                                    <div class="h-full bg-rapanel-blue/40 rounded-full transition-all duration-500"
                                         :style="{ width: barWidth(country.total) + '%' }" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Log Activity -->
                    <div class="bg-white dark:bg-rapanel-navy-800 rounded-xl border border-rapanel-navy-100 dark:border-white/[0.08] shadow-sm overflow-hidden">
                        <div class="px-5 py-3 border-b border-rapanel-navy-100 dark:border-white/[0.07]">
                            <h2 class="text-sm font-semibold text-rapanel-text-light dark:text-white">{{ __('Log Activity') }}</h2>
                        </div>
                        <div class="divide-y divide-rapanel-navy-100 dark:divide-white/[0.05]">
                            <div class="px-5 py-3 flex items-center justify-between">
                                <span class="text-xs text-rapanel-text-light/55 dark:text-white/40">{{ __('Actions today') }}</span>
                                <span class="text-base font-bold tabular-nums text-rapanel-text-light dark:text-white">{{ stats.logs_today }}</span>
                            </div>
                            <div class="px-5 py-3 flex items-center justify-between">
                                <span class="text-xs text-rapanel-text-light/55 dark:text-white/40">{{ __('Total logs') }}</span>
                                <span class="text-base font-bold tabular-nums text-rapanel-text-light/50 dark:text-white/40">{{ stats.logs_total }}</span>
                            </div>
                            <div class="px-5 py-2.5">
                                <Link :href="safeRoute('admin.logs.index')"
                                      class="text-xs font-medium text-rapanel-blue hover:text-rapanel-blue/75 transition-colors">
                                    {{ __('View all action logs') }} →
                                </Link>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </AdminLayout>
</template>

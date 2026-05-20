<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import WorldMap from '@/Components/Admin/WorldMap.vue';
import { Link } from '@inertiajs/vue3';
import {
    UsersIcon,
    ComputerDesktopIcon,
    WifiIcon,
    ShieldExclamationIcon,
} from '@heroicons/vue/24/outline';
import PageHeader from '@/Components/PageHeader.vue';
import StatsCard from '@/Components/StatsCard.vue';

const props = defineProps({
    stats:        Object,
    mapSeries:    Object,
    topCountries: Array,
});

const safeRoute = (name) => { try { return route(name); } catch { return '#'; } };

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

const barWidth = (count) => {
    const max = props.topCountries?.[0]?.total || 1;
    return Math.round((count / max) * 100);
};
</script>

<template>
    <AdminLayout>
        <div class="space-y-6">

            <PageHeader title="Admin Dashboard" description="Server overview and player distribution" />

            <!-- Stats grid -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">

                <StatsCard label="Master Accounts" :value="stats.total_users" accent="blue">
                    <template #icon><UsersIcon class="w-5 h-5 text-rapanel-blue" /></template>
                    <template #footer>
                        <div class="flex gap-3 text-xs">
                            <span class="text-rapanel-success font-semibold">{{ stats.active_users }} active</span>
                            <span class="text-rapanel-danger font-semibold">{{ stats.banned_users }} banned</span>
                        </div>
                    </template>
                </StatsCard>

                <StatsCard label="Admins" :value="stats.admin_users" accent="gold">
                    <template #icon><ShieldExclamationIcon class="w-5 h-5 text-rapanel-gold" /></template>
                    <template #footer>
                        <div class="text-xs text-rapanel-text-light/40 dark:text-white/35">Panel administrators</div>
                    </template>
                </StatsCard>

                <StatsCard label="Game Accounts" :value="stats.total_game_accounts" accent="purple">
                    <template #icon><ComputerDesktopIcon class="w-5 h-5 text-rapanel-purple" /></template>
                    <template #footer>
                        <div class="text-xs text-rapanel-success font-semibold">{{ stats.active_game_accounts }} active</div>
                    </template>
                </StatsCard>

                <StatsCard label="Players Online" :value="$page.props.serverStatus.players" accent="green">
                    <template #icon><WifiIcon class="w-5 h-5 text-rapanel-success" /></template>
                    <template #footer>
                        <div :class="$page.props.serverStatus.online ? 'text-rapanel-success' : 'text-rapanel-danger'"
                            class="text-xs font-semibold flex items-center gap-1.5">
                            <span :class="[$page.props.serverStatus.online ? 'bg-rapanel-success animate-pulse' : 'bg-rapanel-danger', 'w-1.5 h-1.5 rounded-full shrink-0']"></span>
                            {{ $page.props.serverStatus.online ? 'Server Online' : 'Server Offline' }}
                        </div>
                    </template>
                </StatsCard>

            </div>

            <!-- World Map + Top Countries -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

                <!-- Map -->
                <div class="lg:col-span-2 bg-white dark:bg-[#0f1829] rounded-xl border border-rapanel-navy-100 dark:border-white/[0.07] shadow-[0_4px_24px_rgba(0,0,0,0.25)] dark:shadow-[0_4px_32px_rgba(0,0,0,0.5)] overflow-hidden flex flex-col">
                    <div class="flex items-center justify-between px-5 py-3 border-b border-rapanel-navy-100 dark:border-white/[0.07] shrink-0 bg-rapanel-navy-50 dark:bg-white/[0.025]">
                        <div>
                            <h2 class="text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/45 dark:text-white/35">Player Distribution</h2>
                            <p class="text-[10px] text-rapanel-text-light/30 dark:text-white/22 mt-0.5">Based on registered country</p>
                        </div>
                        <span class="text-[10px] font-semibold text-rapanel-text-light/35 dark:text-white/25">
                            {{ stats.total_users }} users · {{ topCountries?.length ?? 0 }} countries
                        </span>
                    </div>
                    <div class="p-2 flex-1 min-h-0">
                        <WorldMap :series="mapSeries" />
                    </div>
                </div>

                <!-- Right column -->
                <div class="flex flex-col gap-4">

                    <!-- Top Countries -->
                    <div class="flex-1 bg-white dark:bg-[#0f1829] rounded-xl border border-rapanel-navy-100 dark:border-white/[0.07] shadow-[0_4px_24px_rgba(0,0,0,0.25)] dark:shadow-[0_4px_32px_rgba(0,0,0,0.5)] overflow-hidden">
                        <div class="px-5 py-3 border-b border-rapanel-navy-100 dark:border-white/[0.07] bg-rapanel-navy-50 dark:bg-white/[0.025]">
                            <h2 class="text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/45 dark:text-white/35">Top Countries</h2>
                        </div>
                        <div class="px-5 py-4 space-y-3">
                            <div v-if="!topCountries?.length" class="text-sm text-rapanel-text-light/40 dark:text-white/35">
                                No country data available.
                            </div>
                            <div v-for="(country, i) in topCountries" :key="country.code" class="space-y-1.5">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <span class="text-[10px] font-black text-rapanel-text-light/25 dark:text-white/20 w-4 text-right shrink-0">{{ i + 1 }}</span>
                                        <span class="text-xs font-semibold text-rapanel-text-light dark:text-white truncate">
                                            {{ countryName(country.code) }}
                                        </span>
                                        <span class="text-[10px] text-rapanel-text-light/35 dark:text-white/30 font-mono shrink-0">{{ country.code }}</span>
                                    </div>
                                    <span class="text-xs font-black text-rapanel-blue shrink-0 ml-2">{{ country.total }}</span>
                                </div>
                                <div class="h-1 bg-rapanel-navy-100 dark:bg-white/[0.05] rounded-full overflow-hidden">
                                    <div class="h-full bg-gradient-to-r from-rapanel-blue to-rapanel-blue/60 rounded-full transition-all duration-500"
                                        :style="{ width: barWidth(country.total) + '%' }"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Log Activity -->
                    <div class="bg-white dark:bg-[#0f1829] rounded-xl border border-rapanel-navy-100 dark:border-white/[0.07] shadow-[0_4px_24px_rgba(0,0,0,0.25)] dark:shadow-[0_4px_32px_rgba(0,0,0,0.5)] overflow-hidden">
                        <div class="px-5 py-3 border-b border-rapanel-navy-100 dark:border-white/[0.07] bg-rapanel-navy-50 dark:bg-white/[0.025]">
                            <h2 class="text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/45 dark:text-white/35">Log Activity</h2>
                        </div>
                        <div class="divide-y divide-rapanel-navy-100 dark:divide-white/[0.05]">
                            <div class="px-5 py-3.5 flex items-center justify-between">
                                <span class="text-sm text-rapanel-text-light/60 dark:text-white/45">Actions today</span>
                                <span class="text-xl font-black text-rapanel-text-light dark:text-white tabular-nums">{{ stats.logs_today }}</span>
                            </div>
                            <div class="px-5 py-3.5 flex items-center justify-between">
                                <span class="text-sm text-rapanel-text-light/60 dark:text-white/45">Total logs</span>
                                <span class="text-xl font-black text-rapanel-text-light dark:text-white tabular-nums">{{ stats.logs_total }}</span>
                            </div>
                            <div class="px-5 py-3">
                                <Link :href="safeRoute('admin.logs.index')"
                                    class="w-full flex items-center justify-center text-xs font-bold text-rapanel-blue hover:text-rapanel-blue/80 transition-colors">
                                    View all action logs →
                                </Link>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </AdminLayout>
</template>

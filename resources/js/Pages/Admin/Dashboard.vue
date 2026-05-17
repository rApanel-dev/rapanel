<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Link } from '@inertiajs/vue3';
import {
    UsersIcon,
    ComputerDesktopIcon,
    ClipboardDocumentListIcon,
    WifiIcon,
    ShieldExclamationIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    stats:       Object,
    recentLogs:  Array,
    recentUsers: Array,
});

const safeRoute = (name) => { try { return route(name); } catch { return '#'; } };
const formatDate = (d) => d ? new Date(d).toLocaleString() : '—';

const actionColor = (action) => {
    if (action?.includes('password')) return 'text-amber-400';
    if (action?.includes('delete') || action?.includes('deactivated')) return 'text-rapanel-danger';
    if (action?.includes('created') || action?.includes('linked')) return 'text-rapanel-success';
    if (action?.includes('gender') || action?.includes('reset')) return 'text-purple-400';
    return 'text-rapanel-blue';
};
</script>

<template>
    <AdminLayout>
        <div class="space-y-6">

            <!-- Page title -->
            <div>
                <h1 class="text-2xl font-bold text-rapanel-text-light dark:text-white">Admin Dashboard</h1>
                <p class="text-sm text-rapanel-text-light/50 dark:text-white/40 mt-1">Server overview and recent activity</p>
            </div>

            <!-- Stats grid: 4 cards -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">

                <div class="bg-white dark:bg-rapanel-navy-800 rounded-xl p-4 border border-rapanel-navy-100 dark:border-white/10 shadow-sm">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-bold uppercase tracking-widest text-rapanel-text-light/40 dark:text-white/40">Users</span>
                        <UsersIcon class="w-5 h-5 text-rapanel-blue" />
                    </div>
                    <div class="text-3xl font-black text-rapanel-text-light dark:text-white">{{ stats.total_users }}</div>
                    <div class="flex gap-3 mt-2 text-xs">
                        <span class="text-rapanel-success font-semibold">{{ stats.active_users }} active</span>
                        <span class="text-rapanel-danger font-semibold">{{ stats.banned_users }} banned</span>
                    </div>
                </div>

                <div class="bg-white dark:bg-rapanel-navy-800 rounded-xl p-4 border border-rapanel-navy-100 dark:border-white/10 shadow-sm">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-bold uppercase tracking-widest text-rapanel-text-light/40 dark:text-white/40">Admins</span>
                        <ShieldExclamationIcon class="w-5 h-5 text-rapanel-gold" />
                    </div>
                    <div class="text-3xl font-black text-rapanel-text-light dark:text-white">{{ stats.admin_users }}</div>
                    <div class="text-xs text-rapanel-text-light/40 dark:text-white/40 mt-2">Panel administrators</div>
                </div>

                <div class="bg-white dark:bg-rapanel-navy-800 rounded-xl p-4 border border-rapanel-navy-100 dark:border-white/10 shadow-sm">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-bold uppercase tracking-widest text-rapanel-text-light/40 dark:text-white/40">Game Accounts</span>
                        <ComputerDesktopIcon class="w-5 h-5 text-purple-400" />
                    </div>
                    <div class="text-3xl font-black text-rapanel-text-light dark:text-white">{{ stats.total_game_accounts }}</div>
                    <div class="text-xs text-rapanel-success font-semibold mt-2">{{ stats.active_game_accounts }} active</div>
                </div>

                <div class="bg-white dark:bg-rapanel-navy-800 rounded-xl p-4 border border-rapanel-navy-100 dark:border-white/10 shadow-sm">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-bold uppercase tracking-widest text-rapanel-text-light/40 dark:text-white/40">Players Online</span>
                        <WifiIcon class="w-5 h-5 text-rapanel-success" />
                    </div>
                    <div class="text-3xl font-black text-rapanel-text-light dark:text-white">{{ $page.props.serverStatus.players }}</div>
                    <div :class="$page.props.serverStatus.online ? 'text-rapanel-success' : 'text-rapanel-danger'"
                        class="text-xs font-semibold flex items-center gap-1 mt-2">
                        <span :class="[$page.props.serverStatus.online ? 'bg-rapanel-success animate-pulse' : 'bg-rapanel-danger', 'w-1.5 h-1.5 rounded-full']"></span>
                        {{ $page.props.serverStatus.online ? 'Server Online' : 'Server Offline' }}
                    </div>
                </div>
            </div>

            <!-- Log stats: 2 cards -->
            <div class="grid grid-cols-2 gap-4">
                <div class="bg-white dark:bg-rapanel-navy-800 rounded-xl p-4 border border-rapanel-navy-100 dark:border-white/10 shadow-sm flex items-center gap-4">
                    <ClipboardDocumentListIcon class="w-8 h-8 text-rapanel-blue shrink-0" />
                    <div>
                        <div class="text-2xl font-black text-rapanel-text-light dark:text-white">{{ stats.logs_today }}</div>
                        <div class="text-xs text-rapanel-text-light/40 dark:text-white/40 uppercase tracking-widest font-bold">Actions today</div>
                    </div>
                </div>
                <div class="bg-white dark:bg-rapanel-navy-800 rounded-xl p-4 border border-rapanel-navy-100 dark:border-white/10 shadow-sm flex items-center gap-4">
                    <ClipboardDocumentListIcon class="w-8 h-8 text-rapanel-text-light/20 dark:text-white/20 shrink-0" />
                    <div>
                        <div class="text-2xl font-black text-rapanel-text-light dark:text-white">{{ stats.logs_total }}</div>
                        <div class="text-xs text-rapanel-text-light/40 dark:text-white/40 uppercase tracking-widest font-bold">Total logs</div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity + Recent Registrations -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                <div class="bg-white dark:bg-rapanel-navy-800 rounded-xl border border-rapanel-navy-100 dark:border-white/10 shadow-sm overflow-hidden">
                    <div class="flex items-center justify-between px-5 py-3 border-b border-rapanel-navy-100 dark:border-white/10">
                        <h2 class="text-xs font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40">Recent Activity</h2>
                        <Link :href="safeRoute('admin.logs.index')" class="text-xs text-rapanel-blue hover:underline">View all</Link>
                    </div>
                    <div class="divide-y divide-rapanel-navy-100 dark:divide-white/5">
                        <div v-if="!recentLogs?.length" class="px-5 py-4 text-sm text-rapanel-text-light/40 dark:text-white/40">
                            No activity yet.
                        </div>
                        <div v-for="log in recentLogs" :key="log.id" class="px-5 py-3">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <span :class="[actionColor(log.action), 'text-xs font-semibold']">{{ log.action }}</span>
                                    <div class="text-xs text-rapanel-text-light/40 dark:text-white/40 truncate">
                                        {{ log.user?.name ?? 'Unknown' }}
                                        <span v-if="log.ip_address"> · {{ log.ip_address }}</span>
                                    </div>
                                </div>
                                <span class="text-[10px] text-rapanel-text-light/30 dark:text-white/30 shrink-0 whitespace-nowrap">
                                    {{ formatDate(log.created_at) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-rapanel-navy-800 rounded-xl border border-rapanel-navy-100 dark:border-white/10 shadow-sm overflow-hidden">
                    <div class="flex items-center justify-between px-5 py-3 border-b border-rapanel-navy-100 dark:border-white/10">
                        <h2 class="text-xs font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40">Recent Registrations</h2>
                        <Link :href="safeRoute('admin.users.index')" class="text-xs text-rapanel-blue hover:underline">View all</Link>
                    </div>
                    <div class="divide-y divide-rapanel-navy-100 dark:divide-white/5">
                        <div v-if="!recentUsers?.length" class="px-5 py-4 text-sm text-rapanel-text-light/40 dark:text-white/40">
                            No users yet.
                        </div>
                        <div v-for="user in recentUsers" :key="user.id"
                            class="px-5 py-3 flex items-center justify-between gap-3 hover:bg-rapanel-navy-50 dark:hover:bg-white/5 transition">
                            <div class="min-w-0">
                                <div class="text-sm font-semibold text-rapanel-text-light dark:text-white truncate">{{ user.name }}</div>
                                <div class="text-xs text-rapanel-text-light/40 dark:text-white/40 truncate">{{ user.email }}</div>
                            </div>
                            <div class="flex flex-col items-end gap-1 shrink-0">
                                <span :class="user.role === 'Admin'
                                    ? 'bg-rapanel-gold/20 text-rapanel-gold'
                                    : 'bg-rapanel-blue/10 text-rapanel-blue',
                                    'text-[10px] font-black uppercase tracking-widest px-2 py-0.5 rounded'">
                                    {{ user.role }}
                                </span>
                                <span :class="user.status ? 'text-rapanel-success' : 'text-rapanel-danger'"
                                    class="text-[10px] font-semibold">
                                    {{ user.status ? 'Active' : 'Banned' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AdminLayout>
</template>

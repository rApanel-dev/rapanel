<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Link } from '@inertiajs/vue3';
import { ArrowLeftIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    user:         Object,
    gameAccounts: Array,
    recentLogs:   Array,
});

const safeRoute = (name, params) => { try { return route(name, params); } catch { return '#'; } };
const formatDate = (d) => d ? new Date(d).toLocaleString() : '—';

const stateLabel = (state) => {
    const map = { 0: 'Active', 1: 'Banned', 5: 'De-linked' };
    return map[state] ?? `State ${state}`;
};
const stateColor = (state) => {
    if (state === 0) return 'bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400';
    if (state === 5) return 'bg-rapanel-navy-100 dark:bg-white/10 text-rapanel-text-light/60 dark:text-white/50';
    return 'bg-red-100 dark:bg-red-900/30 text-red-500 dark:text-red-400';
};

const actionColor = (action) => {
    if (action?.includes('password')) return 'text-amber-400';
    if (action?.includes('delete') || action?.includes('deactivated')) return 'text-red-400';
    if (action?.includes('created') || action?.includes('linked')) return 'text-green-400';
    return 'text-blue-400';
};
</script>

<template>
    <AdminLayout>
        <div class="space-y-5">

            <!-- Header -->
            <div class="flex items-center gap-3">
                <Link :href="safeRoute('admin.users.index')"
                    class="p-2 rounded-lg hover:bg-rapanel-navy-100 dark:hover:bg-white/10 text-rapanel-text-light/60 dark:text-white/50 transition">
                    <ArrowLeftIcon class="w-4 h-4" />
                </Link>
                <div>
                    <h1 class="text-2xl font-bold text-rapanel-text-light dark:text-white">{{ user.name }}</h1>
                    <p class="text-sm text-rapanel-text-light/60 dark:text-white/50">{{ user.email }}</p>
                </div>
                <div class="ml-auto flex gap-2">
                    <span :class="user.role === 'Admin'
                        ? 'bg-rapanel-gold/20 text-rapanel-gold'
                        : 'bg-rapanel-blue/10 text-rapanel-blue',
                        'text-[10px] font-black uppercase tracking-wider px-3 py-1 rounded-full'">
                        {{ user.role }}
                    </span>
                    <span :class="user.status
                        ? 'bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400'
                        : 'bg-red-100 dark:bg-red-900/30 text-red-500 dark:text-red-400',
                        'text-[10px] font-black uppercase tracking-wider px-3 py-1 rounded-full'">
                        {{ user.status ? 'Active' : 'Banned' }}
                    </span>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

                <!-- User info -->
                <div class="bg-white dark:bg-rapanel-navy-800 rounded-xl border border-rapanel-navy-100 dark:border-white/10 shadow-sm p-5 space-y-3">
                    <h2 class="text-xs font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40 mb-4">Profile</h2>
                    <div class="space-y-2.5 text-sm">
                        <div class="flex justify-between gap-2">
                            <span class="text-rapanel-text-light/50 dark:text-white/40">ID</span>
                            <span class="font-mono font-semibold text-rapanel-text-light dark:text-white">{{ user.id }}</span>
                        </div>
                        <div class="flex justify-between gap-2">
                            <span class="text-rapanel-text-light/50 dark:text-white/40">Country</span>
                            <span class="text-rapanel-text-light dark:text-white">{{ user.country ?? '—' }}</span>
                        </div>
                        <div class="flex justify-between gap-2">
                            <span class="text-rapanel-text-light/50 dark:text-white/40">Vote Points</span>
                            <span class="text-rapanel-text-light dark:text-white">{{ user.vote_points ?? 0 }}</span>
                        </div>
                        <div class="flex justify-between gap-2">
                            <span class="text-rapanel-text-light/50 dark:text-white/40">Donation Points</span>
                            <span class="text-rapanel-text-light dark:text-white">{{ user.donation_points ?? 0 }}</span>
                        </div>
                        <div class="flex justify-between gap-2">
                            <span class="text-rapanel-text-light/50 dark:text-white/40">Last Login</span>
                            <span class="text-xs text-rapanel-text-light/60 dark:text-white/50">{{ formatDate(user.last_login) }}</span>
                        </div>
                        <div class="flex justify-between gap-2">
                            <span class="text-rapanel-text-light/50 dark:text-white/40">Last IP</span>
                            <span class="font-mono text-xs text-rapanel-text-light/60 dark:text-white/50">{{ user.last_ip ?? '—' }}</span>
                        </div>
                        <div class="flex justify-between gap-2">
                            <span class="text-rapanel-text-light/50 dark:text-white/40">Joined</span>
                            <span class="text-xs text-rapanel-text-light/60 dark:text-white/50">{{ formatDate(user.created_at) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Game Accounts -->
                <div class="lg:col-span-2 bg-white dark:bg-rapanel-navy-800 rounded-xl border border-rapanel-navy-100 dark:border-white/10 shadow-sm overflow-hidden">
                    <div class="px-5 py-3 border-b border-rapanel-navy-100 dark:border-white/10">
                        <h2 class="text-xs font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40">
                            Game Accounts ({{ gameAccounts.length }})
                        </h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-rapanel-navy-100 dark:border-white/5 bg-rapanel-navy-50 dark:bg-white/5">
                                    <th class="px-4 py-2 text-left text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40">ID</th>
                                    <th class="px-4 py-2 text-left text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40">Username</th>
                                    <th class="px-4 py-2 text-left text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40">Gender</th>
                                    <th class="px-4 py-2 text-left text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40">State</th>
                                    <th class="px-4 py-2 text-left text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40 hidden md:table-cell">Last Login</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-rapanel-navy-100 dark:divide-white/5">
                                <tr v-if="!gameAccounts.length">
                                    <td colspan="5" class="px-4 py-6 text-center text-rapanel-text-light/50 dark:text-white/40">No game accounts linked.</td>
                                </tr>
                                <tr v-for="acc in gameAccounts" :key="acc.account_id"
                                    class="hover:bg-rapanel-navy-50 dark:hover:bg-white/5 transition">
                                    <td class="px-4 py-2.5 font-mono text-xs text-rapanel-text-light/50 dark:text-white/40">{{ acc.account_id }}</td>
                                    <td class="px-4 py-2.5 font-semibold text-rapanel-text-light dark:text-white">{{ acc.userid }}</td>
                                    <td class="px-4 py-2.5 text-rapanel-text-light/60 dark:text-white/60">{{ acc.sex }}</td>
                                    <td class="px-4 py-2.5">
                                        <span :class="[stateColor(acc.state), 'text-[10px] font-black uppercase tracking-wider px-2 py-0.5 rounded']">
                                            {{ stateLabel(acc.state) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2.5 text-xs text-rapanel-text-light/50 dark:text-white/40 hidden md:table-cell">
                                        {{ acc.lastlogin ?? '—' }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Recent Logs -->
            <div class="bg-white dark:bg-rapanel-navy-800 rounded-xl border border-rapanel-navy-100 dark:border-white/10 shadow-sm overflow-hidden">
                <div class="px-5 py-3 border-b border-rapanel-navy-100 dark:border-white/10">
                    <h2 class="text-xs font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40">
                        Recent Activity ({{ recentLogs.length }})
                    </h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-rapanel-navy-100 dark:border-white/5 bg-rapanel-navy-50 dark:bg-white/5">
                                <th class="px-4 py-2 text-left text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40">Date</th>
                                <th class="px-4 py-2 text-left text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40">Category</th>
                                <th class="px-4 py-2 text-left text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40">Action</th>
                                <th class="px-4 py-2 text-left text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40 hidden md:table-cell">IP</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-rapanel-navy-100 dark:divide-white/5">
                            <tr v-if="!recentLogs.length">
                                <td colspan="4" class="px-4 py-6 text-center text-rapanel-text-light/50 dark:text-white/40">No activity found.</td>
                            </tr>
                            <tr v-for="log in recentLogs" :key="log.id"
                                class="hover:bg-rapanel-navy-50 dark:hover:bg-white/5 transition">
                                <td class="px-4 py-2.5 text-xs text-rapanel-text-light/50 dark:text-white/40 whitespace-nowrap">{{ formatDate(log.created_at) }}</td>
                                <td class="px-4 py-2.5 text-xs text-rapanel-text-light/60 dark:text-white/50">{{ log.category }}</td>
                                <td class="px-4 py-2.5">
                                    <span :class="[actionColor(log.action), 'text-xs font-semibold']">{{ log.action }}</span>
                                </td>
                                <td class="px-4 py-2.5 font-mono text-xs text-rapanel-text-light/50 dark:text-white/40 hidden md:table-cell">{{ log.ip_address ?? '—' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </AdminLayout>
</template>

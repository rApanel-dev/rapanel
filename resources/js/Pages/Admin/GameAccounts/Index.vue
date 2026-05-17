<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { MagnifyingGlassIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    accounts: Object,
    filters:  Object,
});

const search = ref(props.filters?.search ?? '');
const state  = ref(props.filters?.state  ?? '');
const gender = ref(props.filters?.gender ?? '');

const safeRoute = (name, params) => { try { return route(name, params); } catch { return '#'; } };

let debounce;
const applyFilters = () => {
    clearTimeout(debounce);
    debounce = setTimeout(() => {
        router.get(safeRoute('admin.game-accounts.index'), {
            search: search.value || undefined,
            state:  state.value  !== '' ? state.value  : undefined,
            gender: gender.value || undefined,
        }, { preserveState: true, replace: true });
    }, 300);
};
watch([search, state, gender], applyFilters);

const stateLabel = (s) => ({ 0: 'Active', 1: 'Banned', 5: 'De-linked' }[s] ?? `State ${s}`);
const stateColor = (s) => {
    if (s === 0) return 'bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400';
    if (s === 5) return 'bg-rapanel-navy-100 dark:bg-white/10 text-rapanel-text-light/60 dark:text-white/50';
    return 'bg-red-100 dark:bg-red-900/30 text-red-500 dark:text-red-400';
};
</script>

<template>
    <AdminLayout>
        <div class="space-y-5">

            <div>
                <h1 class="text-2xl font-bold text-rapanel-text-light dark:text-white">Game Accounts</h1>
                <p class="text-sm text-rapanel-text-light/60 dark:text-white/50 mt-1">{{ accounts.total }} total rAthena accounts</p>
            </div>

            <!-- Filters -->
            <div class="bg-white dark:bg-rapanel-navy-800 rounded-xl border border-rapanel-navy-100 dark:border-white/10 p-4 flex flex-col sm:flex-row gap-3">
                <div class="relative flex-1">
                    <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-rapanel-text-light/40 dark:text-white/30" />
                    <input v-model="search" type="text" placeholder="Search by username or email…"
                        class="w-full pl-9 pr-3 py-2 text-sm bg-rapanel-navy-50 dark:bg-rapanel-navy-700 border border-rapanel-navy-100 dark:border-white/10 rounded-lg text-rapanel-text-light dark:text-white placeholder-rapanel-text-light/30 focus:outline-none focus:ring-2 focus:ring-rapanel-blue" />
                </div>

                <select v-model="state"
                    class="text-sm bg-rapanel-navy-50 dark:bg-rapanel-navy-700 border border-rapanel-navy-100 dark:border-white/10 rounded-lg px-3 py-2 text-rapanel-text-light dark:text-white focus:outline-none focus:ring-2 focus:ring-rapanel-blue">
                    <option value="">All states</option>
                    <option value="0">Active</option>
                    <option value="1">Banned</option>
                    <option value="5">De-linked</option>
                </select>

                <select v-model="gender"
                    class="text-sm bg-rapanel-navy-50 dark:bg-rapanel-navy-700 border border-rapanel-navy-100 dark:border-white/10 rounded-lg px-3 py-2 text-rapanel-text-light dark:text-white focus:outline-none focus:ring-2 focus:ring-rapanel-blue">
                    <option value="">All genders</option>
                    <option value="M">Male</option>
                    <option value="F">Female</option>
                </select>
            </div>

            <!-- Table -->
            <div class="bg-white dark:bg-rapanel-navy-800 rounded-xl border border-rapanel-navy-100 dark:border-white/10 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-white/5">
                                <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40">Acc ID</th>
                                <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40">Username</th>
                                <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40 hidden md:table-cell">Email</th>
                                <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40">Gender</th>
                                <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40">State</th>
                                <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40 hidden lg:table-cell">Last Login</th>
                                <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40 hidden xl:table-cell">Last IP</th>
                                <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40 hidden xl:table-cell">Master User</th>
                                <th class="px-4 py-3 text-right text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-rapanel-navy-100 dark:divide-white/5">
                            <tr v-if="!accounts.data?.length">
                                <td colspan="9" class="px-4 py-8 text-center text-rapanel-text-light/50 dark:text-white/40">No game accounts found.</td>
                            </tr>
                            <tr v-for="acc in accounts.data" :key="acc.account_id"
                                class="hover:bg-rapanel-navy-50 dark:hover:bg-white/5 transition">
                                <td class="px-4 py-3 font-mono text-xs text-rapanel-text-light/50 dark:text-white/40">{{ acc.account_id }}</td>
                                <td class="px-4 py-3 font-semibold text-rapanel-text-light dark:text-white">{{ acc.userid }}</td>
                                <td class="px-4 py-3 text-rapanel-text-light/60 dark:text-white/60 hidden md:table-cell text-xs">{{ acc.email }}</td>
                                <td class="px-4 py-3 text-rapanel-text-light/60 dark:text-white/60">
                                    {{ acc.sex === 'M' ? 'Male' : acc.sex === 'F' ? 'Female' : acc.sex }}
                                </td>
                                <td class="px-4 py-3">
                                    <span :class="[stateColor(acc.state), 'text-[10px] font-black uppercase tracking-wider px-2 py-0.5 rounded']">
                                        {{ stateLabel(acc.state) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-xs text-rapanel-text-light/50 dark:text-white/40 hidden lg:table-cell">
                                    {{ acc.lastlogin ?? '—' }}
                                </td>
                                <td class="px-4 py-3 font-mono text-xs text-rapanel-text-light/50 dark:text-white/40 hidden xl:table-cell">
                                    {{ acc.last_ip ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-xs text-rapanel-text-light/60 dark:text-white/60 hidden xl:table-cell">
                                    {{ acc.master_name ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <Link :href="safeRoute('game-accounts.show', acc.account_id)"
                                        class="text-xs font-semibold text-rapanel-blue hover:underline">
                                        View
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="accounts.last_page > 1"
                    class="flex items-center justify-between px-4 py-3 border-t border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-white/5">
                    <span class="text-xs text-rapanel-text-light/50 dark:text-white/40">
                        {{ accounts.from }}–{{ accounts.to }} of {{ accounts.total }}
                    </span>
                    <div class="flex gap-1">
                        <template v-for="link in accounts.links" :key="link.label">
                            <Link v-if="link.url" :href="link.url" v-html="link.label"
                                :class="[
                                    link.active
                                        ? 'bg-rapanel-blue text-white'
                                        : 'bg-white dark:bg-rapanel-navy-700 text-rapanel-text-light/70 dark:text-white/70 hover:bg-rapanel-navy-100 dark:hover:bg-white/10',
                                    'px-3 py-1.5 rounded text-xs font-medium border border-rapanel-navy-100 dark:border-white/10 transition'
                                ]" />
                            <span v-else v-html="link.label"
                                class="px-3 py-1.5 rounded text-xs text-rapanel-text-light/30 dark:text-white/20" />
                        </template>
                    </div>
                </div>
            </div>

        </div>
    </AdminLayout>
</template>

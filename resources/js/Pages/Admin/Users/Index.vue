<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { MagnifyingGlassIcon, ShieldCheckIcon, NoSymbolIcon, CheckCircleIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    users:   Object,
    filters: Object,
});

const search  = ref(props.filters?.search ?? '');
const role    = ref(props.filters?.role ?? '');
const status  = ref(props.filters?.status ?? '');

const safeRoute = (name, params) => { try { return route(name, params); } catch { return '#'; } };

let debounce;
const applyFilters = () => {
    clearTimeout(debounce);
    debounce = setTimeout(() => {
        router.get(safeRoute('admin.users.index'), {
            search:  search.value || undefined,
            role:    role.value   || undefined,
            status:  status.value !== '' ? status.value : undefined,
        }, { preserveState: true, replace: true });
    }, 300);
};

watch([search, role, status], applyFilters);

const updateRole = (user, newRole) => {
    if (!confirm(`Change ${user.name}'s role to ${newRole}?`)) return;
    router.put(safeRoute('admin.users.role', user.id), { role: newRole }, { preserveScroll: true });
};

const updateStatus = (user, newStatus) => {
    const msg = newStatus ? `Activate ${user.name}?` : `Ban ${user.name}?`;
    if (!confirm(msg)) return;
    router.put(safeRoute('admin.users.status', user.id), { status: newStatus }, { preserveScroll: true });
};

const formatDate = (d) => d ? new Date(d).toLocaleDateString() : '—';
</script>

<template>
    <AdminLayout>
        <div class="space-y-5">

            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-rapanel-text-light dark:text-white">Users</h1>
                    <p class="text-sm text-rapanel-text-light/60 dark:text-white/50 mt-1">
                        {{ users.total }} total panel accounts
                    </p>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white dark:bg-rapanel-navy-800 rounded-xl border border-rapanel-navy-100 dark:border-white/10 p-4 flex flex-col sm:flex-row gap-3">
                <div class="relative flex-1">
                    <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-rapanel-text-light/40 dark:text-white/30" />
                    <input v-model="search" type="text" placeholder="Search by name or email…"
                        class="w-full pl-9 pr-3 py-2 text-sm bg-rapanel-navy-50 dark:bg-rapanel-navy-700 border border-rapanel-navy-100 dark:border-white/10 rounded-lg text-rapanel-text-light dark:text-white placeholder-rapanel-text-light/30 focus:outline-none focus:ring-2 focus:ring-rapanel-blue" />
                </div>

                <select v-model="role"
                    class="text-sm bg-rapanel-navy-50 dark:bg-rapanel-navy-700 border border-rapanel-navy-100 dark:border-white/10 rounded-lg px-3 py-2 text-rapanel-text-light dark:text-white focus:outline-none focus:ring-2 focus:ring-rapanel-blue">
                    <option value="">All roles</option>
                    <option value="User">User</option>
                    <option value="Admin">Admin</option>
                </select>

                <select v-model="status"
                    class="text-sm bg-rapanel-navy-50 dark:bg-rapanel-navy-700 border border-rapanel-navy-100 dark:border-white/10 rounded-lg px-3 py-2 text-rapanel-text-light dark:text-white focus:outline-none focus:ring-2 focus:ring-rapanel-blue">
                    <option value="">All statuses</option>
                    <option value="1">Active</option>
                    <option value="0">Banned</option>
                </select>
            </div>

            <!-- Table -->
            <div class="bg-white dark:bg-rapanel-navy-800 rounded-xl border border-rapanel-navy-100 dark:border-white/10 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-white/5">
                                <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40">ID</th>
                                <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40">Name</th>
                                <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40 hidden md:table-cell">Email</th>
                                <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40">Role</th>
                                <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40">Status</th>
                                <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40 hidden lg:table-cell">Country</th>
                                <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40 hidden lg:table-cell">Joined</th>
                                <th class="px-4 py-3 text-right text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-rapanel-navy-100 dark:divide-white/5">
                            <tr v-if="!users.data?.length">
                                <td colspan="8" class="px-4 py-8 text-center text-rapanel-text-light/50 dark:text-white/40">No users found.</td>
                            </tr>
                            <tr v-for="user in users.data" :key="user.id"
                                class="hover:bg-rapanel-navy-50 dark:hover:bg-white/5 transition">
                                <td class="px-4 py-3 text-rapanel-text-light/50 dark:text-white/40 font-mono text-xs">{{ user.id }}</td>
                                <td class="px-4 py-3 font-semibold text-rapanel-text-light dark:text-white">{{ user.name }}</td>
                                <td class="px-4 py-3 text-rapanel-text-light/60 dark:text-white/60 hidden md:table-cell">{{ user.email }}</td>

                                <!-- Role badge + toggle -->
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-1.5">
                                        <span :class="user.role === 'Admin'
                                            ? 'bg-rapanel-gold/20 text-rapanel-gold border-rapanel-gold/30'
                                            : 'bg-rapanel-blue/10 text-rapanel-blue border-rapanel-blue/20',
                                            'text-[10px] font-black uppercase tracking-wider px-2 py-0.5 rounded border'">
                                            {{ user.role }}
                                        </span>
                                        <button
                                            @click="updateRole(user, user.role === 'Admin' ? 'User' : 'Admin')"
                                            :title="user.role === 'Admin' ? 'Demote to User' : 'Promote to Admin'"
                                            class="p-1 rounded hover:bg-rapanel-navy-100 dark:hover:bg-white/10 text-rapanel-text-light/40 hover:text-rapanel-gold transition">
                                            <ShieldCheckIcon class="w-3.5 h-3.5" />
                                        </button>
                                    </div>
                                </td>

                                <!-- Status badge + toggle -->
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-1.5">
                                        <span :class="user.status
                                            ? 'bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 border-green-200 dark:border-green-700/30'
                                            : 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 border-red-200 dark:border-red-700/30',
                                            'text-[10px] font-black uppercase tracking-wider px-2 py-0.5 rounded border'">
                                            {{ user.status ? 'Active' : 'Banned' }}
                                        </span>
                                        <button
                                            @click="updateStatus(user, user.status ? 0 : 1)"
                                            :title="user.status ? 'Ban user' : 'Activate user'"
                                            :class="user.status ? 'hover:text-red-400' : 'hover:text-green-500'"
                                            class="p-1 rounded hover:bg-rapanel-navy-100 dark:hover:bg-white/10 text-rapanel-text-light/40 transition">
                                            <NoSymbolIcon v-if="user.status" class="w-3.5 h-3.5" />
                                            <CheckCircleIcon v-else class="w-3.5 h-3.5" />
                                        </button>
                                    </div>
                                </td>

                                <td class="px-4 py-3 text-rapanel-text-light/60 dark:text-white/50 hidden lg:table-cell">
                                    {{ user.country ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-rapanel-text-light/50 dark:text-white/40 text-xs hidden lg:table-cell">
                                    {{ formatDate(user.created_at) }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <Link :href="safeRoute('admin.users.show', user.id)"
                                        class="text-xs font-semibold text-rapanel-blue hover:underline">
                                        View
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="users.last_page > 1"
                    class="flex items-center justify-between px-4 py-3 border-t border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-white/5">
                    <span class="text-xs text-rapanel-text-light/50 dark:text-white/40">
                        {{ users.from }}–{{ users.to }} of {{ users.total }}
                    </span>
                    <div class="flex gap-1">
                        <template v-for="link in users.links" :key="link.label">
                            <Link v-if="link.url"
                                :href="link.url"
                                v-html="link.label"
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

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { router, useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { MagnifyingGlassIcon, ShieldCheckIcon } from '@heroicons/vue/24/outline';
import PageHeader from '@/Components/PageHeader.vue';
import DataTable from '@/Components/DataTable.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import ActionButton from '@/Components/ActionButton.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    users:   Object,
    filters: Object,
});

const search  = ref(props.filters?.search ?? '');
const role    = ref(props.filters?.role ?? '');
const status  = ref(props.filters?.status ?? '');

const safeRoute = (name, params) => { try { return route(name, params); } catch { return '#'; } };

const dt = (v) => v ? v.replace('T', ' ').replace(/\.\d+Z?$/, '') : '—';;

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

// ── Role toggle ─────────────────────────────────────────────────────────
const updateRole = (user, newRole) => {
    if (!confirm(`Change ${user.name}'s role to ${newRole}?`)) return;
    router.put(safeRoute('admin.users.role', user.id), { role: newRole }, { preserveScroll: true });
};

// ── Status toggle ────────────────────────────────────────────────────────
const updateStatus = (user, newStatus) => {
    const msg = newStatus ? `Activate ${user.name}?` : `Ban ${user.name}?`;
    if (!confirm(msg)) return;
    router.put(safeRoute('admin.users.status', user.id), { status: newStatus }, { preserveScroll: true });
};

// ── Edit modal ───────────────────────────────────────────────────────────
const editTarget = ref(null);
const editForm   = useForm({
    name:                  '',
    email:                 '',
    country:               '',
    birthdate:             '',
    donation_points:       0,
    vote_points:           0,
    role:                  'User',
    status:                1,
    password:              '',
    password_confirmation: '',
});

const openEdit = (user) => {
    editTarget.value = user;
    editForm.name            = user.name;
    editForm.email           = user.email;
    editForm.country         = user.country ?? '';
    editForm.birthdate       = user.birthdate ?? '';
    editForm.donation_points = user.donation_points ?? 0;
    editForm.vote_points     = user.vote_points ?? 0;
    editForm.role            = user.role;
    editForm.status          = user.status;
    editForm.password        = '';
    editForm.password_confirmation = '';
    editForm.clearErrors();
};
const closeEdit = () => { editTarget.value = null; editForm.reset(); };
const submitEdit = () => {
    editForm.put(safeRoute('admin.users.update', editTarget.value.id), {
        preserveScroll: true,
        onSuccess: () => closeEdit(),
    });
};

// ── Clear MFA ────────────────────────────────────────────────────────────
const clearMfa = (user) => {
    if (!confirm(`Clear MFA for ${user.name}?`)) return;
    router.delete(safeRoute('admin.users.clear-mfa', user.id), { preserveScroll: true });
};

</script>

<template>
    <AdminLayout>
        <div class="space-y-5">

            <PageHeader title="Master Accounts">
                <template #description>
                    <span class="font-bold text-rapanel-blue">{{ users.total }}</span> total master accounts created
                </template>
            </PageHeader>

            <!-- Filters -->
            <div class="bg-white dark:bg-[#0f1829] rounded-xl border border-rapanel-navy-100 dark:border-white/[0.08] p-4 flex flex-col sm:flex-row gap-3 shadow-[0_4px_24px_rgba(0,0,0,0.35)]">
                <div class="relative flex-1">
                    <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-rapanel-text-light/40 dark:text-white/30" />
                    <input v-model="search" type="text" placeholder="Name, email, ID, IP, country, birthdate…"
                        class="w-full pl-9 pr-3 py-2 text-sm bg-rapanel-navy-50 dark:bg-white/[0.04] border border-rapanel-navy-100 dark:border-white/[0.08] rounded-lg text-rapanel-text-light dark:text-white placeholder-rapanel-text-light/30 dark:placeholder-white/20 focus:outline-none focus:ring-1 focus:ring-rapanel-blue/50 focus:border-rapanel-blue/50 transition-colors" />
                </div>
                <select v-model="role"
                    class="text-sm bg-rapanel-navy-50 dark:bg-white/[0.04] border border-rapanel-navy-100 dark:border-white/[0.08] rounded-lg px-3 py-2 text-rapanel-text-light dark:text-white focus:outline-none focus:ring-1 focus:ring-rapanel-blue/50 transition-colors">
                    <option value="">All roles</option>
                    <option value="User">User</option>
                    <option value="Admin">Admin</option>
                </select>
                <select v-model="status"
                    class="text-sm bg-rapanel-navy-50 dark:bg-white/[0.04] border border-rapanel-navy-100 dark:border-white/[0.08] rounded-lg px-3 py-2 text-rapanel-text-light dark:text-white focus:outline-none focus:ring-1 focus:ring-rapanel-blue/50 transition-colors">
                    <option value="">All statuses</option>
                    <option value="1">Active</option>
                    <option value="0">Banned</option>
                </select>
            </div>

            <DataTable :paginator="users">
                <thead>
                    <tr class="border-b border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-white/5">
                        <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40">ID</th>
                        <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40">Name</th>
                        <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40 hidden md:table-cell">Email</th>
                        <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40">Role</th>
                        <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40">Status</th>
                        <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40 hidden lg:table-cell">Country</th>
                        <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40 hidden xl:table-cell">Birthdate</th>
                        <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40 hidden xl:table-cell">Last Login</th>
                        <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40 hidden xl:table-cell">Last IP</th>
                        <th class="px-4 py-3 text-center text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40 hidden lg:table-cell">Donate</th>
                        <th class="px-4 py-3 text-center text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40 hidden lg:table-cell">Votes</th>
                        <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40 hidden lg:table-cell">Joined</th>
                        <th class="px-4 py-3 text-right text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-rapanel-navy-100 dark:divide-white/5">
                    <tr v-if="!users.data?.length">
                        <td colspan="13" class="px-4 py-8 text-center text-rapanel-text-light/50 dark:text-white/40">No users found.</td>
                    </tr>
                    <tr v-for="user in users.data" :key="user.id"
                        class="hover:bg-rapanel-navy-50 dark:hover:bg-white/5 transition">

                        <td class="px-4 py-3 text-rapanel-text-light/50 dark:text-white/40 font-mono text-xs">{{ user.id }}</td>
                        <td class="px-4 py-3 font-semibold text-rapanel-text-light dark:text-white">{{ user.name }}</td>
                        <td class="px-4 py-3 text-rapanel-text-light/60 dark:text-white/60 hidden md:table-cell text-xs">{{ user.email }}</td>

                        <!-- Role badge -->
                        <td class="px-4 py-3">
                            <span v-if="user.role === 'Admin'"
                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-rapanel-gold/15 border border-rapanel-gold/40 text-rapanel-gold text-[10px] font-black uppercase tracking-wider">
                                <ShieldCheckIcon class="w-3 h-3" />
                                Admin
                            </span>
                            <span v-else class="text-sm text-rapanel-text-light/60 dark:text-white/50">
                                User
                            </span>
                        </td>

                        <!-- Status badge -->
                        <td class="px-4 py-3">
                            <StatusBadge
                                :variant="user.status ? 'success' : 'danger'"
                                :label="user.status ? 'Active' : 'Banned'"
                                size="sm"
                            />
                        </td>

                        <td class="px-4 py-3 text-rapanel-text-light/60 dark:text-white/50 text-xs hidden lg:table-cell">{{ user.country ?? '—' }}</td>
                        <td class="px-4 py-3 text-rapanel-text-light/50 dark:text-white/40 text-xs hidden xl:table-cell">{{ dt(user.birthdate) }}</td>
                        <td class="px-4 py-3 text-rapanel-text-light/50 dark:text-white/40 text-xs hidden xl:table-cell whitespace-nowrap">{{ dt(user.last_login) }}</td>
                        <td class="px-4 py-3 font-mono text-rapanel-text-light/50 dark:text-white/40 text-xs hidden xl:table-cell">{{ user.last_ip ?? '—' }}</td>

                        <td class="px-4 py-3 text-center text-xs font-semibold text-rapanel-text-light dark:text-white hidden lg:table-cell">
                            {{ (user.donation_points ?? 0).toLocaleString() }}
                        </td>
                        <td class="px-4 py-3 text-center text-xs font-semibold text-rapanel-text-light dark:text-white hidden lg:table-cell">
                            {{ (user.vote_points ?? 0).toLocaleString() }}
                        </td>

                        <td class="px-4 py-3 text-rapanel-text-light/50 dark:text-white/40 text-xs hidden lg:table-cell">{{ dt(user.created_at) }}</td>

                        <!-- Actions -->
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-1">
                                <!-- 1. View -->
                                <ActionButton variant="blue" size="icon" title="View dashboard"
                                    @click="router.visit(safeRoute('dashboard') + '?as=' + user.id)">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.964-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                </ActionButton>
                                <!-- 2. Edit -->
                                <ActionButton variant="navy" size="icon" title="Edit user" @click="openEdit(user)">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/></svg>
                                </ActionButton>
                                <!-- 3. Promote / Demote -->
                                <ActionButton variant="gold" size="icon"
                                    :title="user.role === 'Admin' ? 'Demote to User' : 'Promote to Admin'"
                                    @click="updateRole(user, user.role === 'Admin' ? 'User' : 'Admin')">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/></svg>
                                </ActionButton>
                                <!-- 4. Ban / Activate -->
                                <ActionButton
                                    :variant="user.status ? 'danger' : 'success'"
                                    size="icon"
                                    :title="user.status ? 'Ban user' : 'Activate user'"
                                    @click="updateStatus(user, user.status ? 0 : 1)">
                                    <svg v-if="user.status" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                    <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </ActionButton>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </DataTable>

        </div>
    </AdminLayout>

    <!-- ── Modal: Edit User ── -->
    <Modal :show="!!editTarget" @close="closeEdit" maxWidth="2xl">
        <div class="bg-white dark:bg-rapanel-navy-900 max-h-[90vh] overflow-y-auto">
            <!-- Header -->
            <div class="px-6 pt-6 pb-4 border-b border-rapanel-navy-100 dark:border-white/10 sticky top-0 bg-white dark:bg-rapanel-navy-900 z-10">
                <h2 class="text-lg font-display font-bold tracking-wide text-rapanel-navy-900 dark:text-white">
                    Edit User — <span class="text-rapanel-blue">{{ editTarget?.name }}</span>
                </h2>
                <p class="text-xs text-rapanel-text-light/40 dark:text-white/30 mt-0.5">ID: {{ editTarget?.id }}</p>
            </div>

            <form @submit.prevent="submitEdit" class="p-6 space-y-6">

                <!-- § 1: Identity -->
                <div>
                    <p class="text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/40 dark:text-white/30 mb-3">Identity</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <InputLabel value="Name" class="text-xs font-bold uppercase text-rapanel-text-light dark:text-rapanel-text-dark" />
                            <TextInput v-model="editForm.name" type="text" required autofocus class="mt-1 block w-full dark:bg-rapanel-navy-900" />
                            <InputError class="mt-1" :message="editForm.errors.name" />
                        </div>
                        <div>
                            <InputLabel value="Email" class="text-xs font-bold uppercase text-rapanel-text-light dark:text-rapanel-text-dark" />
                            <TextInput v-model="editForm.email" type="email" required class="mt-1 block w-full dark:bg-rapanel-navy-900" />
                            <InputError class="mt-1" :message="editForm.errors.email" />
                        </div>
                        <div>
                            <InputLabel value="Country (2-letter code)" class="text-xs font-bold uppercase text-rapanel-text-light dark:text-rapanel-text-dark" />
                            <TextInput v-model="editForm.country" type="text" maxlength="2" placeholder="CL" class="mt-1 block w-full dark:bg-rapanel-navy-900 uppercase" />
                            <InputError class="mt-1" :message="editForm.errors.country" />
                        </div>
                        <div>
                            <InputLabel value="Birthdate" class="text-xs font-bold uppercase text-rapanel-text-light dark:text-rapanel-text-dark" />
                            <TextInput v-model="editForm.birthdate" type="date" class="mt-1 block w-full dark:bg-rapanel-navy-900" />
                            <InputError class="mt-1" :message="editForm.errors.birthdate" />
                        </div>
                    </div>
                </div>

                <!-- § 2: Points -->
                <div class="border-t border-rapanel-navy-100 dark:border-white/[0.06] pt-5">
                    <p class="text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/40 dark:text-white/30 mb-3">Points</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <InputLabel value="Donation Points" class="text-xs font-bold uppercase text-rapanel-text-light dark:text-rapanel-text-dark" />
                            <TextInput v-model="editForm.donation_points" type="number" min="0" class="mt-1 block w-full dark:bg-rapanel-navy-900" />
                            <InputError class="mt-1" :message="editForm.errors.donation_points" />
                        </div>
                        <div>
                            <InputLabel value="Vote Points" class="text-xs font-bold uppercase text-rapanel-text-light dark:text-rapanel-text-dark" />
                            <TextInput v-model="editForm.vote_points" type="number" min="0" class="mt-1 block w-full dark:bg-rapanel-navy-900" />
                            <InputError class="mt-1" :message="editForm.errors.vote_points" />
                        </div>
                    </div>
                </div>

                <!-- § 3: Permissions -->
                <div class="border-t border-rapanel-navy-100 dark:border-white/[0.06] pt-5">
                    <p class="text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/40 dark:text-white/30 mb-3">Permissions</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <InputLabel value="Role" class="text-xs font-bold uppercase text-rapanel-text-light dark:text-rapanel-text-dark" />
                            <select v-model="editForm.role" class="mt-1 block w-full text-sm bg-white dark:bg-rapanel-navy-900 border border-rapanel-navy-100 dark:border-white/10 rounded-lg px-3 py-2 text-rapanel-text-light dark:text-white focus:outline-none focus:ring-1 focus:ring-rapanel-blue/50 transition-colors">
                                <option value="User">User</option>
                                <option value="Admin">Admin</option>
                            </select>
                            <InputError class="mt-1" :message="editForm.errors.role" />
                        </div>
                        <div>
                            <InputLabel value="Status" class="text-xs font-bold uppercase text-rapanel-text-light dark:text-rapanel-text-dark" />
                            <select v-model="editForm.status" class="mt-1 block w-full text-sm bg-white dark:bg-rapanel-navy-900 border border-rapanel-navy-100 dark:border-white/10 rounded-lg px-3 py-2 text-rapanel-text-light dark:text-white focus:outline-none focus:ring-1 focus:ring-rapanel-blue/50 transition-colors">
                                <option :value="1">Active</option>
                                <option :value="0">Banned</option>
                            </select>
                            <InputError class="mt-1" :message="editForm.errors.status" />
                        </div>
                    </div>
                </div>

                <!-- § 4: Security / MFA -->
                <div class="border-t border-rapanel-navy-100 dark:border-white/[0.06] pt-5">
                    <p class="text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/40 dark:text-white/30 mb-3">Security</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- MFA status + clear -->
                        <div class="flex flex-col gap-1.5">
                            <span class="text-xs font-bold uppercase text-rapanel-text-light dark:text-rapanel-text-dark">Two-Factor Auth (MFA)</span>
                            <div class="flex items-center gap-3 mt-1">
                                <span :class="editTarget?.has_two_factor
                                    ? 'bg-rapanel-success/10 text-rapanel-success border-rapanel-success/30'
                                    : 'bg-white/5 text-rapanel-text-light/40 dark:text-white/30 border-white/10',
                                    'text-[10px] font-black uppercase tracking-wider px-2 py-0.5 rounded border'">
                                    {{ editTarget?.has_two_factor ? 'Active' : 'Not set' }}
                                </span>
                                <button v-if="editTarget?.has_two_factor" type="button"
                                    @click="clearMfa(editTarget)"
                                    class="text-xs font-semibold text-rapanel-danger hover:underline">
                                    Clear MFA
                                </button>
                            </div>
                        </div>
                        <!-- Email verified -->
                        <div>
                            <InputLabel value="Email Verified At" class="text-xs font-bold uppercase text-rapanel-text-light dark:text-rapanel-text-dark" />
                            <input :value="dt(editTarget?.email_verified_at) || 'Not verified'" disabled
                                class="mt-1 block w-full text-sm px-3 py-2 rounded-lg bg-rapanel-navy-50 dark:bg-white/[0.03] border border-rapanel-navy-100 dark:border-white/[0.06] text-rapanel-text-light/50 dark:text-white/30 cursor-not-allowed" />
                        </div>
                    </div>
                </div>

                <!-- § 5: System info (read-only) -->
                <div class="border-t border-rapanel-navy-100 dark:border-white/[0.06] pt-5">
                    <p class="text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/40 dark:text-white/30 mb-3">System Info <span class="normal-case font-normal tracking-normal text-rapanel-text-light/30 dark:text-white/20">(read-only)</span></p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div v-for="field in [
                            { label: 'Last IP',        value: editTarget?.last_ip },
                            { label: 'Last Login',     value: dt(editTarget?.last_login) },
                            { label: 'Created At',     value: dt(editTarget?.created_at) },
                            { label: 'Updated At',     value: dt(editTarget?.updated_at) },
                            { label: 'Remember Token', value: editTarget?.remember_token },
                            { label: 'API Token',      value: editTarget?.api_token },
                        ]" :key="field.label">
                            <InputLabel :value="field.label" class="text-xs font-bold uppercase text-rapanel-text-light dark:text-rapanel-text-dark" />
                            <input :value="field.value ?? '—'" disabled
                                class="mt-1 block w-full text-sm px-3 py-2 rounded-lg bg-rapanel-navy-50 dark:bg-white/[0.03] border border-rapanel-navy-100 dark:border-white/[0.06] text-rapanel-text-light/50 dark:text-white/30 font-mono truncate cursor-not-allowed" />
                        </div>
                    </div>
                </div>

                <!-- § 6: Password reset -->
                <div class="border-t border-rapanel-navy-100 dark:border-white/[0.06] pt-5">
                    <p class="text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/40 dark:text-white/30 mb-1">Reset Password</p>
                    <p class="text-xs text-rapanel-text-light/40 dark:text-white/25 mb-3">Leave blank to keep current password.</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <InputLabel value="New Password" class="text-xs font-bold uppercase text-rapanel-text-light dark:text-rapanel-text-dark" />
                            <TextInput v-model="editForm.password" type="password" autocomplete="new-password" class="mt-1 block w-full dark:bg-rapanel-navy-900" />
                            <InputError class="mt-1" :message="editForm.errors.password" />
                        </div>
                        <div>
                            <InputLabel value="Confirm Password" class="text-xs font-bold uppercase text-rapanel-text-light dark:text-rapanel-text-dark" />
                            <TextInput v-model="editForm.password_confirmation" type="password" autocomplete="new-password" class="mt-1 block w-full dark:bg-rapanel-navy-900" />
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="flex justify-end gap-3 pt-2 border-t border-rapanel-navy-100 dark:border-white/[0.06]">
                    <SecondaryButton type="button" @click="closeEdit">Cancel</SecondaryButton>
                    <button type="submit" :disabled="editForm.processing"
                        class="px-4 py-2 rounded-lg bg-rapanel-blue text-white text-sm font-bold hover:opacity-90 transition disabled:opacity-60">
                        {{ editForm.processing ? 'Saving…' : 'Save Changes' }}
                    </button>
                </div>
            </form>
        </div>
    </Modal>
</template>

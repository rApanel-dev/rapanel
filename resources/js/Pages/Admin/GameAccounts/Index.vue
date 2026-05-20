<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { MagnifyingGlassIcon, FunnelIcon, XMarkIcon } from '@heroicons/vue/24/outline';
import PageHeader from '@/Components/PageHeader.vue';
import DataTable from '@/Components/DataTable.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import EmptyState from '@/Components/EmptyState.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import ActionButton from '@/Components/ActionButton.vue';

const props = defineProps({
    accounts: Object,
    filters:  Object,
});

const search        = ref(props.filters?.search        ?? '');
const state         = ref(props.filters?.state         ?? '');
const gender        = ref(props.filters?.gender        ?? '');
const accId         = ref(props.filters?.acc_id        ?? '');
const groupId       = ref(props.filters?.group_id      ?? '');
const lastIp        = ref(props.filters?.last_ip       ?? '');
const linked        = ref(props.filters?.linked        ?? '');
const loginFrom     = ref(props.filters?.login_from    ?? '');
const loginTo       = ref(props.filters?.login_to      ?? '');
const masterId      = ref(props.filters?.master_id     ?? '');
const masterSearch  = ref(props.filters?.master_search ?? '');

const showAdvanced = ref(
    !!(props.filters?.acc_id || props.filters?.group_id || props.filters?.last_ip ||
       props.filters?.linked || props.filters?.login_from || props.filters?.login_to ||
       props.filters?.master_id || props.filters?.master_search)
);

const safeRoute = (name, params) => { try { return route(name, params); } catch { return '#'; } };

const hasAdvancedFilters = () =>
    [accId, groupId, lastIp, linked, loginFrom, loginTo, masterId, masterSearch]
        .some(r => r.value !== '');

const clearAdvanced = () => {
    [accId, groupId, lastIp, linked, loginFrom, loginTo, masterId, masterSearch]
        .forEach(r => (r.value = ''));
};

let debounce;
const applyFilters = () => {
    clearTimeout(debounce);
    debounce = setTimeout(() => {
        router.get(safeRoute('admin.game-accounts.index'), {
            search:         search.value        || undefined,
            state:          state.value !== ''  ? state.value  : undefined,
            gender:         gender.value        || undefined,
            acc_id:         accId.value         || undefined,
            group_id:       groupId.value       || undefined,
            last_ip:        lastIp.value        || undefined,
            linked:         linked.value        || undefined,
            login_from:     loginFrom.value     || undefined,
            login_to:       loginTo.value       || undefined,
            master_id:      masterId.value      || undefined,
            master_search:  masterSearch.value  || undefined,
        }, { preserveState: true, replace: true });
    }, 300);
};
watch([search, state, gender, accId, groupId, lastIp, linked, loginFrom, loginTo, masterId, masterSearch], applyFilters);

const stateLabel = (s) => ({ 0: 'Active', 1: 'Banned', 5: 'De-linked' }[s] ?? `State ${s}`);

// ── Ban modal ──────────────────────────────────────────────────────────
const banTarget = ref(null);
const banForm   = useForm({ type: 'permanent', days: 7, reason: '' });

const openBan  = (acc) => { banTarget.value = acc; banForm.reset(); };
const closeBan = () => { banTarget.value = null; banForm.reset(); };
const submitBan = () => {
    banForm.post(safeRoute('admin.game-accounts.ban', banTarget.value.account_id), {
        preserveScroll: true,
        onSuccess: () => closeBan(),
    });
};

// ── Unban ──────────────────────────────────────────────────────────────
const submitUnban = (acc) => {
    if (!confirm(`Unban "${acc.userid}"?`)) return;
    router.post(safeRoute('admin.game-accounts.unban', acc.account_id), {}, { preserveScroll: true });
};

// ── Group modal ────────────────────────────────────────────────────────
const groupTarget = ref(null);
const groupForm   = useForm({ group_id: 0 });

const openGroup  = (acc) => { groupTarget.value = acc; groupForm.group_id = acc.group_id ?? 0; };
const closeGroup = () => { groupTarget.value = null; groupForm.reset(); };
const submitGroup = () => {
    groupForm.patch(safeRoute('admin.game-accounts.group', groupTarget.value.account_id), {
        preserveScroll: true,
        onSuccess: () => closeGroup(),
    });
};
</script>

<template>
    <AdminLayout>
        <div class="space-y-5">

            <PageHeader title="Game Accounts">
                <template #description>
                    <span class="font-bold text-rapanel-blue">{{ accounts.total }}</span> total server accounts created
                </template>
            </PageHeader>

            <!-- Filters -->
            <div class="bg-white dark:bg-[#0f1829] rounded-xl border border-rapanel-navy-100 dark:border-white/[0.08] p-4 space-y-3 shadow-[0_4px_24px_rgba(0,0,0,0.35)]">

                <!-- Basic row -->
                <div class="flex flex-col sm:flex-row gap-3">
                    <div class="relative flex-1">
                        <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-rapanel-text-light/40 dark:text-white/30" />
                        <input v-model="search" type="text" placeholder="Username, email, acc ID, master ID, master name…"
                            class="w-full pl-9 pr-3 py-2 text-sm bg-rapanel-navy-50 dark:bg-white/[0.04] border border-rapanel-navy-100 dark:border-white/[0.08] rounded-lg text-rapanel-text-light dark:text-white placeholder-rapanel-text-light/30 dark:placeholder-white/20 focus:outline-none focus:ring-1 focus:ring-rapanel-blue/50 focus:border-rapanel-blue/50 transition-colors" />
                    </div>
                    <select v-model="state" class="text-sm bg-rapanel-navy-50 dark:bg-white/[0.04] border border-rapanel-navy-100 dark:border-white/[0.08] rounded-lg px-3 py-2 text-rapanel-text-light dark:text-white focus:outline-none focus:ring-1 focus:ring-rapanel-blue/50 transition-colors">
                        <option value="">All states</option>
                        <option value="0">Active</option>
                        <option value="1">Banned</option>
                        <option value="5">De-linked</option>
                    </select>
                    <select v-model="gender" class="text-sm bg-rapanel-navy-50 dark:bg-white/[0.04] border border-rapanel-navy-100 dark:border-white/[0.08] rounded-lg px-3 py-2 text-rapanel-text-light dark:text-white focus:outline-none focus:ring-1 focus:ring-rapanel-blue/50 transition-colors">
                        <option value="">All genders</option>
                        <option value="M">Male</option>
                        <option value="F">Female</option>
                    </select>
                    <button @click="showAdvanced = !showAdvanced"
                        :class="[showAdvanced || hasAdvancedFilters() ? 'bg-rapanel-blue/10 text-rapanel-blue border-rapanel-blue/30' : 'text-rapanel-text-light/50 dark:text-white/40 border-rapanel-navy-100 dark:border-white/[0.08]', 'flex items-center gap-1.5 px-3 py-2 text-xs font-semibold border rounded-lg hover:border-rapanel-blue/50 hover:text-rapanel-blue transition-colors shrink-0']">
                        <FunnelIcon class="w-3.5 h-3.5" />
                        Advanced
                        <span v-if="hasAdvancedFilters()" class="w-1.5 h-1.5 rounded-full bg-rapanel-blue"></span>
                    </button>
                </div>

                <!-- Advanced filters grid -->
                <div v-show="showAdvanced" class="grid grid-cols-2 md:grid-cols-4 gap-3 pt-2 border-t border-rapanel-navy-100 dark:border-white/[0.06]">
                    <div>
                        <label class="block text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/40 dark:text-white/30 mb-1">Acc ID</label>
                        <input v-model="accId" type="number" placeholder="2000001"
                            class="w-full px-3 py-1.5 text-sm bg-rapanel-navy-50 dark:bg-white/[0.04] border border-rapanel-navy-100 dark:border-white/[0.08] rounded-lg text-rapanel-text-light dark:text-white placeholder-rapanel-text-light/30 dark:placeholder-white/20 focus:outline-none focus:ring-1 focus:ring-rapanel-blue/50 transition-colors" />
                    </div>
                    <div>
                        <label class="block text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/40 dark:text-white/30 mb-1">Group ID</label>
                        <input v-model="groupId" type="number" placeholder="0"
                            class="w-full px-3 py-1.5 text-sm bg-rapanel-navy-50 dark:bg-white/[0.04] border border-rapanel-navy-100 dark:border-white/[0.08] rounded-lg text-rapanel-text-light dark:text-white placeholder-rapanel-text-light/30 dark:placeholder-white/20 focus:outline-none focus:ring-1 focus:ring-rapanel-blue/50 transition-colors" />
                    </div>
                    <div>
                        <label class="block text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/40 dark:text-white/30 mb-1">IP Address</label>
                        <input v-model="lastIp" type="text" placeholder="192.168…"
                            class="w-full px-3 py-1.5 text-sm bg-rapanel-navy-50 dark:bg-white/[0.04] border border-rapanel-navy-100 dark:border-white/[0.08] rounded-lg text-rapanel-text-light dark:text-white placeholder-rapanel-text-light/30 dark:placeholder-white/20 focus:outline-none focus:ring-1 focus:ring-rapanel-blue/50 transition-colors" />
                    </div>
                    <div>
                        <label class="block text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/40 dark:text-white/30 mb-1">Linked</label>
                        <select v-model="linked" class="w-full text-sm bg-rapanel-navy-50 dark:bg-white/[0.04] border border-rapanel-navy-100 dark:border-white/[0.08] rounded-lg px-3 py-1.5 text-rapanel-text-light dark:text-white focus:outline-none focus:ring-1 focus:ring-rapanel-blue/50 transition-colors">
                            <option value="">All</option>
                            <option value="1">Linked</option>
                            <option value="0">Unlinked</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/40 dark:text-white/30 mb-1">Last Login From</label>
                        <input v-model="loginFrom" type="date"
                            class="w-full px-3 py-1.5 text-sm bg-rapanel-navy-50 dark:bg-white/[0.04] border border-rapanel-navy-100 dark:border-white/[0.08] rounded-lg text-rapanel-text-light dark:text-white focus:outline-none focus:ring-1 focus:ring-rapanel-blue/50 transition-colors" />
                    </div>
                    <div>
                        <label class="block text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/40 dark:text-white/30 mb-1">Last Login To</label>
                        <input v-model="loginTo" type="date"
                            class="w-full px-3 py-1.5 text-sm bg-rapanel-navy-50 dark:bg-white/[0.04] border border-rapanel-navy-100 dark:border-white/[0.08] rounded-lg text-rapanel-text-light dark:text-white focus:outline-none focus:ring-1 focus:ring-rapanel-blue/50 transition-colors" />
                    </div>
                    <div>
                        <label class="block text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/40 dark:text-white/30 mb-1">Master ID</label>
                        <input v-model="masterId" type="number" placeholder="1"
                            class="w-full px-3 py-1.5 text-sm bg-rapanel-navy-50 dark:bg-white/[0.04] border border-rapanel-navy-100 dark:border-white/[0.08] rounded-lg text-rapanel-text-light dark:text-white placeholder-rapanel-text-light/30 dark:placeholder-white/20 focus:outline-none focus:ring-1 focus:ring-rapanel-blue/50 transition-colors" />
                    </div>
                    <div>
                        <label class="block text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/40 dark:text-white/30 mb-1">Master Name / Email</label>
                        <input v-model="masterSearch" type="text" placeholder="Search panel user…"
                            class="w-full px-3 py-1.5 text-sm bg-rapanel-navy-50 dark:bg-white/[0.04] border border-rapanel-navy-100 dark:border-white/[0.08] rounded-lg text-rapanel-text-light dark:text-white placeholder-rapanel-text-light/30 dark:placeholder-white/20 focus:outline-none focus:ring-1 focus:ring-rapanel-blue/50 transition-colors" />
                    </div>

                    <!-- Clear advanced -->
                    <div v-if="hasAdvancedFilters()" class="col-span-2 md:col-span-4 flex justify-end">
                        <button @click="clearAdvanced"
                            class="flex items-center gap-1.5 text-xs font-semibold text-rapanel-text-light/40 dark:text-white/30 hover:text-rapanel-danger transition-colors">
                            <XMarkIcon class="w-3.5 h-3.5" />
                            Clear advanced filters
                        </button>
                    </div>
                </div>
            </div>

            <DataTable :paginator="accounts">
                <thead>
                    <tr class="border-b border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-white/5">
                        <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40">Acc ID</th>
                        <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40">Username</th>
                        <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40 hidden md:table-cell">Email</th>
                        <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40">Gender</th>
                        <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40">State</th>
                        <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40 hidden lg:table-cell">Last Login</th>
                        <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40 hidden xl:table-cell">Last IP</th>
                        <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40 hidden xl:table-cell">Master ID</th>
                        <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40 hidden xl:table-cell">Master Email</th>
                        <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40 hidden xl:table-cell">Master Name</th>
                        <th class="px-4 py-3 text-right text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-rapanel-navy-100 dark:divide-white/5">
                    <EmptyState v-if="!accounts.data?.length" :colspan="11" message="No game accounts found." />
                    <tr v-for="acc in accounts.data" :key="acc.account_id"
                        class="hover:bg-rapanel-navy-50 dark:hover:bg-white/5 transition">

                        <td class="px-4 py-3 font-mono text-xs text-rapanel-text-light/50 dark:text-white/40">{{ acc.account_id }}</td>
                        <td class="px-4 py-3 font-semibold text-rapanel-text-light dark:text-white">{{ acc.userid }}</td>
                        <td class="px-4 py-3 text-rapanel-text-light/60 dark:text-white/60 hidden md:table-cell text-xs">{{ acc.email }}</td>
                        <td class="px-4 py-3 text-rapanel-text-light/60 dark:text-white/60 text-xs">
                            {{ acc.sex === 'M' ? 'Male' : acc.sex === 'F' ? 'Female' : acc.sex }}
                        </td>

                        <!-- State -->
                        <td class="px-4 py-3">
                            <StatusBadge
                                :variant="acc.state === 0 ? 'success' : acc.state === 5 ? 'neutral' : 'danger'"
                                :label="stateLabel(acc.state)"
                                size="sm"
                            />
                        </td>

                        <td class="px-4 py-3 text-xs text-rapanel-text-light/50 dark:text-white/40 hidden lg:table-cell">{{ acc.lastlogin ?? '—' }}</td>
                        <td class="px-4 py-3 font-mono text-xs text-rapanel-text-light/50 dark:text-white/40 hidden xl:table-cell">{{ acc.last_ip ?? '—' }}</td>

                        <!-- Master ID -->
                        <td class="px-4 py-3 font-mono text-xs text-rapanel-text-light/50 dark:text-white/40 hidden xl:table-cell">
                            {{ acc.master_id ?? '—' }}
                        </td>

                        <!-- Master Email -->
                        <td class="px-4 py-3 text-xs text-rapanel-text-light/60 dark:text-white/60 hidden xl:table-cell">
                            {{ acc.master_email ?? '—' }}
                        </td>

                        <!-- Master Name -->
                        <td class="px-4 py-3 text-xs text-rapanel-text-light/60 dark:text-white/60 hidden xl:table-cell">
                            {{ acc.master_name ?? '—' }}
                        </td>

                        <!-- Actions -->
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-1.5 flex-wrap">
                                <!-- gold: Group ID -->
                                <ActionButton variant="gold" size="sm" @click="openGroup(acc)">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437l1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008z"/></svg>
                                    Group
                                </ActionButton>
                                <!-- blue: View -->
                                <ActionButton variant="blue" size="sm" @click="router.visit(safeRoute('game-accounts.show', acc.account_id))">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.964-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    View
                                </ActionButton>
                                <!-- danger: Ban (solo si activo) -->
                                <ActionButton v-if="acc.state === 0" variant="danger" size="sm" @click="openBan(acc)">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                    Ban
                                </ActionButton>
                                <!-- success: Unban (solo si baneado) -->
                                <ActionButton v-if="acc.state === 1" variant="success" size="sm" @click="submitUnban(acc)">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    Unban
                                </ActionButton>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </DataTable>

        </div>
    </AdminLayout>

    <!-- ── Modal: Ban ── -->
    <Modal :show="!!banTarget" @close="closeBan">
        <div class="p-6 bg-white dark:bg-rapanel-navy-900">
            <h2 class="text-lg font-bold text-rapanel-danger mb-1 border-b border-rapanel-navy-100 dark:border-white/10 pb-3 uppercase tracking-wider">
                Ban Account: <span class="text-rapanel-blue">{{ banTarget?.userid }}</span>
            </h2>
            <form @submit.prevent="submitBan" class="space-y-4 mt-4">
                <div class="flex gap-4">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" v-model="banForm.type" value="permanent" class="text-rapanel-danger" />
                        <span class="text-sm font-semibold text-rapanel-navy-900 dark:text-white">Permanent</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" v-model="banForm.type" value="temporary" class="text-rapanel-danger" />
                        <span class="text-sm font-semibold text-rapanel-navy-900 dark:text-white">Temporary</span>
                    </label>
                </div>
                <div v-if="banForm.type === 'temporary'">
                    <InputLabel value="Duration (days)" class="text-xs font-bold uppercase text-rapanel-text-light dark:text-rapanel-text-dark" />
                    <TextInput v-model="banForm.days" type="number" min="1" max="3650" required
                        class="mt-1 block w-full bg-white dark:bg-rapanel-navy-900" />
                    <InputError class="mt-1" :message="banForm.errors.days" />
                </div>
                <div>
                    <InputLabel value="Reason (optional)" class="text-xs font-bold uppercase text-rapanel-text-light dark:text-rapanel-text-dark" />
                    <TextInput v-model="banForm.reason" type="text" maxlength="255"
                        class="mt-1 block w-full bg-white dark:bg-rapanel-navy-900" />
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <SecondaryButton type="button" @click="closeBan">Cancel</SecondaryButton>
                    <button type="submit" :disabled="banForm.processing"
                        class="px-4 py-2 rounded-lg bg-rapanel-danger text-white text-sm font-bold hover:opacity-90 transition disabled:opacity-60">
                        {{ banForm.processing ? 'Saving…' : 'Confirm Ban' }}
                    </button>
                </div>
            </form>
        </div>
    </Modal>

    <!-- ── Modal: Group ID ── -->
    <Modal :show="!!groupTarget" @close="closeGroup">
        <div class="p-6 bg-white dark:bg-rapanel-navy-900">
            <h2 class="text-lg font-bold text-rapanel-gold mb-1 border-b border-rapanel-navy-100 dark:border-white/10 pb-3 uppercase tracking-wider">
                Change Group ID: <span class="text-rapanel-blue">{{ groupTarget?.userid }}</span>
            </h2>
            <p class="text-xs text-rapanel-text-light/60 dark:text-rapanel-text-dark/60 mt-3 mb-4">
                Group 0 = Player · 1 = Sub-GM · 2+ = GM levels. Check your rAthena <code>groups.conf</code> for exact values.
            </p>
            <form @submit.prevent="submitGroup" class="space-y-4">
                <div>
                    <InputLabel value="Group ID" class="text-rapanel-gold font-bold uppercase text-xs" />
                    <TextInput v-model="groupForm.group_id" type="number" min="0" max="99" required autofocus
                        class="mt-1 block w-full border-rapanel-gold/30 focus:ring-rapanel-gold focus:border-rapanel-gold bg-white dark:bg-rapanel-navy-900" />
                    <InputError class="mt-1" :message="groupForm.errors.group_id" />
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <SecondaryButton type="button" @click="closeGroup">Cancel</SecondaryButton>
                    <button type="submit" :disabled="groupForm.processing"
                        class="px-4 py-2 rounded-lg bg-rapanel-gold text-white text-sm font-bold hover:opacity-90 transition disabled:opacity-60">
                        {{ groupForm.processing ? 'Saving…' : 'Save' }}
                    </button>
                </div>
            </form>
        </div>
    </Modal>
</template>

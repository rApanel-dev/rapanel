<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { MagnifyingGlassIcon } from '@heroicons/vue/24/outline';
import PageHeader from '@/Components/PageHeader.vue';

const props = defineProps({
    logs:       Object,
    categories: Array,
    filters:    Object,
});

const page = usePage();
const __ = (key, rep = {}) => {
    let t = page.props.translations?.[key] || key;
    Object.entries(rep).forEach(([k, v]) => { t = t.replace(`:${k}`, v); });
    return t;
};

const search   = ref(props.filters?.search   ?? '');
const category = ref(props.filters?.category ?? '');

const safeRoute = (name, params) => { try { return route(name, params); } catch { return '#'; } };

let debounce;
const applyFilters = () => {
    clearTimeout(debounce);
    debounce = setTimeout(() => {
        router.get(safeRoute('admin.logs.index'), {
            search:   search.value   || undefined,
            category: category.value || undefined,
        }, { preserveState: true, replace: true });
    }, 300);
};
watch([search, category], applyFilters);

const formatDate = (d) => d ? String(d).replace('T', ' ').replace(/\.\d+Z?$/, '') : '—';

const actionColor = (action) => {
    if (action?.includes('password'))                                    return 'text-amber-500 dark:text-amber-400';
    if (action?.includes('unbanned') || action?.includes('activated'))   return 'text-rapanel-success';
    if (action?.includes('banned') || action?.includes('deactivated') || action?.includes('delete')) return 'text-rapanel-danger';
    if (action?.includes('created') || action?.includes('linked'))       return 'text-rapanel-success';
    if (action?.includes('gender') || action?.includes('reset'))         return 'text-rapanel-purple';
    if (action?.includes('group') || action?.includes('updated'))        return 'text-rapanel-gold';
    return 'text-rapanel-blue';
};

const metaDetails = (log) => {
    const m = log.metadata ?? {};
    const items = [];

    // — Target principal —
    if (m.username)
        items.push({ label: 'Account', value: `${m.username} (#${m.account_id ?? '?'})`, bold: true });
    else if (m.previous_username)
        items.push({ label: 'Account', value: `${m.previous_username} (#${m.account_id ?? '?'})`, bold: true });
    else if (m.char_name)
        items.push({ label: 'Character', value: `${m.char_name} (#${m.char_id ?? '?'})`, bold: true });
    else if (m.target_user_name)
        items.push({ label: 'User', value: `${m.target_user_name} (#${m.target_user_id ?? '?'})`, bold: true });

    // — Game accounts vinculados (master ban) —
    if (m.game_accounts && typeof m.game_accounts === 'object') {
        const list = Object.entries(m.game_accounts).map(([id, name]) => `${name} (#${id})`).join(', ');
        if (list) items.push({ label: 'Game Accts', value: list });
    }

    // — Detalles por acción —
    if (m.ban_type)
        items.push({ label: 'Ban', value: m.ban_type === 'permanent' ? 'Permanent' : `${m.days}d` });
    if (m.reason)
        items.push({ label: 'Reason', value: `"${m.reason}"` });
    if (m.from_group !== undefined && m.to_group !== undefined)
        items.push({ label: 'Group', value: `${m.from_group} → ${m.to_group}` });
    if (m.new_sex)
        items.push({ label: 'Gender', value: `→ ${m.new_sex === 'M' ? 'Male' : 'Female'}` });
    if (m.reset_to)
        items.push({ label: 'Reset to', value: m.reset_to });
    if (m.from_slot !== undefined && m.to_slot !== undefined)
        items.push({ label: 'Slot', value: `${m.from_slot} → ${m.to_slot}${m.swapped_with ? ` (↔ ${m.swapped_with})` : ''}` });
    if (m.method)
        items.push({ label: 'Method', value: m.method });

    // — Campos editados (game_account_updated) —
    if (m.from && m.to && typeof m.from === 'object') {
        const changed = Object.keys(m.to).filter(k => m.from[k] !== m.to[k]);
        if (changed.length)
            items.push({ label: 'Changed', value: changed.map(k => `${k}: ${m.from[k] ?? '—'} → ${m.to[k]}`).join(' · ') });
    }

    return items;
};
</script>

<template>
    <AdminLayout>
        <div class="space-y-5">

            <PageHeader :title="__('Action Logs')" :description="`${logs.total} ${__('total entries')}`" />

            <!-- Filters -->
            <div class="bg-white dark:bg-[#0f1829] rounded-xl border border-rapanel-navy-100 dark:border-white/[0.07] p-4 flex flex-col sm:flex-row gap-3 shadow-[0_4px_20px_rgba(0,0,0,0.22)] dark:shadow-[0_4px_28px_rgba(0,0,0,0.5)]">
                <div class="relative flex-1">
                    <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-rapanel-text-light/40 dark:text-white/30" />
                    <input v-model="search" type="text" :placeholder="__('Search by user, action, or category…')"
                        class="w-full pl-9 pr-3 py-2 text-sm bg-rapanel-navy-50 dark:bg-white/[0.04] border border-rapanel-navy-100 dark:border-white/[0.08] rounded-lg text-rapanel-text-light dark:text-white placeholder-rapanel-text-light/30 dark:placeholder-white/20 focus:outline-none focus:ring-1 focus:ring-rapanel-blue/50 focus:border-rapanel-blue/50 transition-colors" />
                </div>

                <select v-model="category"
                    class="text-sm bg-rapanel-navy-50 dark:bg-rapanel-navy-700 border border-rapanel-navy-100 dark:border-white/10 rounded-lg px-3 py-2 text-rapanel-text-light dark:text-white focus:outline-none focus:ring-2 focus:ring-rapanel-blue">
                    <option value="">{{ __('All categories') }}</option>
                    <option v-for="cat in categories" :key="cat" :value="cat">{{ cat }}</option>
                </select>
            </div>

            <!-- Table -->
            <div class="bg-white dark:bg-[#0f1829] rounded-xl border border-rapanel-navy-100 dark:border-white/[0.07] shadow-[0_4px_20px_rgba(0,0,0,0.22)] dark:shadow-[0_4px_28px_rgba(0,0,0,0.5)] overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-white/5">
                                <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40 whitespace-nowrap">{{ __('Date') }}</th>
                                <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40">{{ __('Admin') }}</th>
                                <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40 hidden md:table-cell">{{ __('Category') }}</th>
                                <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40">{{ __('Action') }}</th>
                                <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40 hidden lg:table-cell">{{ __('Details') }}</th>
                                <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40 hidden xl:table-cell">{{ __('IP') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-rapanel-navy-100 dark:divide-white/5">
                            <tr v-if="!logs.data?.length">
                                <td colspan="6" class="px-4 py-8 text-center text-rapanel-text-light/50 dark:text-white/40">{{ __('No logs found.') }}</td>
                            </tr>
                            <tr v-for="log in logs.data" :key="log.id"
                                class="hover:bg-rapanel-navy-50 dark:hover:bg-white/5 transition align-top">
                                <td class="px-4 py-3 text-xs text-rapanel-text-light/50 dark:text-white/40 whitespace-nowrap">
                                    {{ formatDate(log.created_at) }}
                                </td>
                                <td class="px-4 py-3">
                                    <span v-if="log.user" class="font-semibold text-rapanel-navy-900 dark:text-white text-sm">
                                        {{ log.user.name }}
                                    </span>
                                    <span v-else class="text-rapanel-text-light/50 dark:text-white/40 text-xs">{{ __('Deleted user') }}</span>
                                </td>
                                <td class="px-4 py-3 text-xs text-rapanel-text-light/60 dark:text-white/50 hidden md:table-cell whitespace-nowrap">
                                    {{ log.category }}
                                </td>
                                <td class="px-4 py-3">
                                    <span :class="[actionColor(log.action), 'text-xs font-semibold font-mono whitespace-nowrap']">
                                        {{ log.action }}
                                    </span>
                                </td>

                                <!-- Details extraídos del metadata -->
                                <td class="px-4 py-3 hidden lg:table-cell">
                                    <div v-if="metaDetails(log).length" class="flex flex-col gap-1">
                                        <div v-for="item in metaDetails(log)" :key="item.label" class="flex items-baseline gap-1.5 text-xs">
                                            <span class="text-[10px] font-black uppercase tracking-wider text-rapanel-text-light/35 dark:text-white/30 shrink-0">{{ item.label }}</span>
                                            <span :class="[item.bold ? 'font-semibold text-rapanel-navy-900 dark:text-white' : 'text-rapanel-text-light/70 dark:text-white/55']">
                                                {{ item.value }}
                                            </span>
                                        </div>
                                    </div>
                                    <span v-else class="text-rapanel-text-light/30 dark:text-white/20 text-xs">—</span>
                                </td>

                                <td class="px-4 py-3 font-mono text-xs text-rapanel-text-light/50 dark:text-white/40 hidden xl:table-cell whitespace-nowrap">
                                    {{ log.ip_address ?? '—' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="logs.last_page > 1"
                    class="flex items-center justify-between px-4 py-3 border-t border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-white/5">
                    <span class="text-xs text-rapanel-text-light/50 dark:text-white/40">
                        {{ logs.from }}–{{ logs.to }} of {{ logs.total }}
                    </span>
                    <div class="flex gap-1">
                        <template v-for="link in logs.links" :key="link.label">
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

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import ActionButton from '@/Components/ActionButton.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import { PlusIcon, BookOpenIcon } from '@heroicons/vue/24/outline';

const safeRoute = (name, params = {}) => { try { return route(name, params); } catch { return '#'; } };

const page = usePage();
const __ = (key, rep = {}) => {
    let t = page.props.translations?.[key] || key;
    Object.entries(rep).forEach(([k, v]) => { t = t.replace(`:${k}`, v); });
    return t;
};

const props = defineProps({ sections: Array });

const confirmState = ref(null);
const askConfirm = (opts) => { confirmState.value = opts; };
const doConfirm   = () => { confirmState.value?.action?.(); confirmState.value = null; };
const closeConfirm = () => { confirmState.value = null; };

const confirmDelete = (id, title) => {
    askConfirm({
        title:        __('Delete Section'),
        entity:       title,
        message:      __('This will also delete all articles in this section. This action cannot be undone.'),
        confirmLabel: __('Delete'),
        variant:      'danger',
        action:       () => router.delete(safeRoute('admin.wiki.sections.destroy', { section: id })),
    });
};
</script>

<template>
    <AdminLayout>
        <div class="space-y-6">

            <PageHeader :title="__('Wiki Sections')" :description="`${sections?.length ?? 0} ${__('sections')}`">
                <Link :href="safeRoute('admin.wiki.sections.create')"
                      class="inline-flex items-center gap-2 px-4 py-2 bg-rapanel-blue hover:opacity-90 text-white text-sm font-bold rounded-lg transition shadow">
                    <PlusIcon class="w-4 h-4" aria-hidden="true" />
                    {{ __('New Section') }}
                </Link>
            </PageHeader>

            <!-- Empty state -->
            <div v-if="!sections?.length"
                 class="bg-white dark:bg-rapanel-surface rounded-xl border border-rapanel-navy-100 dark:border-white/[0.07] shadow-[0_4px_20px_rgba(0,0,0,0.22)] dark:shadow-[0_4px_28px_rgba(0,0,0,0.5)] p-14 flex flex-col items-center justify-center gap-5 text-center">
                <div class="w-16 h-16 rounded-2xl bg-rapanel-navy-50 dark:bg-white/[0.04] ring-1 ring-rapanel-navy-100 dark:ring-white/[0.07] flex items-center justify-center">
                    <BookOpenIcon class="w-8 h-8 text-rapanel-text-light/30 dark:text-white/20" />
                </div>
                <div>
                    <div class="text-lg font-display font-bold tracking-wide text-rapanel-text-light dark:text-white">{{ __('No wiki sections yet.') }}</div>
                    <div class="text-sm text-rapanel-text-light/50 dark:text-white/40 mt-1.5">{{ __('Create the first section to organize your wiki.') }}</div>
                </div>
                <Link :href="safeRoute('admin.wiki.sections.create')"
                      class="inline-flex items-center gap-2 px-4 py-2 bg-rapanel-blue hover:opacity-90 text-white text-sm font-semibold rounded-lg transition shadow">
                    <PlusIcon class="w-4 h-4" aria-hidden="true" />
                    {{ __('New Section') }}
                </Link>
            </div>

            <!-- Table -->
            <div v-else class="bg-white dark:bg-rapanel-surface rounded-xl border border-rapanel-navy-100 dark:border-white/[0.07] overflow-hidden shadow-[0_4px_20px_rgba(0,0,0,0.22)] dark:shadow-[0_4px_28px_rgba(0,0,0,0.5)]">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-white/5">
                            <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40 w-12">{{ __('Order') }}</th>
                            <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40 w-12">{{ __('Icon') }}</th>
                            <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40">{{ __('Title') }}</th>
                            <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40 hidden md:table-cell">{{ __('Description') }}</th>
                            <th class="px-4 py-3 text-center text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40 w-24">{{ __('Articles') }}</th>
                            <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40 hidden lg:table-cell">{{ __('Author') }}</th>
                            <th class="px-4 py-3 text-center text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40 w-24">{{ __('Published') }}</th>
                            <th class="px-4 py-3 text-center text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40 w-28">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-rapanel-navy-100 dark:divide-white/5">
                        <tr v-for="section in sections" :key="section.id"
                            class="hover:bg-rapanel-navy-50 dark:hover:bg-white/[0.03] transition-colors">
                            <td class="px-4 py-3 text-rapanel-text-light/50 dark:text-white/40 font-mono text-xs">{{ section.sort_order }}</td>
                            <td class="px-4 py-3 text-2xl">{{ section.icon }}</td>
                            <td class="px-4 py-3 font-medium text-rapanel-text-light dark:text-white">{{ section.title }}</td>
                            <td class="px-4 py-3 hidden md:table-cell text-rapanel-text-light/55 dark:text-white/45 truncate max-w-xs">{{ section.description }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-rapanel-blue/10 dark:bg-rapanel-blue/20 text-rapanel-blue text-xs font-bold">
                                    {{ section.articles_count }}
                                </span>
                            </td>
                            <td class="px-4 py-3 hidden lg:table-cell">
                                <p class="text-xs text-rapanel-text-light dark:text-white/80">{{ section.created_by_name ?? '—' }}</p>
                                <p v-if="section.updated_by_name" class="text-xs text-rapanel-text-light/50 dark:text-white/40 mt-0.5">
                                    {{ __('Updated by') }}: {{ section.updated_by_name }}
                                </p>
                                <p class="text-xs text-rapanel-text-light/40 dark:text-white/35 mt-0.5">{{ section.created_at }}</p>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span :class="section.is_published
                                    ? 'bg-rapanel-success/10 text-rapanel-success border-rapanel-success/20'
                                    : 'bg-rapanel-text-light/10 dark:bg-white/10 text-rapanel-text-light/50 dark:text-white/40 border-rapanel-navy-100 dark:border-white/10'"
                                      class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold border">
                                    {{ section.is_published ? __('Yes') : __('No') }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-center gap-1">
                                    <ActionButton variant="navy" size="icon" :title="__('Edit section')"
                                        @click="router.visit(safeRoute('admin.wiki.sections.edit', { section: section.id }))">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/></svg>
                                    </ActionButton>
                                    <ActionButton variant="danger" size="icon" :title="__('Delete section')"
                                        @click="confirmDelete(section.id, section.title)">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                    </ActionButton>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AdminLayout>

    <ConfirmModal
        :show="!!confirmState"
        :title="confirmState?.title ?? ''"
        :entity="confirmState?.entity ?? ''"
        :message="confirmState?.message ?? ''"
        :confirm-label="confirmState?.confirmLabel ?? ''"
        :variant="confirmState?.variant ?? 'danger'"
        @confirm="doConfirm"
        @close="closeConfirm"
    />
</template>

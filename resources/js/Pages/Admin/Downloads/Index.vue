<script setup>
import { router, usePage } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import ActionButton from '@/Components/ActionButton.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';

defineProps({
    downloads: Object,
    filters:   Object,
});

const page = usePage();
const __ = (key) => page.props.translations?.[key] || key;

const openUrl = (url) => window.open(url, '_blank');

const confirmState = ref(null);
const askConfirm = (opts) => { confirmState.value = opts; };
const doConfirm   = () => { confirmState.value?.action?.(); confirmState.value = null; };
const closeConfirm = () => { confirmState.value = null; };

const destroy = (id, name) => {
    askConfirm({
        title:        __('Delete Download'),
        entity:       name,
        confirmLabel: __('Delete'),
        variant:      'danger',
        action:       () => router.delete(route('admin.downloads.destroy', id)),
    });
};
</script>

<template>
    <AdminLayout>
        <PageHeader :title="__('Downloads')" :description="__('Manage downloadable files and links.')" class="mb-6">
            <Link :href="route('admin.downloads.create')"
                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-rapanel-blue text-white text-sm font-bold hover:opacity-90 transition">
                + {{ __('New Download') }}
            </Link>
        </PageHeader>

        <!-- Local files info -->
        <div class="bg-rapanel-navy-50 dark:bg-rapanel-surface rounded-xl border border-rapanel-navy-100 dark:border-white/10 p-5 mb-6 space-y-4">
            <h2 class="text-sm font-bold text-rapanel-navy-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                <svg class="w-4 h-4 text-rapanel-blue flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/></svg>
                {{ __('Local Files — Server Paths') }}
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="space-y-1">
                    <p class="text-xs font-bold text-rapanel-navy-900 dark:text-white">{{ __('local_files_patch_title') }}</p>
                    <p class="text-xs text-rapanel-text-light dark:text-rapanel-text-dark opacity-70 leading-relaxed" v-html="__('local_files_patch_desc')" />
                </div>
                <div class="space-y-1">
                    <p class="text-xs font-bold text-rapanel-navy-900 dark:text-white">{{ __('local_files_client_title') }}</p>
                    <p class="text-xs text-rapanel-text-light dark:text-rapanel-text-dark opacity-70 leading-relaxed" v-html="__('local_files_client_desc')" />
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="bg-white dark:bg-rapanel-surface rounded-xl border border-rapanel-navy-100 dark:border-white/[0.07] shadow-[0_4px_20px_rgba(0,0,0,0.22)] dark:shadow-[0_4px_28px_rgba(0,0,0,0.5)] overflow-hidden">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-white/5">
                        <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40">{{ __('Name') }}</th>
                        <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40 hidden md:table-cell">{{ __('Category') }}</th>
                        <th class="px-4 py-3 text-center text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40 hidden sm:table-cell">{{ __('Type') }}</th>
                        <th class="px-4 py-3 text-center text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40 hidden sm:table-cell">{{ __('Downloads') }}</th>
                        <th class="px-4 py-3 text-center text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40">{{ __('Status') }}</th>
                        <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40 hidden lg:table-cell">{{ __('Author') }}</th>
                        <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40 hidden lg:table-cell">{{ __('Created') }}</th>
                        <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40 hidden xl:table-cell">{{ __('Updated') }}</th>
                        <th class="px-4 py-3 text-center text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-rapanel-navy-100 dark:divide-white/5">
                    <tr v-for="dl in downloads.data" :key="dl.id"
                        class="hover:bg-rapanel-navy-50 dark:hover:bg-white/5 transition-colors">
                        <td class="px-4 py-3 font-medium text-rapanel-navy-900 dark:text-white">
                            {{ dl.name }}
                        </td>
                        <td class="px-4 py-3 text-rapanel-text-light dark:text-rapanel-text-dark hidden md:table-cell">
                            <span v-if="dl.category_name">
                                <span v-if="dl.category_icon" class="mr-0.5">{{ dl.category_icon }}</span>{{ dl.category_name }}
                            </span>
                            <span v-else class="opacity-40">—</span>
                        </td>
                        <td class="px-4 py-3 text-center hidden sm:table-cell">
                            <span :class="[
                                'text-[10px] font-black uppercase px-2 py-0.5 rounded border',
                                dl.is_external
                                    ? 'bg-rapanel-blue/10 text-rapanel-blue border-rapanel-blue/20'
                                    : 'bg-rapanel-success/10 text-rapanel-success border-rapanel-success/20'
                            ]">{{ dl.is_external ? __('External') : __('Local') }}</span>
                        </td>
                        <td class="px-4 py-3 text-center text-rapanel-text-light dark:text-rapanel-text-dark hidden sm:table-cell">
                            {{ dl.download_count.toLocaleString() }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span :class="[
                                'text-[10px] font-black uppercase px-2 py-0.5 rounded border',
                                dl.is_active
                                    ? 'bg-rapanel-success/10 text-rapanel-success border-rapanel-success/20'
                                    : 'bg-rapanel-danger/10 text-rapanel-danger border-rapanel-danger/20'
                            ]">{{ dl.is_active ? __('Active') : __('Inactive') }}</span>
                        </td>
                        <td class="px-4 py-3 hidden lg:table-cell">
                            <span v-if="dl.created_by_name" class="text-xs font-semibold text-rapanel-text-light dark:text-white">{{ dl.created_by_name }}</span>
                            <span v-else class="text-xs text-rapanel-text-light/30 dark:text-white/20">—</span>
                        </td>
                        <td class="px-4 py-3 hidden lg:table-cell">
                            <span class="block text-xs text-rapanel-text-light/70 dark:text-white/60 whitespace-nowrap">{{ dl.created_at }}</span>
                            <span class="block text-[10px] text-rapanel-text-light/40 dark:text-white/30 mt-0.5">{{ dl.created_ago }}</span>
                        </td>
                        <td class="px-4 py-3 hidden xl:table-cell">
                            <template v-if="dl.updated_at">
                                <span class="block text-xs text-rapanel-text-light/70 dark:text-white/60 whitespace-nowrap">{{ dl.updated_at }}</span>
                                <span class="block text-[10px] text-rapanel-text-light/40 dark:text-white/30 mt-0.5">{{ dl.updated_ago }}</span>
                                <span v-if="dl.updated_by_name" class="block text-[10px] font-semibold text-rapanel-text-light/50 dark:text-white/40 mt-0.5">{{ dl.updated_by_name }}</span>
                            </template>
                            <span v-else class="text-xs text-rapanel-text-light/30 dark:text-white/20">—</span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-center gap-1">
                                <!-- View -->
                                <ActionButton variant="blue" size="icon" :title="__('View')"
                                    @click="openUrl(route('downloads.show', dl.slug))">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.964-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                </ActionButton>
                                <!-- Edit -->
                                <ActionButton variant="navy" size="icon" :title="__('Edit')"
                                    @click="router.visit(route('admin.downloads.edit', dl.id))">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/></svg>
                                </ActionButton>
                                <!-- Delete -->
                                <ActionButton variant="danger" size="icon" :title="__('Delete')"
                                    @click="destroy(dl.id, dl.name)">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                </ActionButton>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!downloads.data?.length">
                        <td colspan="9" class="px-5 py-10 text-center text-sm text-rapanel-text-light dark:text-rapanel-text-dark">
                            {{ __('No downloads found.') }}
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Pagination -->
            <div v-if="downloads.last_page > 1" class="flex items-center justify-between px-5 py-3 border-t border-rapanel-navy-100 dark:border-white/10 text-xs text-rapanel-text-light dark:text-rapanel-text-dark">
                <span>{{ __('Showing') }} {{ downloads.from }}–{{ downloads.to }} {{ __('of') }} {{ downloads.total }}</span>
                <div class="flex gap-1">
                    <Link v-for="link in downloads.links" :key="link.label"
                        :href="link.url ?? '#'"
                        :class="['px-2.5 py-1 rounded border transition', link.active ? 'bg-rapanel-blue text-white border-rapanel-blue' : 'border-rapanel-navy-100 dark:border-white/10 hover:border-rapanel-blue']"
                        v-html="link.label" />
                </div>
            </div>
        </div>
    </AdminLayout>

    <ConfirmModal
        :show="!!confirmState"
        :title="confirmState?.title ?? ''"
        :entity="confirmState?.entity ?? ''"
        :confirm-label="confirmState?.confirmLabel ?? ''"
        :variant="confirmState?.variant ?? 'danger'"
        @confirm="doConfirm"
        @close="closeConfirm"
    />
</template>

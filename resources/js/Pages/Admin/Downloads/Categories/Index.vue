<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import ActionButton from '@/Components/ActionButton.vue';

defineProps({
    categories: { type: Array, default: () => [] },
});

const page = usePage();
const __ = (key) => page.props.translations?.[key] || key;

const destroy = (id) => {
    if (confirm(__('Are you sure? Downloads in this category will have no category.'))) {
        router.delete(route('admin.download-categories.destroy', id));
    }
};
</script>

<template>
    <AdminLayout>
        <PageHeader :title="__('Download Categories')" :description="__('Organize downloads into categories.')" class="mb-6">
            <Link :href="route('admin.download-categories.create')"
                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-rapanel-blue text-white text-sm font-bold hover:opacity-90 transition">
                + {{ __('New Category') }}
            </Link>
        </PageHeader>

        <div v-if="$page.props.flash?.success" class="mb-4 px-4 py-3 rounded-lg bg-rapanel-success/10 border border-rapanel-success/30 text-sm text-rapanel-success font-medium">
            {{ $page.props.flash.success }}
        </div>

        <div class="bg-white dark:bg-[#0f1829] rounded-xl border border-rapanel-navy-100 dark:border-white/[0.07] shadow-[0_4px_20px_rgba(0,0,0,0.22)] dark:shadow-[0_4px_28px_rgba(0,0,0,0.5)] overflow-hidden">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-white/5">
                        <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40">{{ __('Name') }}</th>
                        <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40 hidden md:table-cell">{{ __('Description') }}</th>
                        <th class="px-4 py-3 text-center text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40">{{ __('Downloads') }}</th>
                        <th class="px-4 py-3 text-center text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40">{{ __('Sort') }}</th>
                        <th class="px-4 py-3 text-center text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40">{{ __('Status') }}</th>
                        <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40 hidden lg:table-cell">{{ __('Created') }}</th>
                        <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40 hidden xl:table-cell">{{ __('Updated') }}</th>
                        <th class="px-4 py-3 text-center text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-rapanel-navy-100 dark:divide-white/5">
                    <tr v-for="cat in categories" :key="cat.id"
                        class="hover:bg-rapanel-navy-50 dark:hover:bg-white/5 transition-colors">
                        <td class="px-4 py-3 font-medium text-rapanel-navy-900 dark:text-white">
                            <span v-if="cat.icon" class="mr-1.5">{{ cat.icon }}</span>{{ cat.name }}
                        </td>
                        <td class="px-4 py-3 text-rapanel-text-light dark:text-rapanel-text-dark hidden md:table-cell max-w-xs truncate">
                            {{ cat.description || '—' }}
                        </td>
                        <td class="px-4 py-3 text-center text-rapanel-text-light dark:text-rapanel-text-dark">
                            {{ cat.downloads_count }}
                        </td>
                        <td class="px-4 py-3 text-center text-rapanel-text-light dark:text-rapanel-text-dark">
                            {{ cat.sort_order }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span :class="[
                                'text-[10px] font-black uppercase px-2 py-0.5 rounded border',
                                cat.is_active
                                    ? 'bg-rapanel-success/10 text-rapanel-success border-rapanel-success/20'
                                    : 'bg-rapanel-danger/10 text-rapanel-danger border-rapanel-danger/20'
                            ]">{{ cat.is_active ? __('Active') : __('Inactive') }}</span>
                        </td>
                        <td class="px-4 py-3 hidden lg:table-cell">
                            <span class="block text-xs text-rapanel-text-light/70 dark:text-white/60 whitespace-nowrap">{{ cat.created_at }}</span>
                            <span class="block text-[10px] text-rapanel-text-light/40 dark:text-white/30 mt-0.5">{{ cat.created_ago }}</span>
                        </td>
                        <td class="px-4 py-3 hidden xl:table-cell">
                            <template v-if="cat.updated_at">
                                <span class="block text-xs text-rapanel-text-light/70 dark:text-white/60 whitespace-nowrap">{{ cat.updated_at }}</span>
                                <span class="block text-[10px] text-rapanel-text-light/40 dark:text-white/30 mt-0.5">{{ cat.updated_ago }}</span>
                                <span v-if="cat.updated_by_name" class="block text-[10px] font-semibold text-rapanel-text-light/50 dark:text-white/40 mt-0.5">{{ cat.updated_by_name }}</span>
                            </template>
                            <span v-else class="text-xs text-rapanel-text-light/30 dark:text-white/20">—</span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-center gap-1">
                                <!-- Edit -->
                                <ActionButton variant="navy" size="icon" :title="__('Edit')"
                                    @click="router.visit(route('admin.download-categories.edit', cat.id))">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/></svg>
                                </ActionButton>
                                <!-- Delete -->
                                <ActionButton variant="danger" size="icon" :title="__('Delete')"
                                    @click="destroy(cat.id)">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                </ActionButton>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!categories.length">
                        <td colspan="8" class="px-5 py-10 text-center text-sm text-rapanel-text-light dark:text-rapanel-text-dark">
                            {{ __('No categories found.') }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AdminLayout>
</template>

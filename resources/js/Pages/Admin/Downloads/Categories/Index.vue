<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

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
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-5 mb-5 border-b border-rapanel-navy-100 dark:border-white/[0.055]">
            <div>
                <h1 class="text-2xl font-display font-bold tracking-wide text-rapanel-navy-900 dark:text-white">{{ __('Download Categories') }}</h1>
                <p class="text-sm text-rapanel-text-light/55 dark:text-white/45 mt-1">{{ __('Organize downloads into categories.') }}</p>
            </div>
            <Link :href="route('admin.download-categories.create')"
                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-rapanel-blue text-white text-sm font-bold hover:opacity-90 transition">
                + {{ __('New Category') }}
            </Link>
        </div>

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
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <Link :href="route('admin.download-categories.edit', cat.id)"
                                    class="text-xs font-semibold text-rapanel-blue hover:underline">{{ __('Edit') }}</Link>
                                <button @click="destroy(cat.id)"
                                    class="text-xs font-semibold text-rapanel-danger hover:underline">{{ __('Delete') }}</button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!categories.length">
                        <td colspan="6" class="px-5 py-10 text-center text-sm text-rapanel-text-light dark:text-rapanel-text-dark">
                            {{ __('No categories found.') }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AdminLayout>
</template>

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
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-xl font-bold text-rapanel-navy-900 dark:text-white">{{ __('Download Categories') }}</h1>
                <p class="text-sm text-rapanel-text-light dark:text-rapanel-text-dark mt-0.5">{{ __('Organize downloads into categories.') }}</p>
            </div>
            <Link :href="route('admin.download-categories.create')"
                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-rapanel-blue text-white text-sm font-bold hover:opacity-90 transition">
                + {{ __('New Category') }}
            </Link>
        </div>

        <div v-if="$page.props.flash?.success" class="mb-4 px-4 py-3 rounded-lg bg-rapanel-success/10 border border-rapanel-success/30 text-sm text-rapanel-success font-medium">
            {{ $page.props.flash.success }}
        </div>

        <div class="bg-white dark:bg-rapanel-navy-900 rounded-xl border border-rapanel-navy-100 dark:border-white/10 shadow-sm overflow-hidden">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="bg-rapanel-navy-100/70 dark:bg-rapanel-navy-800 text-rapanel-text-light dark:text-rapanel-text-dark text-xs uppercase tracking-wider font-bold">
                        <th class="px-5 py-3 text-left">{{ __('Name') }}</th>
                        <th class="px-5 py-3 text-left hidden md:table-cell">{{ __('Description') }}</th>
                        <th class="px-5 py-3 text-center">{{ __('Downloads') }}</th>
                        <th class="px-5 py-3 text-center">{{ __('Sort') }}</th>
                        <th class="px-5 py-3 text-center">{{ __('Status') }}</th>
                        <th class="px-5 py-3 text-right">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-rapanel-navy-100 dark:divide-white/5">
                    <tr v-for="cat in categories" :key="cat.id"
                        class="hover:bg-rapanel-navy-50 dark:hover:bg-white/5 transition-colors">
                        <td class="px-5 py-3.5 font-medium text-rapanel-navy-900 dark:text-white">
                            <span v-if="cat.icon" class="mr-1.5">{{ cat.icon }}</span>{{ cat.name }}
                        </td>
                        <td class="px-5 py-3.5 text-rapanel-text-light dark:text-rapanel-text-dark hidden md:table-cell max-w-xs truncate">
                            {{ cat.description || '—' }}
                        </td>
                        <td class="px-5 py-3.5 text-center text-rapanel-text-light dark:text-rapanel-text-dark">
                            {{ cat.downloads_count }}
                        </td>
                        <td class="px-5 py-3.5 text-center text-rapanel-text-light dark:text-rapanel-text-dark">
                            {{ cat.sort_order }}
                        </td>
                        <td class="px-5 py-3.5 text-center">
                            <span :class="[
                                'text-[10px] font-black uppercase px-2 py-0.5 rounded border',
                                cat.is_active
                                    ? 'bg-rapanel-success/10 text-rapanel-success border-rapanel-success/20'
                                    : 'bg-rapanel-danger/10 text-rapanel-danger border-rapanel-danger/20'
                            ]">{{ cat.is_active ? __('Active') : __('Inactive') }}</span>
                        </td>
                        <td class="px-5 py-3.5 text-right">
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

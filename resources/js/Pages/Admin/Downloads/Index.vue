<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineProps({
    downloads: Object,
    filters:   Object,
});

const page = usePage();
const __ = (key) => page.props.translations?.[key] || key;

const destroy = (id) => {
    if (confirm(__('Are you sure you want to delete this download?'))) {
        router.delete(route('admin.downloads.destroy', id));
    }
};
</script>

<template>
    <AdminLayout>
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-xl font-bold text-rapanel-navy-900 dark:text-white">{{ __('Downloads') }}</h1>
                <p class="text-sm text-rapanel-text-light dark:text-rapanel-text-dark mt-0.5">{{ __('Manage downloadable files and links.') }}</p>
            </div>
            <Link :href="route('admin.downloads.create')"
                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-rapanel-blue text-white text-sm font-bold hover:opacity-90 transition">
                + {{ __('New Download') }}
            </Link>
        </div>

        <!-- Flash -->
        <div v-if="$page.props.flash?.success" class="mb-4 px-4 py-3 rounded-lg bg-rapanel-success/10 border border-rapanel-success/30 text-sm text-rapanel-success font-medium">
            {{ $page.props.flash.success }}
        </div>

        <!-- Table -->
        <div class="bg-white dark:bg-rapanel-navy-900 rounded-xl border border-rapanel-navy-100 dark:border-white/10 shadow-sm overflow-hidden">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="bg-rapanel-navy-100/70 dark:bg-rapanel-navy-800 text-rapanel-text-light dark:text-rapanel-text-dark text-xs uppercase tracking-wider font-bold">
                        <th class="px-5 py-3 text-left">{{ __('Name') }}</th>
                        <th class="px-5 py-3 text-left hidden md:table-cell">{{ __('Category') }}</th>
                        <th class="px-5 py-3 text-center hidden sm:table-cell">{{ __('Type') }}</th>
                        <th class="px-5 py-3 text-center hidden sm:table-cell">{{ __('Downloads') }}</th>
                        <th class="px-5 py-3 text-center">{{ __('Status') }}</th>
                        <th class="px-5 py-3 text-right">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-rapanel-navy-100 dark:divide-white/5">
                    <tr v-for="dl in downloads.data" :key="dl.id"
                        class="hover:bg-rapanel-navy-50 dark:hover:bg-white/5 transition-colors">
                        <td class="px-5 py-3.5 font-medium text-rapanel-navy-900 dark:text-white">
                            {{ dl.name }}
                        </td>
                        <td class="px-5 py-3.5 text-rapanel-text-light dark:text-rapanel-text-dark hidden md:table-cell">
                            <span v-if="dl.category_name">
                                <span v-if="dl.category_icon" class="mr-0.5">{{ dl.category_icon }}</span>{{ dl.category_name }}
                            </span>
                            <span v-else class="opacity-40">—</span>
                        </td>
                        <td class="px-5 py-3.5 text-center hidden sm:table-cell">
                            <span :class="[
                                'text-[10px] font-black uppercase px-2 py-0.5 rounded border',
                                dl.is_external
                                    ? 'bg-rapanel-blue/10 text-rapanel-blue border-rapanel-blue/20'
                                    : 'bg-rapanel-success/10 text-rapanel-success border-rapanel-success/20'
                            ]">{{ dl.is_external ? __('External') : __('Local') }}</span>
                        </td>
                        <td class="px-5 py-3.5 text-center text-rapanel-text-light dark:text-rapanel-text-dark hidden sm:table-cell">
                            {{ dl.download_count.toLocaleString() }}
                        </td>
                        <td class="px-5 py-3.5 text-center">
                            <span :class="[
                                'text-[10px] font-black uppercase px-2 py-0.5 rounded border',
                                dl.is_active
                                    ? 'bg-rapanel-success/10 text-rapanel-success border-rapanel-success/20'
                                    : 'bg-rapanel-danger/10 text-rapanel-danger border-rapanel-danger/20'
                            ]">{{ dl.is_active ? __('Active') : __('Inactive') }}</span>
                        </td>
                        <td class="px-5 py-3.5 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <Link :href="route('admin.downloads.edit', dl.id)"
                                    class="text-xs font-semibold text-rapanel-blue hover:underline">{{ __('Edit') }}</Link>
                                <button @click="destroy(dl.id)"
                                    class="text-xs font-semibold text-rapanel-danger hover:underline">{{ __('Delete') }}</button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!downloads.data?.length">
                        <td colspan="6" class="px-5 py-10 text-center text-sm text-rapanel-text-light dark:text-rapanel-text-dark">
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
</template>

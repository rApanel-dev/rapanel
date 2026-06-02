<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import { PlusIcon, PencilSquareIcon, TrashIcon } from '@heroicons/vue/24/outline';

const safeRoute = (name, params = {}) => { try { return route(name, params); } catch { return '#'; } };

const page = usePage();
const __ = (key, rep = {}) => {
    let t = page.props.translations?.[key] || key;
    Object.entries(rep).forEach(([k, v]) => { t = t.replace(`:${k}`, v); });
    return t;
};

const props = defineProps({ sections: Array });

const confirmingDelete = ref(null);

const destroy = (id) => {
    router.delete(safeRoute('admin.wiki.sections.destroy', { section: id }), {
        onSuccess: () => { confirmingDelete.value = null; },
    });
};
</script>

<template>
    <AdminLayout>
        <div class="space-y-6">

            <!-- Header -->
            <div class="flex items-center justify-between pb-5 border-b border-rapanel-navy-100 dark:border-white/[0.055]">
                <div>
                    <h1 class="text-2xl font-display font-bold tracking-wide text-rapanel-text-light dark:text-white">{{ __('Wiki Sections') }}</h1>
                    <p class="text-sm text-rapanel-text-light/50 dark:text-white/40 mt-0.5">{{ __('Admin › Wiki Sections') }}</p>
                </div>
                <Link :href="safeRoute('admin.wiki.sections.create')"
                      class="inline-flex items-center gap-2 px-4 py-2 bg-rapanel-blue hover:bg-rapanel-blue/85 text-white text-sm font-semibold rounded-lg transition-colors">
                    <PlusIcon class="w-4 h-4" />
                    {{ __('New Section') }}
                </Link>
            </div>

            <!-- Empty state -->
            <div v-if="!sections?.length"
                 class="text-center py-20 text-rapanel-text-light/40 dark:text-white/30">
                <span class="text-5xl">📖</span>
                <p class="mt-4 text-sm">{{ __('No wiki sections yet.') }}</p>
            </div>

            <!-- Table -->
            <div v-else class="bg-white dark:bg-rapanel-navy-800 rounded-xl border border-rapanel-navy-100 dark:border-white/10 overflow-hidden shadow-sm">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-rapanel-navy-100 dark:bg-rapanel-navy-700 text-rapanel-text-light/60 dark:text-white/50 text-xs uppercase tracking-wider">
                            <th class="text-left px-4 py-3 w-12">{{ __('Order') }}</th>
                            <th class="text-left px-4 py-3 w-12">{{ __('Icon') }}</th>
                            <th class="text-left px-4 py-3">{{ __('Title') }}</th>
                            <th class="text-left px-4 py-3 hidden md:table-cell">{{ __('Description') }}</th>
                            <th class="text-center px-4 py-3 w-24">{{ __('Articles') }}</th>
                            <th class="text-left px-4 py-3 hidden lg:table-cell">{{ __('Author') }}</th>
                            <th class="text-center px-4 py-3 w-24">{{ __('Published') }}</th>
                            <th class="text-right px-4 py-3 w-28">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-rapanel-navy-100 dark:divide-white/[0.055]">
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
                                    ? 'bg-rapanel-success/10 text-rapanel-success'
                                    : 'bg-rapanel-text-light/10 dark:bg-white/10 text-rapanel-text-light/50 dark:text-white/40'"
                                      class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold">
                                    {{ section.is_published ? __('Yes') : __('No') }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <Link :href="safeRoute('admin.wiki.sections.edit', { section: section.id })"
                                          class="p-1.5 rounded-lg text-rapanel-blue/70 hover:text-rapanel-blue hover:bg-rapanel-blue/10 transition-colors">
                                        <PencilSquareIcon class="w-4 h-4" />
                                    </Link>
                                    <button @click="confirmingDelete = section.id"
                                            class="p-1.5 rounded-lg text-rapanel-danger/70 hover:text-rapanel-danger hover:bg-rapanel-danger/10 transition-colors">
                                        <TrashIcon class="w-4 h-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

        </div>

        <!-- Confirm delete modal -->
        <Teleport to="body">
            <div v-if="confirmingDelete !== null"
                 class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm px-4">
                <div class="bg-white dark:bg-rapanel-navy-800 rounded-2xl shadow-2xl p-6 w-full max-w-sm border border-rapanel-navy-100 dark:border-white/10">
                    <h3 class="text-lg font-bold text-rapanel-text-light dark:text-white mb-2">{{ __('Delete Section') }}</h3>
                    <p class="text-sm text-rapanel-text-light/60 dark:text-white/50 mb-6">
                        {{ __('This will also delete all articles in this section. This action cannot be undone.') }}
                    </p>
                    <div class="flex gap-3 justify-end">
                        <button @click="confirmingDelete = null"
                                class="px-4 py-2 text-sm rounded-lg border border-rapanel-navy-100 dark:border-white/10 text-rapanel-text-light/70 dark:text-white/60 hover:bg-rapanel-navy-50 dark:hover:bg-white/[0.05] transition-colors">
                            {{ __('Cancel') }}
                        </button>
                        <button @click="destroy(confirmingDelete)"
                                class="px-4 py-2 text-sm rounded-lg bg-rapanel-danger hover:bg-rapanel-danger/85 text-white font-semibold transition-colors">
                            {{ __('Delete') }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AdminLayout>
</template>

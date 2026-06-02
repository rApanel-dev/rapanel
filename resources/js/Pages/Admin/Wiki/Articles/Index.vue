<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { PlusIcon, PencilSquareIcon, TrashIcon } from '@heroicons/vue/24/outline';

const safeRoute = (name, params = {}) => { try { return route(name, params); } catch { return '#'; } };

const page = usePage();
const __ = (key, rep = {}) => {
    let t = page.props.translations?.[key] || key;
    Object.entries(rep).forEach(([k, v]) => { t = t.replace(`:${k}`, v); });
    return t;
};

const props = defineProps({
    articles: Object,
    sections: Array,
    filters:  Object,
});

const sectionFilter = ref(props.filters?.section_id ?? '');
const confirmingDelete = ref(null);

watch(sectionFilter, (val) => {
    router.get(safeRoute('admin.wiki.articles.index'), { section_id: val || undefined }, {
        preserveState: true, replace: true,
    });
});

const destroy = (id) => {
    router.delete(safeRoute('admin.wiki.articles.destroy', { article: id }), {
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
                    <h1 class="text-2xl font-display font-bold tracking-wide text-rapanel-text-light dark:text-white">{{ __('Wiki Articles') }}</h1>
                    <p class="text-sm text-rapanel-text-light/50 dark:text-white/40 mt-0.5">{{ __('Admin › Wiki Articles') }}</p>
                </div>
                <Link :href="safeRoute('admin.wiki.articles.create')"
                      class="inline-flex items-center gap-2 px-4 py-2 bg-rapanel-blue hover:bg-rapanel-blue/85 text-white text-sm font-semibold rounded-lg transition-colors">
                    <PlusIcon class="w-4 h-4" />
                    {{ __('New Article') }}
                </Link>
            </div>

            <!-- Filter -->
            <div class="flex items-center gap-3">
                <select v-model="sectionFilter"
                        class="rounded-lg bg-white dark:bg-rapanel-navy-800 border border-rapanel-navy-100 dark:border-white/10
                               text-rapanel-text-light dark:text-white text-sm px-3 py-2 focus:outline-none focus:ring-2 focus:ring-rapanel-blue/50">
                    <option value="">{{ __('All Sections') }}</option>
                    <option v-for="s in sections" :key="s.id" :value="s.id">{{ s.icon }} {{ s.title }}</option>
                </select>
                <span class="text-sm text-rapanel-text-light/50 dark:text-white/40">
                    {{ articles.total }} {{ __('articles') }}
                </span>
            </div>

            <!-- Empty state -->
            <div v-if="!articles.data?.length"
                 class="text-center py-20 text-rapanel-text-light/40 dark:text-white/30">
                <span class="text-5xl">📄</span>
                <p class="mt-4 text-sm">{{ __('No wiki articles yet.') }}</p>
            </div>

            <!-- Table -->
            <div v-else class="bg-white dark:bg-rapanel-navy-800 rounded-xl border border-rapanel-navy-100 dark:border-white/10 overflow-hidden shadow-sm">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-rapanel-navy-100 dark:bg-rapanel-navy-700 text-rapanel-text-light/60 dark:text-white/50 text-xs uppercase tracking-wider">
                            <th class="text-left px-4 py-3">{{ __('Title') }}</th>
                            <th class="text-left px-4 py-3 hidden md:table-cell">{{ __('Section') }}</th>
                            <th class="text-left px-4 py-3 hidden lg:table-cell">{{ __('Author') }}</th>
                            <th class="text-center px-4 py-3 w-20">{{ __('Order') }}</th>
                            <th class="text-center px-4 py-3 w-24">{{ __('Published') }}</th>
                            <th class="text-right px-4 py-3 w-28">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-rapanel-navy-100 dark:divide-white/[0.055]">
                        <tr v-for="article in articles.data" :key="article.id"
                            class="hover:bg-rapanel-navy-50 dark:hover:bg-white/[0.03] transition-colors">
                            <td class="px-4 py-3">
                                <p class="font-medium text-rapanel-text-light dark:text-white">{{ article.title }}</p>
                            </td>
                            <td class="px-4 py-3 hidden md:table-cell text-rapanel-text-light/60 dark:text-white/50">
                                <span v-if="article.section_title">{{ article.section_icon }} {{ article.section_title }}</span>
                            </td>
                            <td class="px-4 py-3 hidden lg:table-cell">
                                <p class="text-xs text-rapanel-text-light dark:text-white/80">{{ article.created_by_name ?? '—' }}</p>
                                <p v-if="article.updated_by_name" class="text-xs text-rapanel-text-light/50 dark:text-white/40 mt-0.5">
                                    {{ __('Updated by') }}: {{ article.updated_by_name }}
                                </p>
                                <p class="text-xs text-rapanel-text-light/40 dark:text-white/35 mt-0.5">{{ article.created_at }}</p>
                            </td>
                            <td class="px-4 py-3 text-center text-rapanel-text-light/50 dark:text-white/40 font-mono text-xs">{{ article.sort_order }}</td>
                            <td class="px-4 py-3 text-center">
                                <span :class="article.is_published
                                    ? 'bg-rapanel-success/10 text-rapanel-success'
                                    : 'bg-rapanel-text-light/10 dark:bg-white/10 text-rapanel-text-light/50 dark:text-white/40'"
                                      class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold">
                                    {{ article.is_published ? __('Yes') : __('No') }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <Link :href="safeRoute('admin.wiki.articles.edit', { article: article.id })"
                                          class="p-1.5 rounded-lg text-rapanel-blue/70 hover:text-rapanel-blue hover:bg-rapanel-blue/10 transition-colors">
                                        <PencilSquareIcon class="w-4 h-4" />
                                    </Link>
                                    <button @click="confirmingDelete = article.id"
                                            class="p-1.5 rounded-lg text-rapanel-danger/70 hover:text-rapanel-danger hover:bg-rapanel-danger/10 transition-colors">
                                        <TrashIcon class="w-4 h-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div v-if="articles.last_page > 1"
                     class="px-4 py-3 border-t border-rapanel-navy-100 dark:border-white/[0.055] flex items-center justify-between">
                    <p class="text-xs text-rapanel-text-light/50 dark:text-white/40">
                        {{ articles.from }}–{{ articles.to }} / {{ articles.total }}
                    </p>
                    <div class="flex gap-1">
                        <Link v-if="articles.prev_page_url" :href="articles.prev_page_url"
                              class="px-3 py-1 text-xs rounded-lg bg-rapanel-navy-100 dark:bg-white/[0.07] hover:bg-rapanel-blue/10 dark:hover:bg-rapanel-blue/20 transition-colors">
                            ‹
                        </Link>
                        <Link v-if="articles.next_page_url" :href="articles.next_page_url"
                              class="px-3 py-1 text-xs rounded-lg bg-rapanel-navy-100 dark:bg-white/[0.07] hover:bg-rapanel-blue/10 dark:hover:bg-rapanel-blue/20 transition-colors">
                            ›
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <!-- Confirm delete modal -->
        <Teleport to="body">
            <div v-if="confirmingDelete !== null"
                 class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm px-4">
                <div class="bg-white dark:bg-rapanel-navy-800 rounded-2xl shadow-2xl p-6 w-full max-w-sm border border-rapanel-navy-100 dark:border-white/10">
                    <h3 class="text-lg font-bold text-rapanel-text-light dark:text-white mb-2">{{ __('Delete Article') }}</h3>
                    <p class="text-sm text-rapanel-text-light/60 dark:text-white/50 mb-6">{{ __('This action cannot be undone.') }}</p>
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

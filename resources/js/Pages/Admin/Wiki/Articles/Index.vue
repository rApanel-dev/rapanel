<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import ActionButton from '@/Components/ActionButton.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { PlusIcon, BookOpenIcon } from '@heroicons/vue/24/outline';

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

const confirmState = ref(null);
const askConfirm = (opts) => { confirmState.value = opts; };
const doConfirm   = () => { confirmState.value?.action?.(); confirmState.value = null; };
const closeConfirm = () => { confirmState.value = null; };

watch(sectionFilter, (val) => {
    router.get(safeRoute('admin.wiki.articles.index'), { section_id: val || undefined }, {
        preserveState: true, replace: true,
    });
});

const confirmDelete = (id, title) => {
    askConfirm({
        title:        __('Delete Article'),
        entity:       title,
        message:      __('This action cannot be undone.'),
        confirmLabel: __('Delete'),
        variant:      'danger',
        action:       () => router.delete(safeRoute('admin.wiki.articles.destroy', { article: id })),
    });
};
</script>

<template>
    <AdminLayout>
        <div class="space-y-6">

            <PageHeader :title="__('Wiki Articles')" :description="`${articles.total} ${__('articles')}`">
                <Link :href="safeRoute('admin.wiki.articles.create')"
                      class="inline-flex items-center gap-2 px-4 py-2 bg-rapanel-blue hover:opacity-90 text-white text-sm font-bold rounded-lg transition shadow">
                    <PlusIcon class="w-4 h-4" aria-hidden="true" />
                    {{ __('New Article') }}
                </Link>
            </PageHeader>

            <!-- Filter -->
            <div class="flex items-center gap-3 flex-wrap">
                <select v-model="sectionFilter"
                        :aria-label="__('Filter by section')"
                        class="rounded-lg bg-white dark:bg-rapanel-surface border border-rapanel-navy-100 dark:border-white/10
                               text-rapanel-text-light dark:text-white text-sm px-3 py-2 focus:outline-none focus:ring-2 focus:ring-rapanel-blue/50">
                    <option value="">{{ __('All Sections') }}</option>
                    <option v-for="s in sections" :key="s.id" :value="s.id">{{ s.icon }} {{ s.title }}</option>
                </select>
            </div>

            <!-- Empty state -->
            <div v-if="!articles.data?.length"
                 class="bg-white dark:bg-rapanel-surface rounded-xl border border-rapanel-navy-100 dark:border-white/[0.07] shadow-[0_4px_20px_rgba(0,0,0,0.22)] dark:shadow-[0_4px_28px_rgba(0,0,0,0.5)] p-14 flex flex-col items-center justify-center gap-5 text-center">
                <div class="w-16 h-16 rounded-2xl bg-rapanel-navy-50 dark:bg-white/[0.04] ring-1 ring-rapanel-navy-100 dark:ring-white/[0.07] flex items-center justify-center">
                    <BookOpenIcon class="w-8 h-8 text-rapanel-text-light/30 dark:text-white/20" />
                </div>
                <div>
                    <div class="text-lg font-display font-bold tracking-wide text-rapanel-text-light dark:text-white">
                        {{ sectionFilter ? __('No articles in this section.') : __('No wiki articles yet.') }}
                    </div>
                    <div class="text-sm text-rapanel-text-light/50 dark:text-white/40 mt-1.5">
                        {{ __('Create the first article to get started.') }}
                    </div>
                </div>
                <Link :href="safeRoute('admin.wiki.articles.create')"
                      class="inline-flex items-center gap-2 px-4 py-2 bg-rapanel-blue hover:opacity-90 text-white text-sm font-semibold rounded-lg transition shadow">
                    <PlusIcon class="w-4 h-4" aria-hidden="true" />
                    {{ __('New Article') }}
                </Link>
            </div>

            <!-- Table -->
            <div v-else class="bg-white dark:bg-rapanel-surface rounded-xl border border-rapanel-navy-100 dark:border-white/[0.07] overflow-hidden shadow-[0_4px_20px_rgba(0,0,0,0.22)] dark:shadow-[0_4px_28px_rgba(0,0,0,0.5)]">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-white/5">
                            <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40">{{ __('Title') }}</th>
                            <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40 hidden md:table-cell">{{ __('Section') }}</th>
                            <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40 hidden lg:table-cell">{{ __('Author') }}</th>
                            <th class="px-4 py-3 text-center text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40 w-20">{{ __('Order') }}</th>
                            <th class="px-4 py-3 text-center text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40 w-24">{{ __('Published') }}</th>
                            <th class="px-4 py-3 text-center text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40 w-28">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-rapanel-navy-100 dark:divide-white/5">
                        <tr v-for="article in articles.data" :key="article.id"
                            class="hover:bg-rapanel-navy-50 dark:hover:bg-white/[0.03] transition-colors">
                            <td class="px-4 py-3 font-medium text-rapanel-text-light dark:text-white max-w-xs truncate">{{ article.title }}</td>
                            <td class="px-4 py-3 hidden md:table-cell text-rapanel-text-light/60 dark:text-white/50">
                                <span v-if="article.section_title">{{ article.section_icon }} {{ article.section_title }}</span>
                                <span v-else class="text-rapanel-text-light/30 dark:text-white/20">—</span>
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
                                    ? 'bg-rapanel-success/10 text-rapanel-success border-rapanel-success/20'
                                    : 'bg-rapanel-text-light/10 dark:bg-white/10 text-rapanel-text-light/50 dark:text-white/40 border-rapanel-navy-100 dark:border-white/10'"
                                      class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold border">
                                    {{ article.is_published ? __('Yes') : __('No') }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-center gap-1">
                                    <ActionButton variant="navy" size="icon" :title="__('Edit article')"
                                        @click="router.visit(safeRoute('admin.wiki.articles.edit', { article: article.id }))">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/></svg>
                                    </ActionButton>
                                    <ActionButton variant="danger" size="icon" :title="__('Delete article')"
                                        @click="confirmDelete(article.id, article.title)">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                    </ActionButton>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div v-if="articles.last_page > 1"
                     class="px-4 py-3 border-t border-rapanel-navy-100 dark:border-white/10 flex items-center justify-between text-xs text-rapanel-text-light/50 dark:text-white/40">
                    <span>{{ articles.from }}–{{ articles.to }} / {{ articles.total }}</span>
                    <div class="flex gap-1">
                        <Link v-for="link in articles.links" :key="link.label"
                              :href="link.url ?? '#'"
                              :class="[
                                  'px-2.5 py-1 rounded transition',
                                  link.active
                                    ? 'bg-rapanel-blue text-white'
                                    : 'hover:bg-rapanel-navy-100 dark:hover:bg-white/10 text-rapanel-text-light/60 dark:text-white/50',
                                  !link.url && 'opacity-40 pointer-events-none'
                              ]"
                              v-html="link.label" />
                    </div>
                </div>
                <div v-else class="px-4 py-3 border-t border-rapanel-navy-100 dark:border-white/10 text-xs text-rapanel-text-light/50 dark:text-white/40">
                    {{ articles.total }} {{ __('articles') }}
                </div>
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

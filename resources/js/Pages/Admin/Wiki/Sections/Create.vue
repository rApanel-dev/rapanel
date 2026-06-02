<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { ArrowLeftIcon } from '@heroicons/vue/24/outline';

const safeRoute = (name, params = {}) => { try { return route(name, params); } catch { return '#'; } };

const page = usePage();
const __ = (key, rep = {}) => {
    let t = page.props.translations?.[key] || key;
    Object.entries(rep).forEach(([k, v]) => { t = t.replace(`:${k}`, v); });
    return t;
};

const form = useForm({
    title:        '',
    description:  '',
    icon:         '📖',
    sort_order:   0,
    is_published: true,
});

const submit = () => {
    form.post(safeRoute('admin.wiki.sections.store'));
};
</script>

<template>
    <AdminLayout>
        <div class="max-w-2xl space-y-6">

            <!-- Header -->
            <div class="flex items-center gap-4 pb-5 border-b border-rapanel-navy-100 dark:border-white/[0.055]">
                <Link :href="safeRoute('admin.wiki.sections.index')"
                      class="p-2 rounded-lg hover:bg-rapanel-navy-100 dark:hover:bg-white/[0.07] text-rapanel-text-light/55 dark:text-white/45 transition-colors">
                    <ArrowLeftIcon class="w-5 h-5" />
                </Link>
                <div>
                    <h1 class="text-2xl font-display font-bold tracking-wide text-rapanel-text-light dark:text-white">{{ __('Create Wiki Section') }}</h1>
                    <p class="text-sm text-rapanel-text-light/50 dark:text-white/40 mt-0.5">{{ __('Admin › Wiki Sections › Create') }}</p>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-5">

                <!-- Icon + Title row -->
                <div class="grid grid-cols-1 md:grid-cols-[120px_1fr] gap-4">

                    <!-- Icon -->
                    <div class="bg-white dark:bg-rapanel-navy-800 rounded-xl border border-rapanel-navy-100 dark:border-white/10 p-5 shadow-sm">
                        <label class="block text-xs font-bold uppercase tracking-wider text-rapanel-text-light/50 dark:text-white/40 mb-3">
                            {{ __('Icon') }}
                        </label>
                        <input v-model="form.icon" type="text" maxlength="10"
                               class="w-full text-center text-3xl bg-transparent border-none outline-none focus:ring-0"
                               placeholder="📖" />
                        <p class="mt-2 text-xs text-rapanel-text-light/40 dark:text-white/30 text-center">{{ __('Emoji') }}</p>
                        <p v-if="form.errors.icon" class="mt-1 text-xs text-rapanel-danger">{{ form.errors.icon }}</p>
                    </div>

                    <!-- Title -->
                    <div class="bg-white dark:bg-rapanel-navy-800 rounded-xl border border-rapanel-navy-100 dark:border-white/10 p-5 shadow-sm">
                        <label class="block text-xs font-bold uppercase tracking-wider text-rapanel-text-light/50 dark:text-white/40 mb-3">
                            {{ __('Title') }} <span class="text-rapanel-danger">*</span>
                        </label>
                        <input v-model="form.title" type="text" maxlength="100" autofocus
                               :placeholder="__('e.g. Game Mechanics')"
                               class="w-full rounded-lg bg-rapanel-navy-50 dark:bg-rapanel-navy-700 border border-rapanel-navy-100 dark:border-white/10
                                      text-rapanel-text-light dark:text-white text-sm px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-rapanel-blue/50" />
                        <p v-if="form.errors.title" class="mt-1.5 text-xs text-rapanel-danger">{{ form.errors.title }}</p>
                    </div>
                </div>

                <!-- Description -->
                <div class="bg-white dark:bg-rapanel-navy-800 rounded-xl border border-rapanel-navy-100 dark:border-white/10 p-5 shadow-sm">
                    <label class="block text-xs font-bold uppercase tracking-wider text-rapanel-text-light/50 dark:text-white/40 mb-3">
                        {{ __('Description') }} <span class="text-rapanel-text-light/30 dark:text-white/25 font-normal normal-case">{{ __('(Optional)') }}</span>
                    </label>
                    <textarea v-model="form.description" rows="3" maxlength="500"
                              :placeholder="__('Short description shown on the wiki index...')"
                              class="w-full rounded-lg bg-rapanel-navy-50 dark:bg-rapanel-navy-700 border border-rapanel-navy-100 dark:border-white/10
                                     text-rapanel-text-light dark:text-white text-sm px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-rapanel-blue/50 resize-none" />
                    <p v-if="form.errors.description" class="mt-1.5 text-xs text-rapanel-danger">{{ form.errors.description }}</p>
                </div>

                <!-- Sort order + Published -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                    <div class="bg-white dark:bg-rapanel-navy-800 rounded-xl border border-rapanel-navy-100 dark:border-white/10 p-5 shadow-sm">
                        <label class="block text-xs font-bold uppercase tracking-wider text-rapanel-text-light/50 dark:text-white/40 mb-3">
                            {{ __('Sort Order') }}
                        </label>
                        <input v-model.number="form.sort_order" type="number" min="0"
                               class="w-full rounded-lg bg-rapanel-navy-50 dark:bg-rapanel-navy-700 border border-rapanel-navy-100 dark:border-white/10
                                      text-rapanel-text-light dark:text-white text-sm px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-rapanel-blue/50" />
                        <p class="mt-1.5 text-xs text-rapanel-text-light/40 dark:text-white/30">{{ __('Lower number = shown first') }}</p>
                    </div>

                    <div class="bg-white dark:bg-rapanel-navy-800 rounded-xl border border-rapanel-navy-100 dark:border-white/10 p-5 shadow-sm flex items-center justify-between">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wider text-rapanel-text-light/50 dark:text-white/40">{{ __('Published') }}</p>
                            <p class="text-xs text-rapanel-text-light/40 dark:text-white/30 mt-1">{{ __('Visible to all visitors') }}</p>
                        </div>
                        <button type="button" @click="form.is_published = !form.is_published"
                                :class="form.is_published ? 'bg-rapanel-success' : 'bg-rapanel-navy-200 dark:bg-rapanel-navy-600'"
                                class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-rapanel-blue/50">
                            <span :class="form.is_published ? 'translate-x-6' : 'translate-x-1'"
                                  class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform" />
                        </button>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-end gap-3 pt-2">
                    <Link :href="safeRoute('admin.wiki.sections.index')"
                          class="px-4 py-2 text-sm rounded-lg border border-rapanel-navy-100 dark:border-white/10 text-rapanel-text-light/70 dark:text-white/60 hover:bg-rapanel-navy-50 dark:hover:bg-white/[0.05] transition-colors">
                        {{ __('Cancel') }}
                    </Link>
                    <button type="submit" :disabled="form.processing"
                            class="px-5 py-2 text-sm rounded-lg bg-rapanel-blue hover:bg-rapanel-blue/85 text-white font-semibold transition-colors disabled:opacity-60">
                        {{ __('Create Section') }}
                    </button>
                </div>

            </form>
        </div>
    </AdminLayout>
</template>

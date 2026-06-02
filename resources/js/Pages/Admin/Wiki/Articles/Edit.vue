<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { ArrowLeftIcon, PhotoIcon } from '@heroicons/vue/24/outline';
import RichTextEditor from '@/Components/RichTextEditor.vue';

const safeRoute = (name, params = {}) => { try { return route(name, params); } catch { return '#'; } };

const page = usePage();
const __ = (key, rep = {}) => {
    let t = page.props.translations?.[key] || key;
    Object.entries(rep).forEach(([k, v]) => { t = t.replace(`:${k}`, v); });
    return t;
};

const props = defineProps({
    article:  Object,
    sections: Array,
});

const imagePreview = ref(props.article.featured_image_url ?? null);

const form = useForm({
    _method:        'put',
    section_id:     props.article.section_id,
    title:          props.article.title,
    content:        props.article.content ?? '',
    featured_image: null,
    focal_x:        props.article.focal_x ?? 50,
    focal_y:        props.article.focal_y ?? 50,
    show_toc:       props.article.show_toc ?? true,
    sort_order:     props.article.sort_order ?? 0,
    is_published:   props.article.is_published,
});

const onImageChange = (e) => {
    const file = e.target.files[0];
    if (!file) return;
    form.featured_image = file;
    imagePreview.value = URL.createObjectURL(file);
};

const setFocalPoint = (e) => {
    const rect = e.currentTarget.getBoundingClientRect();
    form.focal_x = Math.round(Math.max(0, Math.min(100, ((e.clientX - rect.left)  / rect.width)  * 100)));
    form.focal_y = Math.round(Math.max(0, Math.min(100, ((e.clientY - rect.top)   / rect.height) * 100)));
};

const focalStyle = computed(() => ({ objectPosition: `${form.focal_x}% ${form.focal_y}%` }));

const submit = () => {
    form.post(safeRoute('admin.wiki.articles.update', { article: props.article.id }), {
        forceFormData: true,
    });
};
</script>

<template>
    <AdminLayout>
        <div class="max-w-4xl space-y-6">

            <!-- Header -->
            <div class="flex items-center gap-4 pb-5 border-b border-rapanel-navy-100 dark:border-white/[0.055]">
                <Link :href="safeRoute('admin.wiki.articles.index')"
                      class="p-2 rounded-lg hover:bg-rapanel-navy-100 dark:hover:bg-white/[0.07] text-rapanel-text-light/55 dark:text-white/45 transition-colors">
                    <ArrowLeftIcon class="w-5 h-5" />
                </Link>
                <div>
                    <h1 class="text-2xl font-display font-bold tracking-wide text-rapanel-text-light dark:text-white">{{ __('Edit Wiki Article') }}</h1>
                    <p class="text-sm text-rapanel-text-light/50 dark:text-white/40 mt-0.5">{{ __('Admin › Wiki Articles › Edit') }}</p>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-5">

                <!-- Section + Title -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                    <div class="bg-white dark:bg-rapanel-navy-800 rounded-xl border border-rapanel-navy-100 dark:border-white/10 p-5 shadow-sm">
                        <label class="block text-xs font-bold uppercase tracking-wider text-rapanel-text-light/50 dark:text-white/40 mb-3">
                            {{ __('Section') }} <span class="text-rapanel-danger">*</span>
                        </label>
                        <select v-model="form.section_id"
                                class="w-full rounded-lg bg-rapanel-navy-50 dark:bg-rapanel-navy-700 border border-rapanel-navy-100 dark:border-white/10
                                       text-rapanel-text-light dark:text-white text-sm px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-rapanel-blue/50">
                            <option v-for="s in sections" :key="s.id" :value="s.id"
                                    style="background:#1e2d4a;color:#E2E8F0">
                                {{ s.icon }} {{ s.title }}
                            </option>
                        </select>
                        <p v-if="form.errors.section_id" class="mt-1.5 text-xs text-rapanel-danger">{{ form.errors.section_id }}</p>
                    </div>

                    <div class="bg-white dark:bg-rapanel-navy-800 rounded-xl border border-rapanel-navy-100 dark:border-white/10 p-5 shadow-sm">
                        <label class="block text-xs font-bold uppercase tracking-wider text-rapanel-text-light/50 dark:text-white/40 mb-3">
                            {{ __('Title') }} <span class="text-rapanel-danger">*</span>
                        </label>
                        <input v-model="form.title" type="text" maxlength="200" autofocus
                               class="w-full rounded-lg bg-rapanel-navy-50 dark:bg-rapanel-navy-700 border border-rapanel-navy-100 dark:border-white/10
                                      text-rapanel-text-light dark:text-white text-sm px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-rapanel-blue/50" />
                        <p v-if="form.errors.title" class="mt-1.5 text-xs text-rapanel-danger">{{ form.errors.title }}</p>
                    </div>
                </div>

                <!-- Featured image + focal point picker -->
                <div class="bg-white dark:bg-rapanel-navy-800 rounded-xl border border-rapanel-navy-100 dark:border-white/10 p-5 shadow-sm">
                    <label class="block text-xs font-bold uppercase tracking-wider text-rapanel-text-light/50 dark:text-white/40 mb-3">
                        {{ __('Featured Image') }} <span class="text-rapanel-text-light/30 dark:text-white/25 font-normal normal-case">{{ __('(Optional)') }}</span>
                    </label>

                    <div v-if="imagePreview" class="mb-4 grid grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <p class="text-xs text-rapanel-text-light/40 dark:text-white/30">{{ __('Click to set focal point') }}</p>
                            <div class="relative cursor-crosshair rounded-lg overflow-hidden h-32 select-none"
                                 @click="setFocalPoint">
                                <img :src="imagePreview" class="w-full h-full object-cover pointer-events-none" />
                                <div class="absolute pointer-events-none -translate-x-1/2 -translate-y-1/2"
                                     :style="{ left: form.focal_x + '%', top: form.focal_y + '%' }">
                                    <div class="w-5 h-5 rounded-full border-2 border-white shadow-[0_0_0_1px_rgba(0,0,0,0.4)] bg-white/30" />
                                </div>
                            </div>
                        </div>
                        <div class="space-y-1">
                            <p class="text-xs text-rapanel-text-light/40 dark:text-white/30">{{ __('Preview') }}</p>
                            <div class="rounded-lg overflow-hidden h-32">
                                <img :src="imagePreview" class="w-full h-full object-cover" :style="focalStyle" />
                            </div>
                        </div>
                    </div>

                    <label class="flex items-center gap-2 cursor-pointer w-fit px-3 py-2 rounded-lg bg-rapanel-navy-50 dark:bg-rapanel-navy-700 border border-rapanel-navy-100 dark:border-white/10
                                  text-rapanel-text-light/60 dark:text-white/50 text-sm hover:bg-rapanel-navy-100 dark:hover:bg-rapanel-navy-600 transition-colors">
                        <PhotoIcon class="w-4 h-4" />
                        {{ __('Change image') }}
                        <input type="file" accept="image/*" class="sr-only" @change="onImageChange" />
                    </label>
                    <p v-if="form.errors.featured_image" class="mt-1.5 text-xs text-rapanel-danger">{{ form.errors.featured_image }}</p>
                </div>

                <!-- Content -->
                <div class="bg-white dark:bg-rapanel-navy-800 rounded-xl border border-rapanel-navy-100 dark:border-white/10 p-5 shadow-sm">
                    <label class="block text-xs font-bold uppercase tracking-wider text-rapanel-text-light/50 dark:text-white/40 mb-3">
                        {{ __('Content') }}
                    </label>
                    <RichTextEditor v-model="form.content" />
                    <p v-if="form.errors.content" class="mt-1.5 text-xs text-rapanel-danger">{{ form.errors.content }}</p>
                </div>

                <!-- Sort order + TOC + Published -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

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
                            <p class="text-xs font-bold uppercase tracking-wider text-rapanel-text-light/50 dark:text-white/40">{{ __('Show TOC') }}</p>
                            <p class="text-xs text-rapanel-text-light/40 dark:text-white/30 mt-1">{{ __('Auto-generated index') }}</p>
                        </div>
                        <button type="button" @click="form.show_toc = !form.show_toc"
                                :class="form.show_toc ? 'bg-rapanel-success' : 'bg-rapanel-navy-200 dark:bg-rapanel-navy-600'"
                                class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-rapanel-blue/50">
                            <span :class="form.show_toc ? 'translate-x-6' : 'translate-x-1'"
                                  class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform" />
                        </button>
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
                    <Link :href="safeRoute('admin.wiki.articles.index')"
                          class="px-4 py-2 text-sm rounded-lg border border-rapanel-navy-100 dark:border-white/10 text-rapanel-text-light/70 dark:text-white/60 hover:bg-rapanel-navy-50 dark:hover:bg-white/[0.05] transition-colors">
                        {{ __('Cancel') }}
                    </Link>
                    <button type="submit" :disabled="form.processing"
                            class="px-5 py-2 text-sm rounded-lg bg-rapanel-blue hover:bg-rapanel-blue/85 text-white font-semibold transition-colors disabled:opacity-60">
                        {{ __('Save Changes') }}
                    </button>
                </div>

            </form>
        </div>
    </AdminLayout>
</template>

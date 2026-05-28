<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import { ArrowLeftIcon, PhotoIcon } from '@heroicons/vue/24/outline';
import RichTextEditor from '@/Components/RichTextEditor.vue';

const safeRoute = (name, params = {}) => { try { return route(name, params); } catch { return '#'; } };

const page = usePage();
const __ = (key, rep = {}) => {
    let t = page.props.translations?.[key] || key;
    Object.entries(rep).forEach(([k, v]) => { t = t.replace(`:${k}`, v); });
    return t;
};

const imagePreview = ref(null);

const form = useForm({
    title: '',
    body: '',
    type: 1,
    is_published: true,
    is_pinned: false,
    allow_comments: true,
    featured_image: null,
});

const onImageChange = (e) => {
    const file = e.target.files[0];
    if (!file) return;
    form.featured_image = file;
    imagePreview.value = URL.createObjectURL(file);
};

const submit = () => {
    form.post(safeRoute('admin.news.store'), {
        forceFormData: true,
    });
};
</script>

<template>
    <AdminLayout>
        <div class="max-w-3xl space-y-6">

            <!-- Header -->
            <div class="flex items-center gap-4 pb-5 border-b border-rapanel-navy-100 dark:border-white/[0.055]">
                <Link :href="safeRoute('admin.news.index')"
                      class="p-2 rounded-lg hover:bg-rapanel-navy-100 dark:hover:bg-white/[0.07] text-rapanel-text-light/55 dark:text-white/45 transition-colors">
                    <ArrowLeftIcon class="w-5 h-5" />
                </Link>
                <div>
                    <h1 class="text-2xl font-display font-bold tracking-wide text-rapanel-text-light dark:text-white">{{ __('Create News') }}</h1>
                    <p class="text-sm text-rapanel-text-light/50 dark:text-white/40 mt-0.5">{{ __('Admin › News › Create') }}</p>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-6">

                <!-- Category + Image row -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                    <!-- Category -->
                    <div class="bg-white dark:bg-rapanel-navy-800 rounded-xl border border-rapanel-navy-100 dark:border-white/10 p-5 shadow-sm">
                        <label class="block text-xs font-bold uppercase tracking-wider text-rapanel-text-light/50 dark:text-white/40 mb-3">
                            {{ __('News Category') }}
                        </label>
                        <select v-model="form.type"
                                class="w-full rounded-lg bg-rapanel-navy-50 dark:bg-rapanel-navy-800 border border-rapanel-navy-100 dark:border-white/10
                                       text-rapanel-text-light dark:text-white text-sm px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-rapanel-blue/50">
                            <option style="background:#1e2d4a;color:#E2E8F0" :value="1">{{ __('News') }}</option>
                            <option style="background:#1e2d4a;color:#E2E8F0" :value="2">{{ __('Event') }}</option>
                            <option style="background:#1e2d4a;color:#E2E8F0" :value="3">{{ __('Notice') }}</option>
                        </select>
                        <p v-if="form.errors.type" class="mt-1.5 text-xs text-rapanel-danger">{{ form.errors.type }}</p>
                    </div>

                    <!-- Featured image -->
                    <div class="bg-white dark:bg-rapanel-navy-800 rounded-xl border border-rapanel-navy-100 dark:border-white/10 p-5 shadow-sm">
                        <label class="block text-xs font-bold uppercase tracking-wider text-rapanel-text-light/50 dark:text-white/40 mb-3">
                            {{ __('Featured Image') }} <span class="normal-case font-normal">{{ __('(Optional)') }}</span>
                        </label>
                        <label class="relative flex flex-col items-center justify-center h-28 rounded-lg border-2 border-dashed
                                      border-rapanel-navy-100 dark:border-white/20 hover:border-rapanel-blue/50 transition cursor-pointer overflow-hidden">
                            <img v-if="imagePreview" :src="imagePreview" class="absolute inset-0 w-full h-full object-cover" />
                            <div v-else class="flex flex-col items-center gap-1 text-rapanel-text-light/40 dark:text-white/30">
                                <PhotoIcon class="w-8 h-8" />
                                <span class="text-xs">{{ __('Upload PNG, JPG, GIF up to 2MB') }}</span>
                            </div>
                            <input type="file" class="sr-only" accept="image/*" @change="onImageChange" />
                        </label>
                        <p v-if="form.errors.featured_image" class="mt-1.5 text-xs text-rapanel-danger">{{ form.errors.featured_image }}</p>
                    </div>
                </div>

                <!-- Title -->
                <div class="bg-white dark:bg-rapanel-navy-800 rounded-xl border border-rapanel-navy-100 dark:border-white/10 p-5 shadow-sm">
                    <label class="block text-xs font-bold uppercase tracking-wider text-rapanel-text-light/50 dark:text-white/40 mb-3">{{ __('Title') }}</label>
                    <input v-model="form.title" type="text" :placeholder="__('News title...')"
                           class="w-full rounded-lg bg-rapanel-navy-50 dark:bg-white/5 border border-rapanel-navy-100 dark:border-white/10
                                  text-rapanel-text-light dark:text-white text-sm px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-rapanel-blue/50" />
                    <p v-if="form.errors.title" class="mt-1.5 text-xs text-rapanel-danger">{{ form.errors.title }}</p>
                </div>

                <!-- Body -->
                <div class="bg-white dark:bg-rapanel-navy-800 rounded-xl border border-rapanel-navy-100 dark:border-white/10 p-5 shadow-sm">
                    <label class="block text-xs font-bold uppercase tracking-wider text-rapanel-text-light/50 dark:text-white/40 mb-3">{{ __('Content') }}</label>
                    <RichTextEditor v-model="form.body" />
                    <p v-if="form.errors.body" class="mt-1.5 text-xs text-rapanel-danger">{{ form.errors.body }}</p>
                </div>

                <!-- Toggles -->
                <div class="bg-white dark:bg-rapanel-navy-800 rounded-xl border border-rapanel-navy-100 dark:border-white/10 p-5 shadow-sm flex flex-wrap gap-6">
                    <label v-for="(label, field) in { is_published: 'Published', is_pinned: 'Pinned', allow_comments: 'Allow Comments' }"
                           :key="field" class="flex items-center gap-2.5 cursor-pointer select-none">
                        <button type="button" @click="form[field] = !form[field]"
                                :class="['relative w-10 h-5 rounded-full transition-colors duration-200 focus:outline-none',
                                         form[field] ? 'bg-rapanel-blue' : 'bg-rapanel-navy-100 dark:bg-white/20']">
                            <span :class="['absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform duration-200',
                                           form[field] ? 'translate-x-5' : 'translate-x-0']" />
                        </button>
                        <span class="text-sm font-medium text-rapanel-text-light dark:text-white">{{ __(label) }}</span>
                    </label>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-end gap-3">
                    <Link :href="safeRoute('admin.news.index')"
                          class="px-4 py-2 rounded-lg text-sm font-medium text-rapanel-text-light/70 dark:text-white/60
                                 hover:bg-rapanel-navy-100 dark:hover:bg-white/10 transition">
                        {{ __('Cancel') }}
                    </Link>
                    <button type="submit" :disabled="form.processing"
                            class="px-5 py-2 rounded-lg bg-rapanel-blue text-white text-sm font-semibold hover:opacity-90 transition shadow disabled:opacity-60">
                        {{ form.processing ? __('Creating…') : __('Create News') }}
                    </button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>

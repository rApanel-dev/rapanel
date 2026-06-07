<script setup>
import { ref } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineProps({
    categories: { type: Array, default: () => [] },
});

const page = usePage();
const __ = (key) => page.props.translations?.[key] || key;

// 'external' | 'upload' | 'local'
const sourceMode = ref('external');

const form = useForm({
    name:                   '',
    category_id:            '',
    description:            '',
    image:                  null,
    is_external:            true,
    is_external_url_hidden: false,
    file_url:               '',
    file_name:              '',
    file:                   null,
    is_only_auth:           false,
    is_active:              true,
    sort_order:             0,
});

// Local files
const localFiles    = ref([]);
const localLoading  = ref(false);
const localSelected = ref(null);
const localSearch   = ref('');

const formatSize = (bytes) => {
    if (bytes >= 1073741824) return (bytes / 1073741824).toFixed(2) + ' GB';
    if (bytes >= 1048576)    return (bytes / 1048576).toFixed(1) + ' MB';
    return (bytes / 1024).toFixed(0) + ' KB';
};

const loadLocalFiles = async () => {
    if (localFiles.value.length) return;
    localLoading.value = true;
    try {
        const res  = await fetch(route('admin.downloads.local-files'));
        localFiles.value = await res.json();
    } finally {
        localLoading.value = false;
    }
};

const selectLocalFile = (file) => {
    localSelected.value  = file;
    form.file_url        = window.location.origin + file.url;
    form.file_name       = form.file_name || file.name;
    form.is_external     = true;
};

const setSourceMode = (mode) => {
    sourceMode.value = mode;
    form.is_external = mode !== 'upload';
    if (mode === 'local') loadLocalFiles();
};

const submit = () => {
    form.post(route('admin.downloads.store'), {
        forceFormData: true,
    });
};
</script>

<template>
    <AdminLayout>
        <div class="flex items-center gap-3 mb-6">
            <Link :href="route('admin.downloads.index')" class="text-sm text-rapanel-text-light dark:text-rapanel-text-dark hover:text-rapanel-blue transition-colors">
                ← {{ __('Downloads') }}
            </Link>
            <span class="text-rapanel-text-light dark:text-rapanel-text-dark opacity-40">/</span>
            <h1 class="text-xl font-bold text-rapanel-navy-900 dark:text-white">{{ __('New Download') }}</h1>
        </div>

        <form @submit.prevent="submit" class="space-y-6 max-w-2xl">

            <!-- Name -->
            <div class="bg-white dark:bg-rapanel-navy-900 rounded-xl border border-rapanel-navy-100 dark:border-white/10 shadow-sm p-6 space-y-4">
                <h2 class="text-sm font-bold text-rapanel-navy-900 dark:text-white uppercase tracking-wider">{{ __('Basic Info') }}</h2>

                <div>
                    <label class="block text-sm font-semibold text-rapanel-navy-900 dark:text-white mb-1.5">{{ __('Title') }} *</label>
                    <input v-model="form.name" type="text" required
                        class="w-full rounded-lg border border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-white/[0.05] px-3 py-2 text-sm text-rapanel-navy-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-rapanel-blue" />
                    <p v-if="form.errors.name" class="mt-1 text-xs text-rapanel-danger">{{ form.errors.name }}</p>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-rapanel-navy-900 dark:text-white mb-1.5">{{ __('Category') }}</label>
                    <select v-model="form.category_id"
                        class="w-full rounded-lg border border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-white/[0.05] px-3 py-2 text-sm text-rapanel-navy-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-rapanel-blue">
                        <option value="">— {{ __('No category') }} —</option>
                        <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                            {{ cat.icon ? cat.icon + ' ' : '' }}{{ cat.name }}
                        </option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-rapanel-navy-900 dark:text-white mb-1.5">{{ __('Description') }}</label>
                    <textarea v-model="form.description" rows="4"
                        class="w-full rounded-lg border border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-white/[0.05] px-3 py-2 text-sm text-rapanel-navy-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-rapanel-blue resize-none" />
                </div>

                <div>
                    <label class="block text-sm font-semibold text-rapanel-navy-900 dark:text-white mb-1.5">{{ __('Card Image') }} <span class="font-normal text-rapanel-text-light dark:text-rapanel-text-dark">(optional)</span></label>
                    <input type="file" accept="image/*" @change="e => form.image = e.target.files[0]"
                        class="text-sm text-rapanel-text-light dark:text-rapanel-text-dark file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-rapanel-blue/10 file:text-rapanel-blue hover:file:bg-rapanel-blue/20 cursor-pointer" />
                    <p v-if="form.errors.image" class="mt-1 text-xs text-rapanel-danger">{{ form.errors.image }}</p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-rapanel-navy-900 dark:text-white mb-1.5">{{ __('Sort Order') }}</label>
                        <input v-model="form.sort_order" type="number" min="0"
                            class="w-full rounded-lg border border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-white/[0.05] px-3 py-2 text-sm text-rapanel-navy-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-rapanel-blue" />
                    </div>
                </div>
            </div>

            <!-- File source -->
            <div class="bg-white dark:bg-rapanel-navy-900 rounded-xl border border-rapanel-navy-100 dark:border-white/10 shadow-sm p-6 space-y-4">
                <h2 class="text-sm font-bold text-rapanel-navy-900 dark:text-white uppercase tracking-wider">{{ __('File Source') }}</h2>

                <!-- Type toggle -->
                <div class="flex rounded-lg overflow-hidden border border-rapanel-navy-100 dark:border-white/10 w-fit">
                    <button type="button" @click="setSourceMode('external')"
                        :class="['px-4 py-2 text-sm font-semibold transition', sourceMode === 'external' ? 'bg-rapanel-blue text-white' : 'text-rapanel-text-light dark:text-rapanel-text-dark hover:bg-rapanel-navy-50 dark:hover:bg-rapanel-navy-800']">
                        {{ __('External URL') }}
                    </button>
                    <button type="button" @click="setSourceMode('upload')"
                        :class="['px-4 py-2 text-sm font-semibold transition', sourceMode === 'upload' ? 'bg-rapanel-blue text-white' : 'text-rapanel-text-light dark:text-rapanel-text-dark hover:bg-rapanel-navy-50 dark:hover:bg-rapanel-navy-800']">
                        {{ __('Upload File') }}
                    </button>
                    <button type="button" @click="setSourceMode('local')"
                        :class="['px-4 py-2 text-sm font-semibold transition', sourceMode === 'local' ? 'bg-rapanel-blue text-white' : 'text-rapanel-text-light dark:text-rapanel-text-dark hover:bg-rapanel-navy-50 dark:hover:bg-rapanel-navy-800']">
                        {{ __('Local (FTP)') }}
                    </button>
                </div>

                <!-- External fields -->
                <template v-if="sourceMode === 'external'">
                    <div>
                        <label class="block text-sm font-semibold text-rapanel-navy-900 dark:text-white mb-1.5">{{ __('Download URL') }} *</label>
                        <input v-model="form.file_url" type="url"
                            placeholder="https://drive.google.com/..."
                            class="w-full rounded-lg border border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-white/[0.05] px-3 py-2 text-sm text-rapanel-navy-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-rapanel-blue font-mono" />
                        <p v-if="form.errors.file_url" class="mt-1 text-xs text-rapanel-danger">{{ form.errors.file_url }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-rapanel-navy-900 dark:text-white mb-1.5">{{ __('File Name') }} <span class="font-normal text-rapanel-text-light dark:text-rapanel-text-dark">(e.g. Client_v1.0.rar)</span></label>
                        <input v-model="form.file_name" type="text"
                            class="w-full rounded-lg border border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-white/[0.05] px-3 py-2 text-sm text-rapanel-navy-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-rapanel-blue font-mono" />
                    </div>

                    <label class="flex items-start gap-3 cursor-pointer select-none">
                        <input type="checkbox" v-model="form.is_external_url_hidden" class="mt-0.5 rounded text-rapanel-blue" />
                        <div>
                            <span class="text-sm font-semibold text-rapanel-navy-900 dark:text-white">{{ __('Hide external URL') }}</span>
                            <p class="text-xs text-rapanel-text-light dark:text-rapanel-text-dark mt-0.5">{{ __('Stream the file through this server to hide the real URL from users.') }}</p>
                        </div>
                    </label>
                </template>

                <!-- Local upload -->
                <template v-else-if="sourceMode === 'upload'">
                    <div>
                        <label class="block text-sm font-semibold text-rapanel-navy-900 dark:text-white mb-1.5">{{ __('File') }} *</label>
                        <input type="file" @change="e => form.file = e.target.files[0]"
                            class="text-sm text-rapanel-text-light dark:text-rapanel-text-dark file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-rapanel-blue/10 file:text-rapanel-blue hover:file:bg-rapanel-blue/20 cursor-pointer" />
                        <p class="mt-1 text-xs text-rapanel-text-light dark:text-rapanel-text-dark">{{ __('Max 512 MB.') }}</p>
                        <p v-if="form.errors.file" class="mt-1 text-xs text-rapanel-danger">{{ form.errors.file }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-rapanel-navy-900 dark:text-white mb-1.5">{{ __('File Name Override') }} <span class="font-normal text-rapanel-text-light dark:text-rapanel-text-dark">(optional)</span></label>
                        <input v-model="form.file_name" type="text"
                            class="w-full rounded-lg border border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-white/[0.05] px-3 py-2 text-sm text-rapanel-navy-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-rapanel-blue font-mono" />
                    </div>
                </template>

                <!-- Local (FTP) -->
                <template v-else-if="sourceMode === 'local'">
                    <div v-if="localSelected" class="flex items-center gap-2 px-3 py-2 rounded-lg bg-rapanel-success/10 border border-rapanel-success/20 text-sm font-mono text-rapanel-success">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        {{ localSelected.name }} ({{ formatSize(localSelected.size) }})
                    </div>

                    <input v-model="localSearch" type="text" :placeholder="__('Search files...')"
                        class="w-full rounded-lg border border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-white/[0.05] px-3 py-2 text-sm text-rapanel-navy-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-rapanel-blue" />

                    <div class="rounded-lg border border-rapanel-navy-100 dark:border-white/10 overflow-hidden max-h-64 overflow-y-auto">
                        <div v-if="localLoading" class="px-4 py-6 text-center text-sm text-rapanel-text-light dark:text-rapanel-text-dark">
                            {{ __('Loading...') }}
                        </div>
                        <template v-else-if="localFiles.filter(f => !localSearch || f.name.toLowerCase().includes(localSearch.toLowerCase())).length">
                            <button v-for="f in localFiles.filter(f => !localSearch || f.name.toLowerCase().includes(localSearch.toLowerCase()))" :key="f.name"
                                type="button" @click="selectLocalFile(f)"
                                :class="['w-full flex items-center justify-between px-4 py-2.5 text-left transition border-b border-rapanel-navy-100 dark:border-white/5 last:border-b-0',
                                    localSelected?.name === f.name
                                        ? 'bg-rapanel-blue/10 text-rapanel-blue'
                                        : 'hover:bg-rapanel-navy-50 dark:hover:bg-white/5 text-rapanel-navy-900 dark:text-white']">
                                <span class="text-sm font-mono truncate">{{ f.name }}</span>
                                <span class="text-xs text-rapanel-text-light dark:text-rapanel-text-dark flex-shrink-0 ml-3">{{ formatSize(f.size) }}</span>
                            </button>
                        </template>
                        <div v-else class="px-4 py-6 text-center text-sm text-rapanel-text-light dark:text-rapanel-text-dark">
                            {{ localSearch ? __('No results.') : __('No files found in client folder.') }}
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-rapanel-navy-900 dark:text-white mb-1.5">{{ __('File Name Override') }} <span class="font-normal text-rapanel-text-light dark:text-rapanel-text-dark">(optional)</span></label>
                        <input v-model="form.file_name" type="text"
                            class="w-full rounded-lg border border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-white/[0.05] px-3 py-2 text-sm text-rapanel-navy-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-rapanel-blue font-mono" />
                    </div>
                </template>
            </div>

            <!-- Visibility -->
            <div class="bg-white dark:bg-rapanel-navy-900 rounded-xl border border-rapanel-navy-100 dark:border-white/10 shadow-sm p-6 space-y-3">
                <h2 class="text-sm font-bold text-rapanel-navy-900 dark:text-white uppercase tracking-wider mb-2">{{ __('Visibility') }}</h2>

                <label class="flex items-center gap-3 cursor-pointer select-none">
                    <input type="checkbox" v-model="form.is_only_auth" class="rounded text-rapanel-blue" />
                    <div>
                        <span class="text-sm font-semibold text-rapanel-navy-900 dark:text-white">{{ __('Authenticated users only') }}</span>
                        <p class="text-xs text-rapanel-text-light dark:text-rapanel-text-dark">{{ __('Only logged-in users can see and download this.') }}</p>
                    </div>
                </label>

                <label class="flex items-center gap-3 cursor-pointer select-none">
                    <input type="checkbox" v-model="form.is_active" class="rounded text-rapanel-blue" />
                    <div>
                        <span class="text-sm font-semibold text-rapanel-navy-900 dark:text-white">{{ __('Active') }}</span>
                        <p class="text-xs text-rapanel-text-light dark:text-rapanel-text-dark">{{ __('Active downloads are visible to users.') }}</p>
                    </div>
                </label>
            </div>

            <!-- Actions -->
            <div class="flex items-center gap-3">
                <button type="submit" :disabled="form.processing"
                    :class="['px-6 py-2.5 rounded-lg bg-rapanel-blue text-white text-sm font-bold hover:opacity-90 transition', form.processing ? 'opacity-50 cursor-not-allowed' : '']">
                    {{ __('Create Download') }}
                </button>
                <Link :href="route('admin.downloads.index')" class="text-sm text-rapanel-text-light dark:text-rapanel-text-dark hover:text-rapanel-blue transition-colors">
                    {{ __('Cancel') }}
                </Link>
            </div>

        </form>
    </AdminLayout>
</template>

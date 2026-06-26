<script setup>
import { ref, reactive, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import {
    PlusIcon,
    PencilIcon,
    TrashIcon,
    ShieldExclamationIcon,
    PhotoIcon,
    XMarkIcon,
    EyeIcon,
    EyeSlashIcon,
} from '@heroicons/vue/24/outline';

// ── WOE preview toggle (shared with WoeLiveBanner via localStorage) ─
const previewActive = ref(localStorage.getItem('woe_preview') === '1');
const togglePreview = () => {
    previewActive.value = !previewActive.value;
    localStorage.setItem('woe_preview', previewActive.value ? '1' : '0');
    window.dispatchEvent(new Event('woe-preview-changed'));
};

const props = defineProps({
    schedules: { type: Array, default: () => [] },
});

const page = usePage();
const __ = (key, rep = {}) => {
    let t = page.props.translations?.[key] || key;
    Object.entries(rep).forEach(([k, v]) => { t = t.replace(`:${k}`, v); });
    return t;
};

const DAY_NAMES = computed(() => [
    __('Sunday'), __('Monday'), __('Tuesday'), __('Wednesday'),
    __('Thursday'), __('Friday'), __('Saturday'),
]);
const TYPE_LABELS = { 1: 'WOE 1 (FE)', 2: 'WOE 2 (SE)', 3: 'WOE TE' };
const TYPE_COLORS = {
    1: 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20',
    2: 'bg-rapanel-blue/10 text-rapanel-blue border-rapanel-blue/20',
    3: 'bg-purple-500/10 text-purple-500 border-purple-500/20',
};

// ── Modal ─────────────────────────────────────────────────────────────
const showModal      = ref(false);
const editId         = ref(null);
const processing     = ref(false);
const imageFile      = ref(null);
const imagePreview   = ref(null);
const currentImgUrl  = ref(null);
const removeImage    = ref(false);
const imageInputRef  = ref(null);

const blankForm = () => ({
    label:      '',
    type:       1,
    start_day:  1,
    start_time: '20:00',
    end_day:    1,
    end_time:   '22:00',
    enabled:    true,
});

const form   = reactive(blankForm());
const errors = reactive({});

const clearErrors = () => Object.keys(errors).forEach(k => delete errors[k]);

const resetImage = () => {
    imageFile.value     = null;
    imagePreview.value  = null;
    removeImage.value   = false;
    if (imageInputRef.value) imageInputRef.value.value = '';
};

const openCreate = () => {
    editId.value      = null;
    currentImgUrl.value = null;
    resetImage();
    Object.assign(form, blankForm());
    clearErrors();
    showModal.value = true;
};

const openEdit = (s) => {
    editId.value        = s.id;
    currentImgUrl.value = s.image_url || null;
    resetImage();
    Object.assign(form, {
        label:      s.label ?? '',
        type:       s.type,
        start_day:  s.start_day,
        start_time: s.start_time,
        end_day:    s.end_day,
        end_time:   s.end_time,
        enabled:    s.enabled,
    });
    clearErrors();
    showModal.value = true;
};

const closeModal = () => { showModal.value = false; };

const onImageChange = (e) => {
    const file = e.target.files[0];
    if (!file) return;
    imageFile.value    = file;
    imagePreview.value = URL.createObjectURL(file);
    removeImage.value  = false;
};

const clearSelectedImage = () => {
    removeImage.value  = true;
    imageFile.value    = null;
    imagePreview.value = null;
    if (imageInputRef.value) imageInputRef.value.value = '';
};

const submitForm = () => {
    processing.value = true;
    clearErrors();

    const isEdit = editId.value !== null;
    const url    = isEdit
        ? route('admin.woe.update', editId.value)
        : route('admin.woe.store');

    const payload = {
        ...form,
        ...(imageFile.value    ? { image: imageFile.value } : {}),
        ...(removeImage.value  ? { remove_image: true }     : {}),
        ...(isEdit             ? { _method: 'put' }         : {}),
    };

    router.post(url, payload, {
        forceFormData:  true,
        preserveScroll: true,
        onSuccess: () => { showModal.value = false; },
        onError:   (e) => { Object.assign(errors, e); },
        onFinish:  () => { processing.value = false; },
    });
};

// ── Toggle & Delete ───────────────────────────────────────────────────
const toggle = (s) => {
    router.patch(route('admin.woe.toggle', s.id), {}, { preserveScroll: true });
};

const confirmDelete = (s) => {
    if (!confirm(`Delete "${s.label || TYPE_LABELS[s.type]}"?`)) return;
    router.delete(route('admin.woe.destroy', s.id), { preserveScroll: true });
};

const formatSlot = (s) =>
    `${DAY_NAMES.value[s.start_day]} ${s.start_time} – ${DAY_NAMES.value[s.end_day]} ${s.end_time}`;

// Preview URL mostrada en tabla
const displayImg = (s) => s.image_url || null;
</script>

<template>
    <AdminLayout>
        <PageHeader
            :title="__('WOE Schedule')"
            :description="schedules.length ? `${schedules.length} session(s) configured` : 'No sessions configured'"
            class="mb-6"
        >
            <template #default>
                <div class="flex items-center gap-2">
                    <button @click="togglePreview"
                        :class="[
                            'inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold border transition',
                            previewActive
                                ? 'bg-rapanel-gold/10 border-rapanel-gold/30 text-rapanel-gold hover:bg-rapanel-gold/20'
                                : 'bg-white dark:bg-white/5 border-rapanel-navy-100 dark:border-white/10 text-rapanel-text-light dark:text-rapanel-text-dark hover:text-rapanel-navy-900 dark:hover:text-white'
                        ]">
                        <EyeSlashIcon v-if="previewActive" class="w-4 h-4" />
                        <EyeIcon v-else class="w-4 h-4" />
                        {{ previewActive ? __('Stop Preview') : __('Preview WOE') }}
                    </button>

                    <button @click="openCreate"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-rapanel-blue text-white text-sm font-semibold hover:bg-rapanel-blue/90 transition">
                        <PlusIcon class="w-4 h-4" />
                        {{ __('New Session') }}
                    </button>
                </div>
            </template>
        </PageHeader>

        <!-- Empty state -->
        <div v-if="!schedules.length"
            class="flex flex-col items-center justify-center py-20 gap-4 text-center">
            <ShieldExclamationIcon class="w-14 h-14 text-rapanel-navy-200 dark:text-white/20" />
            <p class="text-rapanel-text-light dark:text-rapanel-text-dark text-sm">
                {{ __('No WOE sessions configured yet.') }}
            </p>
            <button @click="openCreate"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-rapanel-blue text-white text-sm font-semibold hover:bg-rapanel-blue/90 transition">
                <PlusIcon class="w-4 h-4" />
                {{ __('Add First Session') }}
            </button>
        </div>

        <!-- Schedule table -->
        <div v-else class="bg-white dark:bg-rapanel-navy-900 rounded-xl border border-rapanel-navy-100 dark:border-white/10 shadow-sm overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-rapanel-navy-100 dark:bg-rapanel-navy-800 text-rapanel-navy-900 dark:text-white text-xs uppercase tracking-wider">
                        <th class="px-4 py-3 text-left w-10">{{ __('Image') }}</th>
                        <th class="px-4 py-3 text-left">{{ __('Label') }}</th>
                        <th class="px-4 py-3 text-left">{{ __('Type') }}</th>
                        <th class="px-4 py-3 text-left">{{ __('Schedule') }}</th>
                        <th class="px-4 py-3 text-center">{{ __('Enabled') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-rapanel-navy-100 dark:divide-white/[0.06]">
                    <tr v-for="s in schedules" :key="s.id"
                        class="hover:bg-rapanel-navy-50 dark:hover:bg-white/[0.02] transition-colors">

                        <!-- Thumbnail -->
                        <td class="px-4 py-3">
                            <img v-if="displayImg(s)" :src="displayImg(s)"
                                class="w-10 h-10 rounded-lg object-cover border border-rapanel-navy-100 dark:border-white/10" />
                            <div v-else class="w-10 h-10 rounded-lg bg-rapanel-navy-100 dark:bg-white/10 flex items-center justify-center">
                                <PhotoIcon class="w-5 h-5 text-rapanel-text-light dark:text-rapanel-text-dark" />
                            </div>
                        </td>

                        <!-- Label -->
                        <td class="px-4 py-3">
                            <span v-if="s.label" class="font-semibold text-rapanel-navy-900 dark:text-white">{{ s.label }}</span>
                            <span v-else class="text-rapanel-text-light dark:text-rapanel-text-dark italic text-xs">—</span>
                        </td>

                        <!-- Type badge -->
                        <td class="px-4 py-3">
                            <span :class="['inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold border', TYPE_COLORS[s.type]]">
                                {{ TYPE_LABELS[s.type] }}
                            </span>
                        </td>

                        <!-- Schedule -->
                        <td class="px-4 py-3 text-rapanel-navy-700 dark:text-rapanel-text-dark font-mono text-xs">
                            {{ formatSlot(s) }}
                        </td>

                        <!-- Toggle enabled -->
                        <td class="px-4 py-3 text-center">
                            <button @click="toggle(s)"
                                :class="[
                                    'relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none',
                                    s.enabled ? 'bg-rapanel-success' : 'bg-rapanel-navy-200 dark:bg-white/20'
                                ]"
                                :title="s.enabled ? __('Disable') : __('Enable')">
                                <span :class="[
                                    'pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                                    s.enabled ? 'translate-x-4' : 'translate-x-0'
                                ]" />
                            </button>
                        </td>

                        <!-- Actions -->
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-2">
                                <button @click="openEdit(s)"
                                    class="p-1.5 rounded-lg text-rapanel-text-light dark:text-rapanel-text-dark hover:text-rapanel-blue hover:bg-rapanel-blue/10 transition"
                                    :title="__('Edit')">
                                    <PencilIcon class="w-4 h-4" />
                                </button>
                                <button @click="confirmDelete(s)"
                                    class="p-1.5 rounded-lg text-rapanel-text-light dark:text-rapanel-text-dark hover:text-rapanel-danger hover:bg-rapanel-danger/10 transition"
                                    :title="__('Delete')">
                                    <TrashIcon class="w-4 h-4" />
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- ── Modal crear / editar ───────────────────────────────────── -->
        <Teleport to="body">
            <Transition enter-active-class="transition duration-150" enter-from-class="opacity-0" leave-active-class="transition duration-100" leave-to-class="opacity-0">
                <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" @click.self="closeModal">
                    <div class="w-full max-w-md bg-white dark:bg-rapanel-navy-900 rounded-2xl shadow-2xl border border-rapanel-navy-100 dark:border-white/10 overflow-hidden max-h-[90vh] overflow-y-auto">

                        <!-- Header -->
                        <div class="px-6 py-4 border-b border-rapanel-navy-100 dark:border-white/10 flex items-center justify-between sticky top-0 bg-white dark:bg-rapanel-navy-900 z-10">
                            <h2 class="text-base font-bold text-rapanel-navy-900 dark:text-white">
                                {{ editId ? __('Edit Session') : __('New Session') }}
                            </h2>
                            <button @click="closeModal" class="text-rapanel-text-light dark:text-rapanel-text-dark hover:text-rapanel-navy-900 dark:hover:text-white transition text-xl leading-none">&times;</button>
                        </div>

                        <!-- Body -->
                        <form @submit.prevent="submitForm" class="px-6 py-5 space-y-4">

                            <!-- Label -->
                            <div>
                                <label class="block text-xs font-semibold text-rapanel-navy-900 dark:text-white mb-1.5 uppercase tracking-wider">
                                    {{ __('Label') }} <span class="text-rapanel-text-light dark:text-rapanel-text-dark font-normal normal-case">(optional)</span>
                                </label>
                                <input v-model="form.label" type="text" maxlength="100" placeholder="e.g. Ancient WOE, Friday Fight..."
                                    class="w-full rounded-lg border border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-white/[0.05] px-3 py-2 text-sm text-rapanel-navy-900 dark:text-white placeholder:text-rapanel-text-light dark:placeholder:text-rapanel-text-dark focus:outline-none focus:ring-2 focus:ring-rapanel-blue" />
                            </div>

                            <!-- Image -->
                            <div>
                                <label class="block text-xs font-semibold text-rapanel-navy-900 dark:text-white mb-1.5 uppercase tracking-wider">
                                    {{ __('Image / GIF') }} <span class="text-rapanel-text-light dark:text-rapanel-text-dark font-normal normal-case">(optional)</span>
                                </label>

                                <!-- Preview -->
                                <div v-if="imagePreview || (currentImgUrl && !removeImage)"
                                    class="relative mb-2 rounded-xl overflow-hidden border border-rapanel-navy-100 dark:border-white/10">
                                    <img :src="imagePreview || currentImgUrl" class="w-full h-36 object-cover" />
                                    <button type="button" @click="clearSelectedImage"
                                        class="absolute top-2 right-2 w-6 h-6 rounded-full bg-black/60 text-white flex items-center justify-center hover:bg-rapanel-danger transition">
                                        <XMarkIcon class="w-3.5 h-3.5" />
                                    </button>
                                </div>

                                <!-- Upload area -->
                                <label v-else
                                    class="flex flex-col items-center justify-center gap-2 w-full h-24 rounded-xl border-2 border-dashed border-rapanel-navy-100 dark:border-white/10 cursor-pointer hover:border-rapanel-btn-blue transition">
                                    <PhotoIcon class="w-7 h-7 text-rapanel-text-light dark:text-rapanel-text-dark" />
                                    <span class="text-xs text-rapanel-text-light dark:text-rapanel-text-dark">PNG, JPG, GIF, WEBP · max 10 MB</span>
                                    <input ref="imageInputRef" type="file" accept="image/jpeg,image/png,image/gif,image/webp" class="hidden" @change="onImageChange" />
                                </label>

                                <p v-if="errors.image" class="mt-1 text-xs text-rapanel-danger">{{ errors.image }}</p>
                            </div>

                            <!-- Type -->
                            <div>
                                <label class="block text-xs font-semibold text-rapanel-navy-900 dark:text-white mb-1.5 uppercase tracking-wider">{{ __('Type') }}</label>
                                <div class="grid grid-cols-3 gap-2">
                                    <button v-for="(label, val) in TYPE_LABELS" :key="val" type="button"
                                        @click="form.type = Number(val)"
                                        :class="[
                                            'py-2 rounded-lg text-xs font-bold border transition',
                                            form.type === Number(val)
                                                ? 'bg-rapanel-navy-900 dark:bg-white text-white dark:text-rapanel-navy-900 border-rapanel-navy-900 dark:border-white'
                                                : 'bg-white dark:bg-transparent text-rapanel-text-light dark:text-rapanel-text-dark border-rapanel-navy-100 dark:border-white/10 hover:border-rapanel-btn-blue hover:text-rapanel-blue'
                                        ]">
                                        {{ label }}
                                    </button>
                                </div>
                                <p v-if="errors.type" class="mt-1 text-xs text-rapanel-danger">{{ errors.type }}</p>
                            </div>

                            <!-- Start -->
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-semibold text-rapanel-navy-900 dark:text-white mb-1.5 uppercase tracking-wider">{{ __('Start Day') }}</label>
                                    <select v-model="form.start_day"
                                        class="w-full rounded-lg border border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-white/[0.05] px-3 py-2 text-sm text-rapanel-navy-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-rapanel-blue">
                                        <option v-for="(name, idx) in DAY_NAMES" :key="idx" :value="idx">{{ name }}</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-rapanel-navy-900 dark:text-white mb-1.5 uppercase tracking-wider">{{ __('Start Time') }}</label>
                                    <input v-model="form.start_time" type="time"
                                        class="w-full rounded-lg border border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-white/[0.05] px-3 py-2 text-sm text-rapanel-navy-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-rapanel-blue" />
                                    <p v-if="errors.start_time" class="mt-1 text-xs text-rapanel-danger">{{ errors.start_time }}</p>
                                </div>
                            </div>

                            <!-- End -->
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-semibold text-rapanel-navy-900 dark:text-white mb-1.5 uppercase tracking-wider">{{ __('End Day') }}</label>
                                    <select v-model="form.end_day"
                                        class="w-full rounded-lg border border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-white/[0.05] px-3 py-2 text-sm text-rapanel-navy-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-rapanel-blue">
                                        <option v-for="(name, idx) in DAY_NAMES" :key="idx" :value="idx">{{ name }}</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-rapanel-navy-900 dark:text-white mb-1.5 uppercase tracking-wider">{{ __('End Time') }}</label>
                                    <input v-model="form.end_time" type="time"
                                        class="w-full rounded-lg border border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-white/[0.05] px-3 py-2 text-sm text-rapanel-navy-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-rapanel-blue" />
                                    <p v-if="errors.end_time" class="mt-1 text-xs text-rapanel-danger">{{ errors.end_time }}</p>
                                </div>
                            </div>

                            <!-- Enabled -->
                            <label class="flex items-center gap-3 cursor-pointer select-none">
                                <button type="button" @click="form.enabled = !form.enabled"
                                    :class="['relative inline-flex h-5 w-9 flex-shrink-0 rounded-full border-2 border-transparent transition-colors', form.enabled ? 'bg-rapanel-success' : 'bg-rapanel-navy-200 dark:bg-white/20']">
                                    <span :class="['pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow transition', form.enabled ? 'translate-x-4' : 'translate-x-0']" />
                                </button>
                                <span class="text-sm text-rapanel-navy-900 dark:text-white font-medium">{{ __('Enabled') }}</span>
                            </label>

                            <!-- Footer buttons -->
                            <div class="flex items-center justify-end gap-3 pt-2">
                                <button type="button" @click="closeModal"
                                    class="px-4 py-2 rounded-lg text-sm font-semibold text-rapanel-text-light dark:text-rapanel-text-dark hover:text-rapanel-navy-900 dark:hover:text-white transition">
                                    {{ __('Cancel') }}
                                </button>
                                <button type="submit" :disabled="processing"
                                    class="px-5 py-2 rounded-lg bg-rapanel-blue text-white text-sm font-semibold hover:bg-rapanel-blue/90 disabled:opacity-60 transition">
                                    {{ processing ? __('Saving…') : (editId ? __('Save Changes') : __('Create')) }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AdminLayout>
</template>

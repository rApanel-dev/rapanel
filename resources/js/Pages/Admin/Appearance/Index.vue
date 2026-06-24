<script setup>
import { ref, watch, onMounted, onBeforeUnmount } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import ActionButton from '@/Components/ActionButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import { applyThemePreview, clearThemePreview, commitThemePreview } from '@/Composables/useThemePreview';
import { Bars3BottomLeftIcon, SwatchIcon, PhotoIcon, ArrowPathIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    theme:    { type: Object, default: () => ({}) },
    defaults: { type: Object, default: () => ({}) },
    bgImage:  { type: String, default: null },
});

const page = usePage();
const __ = (key, rep = {}) => {
    let t = page.props.translations?.[key] || key;
    Object.entries(rep).forEach(([k, v]) => { t = t.replace(`:${k}`, v); });
    return t;
};

// --- Form (modelo v2: header/footer por sección + buttons + globales + imagen) ---
const form = useForm({
    header:          { light: { ...props.theme.header.light }, dark: { ...props.theme.header.dark } },
    footer:          { light: { ...props.theme.footer.light }, dark: { ...props.theme.footer.dark } },
    buttons:         { ...props.theme.buttons },
    light:           { ...props.theme.light },
    dark:            { ...props.theme.dark },
    bg_image:        null,
    remove_bg_image: false,
});

// --- Tabs / secciones ---
const tabs = [
    { key: 'header-footer', label: 'Header & Footer', icon: Bars3BottomLeftIcon },
    { key: 'buttons',       label: 'Button colors',   icon: SwatchIcon },
    { key: 'background',    label: 'Background image', icon: PhotoIcon },
];
const activeTab = ref('header-footer');

// --- Imagen de fondo ---
const bgPreview = ref(props.bgImage ? '/storage/' + props.bgImage : null);
const onBgImage = (e) => {
    const file = e.target.files[0];
    if (!file) return;
    form.bg_image = file;
    form.remove_bg_image = false;
    bgPreview.value = URL.createObjectURL(file);
};
const clearBgImage = () => {
    form.bg_image = null;
    form.remove_bg_image = true;
    bgPreview.value = null;
};

// --- Preview en vivo ---
const themeFromForm = () => ({
    header:  { light: { ...form.header.light }, dark: { ...form.header.dark } },
    footer:  { light: { ...form.footer.light }, dark: { ...form.footer.dark } },
    buttons: { ...form.buttons },
    light:   { ...form.light },
    dark:    { ...form.dark },
});

onMounted(() => applyThemePreview(themeFromForm()));
watch(
    () => [form.header, form.footer, form.buttons, form.light, form.dark],
    () => applyThemePreview(themeFromForm()),
    { deep: true },
);
onBeforeUnmount(() => clearThemePreview());

const discard = () => {
    form.reset('header', 'footer', 'buttons', 'light', 'dark');
    applyThemePreview(themeFromForm());
};

// --- Submit / reset ---
const save = () => {
    form.post(route('admin.appearance.update'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => commitThemePreview(themeFromForm()),
    });
};
const showReset = ref(false);
const resetForm = useForm({});
const doReset = () => {
    resetForm.post(route('admin.appearance.reset'), {
        preserveScroll: true,
        onSuccess: () => { showReset.value = false; window.location.reload(); },
    });
};

// --- Definición de campos ---
const sectionFields = [
    { key: 'bg',   label: 'Background color' },
    { key: 'text', label: 'Text color' },
    { key: 'link', label: 'Link color' },
];
const buttonFields = [
    { key: 'blue',    label: 'Blue',    variant: 'blue'    },
    { key: 'gold',    label: 'Gold',    variant: 'gold'    },
    { key: 'purple',  label: 'Purple',  variant: 'purple'  },
    { key: 'navy',    label: 'Navy',    variant: 'navy'    },
    { key: 'success', label: 'Success', variant: 'success' },
    { key: 'danger',  label: 'Danger',  variant: 'danger'  },
];
</script>

<template>
    <AdminLayout>
        <PageHeader :title="__('Appearance')" :description="__('Customize the look of your site')" class="mb-6" />

        <!-- Tabs -->
        <div class="flex flex-wrap gap-2 mb-6">
            <button v-for="t in tabs" :key="t.key" @click="activeTab = t.key"
                :class="[
                    'inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold border transition',
                    activeTab === t.key
                        ? 'bg-rapanel-blue/30 dark:bg-rapanel-blue/10 text-rapanel-blue border-rapanel-blue/40 dark:border-rapanel-blue/20'
                        : 'bg-white dark:bg-rapanel-navy-900 text-rapanel-text-light/70 dark:text-rapanel-text-dark/70 border-rapanel-navy-100 dark:border-white/10 hover:text-rapanel-text-light dark:hover:text-rapanel-text-dark',
                ]">
                <component :is="t.icon" class="w-4 h-4" /> {{ __(t.label) }}
            </button>
        </div>

        <div class="max-w-5xl pb-28">

            <!-- ════════ HEADER & FOOTER ════════ -->
            <div v-show="activeTab === 'header-footer'" class="space-y-6">

                <p class="text-xs text-rapanel-text-light/55 dark:text-rapanel-text-dark/55">
                    {{ __('Applies to public pages and the logged-in user area (not the admin panel). Toggle dark/light mode to preview the other theme.') }}
                </p>

                <!-- Vista previa en vivo (usa los tokens reales) -->
                <div class="rounded-2xl overflow-hidden border border-rapanel-navy-100 dark:border-white/10 shadow-sm">
                    <div class="bg-rapanel-header px-5 py-3.5 flex items-center justify-between">
                        <span class="font-display font-black uppercase tracking-wider text-rapanel-header-text">{{ $page.props.serverName }}</span>
                        <div class="flex items-center gap-4 text-sm">
                            <span class="text-rapanel-header-text">{{ __('Home') }}</span>
                            <span class="text-rapanel-header-link font-bold">{{ __('News') }}</span>
                            <span class="text-rapanel-header-text">{{ __('Profile') }}</span>
                        </div>
                    </div>
                    <div class="bg-rapanel-navy-50 dark:bg-rapanel-base-dark px-5 py-10 text-center text-xs text-rapanel-text-light/40 dark:text-rapanel-text-dark/40 uppercase tracking-widest">
                        {{ __('Page content') }}
                    </div>
                    <div class="bg-rapanel-footer px-5 py-3.5 flex items-center justify-between text-sm">
                        <span class="text-rapanel-footer-text">© {{ $page.props.serverName }}</span>
                        <span class="text-rapanel-footer-link font-semibold">Discord · Facebook</span>
                    </div>
                </div>

                <!-- Pickers: Header y Footer -->
                <div v-for="zone in [{ key: 'header', label: 'Header' }, { key: 'footer', label: 'Footer' }]" :key="zone.key"
                    class="bg-white dark:bg-rapanel-navy-900 border border-rapanel-navy-100 dark:border-white/10 rounded-xl p-6">
                    <h2 class="font-display font-bold uppercase tracking-wider text-sm text-rapanel-text-light dark:text-rapanel-text-dark mb-5">
                        {{ __(zone.label) }}
                    </h2>
                    <div class="grid md:grid-cols-2 gap-x-10 gap-y-5">
                        <div v-for="mode in [{ key: 'light', label: 'Light theme' }, { key: 'dark', label: 'Dark theme' }]" :key="mode.key">
                            <p class="text-[11px] font-black uppercase tracking-widest text-rapanel-text-light/45 dark:text-rapanel-text-dark/45 mb-3">{{ __(mode.label) }}</p>
                            <div class="space-y-3">
                                <div v-for="f in sectionFields" :key="f.key" class="flex items-center gap-3">
                                    <input type="color" v-model="form[zone.key][mode.key][f.key]"
                                        class="h-9 w-11 shrink-0 rounded-md border border-rapanel-navy-100 dark:border-white/10 cursor-pointer bg-transparent p-0.5" />
                                    <input type="text" v-model="form[zone.key][mode.key][f.key]" maxlength="7"
                                        class="w-24 font-mono text-xs rounded-md border-rapanel-navy-100 dark:border-white/10 bg-white dark:bg-rapanel-surface text-rapanel-text-light dark:text-rapanel-text-dark focus:ring-rapanel-blue focus:border-rapanel-blue" />
                                    <span class="text-sm text-rapanel-text-light/70 dark:text-rapanel-text-dark/70">{{ __(f.label) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ════════ BOTONES ════════ -->
            <div v-show="activeTab === 'buttons'"
                class="bg-white dark:bg-rapanel-navy-900 border border-rapanel-navy-100 dark:border-white/10 rounded-xl p-6">
                <div class="grid sm:grid-cols-2 gap-x-8 gap-y-4">
                    <div v-for="f in buttonFields" :key="f.key" class="flex items-center gap-3">
                        <input type="color" v-model="form.buttons[f.key]"
                            class="h-9 w-11 shrink-0 rounded-md border border-rapanel-navy-100 dark:border-white/10 cursor-pointer bg-transparent p-0.5" />
                        <input type="text" v-model="form.buttons[f.key]" maxlength="7"
                            class="w-24 font-mono text-xs rounded-md border-rapanel-navy-100 dark:border-white/10 bg-white dark:bg-rapanel-surface text-rapanel-text-light dark:text-rapanel-text-dark focus:ring-rapanel-blue focus:border-rapanel-blue" />
                        <ActionButton :variant="f.variant" size="sm" class="pointer-events-none ml-auto">{{ __(f.label) }}</ActionButton>
                    </div>
                </div>
            </div>

            <!-- ════════ IMAGEN DE FONDO ════════ -->
            <div v-show="activeTab === 'background'"
                class="bg-white dark:bg-rapanel-navy-900 border border-rapanel-navy-100 dark:border-white/10 rounded-xl p-6">
                <div class="flex flex-col sm:flex-row gap-5 items-start">
                    <div class="w-full sm:w-64 aspect-video rounded-lg overflow-hidden border border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-rapanel-surface flex items-center justify-center shrink-0">
                        <img v-if="bgPreview" :src="bgPreview" alt="" class="w-full h-full object-cover" />
                        <span v-else class="text-xs text-rapanel-text-light/45 dark:text-rapanel-text-dark/45 px-3 text-center">{{ __('Use default background') }}</span>
                    </div>
                    <div class="flex flex-col gap-3">
                        <label class="inline-flex">
                            <input type="file" accept="image/jpeg,image/png,image/webp" class="hidden" @change="onBgImage" />
                            <span class="cursor-pointer inline-flex items-center gap-1.5 font-display font-bold text-xs px-3 py-1.5 rounded-lg border bg-rapanel-blue/30 dark:bg-rapanel-blue/10 text-rapanel-blue border-rapanel-blue/40 dark:border-rapanel-blue/20 hover:bg-rapanel-blue hover:text-white transition-all">
                                <PhotoIcon class="w-4 h-4" /> {{ __('Background image') }}
                            </span>
                        </label>
                        <ActionButton v-if="bgPreview" variant="danger" size="sm" @click="clearBgImage">{{ __('Remove image') }}</ActionButton>
                        <p class="text-[11px] text-rapanel-text-light/45 dark:text-rapanel-text-dark/45 max-w-xs">JPG, PNG, WEBP · max 10MB</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Barra de acciones fija -->
        <div class="fixed bottom-0 inset-x-0 lg:left-64 z-20 bg-white/95 dark:bg-rapanel-surface-deep/95 backdrop-blur border-t border-rapanel-navy-100 dark:border-white/10 px-4 sm:px-8 py-3 flex items-center gap-3">
            <ActionButton variant="reset-look" @click="showReset = true">
                <ArrowPathIcon class="w-4 h-4" /> {{ __('Reset to defaults') }}
            </ActionButton>
            <ActionButton variant="navy" class="ml-auto" :disabled="!form.isDirty" @click="discard">{{ __('Discard') }}</ActionButton>
            <PrimaryButton :class="{ 'opacity-50': form.processing }" :disabled="form.processing" @click="save">{{ __('Save') }}</PrimaryButton>
        </div>

        <ConfirmModal
            :show="showReset"
            variant="warning"
            :title="__('Reset to defaults')"
            :message="__('This will discard all custom colors and the background image.')"
            :confirmLabel="__('Reset to defaults')"
            :loading="resetForm.processing"
            @confirm="doReset"
            @close="showReset = false"
        />
    </AdminLayout>
</template>

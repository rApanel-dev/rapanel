<script setup>
import { ref } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import ActionButton from '@/Components/ActionButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import { PhotoIcon, SwatchIcon, SunIcon, MoonIcon, ArrowPathIcon } from '@heroicons/vue/24/outline';

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

// --- Form (modelo: buttons + light + dark + imagen de fondo) ---
const form = useForm({
    buttons:         { ...props.theme.buttons },
    light:           { ...props.theme.light },
    dark:            { ...props.theme.dark },
    bg_image:        null,
    remove_bg_image: false,
});

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

// --- Submit ---
const save = () => {
    form.post(route('admin.appearance.update'), {
        forceFormData: true,
        preserveScroll: true,
    });
};

// --- Reset ---
const showReset = ref(false);
const resetForm = useForm({});
const doReset = () => {
    resetForm.post(route('admin.appearance.reset'), {
        preserveScroll: true,
        onSuccess: () => { showReset.value = false; window.location.reload(); },
    });
};

// --- Campos ---
const buttonFields = [
    { key: 'blue',    label: 'Blue',    variant: 'blue'    },
    { key: 'gold',    label: 'Gold',    variant: 'gold'    },
    { key: 'purple',  label: 'Purple',  variant: 'purple'  },
    { key: 'navy',    label: 'Navy',    variant: 'navy'    },
    { key: 'success', label: 'Success', variant: 'success' },
    { key: 'danger',  label: 'Danger',  variant: 'danger'  },
];
const lightFields = [
    { key: 'bg',   label: 'Background color' },
    { key: 'text', label: 'Text color' },
];
const darkFields = [
    { key: 'bg',      label: 'Background color' },
    { key: 'surface', label: 'Surface color' },
    { key: 'text',    label: 'Text color' },
];
</script>

<template>
    <AdminLayout>
        <PageHeader :title="__('Appearance')" :description="__('Customize the look of your site')" class="mb-6" />

        <div class="space-y-6 max-w-4xl pb-28">

            <!-- Aviso: el preview en vivo llega en Fase 6 -->
            <p class="text-xs text-rapanel-text-light/55 dark:text-rapanel-text-dark/55">
                {{ __('Colors apply across the whole site after saving. Reload the page to see site-wide changes.') }}
            </p>

            <!-- ===== Imagen de fondo ===== -->
            <section class="bg-white dark:bg-rapanel-navy-900 border border-rapanel-navy-100 dark:border-white/10 rounded-xl p-6">
                <div class="flex items-center gap-2 mb-4">
                    <PhotoIcon class="w-5 h-5 text-rapanel-blue" />
                    <h2 class="font-display font-bold uppercase tracking-wider text-sm text-rapanel-text-light dark:text-rapanel-text-dark">
                        {{ __('Background image') }}
                    </h2>
                </div>

                <div class="flex flex-col sm:flex-row gap-5 items-start">
                    <div class="w-full sm:w-64 aspect-video rounded-lg overflow-hidden border border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-rapanel-surface flex items-center justify-center shrink-0">
                        <img v-if="bgPreview" :src="bgPreview" alt="" class="w-full h-full object-cover" />
                        <span v-else class="text-xs text-rapanel-text-light/45 dark:text-rapanel-text-dark/45 px-3 text-center">
                            {{ __('Use default background') }}
                        </span>
                    </div>
                    <div class="flex flex-col gap-3">
                        <label class="inline-flex">
                            <input type="file" accept="image/jpeg,image/png,image/webp" class="hidden" @change="onBgImage" />
                            <span class="cursor-pointer inline-flex items-center gap-1.5 font-display font-bold text-xs px-3 py-1.5 rounded-lg border bg-rapanel-blue/30 dark:bg-rapanel-blue/10 text-rapanel-blue border-rapanel-blue/40 dark:border-rapanel-blue/20 hover:bg-rapanel-blue hover:text-white transition-all">
                                <PhotoIcon class="w-4 h-4" /> {{ __('Background image') }}
                            </span>
                        </label>
                        <ActionButton v-if="bgPreview" variant="danger" size="sm" @click="clearBgImage">
                            {{ __('Remove image') }}
                        </ActionButton>
                        <p class="text-[11px] text-rapanel-text-light/45 dark:text-rapanel-text-dark/45 max-w-xs">
                            JPG, PNG, WEBP · max 10MB
                        </p>
                    </div>
                </div>
            </section>

            <!-- ===== Colores de botones ===== -->
            <section class="bg-white dark:bg-rapanel-navy-900 border border-rapanel-navy-100 dark:border-white/10 rounded-xl p-6">
                <div class="flex items-center gap-2 mb-4">
                    <SwatchIcon class="w-5 h-5 text-rapanel-purple" />
                    <h2 class="font-display font-bold uppercase tracking-wider text-sm text-rapanel-text-light dark:text-rapanel-text-dark">
                        {{ __('Button colors') }}
                    </h2>
                </div>

                <div class="grid sm:grid-cols-2 gap-x-8 gap-y-4">
                    <div v-for="f in buttonFields" :key="f.key" class="flex items-center gap-3">
                        <input type="color" v-model="form.buttons[f.key]"
                            class="h-9 w-11 shrink-0 rounded-md border border-rapanel-navy-100 dark:border-white/10 cursor-pointer bg-transparent p-0.5" />
                        <input type="text" v-model="form.buttons[f.key]" maxlength="7"
                            class="w-24 font-mono text-xs rounded-md border-rapanel-navy-100 dark:border-white/10 bg-white dark:bg-rapanel-surface text-rapanel-text-light dark:text-rapanel-text-dark focus:ring-rapanel-blue focus:border-rapanel-blue" />
                        <ActionButton :variant="f.variant" size="sm" class="pointer-events-none ml-auto">
                            {{ __(f.label) }}
                        </ActionButton>
                    </div>
                </div>
            </section>

            <!-- ===== Tema claro / oscuro ===== -->
            <div class="grid md:grid-cols-2 gap-6">
                <!-- Claro -->
                <section class="bg-white dark:bg-rapanel-navy-900 border border-rapanel-navy-100 dark:border-white/10 rounded-xl p-6">
                    <div class="flex items-center gap-2 mb-4">
                        <SunIcon class="w-5 h-5 text-rapanel-gold" />
                        <h2 class="font-display font-bold uppercase tracking-wider text-sm text-rapanel-text-light dark:text-rapanel-text-dark">
                            {{ __('Light theme') }}
                        </h2>
                    </div>
                    <div class="space-y-4">
                        <div v-for="f in lightFields" :key="f.key" class="flex items-center gap-3">
                            <input type="color" v-model="form.light[f.key]"
                                class="h-9 w-11 shrink-0 rounded-md border border-rapanel-navy-100 dark:border-white/10 cursor-pointer bg-transparent p-0.5" />
                            <input type="text" v-model="form.light[f.key]" maxlength="7"
                                class="w-24 font-mono text-xs rounded-md border-rapanel-navy-100 dark:border-white/10 bg-white dark:bg-rapanel-surface text-rapanel-text-light dark:text-rapanel-text-dark focus:ring-rapanel-blue focus:border-rapanel-blue" />
                            <span class="text-sm text-rapanel-text-light/70 dark:text-rapanel-text-dark/70">{{ __(f.label) }}</span>
                        </div>
                    </div>
                </section>

                <!-- Oscuro -->
                <section class="bg-white dark:bg-rapanel-navy-900 border border-rapanel-navy-100 dark:border-white/10 rounded-xl p-6">
                    <div class="flex items-center gap-2 mb-4">
                        <MoonIcon class="w-5 h-5 text-rapanel-blue" />
                        <h2 class="font-display font-bold uppercase tracking-wider text-sm text-rapanel-text-light dark:text-rapanel-text-dark">
                            {{ __('Dark theme') }}
                        </h2>
                    </div>
                    <div class="space-y-4">
                        <div v-for="f in darkFields" :key="f.key" class="flex items-center gap-3">
                            <input type="color" v-model="form.dark[f.key]"
                                class="h-9 w-11 shrink-0 rounded-md border border-rapanel-navy-100 dark:border-white/10 cursor-pointer bg-transparent p-0.5" />
                            <input type="text" v-model="form.dark[f.key]" maxlength="7"
                                class="w-24 font-mono text-xs rounded-md border-rapanel-navy-100 dark:border-white/10 bg-white dark:bg-rapanel-surface text-rapanel-text-light dark:text-rapanel-text-dark focus:ring-rapanel-blue focus:border-rapanel-blue" />
                            <span class="text-sm text-rapanel-text-light/70 dark:text-rapanel-text-dark/70">{{ __(f.label) }}</span>
                        </div>
                    </div>
                </section>
            </div>
        </div>

        <!-- ===== Barra de acciones fija ===== -->
        <div class="fixed bottom-0 inset-x-0 lg:left-64 z-20 bg-white/95 dark:bg-rapanel-surface-deep/95 backdrop-blur border-t border-rapanel-navy-100 dark:border-white/10 px-4 sm:px-8 py-3 flex items-center gap-3">
            <ActionButton variant="reset-look" @click="showReset = true">
                <ArrowPathIcon class="w-4 h-4" /> {{ __('Reset to defaults') }}
            </ActionButton>
            <PrimaryButton class="ml-auto" :class="{ 'opacity-50': form.processing }" :disabled="form.processing" @click="save">
                {{ __('Save') }}
            </PrimaryButton>
        </div>

        <!-- ===== Confirmación de reset ===== -->
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

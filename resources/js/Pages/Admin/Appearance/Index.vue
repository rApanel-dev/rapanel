<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import ActionButton from '@/Components/ActionButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import { applyThemePreview, clearThemePreview, commitThemePreview } from '@/Composables/useThemePreview';
import { Bars3BottomLeftIcon, HomeIcon, SwatchIcon, PhotoIcon, UserIcon, ArrowPathIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    theme:    { type: Object, default: () => ({}) },
    defaults: { type: Object, default: () => ({}) },
    bgImage:     { type: String, default: null },
    infoBgImage: { type: String, default: null },
    heroBgImage: { type: String, default: null },
    character:{ type: Object, default: () => ({ enabled: true, mobile: false, position: 'right', size: '', frames: [null, null, null, null] }) },
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
    home:            {
        title_gradient: { ...props.theme.home.title_gradient },
        accent:         props.theme.home.accent,
        palette:        [...props.theme.home.palette],
    },
    bg_image:             null,
    remove_bg_image:      false,
    info_bg_image:        null,
    remove_info_bg_image: false,
});

// --- Tabs / secciones ---
const tabs = [
    { key: 'header-footer', label: 'Header & Footer', icon: Bars3BottomLeftIcon },
    { key: 'home',          label: 'Home',            icon: HomeIcon },
    { key: 'buttons',       label: 'Button colors',   icon: SwatchIcon },
];
const activeTab = ref('header-footer');

// --- Imágenes: siempre se muestra la actual (custom o el default real) ---
const BG_DEFAULT   = '/images/bg-main.svg';
const INFO_DEFAULT = '/images/bg-main-rapanel.svg';

const bgPreview  = ref(props.bgImage ? '/storage/' + props.bgImage : BG_DEFAULT);
const bgIsCustom = ref(!!props.bgImage);
const onBgImage = (e) => {
    const file = e.target.files[0];
    if (!file) return;
    form.bg_image = file;
    form.remove_bg_image = false;
    bgPreview.value = URL.createObjectURL(file);
    bgIsCustom.value = true;
};
const clearBgImage = () => {
    form.bg_image = null;
    form.remove_bg_image = true;
    bgPreview.value = BG_DEFAULT;
    bgIsCustom.value = false;
};

const infoPreview  = ref(props.infoBgImage ? '/storage/' + props.infoBgImage : INFO_DEFAULT);
const infoIsCustom = ref(!!props.infoBgImage);
const onInfoImage = (e) => {
    const file = e.target.files[0];
    if (!file) return;
    form.info_bg_image = file;
    form.remove_info_bg_image = false;
    infoPreview.value = URL.createObjectURL(file);
    infoIsCustom.value = true;
};
const clearInfoImage = () => {
    form.info_bg_image = null;
    form.remove_info_bg_image = true;
    infoPreview.value = INFO_DEFAULT;
    infoIsCustom.value = false;
};

// --- Personaje de la home (HomeAlt /home2) — form separado ---
const charForm = useForm({
    enabled:  props.character.enabled,
    mobile:   props.character.mobile,
    position: props.character.position || 'right',
    size:     props.character.size || 'lg',
    frame1: null, frame2: null, frame3: null, frame4: null,
    remove_frame1: false, remove_frame2: false, remove_frame3: false, remove_frame4: false,
});
const defaultFrame = (n) => `/images/ex_se_star0${n}.png`;
const framePreviews = ref([1, 2, 3, 4].map((n, i) =>
    props.character.frames?.[i] ? '/storage/' + props.character.frames[i] : defaultFrame(n)
));
const onFrame = (n, e) => {
    const file = e.target.files[0];
    if (!file) return;
    charForm['frame' + n] = file;
    charForm['remove_frame' + n] = false;
    framePreviews.value[n - 1] = URL.createObjectURL(file);
};
const resetFrame = (n) => {
    charForm['frame' + n] = null;
    charForm['remove_frame' + n] = true;
    framePreviews.value[n - 1] = defaultFrame(n);
};
const saveChar = () => charForm.post(route('admin.appearance.character'), { forceFormData: true, preserveScroll: true });

const charPositions = [{ key: 'left', label: 'Left' }, { key: 'center', label: 'Center' }, { key: 'right', label: 'Right' }];
const charSizes = [{ key: 'sm', label: 'Small' }, { key: 'md', label: 'Medium' }, { key: 'lg', label: 'Large' }];

// Vista previa del personaje (posición + tamaño en vivo dentro del mock)
const charPreviewPos = computed(() => ({
    left: 'left-4', center: 'left-1/2 -translate-x-1/2', right: 'right-4',
}[charForm.position] || 'right-4'));
const charPreviewSize = computed(() => ({
    sm: 'h-[55%]', md: 'h-[72%]', lg: 'h-[90%]',
}[charForm.size] || 'h-[90%]'));

// Degradado de títulos en vivo (para el mock de la home)
const homeGradientStyle = computed(() => ({
    background: `linear-gradient(120deg, ${form.home.title_gradient.from} 0%, ${form.home.title_gradient.mid} 45%, ${form.home.title_gradient.to} 100%)`,
    '-webkit-background-clip': 'text',
    'background-clip': 'text',
    '-webkit-text-fill-color': 'transparent',
}));

// --- Preview en vivo ---
const themeFromForm = () => ({
    header:  { light: { ...form.header.light }, dark: { ...form.header.dark } },
    footer:  { light: { ...form.footer.light }, dark: { ...form.footer.dark } },
    buttons: { ...form.buttons },
    light:   { ...form.light },
    dark:    { ...form.dark },
    home:    { title_gradient: { ...form.home.title_gradient }, accent: form.home.accent, palette: [...form.home.palette] },
});

onMounted(() => applyThemePreview(themeFromForm()));
watch(
    () => [form.header, form.footer, form.buttons, form.light, form.dark, form.home],
    () => applyThemePreview(themeFromForm()),
    { deep: true },
);
onBeforeUnmount(() => clearThemePreview());

const discard = () => {
    form.reset('header', 'footer', 'buttons', 'light', 'dark', 'home');
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

        <div class="max-w-7xl pb-28">

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
                    <div class="bg-rapanel-page px-5 py-10 text-center text-xs text-rapanel-text-light/40 dark:text-rapanel-text-dark/40 uppercase tracking-widest">
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

            <!-- ════════ HOME (fondo + imagen + personaje) ════════ -->
            <div v-show="activeTab === 'home'" class="space-y-6">

                <!-- Vista previa en vivo del hero de la home: fondo + degradado + acento + paleta + personaje -->
                <div class="relative aspect-[21/8] rounded-2xl overflow-hidden border border-rapanel-navy-100 dark:border-white/10 shadow-sm bg-rapanel-page">
                    <!-- glow del acento -->
                    <div class="absolute inset-0 pointer-events-none" :style="{ background: `radial-gradient(60% 80% at 30% 70%, ${form.home.accent}22 0%, transparent 60%)` }" />
                    <!-- contenido del hero -->
                    <div class="absolute inset-0 flex flex-col items-center justify-center gap-1.5 px-4 text-center">
                        <span class="text-[10px] uppercase tracking-widest text-rapanel-text-light/55 dark:text-rapanel-text-dark/55">{{ __('Welcome to') }}</span>
                        <span class="text-2xl sm:text-3xl font-display font-black leading-none" :style="homeGradientStyle">{{ $page.props.serverName }}</span>
                        <span class="mt-1 px-3 py-1 rounded-md text-[11px] font-bold text-white shadow" :style="{ background: `linear-gradient(135deg, ${form.home.accent} 0%, color-mix(in srgb, ${form.home.accent}, #000 30%) 100%)` }">{{ __('Register Now') }}</span>
                    </div>
                    <!-- paleta de tarjetas -->
                    <div class="absolute top-2.5 right-3 flex gap-1">
                        <span v-for="(c, i) in form.home.palette" :key="i" class="w-2.5 h-2.5 rounded-full ring-1 ring-black/10" :style="{ background: c }" />
                    </div>
                    <!-- personaje -->
                    <img v-if="charForm.enabled" :src="framePreviews[0]" alt=""
                         :class="['absolute bottom-0 object-contain pointer-events-none select-none', charPreviewPos, charPreviewSize]" />
                    <span class="absolute top-2.5 left-3.5 text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/40 dark:text-rapanel-text-dark/40">{{ __('Preview') }}</span>
                </div>

                <div class="grid lg:grid-cols-2 gap-6 items-start">

                <!-- Color de fondo de página (claro/oscuro) -->
                <div class="bg-white dark:bg-rapanel-navy-900 border border-rapanel-navy-100 dark:border-white/10 rounded-xl p-6">
                    <h2 class="font-display font-bold uppercase tracking-wider text-sm text-rapanel-text-light dark:text-rapanel-text-dark mb-5">
                        {{ __('Page background') }}
                    </h2>
                    <div class="grid sm:grid-cols-2 gap-x-10 gap-y-5">
                        <div v-for="mode in [{ key: 'light', label: 'Light theme' }, { key: 'dark', label: 'Dark theme' }]" :key="mode.key">
                            <p class="text-[11px] font-black uppercase tracking-widest text-rapanel-text-light/45 dark:text-rapanel-text-dark/45 mb-3">{{ __(mode.label) }}</p>
                            <div class="flex items-center gap-3">
                                <input type="color" v-model="form[mode.key].bg"
                                    class="h-9 w-11 shrink-0 rounded-md border border-rapanel-navy-100 dark:border-white/10 cursor-pointer bg-transparent p-0.5" />
                                <input type="text" v-model="form[mode.key].bg" maxlength="7"
                                    class="w-24 font-mono text-xs rounded-md border-rapanel-navy-100 dark:border-white/10 bg-white dark:bg-rapanel-surface text-rapanel-text-light dark:text-rapanel-text-dark focus:ring-rapanel-blue focus:border-rapanel-blue" />
                                <span class="text-sm text-rapanel-text-light/70 dark:text-rapanel-text-dark/70">{{ __('Background color') }}</span>
                            </div>
                        </div>
                    </div>
                    <p class="mt-4 text-[11px] text-rapanel-text-light/45 dark:text-rapanel-text-dark/45">
                        {{ __('Shown where there is no background image. The home hero has its own video/image.') }}
                    </p>
                </div>

                <!-- Estilo de la home: degradado de títulos + acento + paleta -->
                <div class="bg-white dark:bg-rapanel-navy-900 border border-rapanel-navy-100 dark:border-white/10 rounded-xl p-6 space-y-6">
                    <h2 class="font-display font-bold uppercase tracking-wider text-sm text-rapanel-text-light dark:text-rapanel-text-dark">{{ __('Home style') }}</h2>

                    <!-- Degradado de títulos -->
                    <div>
                        <p class="text-[11px] font-black uppercase tracking-widest text-rapanel-text-light/45 dark:text-rapanel-text-dark/45 mb-3">{{ __('Title gradient') }}</p>
                        <div class="flex flex-wrap items-center gap-3">
                            <input v-for="k in ['from', 'mid', 'to']" :key="k" type="color" v-model="form.home.title_gradient[k]"
                                class="h-9 w-11 shrink-0 rounded-md border border-rapanel-navy-100 dark:border-white/10 cursor-pointer bg-transparent p-0.5" />
                            <span class="ml-2 text-3xl font-display font-black"
                                  :style="{ background: `linear-gradient(120deg, ${form.home.title_gradient.from} 0%, ${form.home.title_gradient.mid} 45%, ${form.home.title_gradient.to} 100%)`, '-webkit-background-clip': 'text', 'background-clip': 'text', '-webkit-text-fill-color': 'transparent' }">
                                {{ $page.props.serverName }}
                            </span>
                        </div>
                    </div>

                    <!-- Acento decorativo -->
                    <div class="flex items-center gap-3">
                        <input type="color" v-model="form.home.accent"
                            class="h-9 w-11 shrink-0 rounded-md border border-rapanel-navy-100 dark:border-white/10 cursor-pointer bg-transparent p-0.5" />
                        <input type="text" v-model="form.home.accent" maxlength="7"
                            class="w-24 font-mono text-xs rounded-md border-rapanel-navy-100 dark:border-white/10 bg-white dark:bg-rapanel-surface text-rapanel-text-light dark:text-rapanel-text-dark focus:ring-rapanel-blue focus:border-rapanel-blue" />
                        <span class="text-sm text-rapanel-text-light/70 dark:text-rapanel-text-dark/70">{{ __('Accent color') }}</span>
                    </div>

                    <!-- Paleta de tarjetas -->
                    <div>
                        <p class="text-[11px] font-black uppercase tracking-widest text-rapanel-text-light/45 dark:text-rapanel-text-dark/45 mb-3">{{ __('Card palette') }}</p>
                        <div class="flex flex-wrap gap-2">
                            <input v-for="(c, i) in form.home.palette" :key="i" type="color" v-model="form.home.palette[i]"
                                class="h-9 w-9 shrink-0 rounded-md border border-rapanel-navy-100 dark:border-white/10 cursor-pointer bg-transparent p-0.5" />
                        </div>
                        <p class="mt-2 text-[11px] text-rapanel-text-light/45 dark:text-rapanel-text-dark/45">{{ __('Applied after saving.') }}</p>
                    </div>
                </div>

                <!-- Imagen de la sección "Server Info" -->
                <div class="bg-white dark:bg-rapanel-navy-900 border border-rapanel-navy-100 dark:border-white/10 rounded-xl p-6">
                    <h2 class="font-display font-bold uppercase tracking-wider text-sm text-rapanel-text-light dark:text-rapanel-text-dark mb-4">{{ __('Info section image') }}</h2>
                    <div class="flex flex-col sm:flex-row gap-5 items-start">
                        <div class="w-full sm:w-64 aspect-video rounded-lg overflow-hidden border border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-rapanel-surface flex items-center justify-center shrink-0">
                            <img :src="infoPreview" alt="" class="w-full h-full object-cover" />
                        </div>
                        <div class="flex flex-col gap-3">
                            <label class="inline-flex">
                                <input type="file" accept="image/jpeg,image/png,image/webp,image/svg+xml" class="hidden" @change="onInfoImage" />
                                <span class="cursor-pointer inline-flex items-center gap-1.5 font-display font-bold text-xs px-3 py-1.5 rounded-lg border bg-rapanel-blue/30 dark:bg-rapanel-blue/10 text-rapanel-blue border-rapanel-blue/40 dark:border-rapanel-blue/20 hover:bg-rapanel-blue hover:text-white transition-all">
                                    <PhotoIcon class="w-4 h-4" /> {{ __('Change') }}
                                </span>
                            </label>
                            <ActionButton v-if="infoIsCustom" variant="danger" size="sm" @click="clearInfoImage">{{ __('Remove image') }}</ActionButton>
                            <span class="text-[11px] font-bold uppercase tracking-wider" :class="infoIsCustom ? 'text-rapanel-success' : 'text-rapanel-text-light/40 dark:text-rapanel-text-dark/40'">{{ infoIsCustom ? __('Custom') : __('Default') }}</span>
                        </div>
                    </div>
                </div>

                <!-- Imagen de fondo (otras páginas) -->
                <div class="bg-white dark:bg-rapanel-navy-900 border border-rapanel-navy-100 dark:border-white/10 rounded-xl p-6">
                <h2 class="font-display font-bold uppercase tracking-wider text-sm text-rapanel-text-light dark:text-rapanel-text-dark mb-1">{{ __('Background image') }}</h2>
                <p class="text-[11px] text-rapanel-text-light/55 dark:text-rapanel-text-dark/55 mb-4">{{ __('Used on inner pages. The home page background (hero) is set in Settings → Home.') }}</p>
                <div class="flex flex-col sm:flex-row gap-5 items-start">
                    <div class="w-full sm:w-64 aspect-video rounded-lg overflow-hidden border border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-rapanel-surface flex items-center justify-center shrink-0">
                        <img :src="bgPreview" alt="" class="w-full h-full object-cover" />
                    </div>
                    <div class="flex flex-col gap-3">
                        <label class="inline-flex">
                            <input type="file" accept="image/jpeg,image/png,image/webp" class="hidden" @change="onBgImage" />
                            <span class="cursor-pointer inline-flex items-center gap-1.5 font-display font-bold text-xs px-3 py-1.5 rounded-lg border bg-rapanel-blue/30 dark:bg-rapanel-blue/10 text-rapanel-blue border-rapanel-blue/40 dark:border-rapanel-blue/20 hover:bg-rapanel-blue hover:text-white transition-all">
                                <PhotoIcon class="w-4 h-4" /> {{ __('Change') }}
                            </span>
                        </label>
                        <ActionButton v-if="bgIsCustom" variant="danger" size="sm" @click="clearBgImage">{{ __('Remove image') }}</ActionButton>
                        <span class="text-[11px] font-bold uppercase tracking-wider" :class="bgIsCustom ? 'text-rapanel-success' : 'text-rapanel-text-light/40 dark:text-rapanel-text-dark/40'">{{ bgIsCustom ? __('Custom') : __('Default') }}</span>
                        <p class="text-[11px] text-rapanel-text-light/45 dark:text-rapanel-text-dark/45 max-w-xs">JPG, PNG, WEBP · max 10MB</p>
                    </div>
                </div>
            </div>

                </div>

                <!-- ── Personaje de la home ── -->
                <div class="bg-white dark:bg-rapanel-navy-900 border border-rapanel-navy-100 dark:border-white/10 rounded-xl p-6 space-y-6">
                    <div class="flex items-center gap-2">
                        <UserIcon class="w-5 h-5 text-rapanel-purple" />
                        <h2 class="font-display font-bold uppercase tracking-wider text-sm text-rapanel-text-light dark:text-rapanel-text-dark">{{ __('Home character') }}</h2>
                    </div>
                    <p class="text-xs text-rapanel-text-light/55 dark:text-rapanel-text-dark/55">
                        {{ __('Animated character on the home hero. Frames 1-3 cycle as movement; frame 4 shows on hover.') }}
                    </p>

                    <!-- Toggles -->
                    <div class="flex flex-wrap items-center gap-x-8 gap-y-3">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" v-model="charForm.enabled" class="rounded border-rapanel-navy-100 dark:border-white/20 text-rapanel-blue focus:ring-rapanel-blue" />
                            <span class="text-sm font-semibold text-rapanel-text-light dark:text-rapanel-text-dark">{{ __('Show character') }}</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" v-model="charForm.mobile" class="rounded border-rapanel-navy-100 dark:border-white/20 text-rapanel-blue focus:ring-rapanel-blue" />
                            <span class="text-sm font-semibold text-rapanel-text-light dark:text-rapanel-text-dark">{{ __('Show on mobile') }}</span>
                        </label>
                    </div>

                    <!-- Posición + tamaño -->
                    <div class="grid sm:grid-cols-2 gap-6">
                        <div>
                            <p class="text-[11px] font-black uppercase tracking-widest text-rapanel-text-light/45 dark:text-rapanel-text-dark/45 mb-2">{{ __('Position') }}</p>
                            <div class="flex gap-2">
                                <button v-for="p in charPositions" :key="p.key" type="button" @click="charForm.position = p.key"
                                    :class="['px-3 py-1.5 rounded-lg text-sm font-semibold border transition', charForm.position === p.key ? 'bg-rapanel-blue/30 dark:bg-rapanel-blue/10 text-rapanel-blue border-rapanel-blue/40' : 'border-rapanel-navy-100 dark:border-white/10 text-rapanel-text-light/70 dark:text-rapanel-text-dark/70']">
                                    {{ __(p.label) }}
                                </button>
                            </div>
                        </div>
                        <div>
                            <p class="text-[11px] font-black uppercase tracking-widest text-rapanel-text-light/45 dark:text-rapanel-text-dark/45 mb-2">{{ __('Size') }}</p>
                            <div class="flex gap-2">
                                <button v-for="s in charSizes" :key="s.key" type="button" @click="charForm.size = s.key"
                                    :class="['px-3 py-1.5 rounded-lg text-sm font-semibold border transition', charForm.size === s.key ? 'bg-rapanel-blue/30 dark:bg-rapanel-blue/10 text-rapanel-blue border-rapanel-blue/40' : 'border-rapanel-navy-100 dark:border-white/10 text-rapanel-text-light/70 dark:text-rapanel-text-dark/70']">
                                    {{ __(s.label) }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Frames -->
                    <div>
                        <p class="text-[11px] font-black uppercase tracking-widest text-rapanel-text-light/45 dark:text-rapanel-text-dark/45 mb-3">{{ __('Frames') }}</p>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                            <div v-for="n in 4" :key="n" class="space-y-2">
                                <div class="aspect-square rounded-lg border border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-rapanel-surface flex items-center justify-center overflow-hidden p-2">
                                    <img :src="framePreviews[n - 1]" alt="" class="max-h-full max-w-full object-contain" />
                                </div>
                                <p class="text-center text-[10px] font-bold text-rapanel-text-light/50 dark:text-rapanel-text-dark/50">
                                    {{ n < 4 ? __('Movement') : __('Hover') }} {{ n }}
                                </p>
                                <div class="flex justify-center gap-2">
                                    <label class="cursor-pointer text-[11px] font-bold text-rapanel-blue hover:underline">
                                        <input type="file" accept="image/png,image/webp" class="hidden" @change="(e) => onFrame(n, e)" />
                                        {{ __('Change') }}
                                    </label>
                                    <button type="button" @click="resetFrame(n)" class="text-[11px] font-bold text-rapanel-text-light/50 dark:text-rapanel-text-dark/50 hover:underline">{{ __('Default') }}</button>
                                </div>
                            </div>
                        </div>
                        <p class="mt-3 text-[11px] text-rapanel-text-light/45 dark:text-rapanel-text-dark/45">PNG (transparente) o WEBP · max 4MB</p>
                    </div>

                    <div class="flex justify-end pt-4 border-t border-rapanel-navy-100 dark:border-white/10">
                        <PrimaryButton :class="{ 'opacity-50': charForm.processing }" :disabled="charForm.processing" @click="saveChar">{{ __('Save') }}</PrimaryButton>
                    </div>
                </div>
            </div>
        </div>

        <!-- Barra de acciones: tema (colores) -->
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

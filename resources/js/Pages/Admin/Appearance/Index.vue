<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue';
import { useForm, usePage, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import ActionButton from '@/Components/ActionButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import { applyThemePreview, clearThemePreview, commitThemePreview } from '@/Composables/useThemePreview';
import { Bars3BottomLeftIcon, HomeIcon, SwatchIcon, PhotoIcon, UserIcon, ArrowPathIcon, SparklesIcon, Squares2X2Icon } from '@heroicons/vue/24/outline';

const props = defineProps({
    theme:    { type: Object, default: () => ({}) },
    defaults: { type: Object, default: () => ({}) },
    bgImage:     { type: String, default: null },
    infoBgImage: { type: String, default: null },
    heroBgImage: { type: String, default: null },
    heroBgVideo: { type: String, default: null },
    brand:    { type: Object, default: () => ({ logo_light: null, logo_dark: null, favicon: null, theme_color: '#3b82f6' }) },
    character:{ type: Object, default: () => ({ enabled: true, mobile: false, position: 'right', size: '', frames: [null, null, null, null] }) },
    cards:    { type: Object, default: () => ({ info: [], feature: [] }) },
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
        accent2:        props.theme.home.accent2,
        ghost:          props.theme.home.ghost,
        card:           { light: props.theme.home.card.light, dark: props.theme.home.card.dark },
        palette:        [...props.theme.home.palette],
    },
    bg_image:             null,
    remove_bg_image:      false,
    info_bg_image:        null,
    remove_info_bg_image: false,
    hero_bg_image:        null,
    remove_hero_bg_image: false,
    hero_bg_video:        null,
    remove_hero_bg_video: false,
});

// --- Tabs / secciones ---
const tabs = [
    { key: 'brand',         label: 'Brand',           icon: SparklesIcon },
    { key: 'header-footer', label: 'Header & Footer', icon: Bars3BottomLeftIcon },
    { key: 'home',          label: 'Home',            icon: HomeIcon },
    { key: 'cards',         label: 'Cards',           icon: Squares2X2Icon },
    { key: 'buttons',       label: 'Button colors',   icon: SwatchIcon },
];
const activeTab = ref('brand');

// --- Brand: logos (claro/oscuro), favicon, color de tema (form independiente) ---
const brandForm = useForm({
    logo_light:  null,
    logo_dark:   null,
    favicon:     null,
    theme_color: props.brand.theme_color || '#3b82f6',
    // PWA / manifest
    pwa_icon:        null,
    pwa_name:        props.brand.pwa_name        || '',
    pwa_short_name:  props.brand.pwa_short_name  || '',
    pwa_description: props.brand.pwa_description || '',
    pwa_bg_color:    props.brand.pwa_bg_color    || '#0f172a',
});
const logoLightPreview = ref(props.brand.logo_light ? '/storage/' + props.brand.logo_light : '/images/logo.png');
const logoDarkPreview  = ref(props.brand.logo_dark  ? '/storage/' + props.brand.logo_dark  : '/images/logo.png');
const faviconPreview   = ref(props.brand.favicon    ? '/storage/' + props.brand.favicon    : null);
const pwaIconPreview   = ref(props.brand.pwa_icon   ? '/storage/' + props.brand.pwa_icon   : '/icons/icon-192x192.png');
const onLogoLight = (e) => { const f = e.target.files[0]; if (!f) return; brandForm.logo_light = f; logoLightPreview.value = URL.createObjectURL(f); };
const onLogoDark  = (e) => { const f = e.target.files[0]; if (!f) return; brandForm.logo_dark  = f; logoDarkPreview.value  = URL.createObjectURL(f); };
const onFavicon   = (e) => { const f = e.target.files[0]; if (!f) return; brandForm.favicon     = f; faviconPreview.value   = URL.createObjectURL(f); };
const onPwaIcon   = (e) => { const f = e.target.files[0]; if (!f) return; brandForm.pwa_icon    = f; pwaIconPreview.value   = URL.createObjectURL(f); };
const saveBrand = () => brandForm.post(route('admin.appearance.brand'), { forceFormData: true, preserveScroll: true });

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

// --- Hero media (fondo del hero de la home): imagen o video. El video tiene prioridad. ---
const HERO_DEFAULT = '/images/bg-main.svg';
const heroPreview  = ref(props.heroBgImage ? '/storage/' + props.heroBgImage : HERO_DEFAULT);
const heroIsCustom = ref(!!props.heroBgImage);
const onHeroImage = (e) => {
    const file = e.target.files[0];
    if (!file) return;
    form.hero_bg_image = file;
    form.remove_hero_bg_image = false;
    heroPreview.value = URL.createObjectURL(file);
    heroIsCustom.value = true;
};
const clearHeroImage = () => {
    form.hero_bg_image = null;
    form.remove_hero_bg_image = true;
    heroPreview.value = HERO_DEFAULT;
    heroIsCustom.value = false;
};

const heroVideoPreview = ref(props.heroBgVideo ? '/storage/' + props.heroBgVideo : null);
const onHeroVideo = (e) => {
    const file = e.target.files[0];
    if (!file) return;
    form.hero_bg_video = file;
    form.remove_hero_bg_video = false;
    heroVideoPreview.value = URL.createObjectURL(file);
};
const clearHeroVideo = () => {
    form.hero_bg_video = null;
    form.remove_hero_bg_video = true;
    heroVideoPreview.value = null;
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
    home:    { title_gradient: { ...form.home.title_gradient }, accent: form.home.accent, accent2: form.home.accent2, ghost: form.home.ghost, card: { ...form.home.card }, palette: [...form.home.palette] },
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

// =============================================================================
// Pestaña TARJETAS — iconos de los info blocks + feature cards de la home.
// El texto/visibilidad es contenido (Settings); aquí solo se edita el ICONO.
// El acento de cada tarjeta sale de la paleta de Home (form.home.palette), en vivo.
// =============================================================================
const infoMeta = [
    { id: 'rates',   label: 'EXP / Job / Drop', svg: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z"/></svg>` },
    { id: 'level',   label: 'Max Base / Job',   svg: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"/></svg>` },
    { id: 'mode',    label: 'Game Mode',        svg: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6.75V15m6-6v8.25m.503 3.498l4.875-2.437c.381-.19.622-.58.622-1.006V4.82c0-.836-.88-1.38-1.628-1.006l-3.869 1.934c-.317.159-.69.159-1.006 0L9.503 3.252a1.125 1.125 0 00-1.006 0L3.622 5.689C3.24 5.88 3 6.27 3 6.695V19.18c0 .836.88 1.38 1.628 1.006l3.869-1.934c.317-.159.69-.159 1.006 0l4.994 2.497c.317.158.69.158 1.006 0z"/></svg>` },
    { id: 'episode', label: 'Episode',          svg: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>` },
    { id: 'intl',    label: 'International',     svg: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418"/></svg>` },
];
const featureMeta = [
    { title: 'Dashboard',    svg: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/></svg>` },
    { title: 'Item DB',      svg: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/></svg>` },
    { title: 'Wiki',         svg: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/></svg>` },
    { title: 'MvP Cards',    svg: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"/></svg>` },
    { title: 'Live Console', svg: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6.75 7.5l3 2.25-3 2.25m4.5 0h3m-9 8.25h13.5A2.25 2.25 0 0021 18V6a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 6v12a2.25 2.25 0 002.25 2.25z"/></svg>` },
    { title: 'Who Sell',     svg: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z"/></svg>` },
];

const buildCardState = (meta, saved, key) => meta.map((m, i) => {
    const s = (key === 'id' ? (props.cards.info || []).find(b => b.id === m.id) : (props.cards.feature || [])[i]) || {};
    return {
        label:      key === 'id' ? m.label : (s.title || m.title),
        defaultSvg: m.svg,
        icon_type:  s.icon_type || 'icon',
        image:      s.image || null,
        svg_code:   s.svg_code || null,
        removed:    false,
    };
});

const infoCards    = ref(buildCardState(infoMeta, props.cards.info, 'id'));
const featCards    = ref(buildCardState(featureMeta, props.cards.feature, 'idx'));
const infoFiles    = ref(Array(infoMeta.length).fill(null));
const featFiles    = ref(Array(featureMeta.length).fill(null));
const infoPreviews = ref(infoCards.value.map(c => c.image ? '/storage/' + c.image : null));
const featPreviews = ref(featCards.value.map(c => c.image ? '/storage/' + c.image : null));
const cardsSaving  = ref(false);

const cardAccent = (i) => form.home.palette[i % form.home.palette.length] || '#4A90E2';

const onCardImage = (group, i, e) => {
    const f = e.target.files[0];
    if (!f) return;
    const [arr, files, prev] = group === 'info'
        ? [infoCards, infoFiles, infoPreviews]
        : [featCards, featFiles, featPreviews];
    files.value[i]    = f;
    prev.value[i]     = URL.createObjectURL(f);
    arr.value[i].icon_type = 'image';
    arr.value[i].svg_code  = null;
    arr.value[i].removed   = false;
};
const onCardSvg = (group, i) => {
    const [arr, files, prev] = group === 'info'
        ? [infoCards, infoFiles, infoPreviews]
        : [featCards, featFiles, featPreviews];
    files.value[i] = null;
    prev.value[i]  = null;
    arr.value[i].removed = false;
};
const resetCardIcon = (group, i) => {
    const [arr, files, prev] = group === 'info'
        ? [infoCards, infoFiles, infoPreviews]
        : [featCards, featFiles, featPreviews];
    files.value[i]   = null;
    prev.value[i]    = null;
    arr.value[i].icon_type = 'icon';
    arr.value[i].svg_code  = null;
    arr.value[i].image     = null;
    arr.value[i].removed   = true;
};

const saveCards = () => {
    cardsSaving.value = true;
    const fd = new FormData();
    const pack = (group, arr, files) => {
        arr.value.forEach((c, i) => {
            if (files.value[i])      fd.append(`${group}[${i}][image]`, files.value[i]);
            else if (c.removed)      fd.append(`${group}[${i}][remove]`, '1');
            else if (c.svg_code)     fd.append(`${group}[${i}][svg_code]`, c.svg_code);
        });
    };
    pack('blocks', infoCards, infoFiles);
    pack('cards',  featCards, featFiles);
    router.post(route('admin.appearance.cards'), fd, {
        forceFormData: true, preserveScroll: true,
        onFinish: () => { cardsSaving.value = false; },
    });
};
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

            <!-- ════════ BRAND ════════ -->
            <form v-show="activeTab === 'brand'" @submit.prevent="saveBrand" class="space-y-6">
                <p class="text-xs text-rapanel-text-light/55 dark:text-rapanel-text-dark/55">
                    {{ __('Logo, favicon and browser theme color. Shown across the public site and the logged-in area.') }}
                </p>

                <!-- Logos -->
                <div class="bg-white dark:bg-rapanel-navy-900 border border-rapanel-navy-100 dark:border-white/10 rounded-xl p-6 space-y-4">
                    <h2 class="font-display font-bold uppercase tracking-wider text-sm text-rapanel-text-light dark:text-rapanel-text-dark">{{ __('Logo') }}</h2>
                    <p class="text-[11px] text-rapanel-text-light/55 dark:text-rapanel-text-dark/55">{{ __('Recommended size: 300×60 px — PNG or SVG with transparent background, max 2 MB. The logo is displayed at 36–48 px height in the header.') }}</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-xs font-bold text-rapanel-text-light dark:text-rapanel-text-dark mb-2">{{ __('Logo (Light Mode)') }}</label>
                            <div class="h-14 bg-rapanel-navy-50 border border-rapanel-navy-100 dark:border-white/10 rounded-lg px-3 flex items-center mb-2">
                                <img :src="logoLightPreview" class="h-9 object-contain max-w-[200px]" alt="" />
                            </div>
                            <input type="file" accept="image/*" @change="onLogoLight"
                                class="text-xs text-rapanel-text-light dark:text-rapanel-text-dark file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-rapanel-blue/10 file:text-rapanel-blue hover:file:bg-rapanel-blue/20 cursor-pointer" />
                            <p v-if="brandForm.errors.logo_light" class="mt-1 text-xs text-rapanel-danger">{{ brandForm.errors.logo_light }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-rapanel-text-light dark:text-rapanel-text-dark mb-2">{{ __('Logo (Dark Mode)') }}</label>
                            <div class="h-14 bg-rapanel-navy-800 border border-white/10 rounded-lg px-3 flex items-center mb-2">
                                <img :src="logoDarkPreview" class="h-9 object-contain max-w-[200px]" alt="" />
                            </div>
                            <input type="file" accept="image/*" @change="onLogoDark"
                                class="text-xs text-rapanel-text-light dark:text-rapanel-text-dark file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-rapanel-blue/10 file:text-rapanel-blue hover:file:bg-rapanel-blue/20 cursor-pointer" />
                            <p v-if="brandForm.errors.logo_dark" class="mt-1 text-xs text-rapanel-danger">{{ brandForm.errors.logo_dark }}</p>
                        </div>
                    </div>
                </div>

                <!-- Favicon + Theme color -->
                <div class="bg-white dark:bg-rapanel-navy-900 border border-rapanel-navy-100 dark:border-white/10 rounded-xl p-6">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-xs font-bold text-rapanel-text-light dark:text-rapanel-text-dark mb-2">{{ __('Favicon') }}</label>
                            <div class="h-20 w-20 bg-rapanel-navy-50 dark:bg-rapanel-navy-800 rounded-xl border border-rapanel-navy-100 dark:border-white/10 flex items-center justify-center mb-2">
                                <img v-if="faviconPreview" :src="faviconPreview" class="h-12 w-12 object-contain" alt="" />
                                <PhotoIcon v-else class="w-8 h-8 text-rapanel-navy-300 dark:text-rapanel-navy-600" />
                            </div>
                            <input type="file" accept=".png,.jpg,.jpeg,.ico,.svg" @change="onFavicon"
                                class="block text-xs text-rapanel-text-light dark:text-rapanel-text-dark file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-rapanel-blue/10 file:text-rapanel-blue hover:file:bg-rapanel-blue/20 cursor-pointer" />
                            <p class="mt-1 text-[11px] text-rapanel-text-light/55 dark:text-rapanel-text-dark/55">{{ __('PNG/ICO, max 512KB') }}</p>
                            <p v-if="brandForm.errors.favicon" class="mt-1 text-xs text-rapanel-danger">{{ brandForm.errors.favicon }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-rapanel-text-light dark:text-rapanel-text-dark mb-2">{{ __('Theme Color') }}</label>
                            <div class="flex items-center gap-3">
                                <input type="color" v-model="brandForm.theme_color"
                                    class="h-9 w-14 rounded-lg border border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-rapanel-navy-800 cursor-pointer p-0.5" />
                                <input type="text" v-model="brandForm.theme_color" maxlength="20" placeholder="#3b82f6"
                                    class="w-32 rounded-lg border border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-rapanel-navy-800 px-3 py-2 text-sm text-rapanel-navy-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-rapanel-blue font-mono" />
                            </div>
                            <p class="mt-1 text-[11px] text-rapanel-text-light/55 dark:text-rapanel-text-dark/55">{{ __('Browser tab and PWA color.') }}</p>
                            <p v-if="brandForm.errors.theme_color" class="mt-1 text-xs text-rapanel-danger">{{ brandForm.errors.theme_color }}</p>
                        </div>
                    </div>
                </div>

                <!-- App / PWA -->
                <div class="bg-white dark:bg-rapanel-navy-900 border border-rapanel-navy-100 dark:border-white/10 rounded-xl p-6">
                    <h2 class="font-display font-bold uppercase tracking-wider text-sm text-rapanel-text-light dark:text-rapanel-text-dark">{{ __('App icon (PWA)') }}</h2>
                    <p class="text-sm text-rapanel-text-light/70 dark:text-rapanel-text-dark/70 mt-1 mb-5">
                        {{ __('Upload one square master image (512×512). All sizes (192, 512, maskable and Apple touch) are generated automatically.') }}
                    </p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-xs font-bold text-rapanel-text-light dark:text-rapanel-text-dark mb-2">{{ __('Master icon') }}</label>
                            <div class="h-20 w-20 bg-rapanel-navy-50 dark:bg-rapanel-navy-800 rounded-xl border border-rapanel-navy-100 dark:border-white/10 flex items-center justify-center mb-2 overflow-hidden">
                                <img v-if="pwaIconPreview" :src="pwaIconPreview" class="h-16 w-16 object-contain" alt="" />
                                <PhotoIcon v-else class="w-8 h-8 text-rapanel-navy-300 dark:text-rapanel-navy-600" />
                            </div>
                            <input type="file" accept=".png,.jpg,.jpeg,.webp" @change="onPwaIcon"
                                class="block text-xs text-rapanel-text-light dark:text-rapanel-text-dark file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-rapanel-blue/10 file:text-rapanel-blue hover:file:bg-rapanel-blue/20 cursor-pointer" />
                            <p class="mt-1 text-[11px] text-rapanel-text-light/55 dark:text-rapanel-text-dark/55">{{ __('PNG/WebP, square, max 4MB') }}</p>
                            <p v-if="brandForm.errors.pwa_icon" class="mt-1 text-xs text-rapanel-danger">{{ brandForm.errors.pwa_icon }}</p>
                        </div>
                        <div class="space-y-3">
                            <div>
                                <label class="block text-xs font-bold text-rapanel-text-light dark:text-rapanel-text-dark mb-1.5">{{ __('App name') }}</label>
                                <input type="text" v-model="brandForm.pwa_name" maxlength="60" placeholder="rApanel"
                                    class="w-full rounded-lg border border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-rapanel-navy-800 px-3 py-2 text-sm text-rapanel-navy-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-rapanel-blue" />
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-bold text-rapanel-text-light dark:text-rapanel-text-dark mb-1.5">{{ __('Short name') }}</label>
                                    <input type="text" v-model="brandForm.pwa_short_name" maxlength="30" placeholder="rApanel"
                                        class="w-full rounded-lg border border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-rapanel-navy-800 px-3 py-2 text-sm text-rapanel-navy-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-rapanel-blue" />
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-rapanel-text-light dark:text-rapanel-text-dark mb-1.5">{{ __('Background color') }}</label>
                                    <div class="flex items-center gap-2">
                                        <input type="color" v-model="brandForm.pwa_bg_color"
                                            class="h-9 w-11 shrink-0 rounded-lg border border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-rapanel-navy-800 cursor-pointer p-0.5" />
                                        <input type="text" v-model="brandForm.pwa_bg_color" maxlength="20" placeholder="#0f172a"
                                            class="w-full rounded-lg border border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-rapanel-navy-800 px-2 py-2 text-xs font-mono text-rapanel-navy-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-rapanel-blue" />
                                    </div>
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-rapanel-text-light dark:text-rapanel-text-dark mb-1.5">{{ __('Description') }}</label>
                                <input type="text" v-model="brandForm.pwa_description" maxlength="200"
                                    class="w-full rounded-lg border border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-rapanel-navy-800 px-3 py-2 text-sm text-rapanel-navy-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-rapanel-blue" />
                            </div>
                        </div>
                    </div>
                </div>

                <div>
                    <PrimaryButton type="submit" :disabled="brandForm.processing">
                        {{ brandForm.processing ? __('Saving...') : __('Save') }}
                    </PrimaryButton>
                </div>
            </form>

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

            <!-- ════════ CARDS (iconos de info blocks + feature cards) ════════ -->
            <div v-show="activeTab === 'cards'" class="space-y-6">
                <p class="text-sm text-rapanel-text-light/70 dark:text-rapanel-text-dark/70 -mt-2">
                    {{ __('Set the icon for each home card. Titles and text are edited in Settings; the accent comes from the Home palette.') }}
                </p>

                <!-- Reusable: grupo de tarjetas -->
                <template v-for="grp in [
                    { key: 'info',    title: __('Server Info blocks'), arr: infoCards, files: infoFiles, prev: infoPreviews, group: 'info' },
                    { key: 'feature', title: __('Feature cards'),      arr: featCards, files: featFiles, prev: featPreviews, group: 'feature' },
                ]" :key="grp.key">
                    <div class="bg-white dark:bg-rapanel-navy-900 border border-rapanel-navy-100 dark:border-white/10 rounded-xl p-6">
                        <h2 class="font-display font-bold uppercase tracking-wider text-sm text-rapanel-text-light dark:text-rapanel-text-dark mb-5">{{ grp.title }}</h2>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div v-for="(card, i) in grp.arr" :key="i"
                                 class="rounded-xl border border-rapanel-navy-100 dark:border-white/10 p-4">
                                <div class="flex items-center gap-2 mb-3">
                                    <span class="w-2.5 h-2.5 rounded-full flex-shrink-0" :style="`background:${cardAccent(i)}`"></span>
                                    <span class="text-sm font-semibold text-rapanel-navy-900 dark:text-white truncate">{{ card.label }}</span>
                                </div>
                                <div class="flex items-start gap-3">
                                    <!-- Preview -->
                                    <div class="relative flex-shrink-0">
                                        <div class="w-12 h-12 rounded-xl flex items-center justify-center overflow-hidden"
                                             :style="`background:${cardAccent(i)}20; border:1px solid ${cardAccent(i)}40`">
                                            <img v-if="grp.prev[i]" :src="grp.prev[i]" class="w-10 h-10 object-contain" />
                                            <span v-else-if="card.svg_code" class="w-6 h-6" :style="`color:${cardAccent(i)}`" v-html="card.svg_code" />
                                            <span v-else class="w-6 h-6" :style="`color:${cardAccent(i)}`" v-html="card.defaultSvg" />
                                        </div>
                                        <button v-if="grp.prev[i] || card.svg_code" type="button"
                                                @click="resetCardIcon(grp.group, i)"
                                                class="absolute -top-1.5 -right-1.5 w-5 h-5 rounded-full bg-rapanel-danger flex items-center justify-center shadow hover:opacity-80 transition-opacity z-10"
                                                :title="__('Revert to default icon')">
                                            <svg class="w-2.5 h-2.5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                    </div>
                                    <!-- Controls -->
                                    <div class="min-w-0 flex-1 space-y-2">
                                        <input type="file" accept="image/png,image/svg+xml,image/webp,image/jpeg"
                                               @change="onCardImage(grp.group, i, $event)"
                                               class="block w-full text-xs text-rapanel-text-light dark:text-rapanel-text-dark file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-rapanel-blue/10 file:text-rapanel-blue hover:file:bg-rapanel-blue/20 cursor-pointer" />
                                        <p class="text-xs text-rapanel-text-light dark:text-rapanel-text-dark opacity-40">PNG / WebP · 40×40 px</p>
                                        <div>
                                            <label class="block text-xs text-rapanel-text-light dark:text-rapanel-text-dark opacity-60 mb-1">{{ __('Or paste SVG code (e.g. from heroicons):') }}</label>
                                            <textarea v-model="card.svg_code" rows="2" spellcheck="false"
                                                      placeholder="<svg viewBox=&quot;0 0 24 24&quot; ...>...</svg>"
                                                      @input="onCardSvg(grp.group, i)"
                                                      class="w-full rounded-lg border border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-rapanel-navy-800 px-2 py-1.5 text-xs text-rapanel-navy-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-rapanel-blue resize-none font-mono" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <div class="flex items-center gap-3">
                    <PrimaryButton :class="{ 'opacity-50': cardsSaving }" :disabled="cardsSaving" @click="saveCards">
                        {{ cardsSaving ? __('Saving...') : __('Save') }}
                    </PrimaryButton>
                    <p class="text-xs text-rapanel-text-light/60 dark:text-rapanel-text-dark/60">{{ __('Free SVG icons:') }}
                        <a href="https://heroicons.com" target="_blank" class="text-rapanel-gold hover:underline">heroicons.com</a> ·
                        <a href="https://lucide.dev" target="_blank" class="text-rapanel-gold hover:underline">lucide.dev</a>
                    </p>
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
                        <span class="mt-1 px-3 py-1 rounded-md text-[11px] font-bold text-white shadow" :style="{ background: `linear-gradient(135deg, ${form.home.accent} 0%, ${form.home.accent2} 100%)` }">{{ __('Register Now') }}</span>
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
                            <div class="space-y-3">
                                <div class="flex items-center gap-3">
                                    <input type="color" v-model="form[mode.key].bg"
                                        class="h-9 w-11 shrink-0 rounded-md border border-rapanel-navy-100 dark:border-white/10 cursor-pointer bg-transparent p-0.5" />
                                    <input type="text" v-model="form[mode.key].bg" maxlength="7"
                                        class="w-24 font-mono text-xs rounded-md border-rapanel-navy-100 dark:border-white/10 bg-white dark:bg-rapanel-surface text-rapanel-text-light dark:text-rapanel-text-dark focus:ring-rapanel-blue focus:border-rapanel-blue" />
                                    <span class="text-sm text-rapanel-text-light/70 dark:text-rapanel-text-dark/70">{{ __('Background color') }}</span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <input type="color" v-model="form.home.card[mode.key]"
                                        class="h-9 w-11 shrink-0 rounded-md border border-rapanel-navy-100 dark:border-white/10 cursor-pointer bg-transparent p-0.5" />
                                    <input type="text" v-model="form.home.card[mode.key]" maxlength="7"
                                        class="w-24 font-mono text-xs rounded-md border-rapanel-navy-100 dark:border-white/10 bg-white dark:bg-rapanel-surface text-rapanel-text-light dark:text-rapanel-text-dark focus:ring-rapanel-blue focus:border-rapanel-blue" />
                                    <span class="text-sm text-rapanel-text-light/70 dark:text-rapanel-text-dark/70">{{ __('Card color') }}</span>
                                </div>
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

                    <!-- Acento + degradado del botón del hero (2 colores) -->
                    <div>
                        <p class="text-[11px] font-black uppercase tracking-widest text-rapanel-text-light/45 dark:text-rapanel-text-dark/45 mb-3">{{ __('Accent color') }}</p>
                        <div class="flex flex-wrap items-center gap-3">
                            <input type="color" v-model="form.home.accent" title="Inicio del degradado"
                                class="h-9 w-11 shrink-0 rounded-md border border-rapanel-navy-100 dark:border-white/10 cursor-pointer bg-transparent p-0.5" />
                            <input type="color" v-model="form.home.accent2" title="Fin del degradado"
                                class="h-9 w-11 shrink-0 rounded-md border border-rapanel-navy-100 dark:border-white/10 cursor-pointer bg-transparent p-0.5" />
                            <span class="px-4 py-1.5 rounded-md text-xs font-bold text-white shadow"
                                  :style="{ background: `linear-gradient(135deg, ${form.home.accent} 0%, ${form.home.accent2} 100%)` }">{{ __('Register Now') }}</span>
                        </div>
                        <p class="mt-2 text-[11px] text-rapanel-text-light/45 dark:text-rapanel-text-dark/45">{{ __('Two colors for the hero button gradient. The first also tints the background grids and glows.') }}</p>
                    </div>

                    <!-- Botón secundario del hero (Learn More) -->
                    <div>
                        <p class="text-[11px] font-black uppercase tracking-widest text-rapanel-text-light/45 dark:text-rapanel-text-dark/45 mb-3">{{ __('Secondary button') }}</p>
                        <div class="flex flex-wrap items-center gap-3">
                            <input type="color" v-model="form.home.ghost"
                                class="h-9 w-11 shrink-0 rounded-md border border-rapanel-navy-100 dark:border-white/10 cursor-pointer bg-transparent p-0.5" />
                            <input type="text" v-model="form.home.ghost" maxlength="7"
                                class="w-24 font-mono text-xs rounded-md border-rapanel-navy-100 dark:border-white/10 bg-white dark:bg-rapanel-surface text-rapanel-text-light dark:text-rapanel-text-dark focus:ring-rapanel-blue focus:border-rapanel-blue" />
                            <span class="px-4 py-1.5 rounded-md text-xs font-bold text-rapanel-text-light dark:text-rapanel-text-dark"
                                  :style="{ background: `${form.home.ghost}1a`, border: `1px solid ${form.home.ghost}59` }">{{ __('Learn More') }}</span>
                        </div>
                        <p class="mt-2 text-[11px] text-rapanel-text-light/45 dark:text-rapanel-text-dark/45">{{ __('The "Learn More" outline button in the hero.') }}</p>
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

                <!-- Fondo del hero de la home (imagen / video) -->
                <div class="bg-white dark:bg-rapanel-navy-900 border border-rapanel-navy-100 dark:border-white/10 rounded-xl p-6 space-y-5">
                    <div>
                        <h2 class="font-display font-bold uppercase tracking-wider text-sm text-rapanel-text-light dark:text-rapanel-text-dark">{{ __('Hero background') }}</h2>
                        <p class="text-[11px] text-rapanel-text-light/55 dark:text-rapanel-text-dark/55 mt-1">{{ __('If a video is set it takes priority over the image. Leave both empty to use the default background.') }}</p>
                    </div>

                    <!-- Imagen del hero -->
                    <div class="flex flex-col sm:flex-row gap-5 items-start">
                        <div class="w-full sm:w-64 aspect-video rounded-lg overflow-hidden border border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-rapanel-surface flex items-center justify-center shrink-0">
                            <img :src="heroPreview" alt="" class="w-full h-full object-cover" />
                        </div>
                        <div class="flex flex-col gap-3">
                            <span class="text-xs font-bold text-rapanel-text-light dark:text-rapanel-text-dark">{{ __('Background Image') }}</span>
                            <label class="inline-flex">
                                <input type="file" accept="image/jpeg,image/png,image/webp,image/gif" class="hidden" @change="onHeroImage" />
                                <span class="cursor-pointer inline-flex items-center gap-1.5 font-display font-bold text-xs px-3 py-1.5 rounded-lg border bg-rapanel-blue/30 dark:bg-rapanel-blue/10 text-rapanel-blue border-rapanel-blue/40 dark:border-rapanel-blue/20 hover:bg-rapanel-blue hover:text-white transition-all">
                                    <PhotoIcon class="w-4 h-4" /> {{ __('Change') }}
                                </span>
                            </label>
                            <ActionButton v-if="heroIsCustom" variant="danger" size="sm" @click="clearHeroImage">{{ __('Remove image') }}</ActionButton>
                            <span class="text-[11px] font-bold uppercase tracking-wider" :class="heroIsCustom ? 'text-rapanel-success' : 'text-rapanel-text-light/40 dark:text-rapanel-text-dark/40'">{{ heroIsCustom ? __('Custom') : __('Default') }}</span>
                            <p class="text-[11px] text-rapanel-text-light/45 dark:text-rapanel-text-dark/45 max-w-xs">JPG, PNG, WEBP · max 10MB · 1920×1080</p>
                        </div>
                    </div>

                    <!-- Video del hero -->
                    <div class="flex flex-col sm:flex-row gap-5 items-start border-t border-rapanel-navy-100 dark:border-white/10 pt-5">
                        <div class="w-full sm:w-64 aspect-video rounded-lg overflow-hidden border border-rapanel-navy-100 dark:border-white/10 bg-black flex items-center justify-center shrink-0">
                            <video v-if="heroVideoPreview" :src="heroVideoPreview" class="w-full h-full object-cover" autoplay muted loop playsinline />
                            <svg v-else class="w-6 h-6 text-white/30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25h-9A2.25 2.25 0 002.25 7.5v9a2.25 2.25 0 002.25 2.25z"/></svg>
                        </div>
                        <div class="flex flex-col gap-3">
                            <span class="text-xs font-bold text-rapanel-text-light dark:text-rapanel-text-dark">{{ __('Background Video') }}</span>
                            <label class="inline-flex">
                                <input type="file" accept="video/mp4,video/webm" class="hidden" @change="onHeroVideo" />
                                <span class="cursor-pointer inline-flex items-center gap-1.5 font-display font-bold text-xs px-3 py-1.5 rounded-lg border bg-rapanel-blue/30 dark:bg-rapanel-blue/10 text-rapanel-blue border-rapanel-blue/40 dark:border-rapanel-blue/20 hover:bg-rapanel-blue hover:text-white transition-all">
                                    <PhotoIcon class="w-4 h-4" /> {{ __('Change') }}
                                </span>
                            </label>
                            <ActionButton v-if="heroVideoPreview" variant="danger" size="sm" @click="clearHeroVideo">{{ __('Remove video') }}</ActionButton>
                            <p class="text-[11px] text-rapanel-text-light/45 dark:text-rapanel-text-dark/45 max-w-xs">MP4, WEBM · max 100MB · {{ __('Takes priority over the image.') }}</p>
                        </div>
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
                <p class="text-[11px] text-rapanel-text-light/55 dark:text-rapanel-text-dark/55 mb-4">{{ __('Used on inner pages, not the home hero (set above).') }}</p>
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

        <!-- Barra de acciones: tema (colores). En la pestaña Brand hay un form aparte. -->
        <div v-show="activeTab !== 'brand' && activeTab !== 'cards'" class="fixed bottom-0 inset-x-0 lg:left-64 z-20 bg-white/95 dark:bg-rapanel-surface-deep/95 backdrop-blur border-t border-rapanel-navy-100 dark:border-white/10 px-4 sm:px-8 py-3 flex items-center gap-3">
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

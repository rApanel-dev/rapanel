<script setup>
import { ref, computed, watch } from 'vue';
import { useForm, usePage, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { ro_game_modes, ro_episodes } from '@/helpers';
import {
    Cog6ToothIcon,
    HomeIcon,
    MagnifyingGlassIcon,
    ExclamationTriangleIcon,
    PhotoIcon,
    SparklesIcon,
    ChartBarIcon,
    InformationCircleIcon,
    NewspaperIcon,
    Squares2X2Icon,
    CursorArrowRaysIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    settings: { type: Object, default: () => ({}) },
});

const page = usePage();
const __ = (key, rep = {}) => {
    let t = page.props.translations?.[key] || key;
    Object.entries(rep).forEach(([k, v]) => { t = t.replace(`:${k}`, v); });
    return t;
};

const activeTab = ref('general');

// --- General form ---
const generalForm = useForm({
    site_name:          props.settings.site_name ?? '',
    logo_light:         null,
    logo_dark:          null,
    discord_server_id:  props.settings.discord_server_id ?? '',
    discord_invite_url: props.settings.discord_invite_url ?? '',
});

const logoLightPreview = ref(
    props.settings.logo_light ? '/storage/' + props.settings.logo_light : '/images/logo.png'
);
const logoDarkPreview = ref(
    props.settings.logo_dark ? '/storage/' + props.settings.logo_dark : '/images/logo.png'
);

const onLogoLight = (e) => {
    const file = e.target.files[0];
    if (!file) return;
    generalForm.logo_light = file;
    logoLightPreview.value = URL.createObjectURL(file);
};

const onLogoDark = (e) => {
    const file = e.target.files[0];
    if (!file) return;
    generalForm.logo_dark = file;
    logoDarkPreview.value = URL.createObjectURL(file);
};

const submitGeneral = () => {
    generalForm.post(route('admin.settings.general'), { forceFormData: true });
};

// --- Home sections toggles ---
const sections = ref({
    home_show_stats:    props.settings.home_show_stats    !== '0',
    home_show_info:     props.settings.home_show_info     !== '0',
    home_show_news:     props.settings.home_show_news     !== '0',
    home_show_features: props.settings.home_show_features !== '0',
    home_show_cta:      props.settings.home_show_cta      !== '0',
});

const tabs = computed(() => [
    { key: 'general',     label: 'General',     icon: Cog6ToothIcon },
    { key: 'home',        label: 'Home',         icon: HomeIcon },
    ...(sections.value.home_show_info ? [{ key: 'server-info', label: 'Server Info', icon: InformationCircleIcon }] : []),
    { key: 'seo',         label: 'SEO',          icon: MagnifyingGlassIcon },
    { key: 'danger',      label: 'Danger Zone',  icon: ExclamationTriangleIcon },
]);

watch(sections, (val) => {
    if (activeTab.value === 'server-info' && !val.home_show_info) activeTab.value = 'home';
}, { deep: true });

const savingSections = ref(false);

const toggleSection = () => {
    savingSections.value = true;
    router.post(route('admin.settings.home-sections'), {
        home_show_stats:    sections.value.home_show_stats,
        home_show_info:     sections.value.home_show_info,
        home_show_news:     sections.value.home_show_news,
        home_show_features: sections.value.home_show_features,
        home_show_cta:      sections.value.home_show_cta,
    }, {
        preserveScroll: true,
        preserveState:  true,
        onFinish: () => { savingSections.value = false; },
    });
};

const sectionRows = [
    { key: 'home_show_stats',    icon: ChartBarIcon,          label: 'Stats Bar',       desc: 'Server status and peak players bar.' },
    { key: 'home_show_info',     icon: InformationCircleIcon, label: 'Server Info',     desc: 'Rates, max level, game mode, episode and highlight cards.' },
    { key: 'home_show_news',     icon: NewspaperIcon,         label: 'Latest News',     desc: 'Scrolling news marquee.' },
    { key: 'home_show_features', icon: Squares2X2Icon,        label: 'Panel Features',  desc: 'Feature cards grid (Dashboard, Item DB, Wiki…).' },
    { key: 'home_show_cta',      icon: CursorArrowRaysIcon,   label: 'Call to Action',  desc: 'Final register banner at the bottom.' },
];

// --- Info Blocks ---
const parseInfoBlocks = () => {
    try { const r = props.settings.home_info_blocks; if (r) return JSON.parse(r) } catch {}
    return ['rates','level','mode','episode','intl'].map(id => ({ id, show: true, icon_type: 'icon', image: null, svg_code: null }))
}

const infoBlocksMeta = [
    {
        id: 'rates', label: 'EXP / Job / Drop', color: '#4A90E2',
        svg: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z"/></svg>`,
    },
    {
        id: 'level', label: 'Max Base / Job', color: '#F1C40F',
        svg: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"/></svg>`,
    },
    {
        id: 'mode', label: 'Game Mode', color: '#2ECC71',
        svg: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6.75V15m6-6v8.25m.503 3.498l4.875-2.437c.381-.19.622-.58.622-1.006V4.82c0-.836-.88-1.38-1.628-1.006l-3.869 1.934c-.317.159-.69.159-1.006 0L9.503 3.252a1.125 1.125 0 00-1.006 0L3.622 5.689C3.24 5.88 3 6.27 3 6.695V19.18c0 .836.88 1.38 1.628 1.006l3.869-1.934c.317-.159.69-.159 1.006 0l4.994 2.497c.317.158.69.158 1.006 0z"/></svg>`,
    },
    {
        id: 'episode', label: 'Episode', color: '#F1C40F',
        svg: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>`,
    },
    {
        id: 'intl', label: 'International', color: '#a855f7',
        svg: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418"/></svg>`,
    },
]

const infoBlocks   = ref(parseInfoBlocks())
const infoFiles    = ref([null, null, null, null, null])
const infoPreviews = ref(parseInfoBlocks().map(b => b.image ? '/storage/' + b.image : null))
const infoSaving   = ref(false)

const onInfoFile = (e, i) => {
    const file = e.target.files[0]
    if (!file) return
    infoFiles.value[i]    = file
    infoPreviews.value[i] = URL.createObjectURL(file)
    infoBlocks.value[i].icon_type = 'image'
}

// Valores de texto de cada bloque
const infoTextValues = ref({
    home_base_rate:      props.settings.home_base_rate      ?? '100',
    home_job_rate:       props.settings.home_job_rate       ?? '100',
    home_drop_rate:      props.settings.home_drop_rate      ?? '100',
    home_max_base_level: props.settings.home_max_base_level ?? '99',
    home_max_job_level:  props.settings.home_max_job_level  ?? '70',
    home_game_mode:      props.settings.home_game_mode      ?? '',
    home_episode:        props.settings.home_episode        ?? '',
    home_intl_text:      props.settings.home_intl_text      ?? 'EN · ES · PT · FR',
})

const submitInfoBlocks = () => {
    infoSaving.value = true
    const fd = new FormData()
    // Bloques config
    infoBlocks.value.forEach((b, i) => {
        fd.append(`blocks[${i}][show]`,      b.show ? '1' : '0')
        fd.append(`blocks[${i}][icon_type]`, b.icon_type)
        if (infoFiles.value[i]) fd.append(`blocks[${i}][image]`, infoFiles.value[i])
    })
    // svg_code por bloque
    infoBlocks.value.forEach((b, i) => {
        fd.append(`blocks[${i}][svg_code]`, b.svg_code ?? '')
    })
    // Valores de texto (incluyendo intl)
    Object.entries(infoTextValues.value).forEach(([k, v]) => fd.append(k, v ?? ''))
    router.post(route('admin.settings.home-info-blocks'), fd, {
        forceFormData: true, preserveScroll: true,
        onFinish: () => { infoSaving.value = false },
    })
}

// --- Highlight Cards ---
const parseHighlightCards = () => {
    try { const r = props.settings.home_highlight_cards; if (r) return JSON.parse(r) } catch {}
    return Array.from({ length: 4 }, () => ({ show: true, image: null, title: '', desc: '', url: '#' }))
}

const highlightCards    = ref(parseHighlightCards())
const highlightFiles    = ref([null, null, null, null])
const highlightPreviews = ref(parseHighlightCards().map(c => c.image ? '/storage/' + c.image : null))
const highlightSaving   = ref(false)

const onHighlightFile = (e, i) => {
    const file = e.target.files[0]
    if (!file) return
    highlightFiles.value[i]    = file
    highlightPreviews.value[i] = URL.createObjectURL(file)
}

const submitHighlightCards = () => {
    highlightSaving.value = true
    const fd = new FormData()
    highlightCards.value.forEach((c, i) => {
        fd.append(`cards[${i}][show]`,  c.show ? '1' : '0')
        fd.append(`cards[${i}][title]`, c.title ?? '')
        fd.append(`cards[${i}][desc]`,  c.desc  ?? '')
        fd.append(`cards[${i}][url]`,   c.url   ?? '#')
        if (highlightFiles.value[i]) fd.append(`cards[${i}][image]`, highlightFiles.value[i])
    })
    router.post(route('admin.settings.home-highlight-cards'), fd, {
        forceFormData: true, preserveScroll: true,
        onFinish: () => { highlightSaving.value = false },
    })
}

// --- Home form ---
// Hero form (Home tab)
const heroForm = useForm({
    home_hero_subtitle:   props.settings.home_hero_subtitle  ?? '',
    home_learn_more_url:  props.settings.home_learn_more_url ?? '',
    home_hero_bg_image:   null,
    home_hero_bg_video:   null,
    remove_hero_bg_image: false,
    remove_hero_bg_video: false,
});

const heroBgImageIsCustom = ref(!!props.settings.home_hero_bg_image);
const heroBgImagePreview  = ref(
    props.settings.home_hero_bg_image
        ? '/storage/' + props.settings.home_hero_bg_image
        : '/images/bg-main.svg'
);
const heroBgVideoPreview = ref(
    props.settings.home_hero_bg_video ? '/storage/' + props.settings.home_hero_bg_video : null
);

const onHeroBgImage = (e) => {
    const file = e.target.files[0];
    if (!file) return;
    heroForm.home_hero_bg_image = file;
    heroBgImagePreview.value    = URL.createObjectURL(file);
    heroBgImageIsCustom.value   = true;
};

const onHeroBgVideo = (e) => {
    const file = e.target.files[0];
    if (!file) return;
    heroForm.home_hero_bg_video = file;
    heroBgVideoPreview.value = URL.createObjectURL(file);
};

const submitHero = () => {
    heroForm.post(route('admin.settings.home-hero'), { forceFormData: true });
};


// --- SEO form ---
const seoForm = useForm({
    seo_title:       props.settings.seo_title ?? '',
    seo_description: props.settings.seo_description ?? '',
    seo_theme_color: props.settings.seo_theme_color ?? '#3b82f6',
    favicon:         null,
    seo_og_image:    null,
});

const faviconPreview = ref(
    props.settings.favicon ? '/storage/' + props.settings.favicon : null
);
const ogImagePreview = ref(
    props.settings.seo_og_image ? '/storage/' + props.settings.seo_og_image : null
);

const onFavicon = (e) => {
    const file = e.target.files[0];
    if (!file) return;
    seoForm.favicon = file;
    faviconPreview.value = URL.createObjectURL(file);
};

const onOgImage = (e) => {
    const file = e.target.files[0];
    if (!file) return;
    seoForm.seo_og_image = file;
    ogImagePreview.value = URL.createObjectURL(file);
};

const submitSeo = () => {
    seoForm.post(route('admin.settings.seo'), { forceFormData: true });
};

// --- Danger zone ---
const dangerConfirm = ref(null);

const dangerActions = [
    {
        key:         'logs',
        title:       'Clear Action Logs',
        description: 'Permanently delete all audit log entries from the panel.',
        route:       'admin.settings.danger.logs',
        color:       'danger',
    },
    {
        key:         'cache',
        title:       'Clear Site Cache',
        description: 'Flush all application caches (settings, news, server status, etc.).',
        route:       'admin.settings.danger.cache',
        color:       'warning',
    },
    {
        key:         'sessions',
        title:       'Clear All Sessions',
        description: 'Force all users (including admins) to log in again.',
        route:       'admin.settings.danger.sessions',
        color:       'danger',
    },
];

const dangerForm = useForm({});

const confirmDanger = (action) => {
    dangerConfirm.value = action;
};

const executeDanger = () => {
    if (!dangerConfirm.value) return;
    dangerForm.post(route(dangerConfirm.value.route), {
        onFinish: () => { dangerConfirm.value = null; },
    });
};
</script>

<template>
    <AdminLayout>
        <!-- Header -->
        <div class="flex items-center gap-3 mb-6">
            <Cog6ToothIcon class="w-6 h-6 text-rapanel-blue" />
            <h1 class="text-xl font-bold text-rapanel-navy-900 dark:text-white">{{ __('Site Settings') }}</h1>
        </div>

        <!-- Tabs -->
        <div class="flex gap-1 mb-6 bg-rapanel-navy-50 dark:bg-rapanel-navy-800 rounded-xl p-1 w-fit">
            <button
                v-for="tab in tabs"
                :key="tab.key"
                @click="activeTab = tab.key"
                :class="[
                    'flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold transition-all',
                    activeTab === tab.key
                        ? 'bg-white dark:bg-rapanel-navy-700 text-rapanel-navy-900 dark:text-white shadow-sm'
                        : 'text-rapanel-text-light dark:text-rapanel-text-dark hover:text-rapanel-navy-900 dark:hover:text-white',
                    tab.key === 'danger' && activeTab !== 'danger' ? 'hover:text-rapanel-danger!' : '',
                    tab.key === 'danger' && activeTab === 'danger' ? 'text-rapanel-danger!' : '',
                ]"
            >
                <component :is="tab.icon" class="w-4 h-4" />
                {{ __(tab.label) }}
            </button>
        </div>

        <!-- ========== TAB: GENERAL ========== -->
        <form v-if="activeTab === 'general'" @submit.prevent="submitGeneral" class="space-y-6 max-w-2xl">
            <!-- Site Identity -->
            <div class="bg-white dark:bg-rapanel-navy-900 rounded-xl border border-rapanel-navy-100 dark:border-white/10 shadow-sm p-6 space-y-5">
                <h2 class="text-sm font-bold text-rapanel-navy-900 dark:text-white uppercase tracking-wider">{{ __('Site Identity') }}</h2>

                <div>
                    <label class="block text-sm font-semibold text-rapanel-navy-900 dark:text-white mb-1.5">{{ __('Server Name') }} *</label>
                    <input v-model="generalForm.site_name" type="text" required maxlength="100"
                        class="w-full rounded-lg border border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-rapanel-navy-800 px-3 py-2 text-sm text-rapanel-navy-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-rapanel-blue" />
                    <p v-if="generalForm.errors.site_name" class="mt-1 text-xs text-rapanel-danger">{{ generalForm.errors.site_name }}</p>
                </div>

                <!-- Logos -->
                <div>
                    <p class="text-xs text-rapanel-text-light dark:text-rapanel-text-dark opacity-60 mb-3">
                        {{ __('Recommended size: 300×60 px — PNG or SVG with transparent background, max 2 MB. The logo is displayed at 36–48 px height in the header.') }}
                    </p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <!-- Logo light -->
                        <div>
                            <label class="block text-sm font-semibold text-rapanel-navy-900 dark:text-white mb-2">{{ __('Logo (Light Mode)') }}</label>
                            <div class="h-14 bg-rapanel-navy-50 border border-rapanel-navy-100 dark:border-white/10 rounded-lg px-3 flex items-center mb-2">
                                <img :src="logoLightPreview" class="h-9 object-contain max-w-[200px]" alt="Logo light preview" />
                            </div>
                            <input type="file" accept="image/*" @change="onLogoLight"
                                class="text-sm text-rapanel-text-light dark:text-rapanel-text-dark file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-rapanel-blue/10 file:text-rapanel-blue hover:file:bg-rapanel-blue/20 cursor-pointer" />
                            <p class="mt-1 text-xs text-rapanel-text-light dark:text-rapanel-text-dark opacity-50">{{ __('Used on white/light backgrounds') }}</p>
                            <p v-if="generalForm.errors.logo_light" class="mt-1 text-xs text-rapanel-danger">{{ generalForm.errors.logo_light }}</p>
                        </div>

                        <!-- Logo dark -->
                        <div>
                            <label class="block text-sm font-semibold text-rapanel-navy-900 dark:text-white mb-2">{{ __('Logo (Dark Mode)') }}</label>
                            <div class="h-14 bg-rapanel-navy-800 border border-white/10 rounded-lg px-3 flex items-center mb-2">
                                <img :src="logoDarkPreview" class="h-9 object-contain max-w-[200px]" alt="Logo dark preview" />
                            </div>
                            <input type="file" accept="image/*" @change="onLogoDark"
                                class="text-sm text-rapanel-text-light dark:text-rapanel-text-dark file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-rapanel-blue/10 file:text-rapanel-blue hover:file:bg-rapanel-blue/20 cursor-pointer" />
                            <p class="mt-1 text-xs text-rapanel-text-light dark:text-rapanel-text-dark opacity-50">{{ __('Used on dark/navy backgrounds') }}</p>
                            <p v-if="generalForm.errors.logo_dark" class="mt-1 text-xs text-rapanel-danger">{{ generalForm.errors.logo_dark }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Discord -->
            <div class="bg-white dark:bg-rapanel-navy-900 rounded-xl border border-rapanel-navy-100 dark:border-white/10 shadow-sm p-6 space-y-4">
                <h2 class="text-sm font-bold text-rapanel-navy-900 dark:text-white uppercase tracking-wider">{{ __('Discord') }}</h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-rapanel-navy-900 dark:text-white mb-1.5">{{ __('Discord Server ID') }}</label>
                        <input v-model="generalForm.discord_server_id" type="text" maxlength="100" placeholder="764468997843845131"
                            class="w-full rounded-lg border border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-rapanel-navy-800 px-3 py-2 text-sm text-rapanel-navy-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-rapanel-blue" />
                        <p v-if="generalForm.errors.discord_server_id" class="mt-1 text-xs text-rapanel-danger">{{ generalForm.errors.discord_server_id }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-rapanel-navy-900 dark:text-white mb-1.5">{{ __('Discord Invite URL') }}</label>
                        <input v-model="generalForm.discord_invite_url" type="url" maxlength="500" placeholder="https://discord.gg/..."
                            class="w-full rounded-lg border border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-rapanel-navy-800 px-3 py-2 text-sm text-rapanel-navy-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-rapanel-blue" />
                        <p v-if="generalForm.errors.discord_invite_url" class="mt-1 text-xs text-rapanel-danger">{{ generalForm.errors.discord_invite_url }}</p>
                    </div>
                </div>
            </div>

            <button type="submit" :disabled="generalForm.processing"
                class="px-6 py-2.5 bg-rapanel-blue hover:bg-rapanel-blue/90 disabled:opacity-60 text-white text-sm font-bold rounded-lg transition-colors">
                {{ generalForm.processing ? __('Saving...') : __('Save General Settings') }}
            </button>
        </form>

        <!-- ========== TAB: HOME ========== -->
        <div v-else-if="activeTab === 'home'" class="space-y-6 max-w-2xl">

            <!-- Section Visibility -->
            <div class="bg-white dark:bg-rapanel-navy-900 rounded-xl border border-rapanel-navy-100 dark:border-white/10 shadow-sm p-6">
                <div class="flex items-center justify-between mb-1">
                    <h2 class="text-sm font-bold text-rapanel-navy-900 dark:text-white uppercase tracking-wider">{{ __('Home Sections') }}</h2>
                    <span v-if="savingSections" class="text-xs text-rapanel-text-light dark:text-rapanel-text-dark opacity-50 animate-pulse">{{ __('Saving...') }}</span>
                </div>
                <p class="text-xs text-rapanel-text-light dark:text-rapanel-text-dark opacity-60 mb-5">{{ __('Toggle which sections are visible on the home page. Changes are saved instantly.') }}</p>

                <div class="divide-y divide-rapanel-navy-100 dark:divide-white/5">

                    <!-- Hero: siempre activo -->
                    <div class="flex items-center justify-between py-3.5">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-rapanel-blue/10 flex items-center justify-center flex-shrink-0">
                                <SparklesIcon class="w-4 h-4 text-rapanel-blue" />
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-rapanel-navy-900 dark:text-white">{{ __('Hero') }}</p>
                                <p class="text-xs text-rapanel-text-light dark:text-rapanel-text-dark opacity-55">{{ __('Main banner with title and call-to-action buttons.') }}</p>
                            </div>
                        </div>
                        <span class="text-xs font-bold text-rapanel-success uppercase tracking-wider px-2.5 py-1 bg-rapanel-success/10 rounded-full">{{ __('Always On') }}</span>
                    </div>

                    <!-- Toggleables -->
                    <div v-for="row in sectionRows" :key="row.key" class="flex items-center justify-between py-3.5">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0"
                                 :class="sections[row.key] ? 'bg-rapanel-blue/10' : 'bg-rapanel-navy-100 dark:bg-rapanel-navy-800'">
                                <component :is="row.icon" class="w-4 h-4"
                                           :class="sections[row.key] ? 'text-rapanel-blue' : 'text-rapanel-text-light dark:text-rapanel-text-dark opacity-40'" />
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-rapanel-navy-900 dark:text-white">{{ __(row.label) }}</p>
                                <p class="text-xs text-rapanel-text-light dark:text-rapanel-text-dark opacity-55">{{ __(row.desc) }}</p>
                            </div>
                        </div>
                        <!-- Toggle switch -->
                        <button type="button" role="switch" :aria-checked="sections[row.key]"
                                @click="sections[row.key] = !sections[row.key]; toggleSection()"
                                class="relative inline-flex h-6 w-11 flex-shrink-0 items-center rounded-full transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-rapanel-blue focus:ring-offset-2 dark:focus:ring-offset-rapanel-navy-900"
                                :class="sections[row.key] ? 'bg-rapanel-success' : 'bg-rapanel-navy-100 dark:bg-rapanel-navy-700'">
                            <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow-md transition-transform duration-200"
                                  :class="sections[row.key] ? 'translate-x-6' : 'translate-x-1'" />
                        </button>
                    </div>

                </div>
            </div>

            <!-- Hero settings -->
            <form @submit.prevent="submitHero" class="space-y-6">
                <div class="bg-white dark:bg-rapanel-navy-900 rounded-xl border border-rapanel-navy-100 dark:border-white/10 shadow-sm p-6 space-y-4">
                    <h2 class="text-sm font-bold text-rapanel-navy-900 dark:text-white uppercase tracking-wider">{{ __('Hero') }}</h2>

                    <div>
                        <label class="block text-sm font-semibold text-rapanel-navy-900 dark:text-white mb-1.5">{{ __('Hero Subtitle') }}</label>
                        <textarea v-model="heroForm.home_hero_subtitle" rows="2" maxlength="300"
                            :placeholder="__('A classic Ragnarok Online experience, reimagined for a new era.')"
                            class="w-full rounded-lg border border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-rapanel-navy-800 px-3 py-2 text-sm text-rapanel-navy-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-rapanel-blue resize-none" />
                        <p class="mt-1 text-xs text-rapanel-text-light dark:text-rapanel-text-dark opacity-50">{{ __('Leave empty to use the default translated text.') }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-rapanel-navy-900 dark:text-white mb-1.5">{{ __('Learn More — Button URL') }}</label>
                        <input v-model="heroForm.home_learn_more_url" type="url" maxlength="500" placeholder="https://... o /wiki"
                            class="w-full rounded-lg border border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-rapanel-navy-800 px-3 py-2 text-sm text-rapanel-navy-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-rapanel-blue" />
                        <p class="mt-1 text-xs text-rapanel-text-light dark:text-rapanel-text-dark opacity-50">{{ __('Leave empty to scroll to the next section.') }}</p>
                    </div>

                    <!-- Background Image -->
                    <div class="border-t border-rapanel-navy-100 dark:border-white/10 pt-4">
                        <p class="text-xs font-bold text-rapanel-navy-900 dark:text-white uppercase tracking-wider mb-3">{{ __('Background') }}</p>
                        <p class="text-xs text-rapanel-text-light dark:text-rapanel-text-dark opacity-55 mb-4">
                            {{ __('If a video is set it takes priority over the image. Leave both empty to use the default SVG background.') }}
                        </p>

                        <!-- Image -->
                        <div class="space-y-2 mb-5">
                            <label class="block text-sm font-semibold text-rapanel-navy-900 dark:text-white">{{ __('Background Image') }}</label>
                            <div class="relative w-full h-28 rounded-xl overflow-hidden border border-rapanel-navy-100 dark:border-white/10">
                                <img :src="heroBgImagePreview" class="w-full h-full object-cover" />
                                <span class="absolute top-2 left-2 text-xs bg-black/60 text-white px-2 py-0.5 rounded-full">
                                    {{ heroBgImageIsCustom ? __('Custom image') : __('Default background') }}
                                </span>
                                <button v-if="heroBgImageIsCustom" type="button"
                                        @click="heroBgImagePreview = '/images/bg-main.svg'; heroBgImageIsCustom = false; heroForm.remove_hero_bg_image = true; heroForm.home_hero_bg_image = null; heroForm.post(route('admin.settings.home-hero'), { forceFormData: true })"
                                        class="absolute top-2 right-2 w-6 h-6 rounded-full bg-rapanel-danger flex items-center justify-center hover:opacity-80 transition-opacity"
                                        title="Remove image">
                                    <svg class="w-3 h-3 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                            <input type="file" accept="image/jpeg,image/png,image/webp,image/gif" @change="onHeroBgImage"
                                class="text-xs text-rapanel-text-light dark:text-rapanel-text-dark file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-rapanel-blue/10 file:text-rapanel-blue hover:file:bg-rapanel-blue/20 cursor-pointer" />
                            <p class="text-xs text-rapanel-text-light dark:text-rapanel-text-dark opacity-40">JPG/PNG/WebP — max 10 MB. Recomendado: 1920×1080 px.</p>
                        </div>

                        <!-- Video -->
                        <div class="space-y-2">
                            <label class="block text-sm font-semibold text-rapanel-navy-900 dark:text-white">{{ __('Background Video') }}</label>
                            <div v-if="heroBgVideoPreview"
                                 class="relative w-full h-28 rounded-xl overflow-hidden border border-rapanel-navy-100 dark:border-white/10 bg-black">
                                <video :src="heroBgVideoPreview" class="w-full h-full object-cover" autoplay muted loop playsinline />
                                <span class="absolute top-2 left-2 text-xs bg-black/60 text-white px-2 py-0.5 rounded-full">{{ __('Current video') }}</span>
                                <button type="button"
                                        @click="heroBgVideoPreview = null; heroForm.remove_hero_bg_video = true; heroForm.home_hero_bg_video = null; heroForm.post(route('admin.settings.home-hero'), { forceFormData: true })"
                                        class="absolute top-2 right-2 w-6 h-6 rounded-full bg-rapanel-danger flex items-center justify-center hover:opacity-80 transition-opacity"
                                        title="Remove video">
                                    <svg class="w-3 h-3 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                            <div v-else class="w-full h-16 rounded-xl border border-dashed border-rapanel-navy-100 dark:border-white/10 flex items-center justify-center">
                                <svg class="w-5 h-5 text-rapanel-text-light dark:text-rapanel-text-dark opacity-30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25h-9A2.25 2.25 0 002.25 7.5v9a2.25 2.25 0 002.25 2.25z"/>
                                </svg>
                            </div>
                            <input type="file" accept="video/mp4,video/webm" @change="onHeroBgVideo"
                                class="text-xs text-rapanel-text-light dark:text-rapanel-text-dark file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-rapanel-blue/10 file:text-rapanel-blue hover:file:bg-rapanel-blue/20 cursor-pointer" />
                            <p class="text-xs text-rapanel-text-light dark:text-rapanel-text-dark opacity-40">MP4/WebM — max 100 MB. Tiene prioridad sobre la imagen.</p>
                        </div>
                    </div>
                </div>

                <button type="submit" :disabled="heroForm.processing"
                    class="px-6 py-2.5 bg-rapanel-blue hover:bg-rapanel-blue/90 disabled:opacity-60 text-white text-sm font-bold rounded-lg transition-colors">
                    {{ heroForm.processing ? __('Saving...') : __('Save Home Settings') }}
                </button>
            </form>

        </div>

        <!-- ========== TAB: SERVER INFO ========== -->
        <div v-else-if="activeTab === 'server-info'" class="space-y-6 max-w-2xl">

            <!-- ══ Info Blocks fusionados ══ -->
            <div class="bg-white dark:bg-rapanel-navy-900 rounded-xl border border-rapanel-navy-100 dark:border-white/10 shadow-sm p-6">
                <div class="flex items-center justify-between mb-1">
                    <h2 class="text-sm font-bold text-rapanel-navy-900 dark:text-white uppercase tracking-wider">{{ __('Server Info') }}</h2>
                    <span v-if="infoSaving" class="text-xs text-rapanel-text-light dark:text-rapanel-text-dark opacity-50 animate-pulse">{{ __('Saving...') }}</span>
                </div>
                <p class="text-xs text-rapanel-text-light dark:text-rapanel-text-dark opacity-60 mb-1">{{ __('Configure each info block: toggle visibility, choose icon type and set the display value.') }}</p>
                <p class="text-xs text-rapanel-text-light dark:text-rapanel-text-dark opacity-50 mb-5">
                    {{ __('Custom icon recommended size: 40×40 px.') }}
                    {{ __('Free SVG icons:') }}
                    <a href="https://heroicons.com" target="_blank" class="text-rapanel-gold hover:underline">heroicons.com</a> ·
                    <a href="https://lucide.dev" target="_blank" class="text-rapanel-gold hover:underline">lucide.dev</a> ·
                    <a href="https://iconify.design" target="_blank" class="text-rapanel-gold hover:underline">iconify.design</a>
                </p>

                <div class="space-y-3">
                    <div v-for="(block, i) in infoBlocks" :key="block.id"
                         class="rounded-xl border border-rapanel-navy-100 dark:border-white/10 p-4 transition-opacity"
                         :class="block.show ? '' : 'opacity-40'">

                        <!-- Fila superior: nombre del bloque + toggle -->
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full flex-shrink-0"
                                      :style="`background:${infoBlocksMeta[i].color}`"></span>
                                <span class="text-sm font-semibold text-rapanel-navy-900 dark:text-white">{{ __(infoBlocksMeta[i].label) }}</span>
                            </div>
                            <button type="button" role="switch" :aria-checked="block.show"
                                    @click="block.show = !block.show"
                                    class="relative inline-flex h-6 w-11 flex-shrink-0 items-center rounded-full transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-rapanel-blue focus:ring-offset-2 dark:focus:ring-offset-rapanel-navy-900"
                                    :class="block.show ? 'bg-rapanel-success' : 'bg-rapanel-navy-100 dark:bg-rapanel-navy-700'">
                                <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow-md transition-transform duration-200"
                                      :class="block.show ? 'translate-x-6' : 'translate-x-1'" />
                            </button>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Columna izquierda: icono -->
                            <div class="space-y-2">
                                <p class="text-xs font-semibold text-rapanel-text-light dark:text-rapanel-text-dark opacity-70 uppercase tracking-wide">{{ __('Icon') }}</p>

                                <div class="flex items-start gap-3">
                                    <!-- Preview: imagen custom O svg original -->
                                    <!-- Preview actual -->
                                    <div class="relative flex-shrink-0">
                                        <div class="w-12 h-12 rounded-xl flex items-center justify-center overflow-hidden"
                                             :style="`background:${infoBlocksMeta[i].color}20; border:1px solid ${infoBlocksMeta[i].color}40`">
                                            <img v-if="infoPreviews[i]" :src="infoPreviews[i]" class="w-10 h-10 object-contain" />
                                            <span v-else-if="block.svg_code"
                                                  class="w-6 h-6" :style="`color:${infoBlocksMeta[i].color}`"
                                                  v-html="block.svg_code" />
                                            <span v-else
                                                  class="w-6 h-6" :style="`color:${infoBlocksMeta[i].color}`"
                                                  v-html="infoBlocksMeta[i].svg" />
                                        </div>
                                        <!-- × quita lo custom y vuelve al original -->
                                        <button v-if="infoPreviews[i] || block.svg_code" type="button"
                                                @click="infoPreviews[i] = null; block.icon_type = 'icon'; infoFiles[i] = null; block.svg_code = null"
                                                class="absolute -top-1.5 -right-1.5 w-5 h-5 rounded-full bg-rapanel-danger flex items-center justify-center shadow hover:opacity-80 transition-opacity z-10"
                                                title="Volver al SVG original">
                                            <svg class="w-2.5 h-2.5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Opciones de cambio -->
                                    <div class="min-w-0 flex-1 space-y-2">
                                        <!-- Subir archivo -->
                                        <input type="file" accept="image/png,image/svg+xml,image/webp,image/jpeg"
                                               @change="onInfoFile($event, i)"
                                               class="block w-full text-xs text-rapanel-text-light dark:text-rapanel-text-dark file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-rapanel-blue/10 file:text-rapanel-blue hover:file:bg-rapanel-blue/20 cursor-pointer" />
                                        <p class="text-xs text-rapanel-text-light dark:text-rapanel-text-dark opacity-40">PNG / WebP · 40×40 px</p>

                                        <!-- Pegar código SVG -->
                                        <div>
                                            <label class="block text-xs text-rapanel-text-light dark:text-rapanel-text-dark opacity-60 mb-1">
                                                {{ __('Or paste SVG code (e.g. from heroicons):') }}
                                            </label>
                                            <textarea v-model="block.svg_code" rows="2" spellcheck="false"
                                                      placeholder="<svg viewBox=&quot;0 0 24 24&quot; ...>...</svg>"
                                                      @input="infoPreviews[i] = null; infoFiles[i] = null"
                                                      class="w-full rounded-lg border border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-rapanel-navy-800 px-2 py-1.5 text-xs text-rapanel-navy-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-rapanel-blue resize-none font-mono" />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Columna derecha: valores de texto -->
                            <div class="space-y-2">
                                <p class="text-xs font-semibold text-rapanel-text-light dark:text-rapanel-text-dark opacity-70 uppercase tracking-wide">{{ __('Value') }}</p>

                                <!-- Rates -->
                                <template v-if="block.id === 'rates'">
                                    <div class="grid grid-cols-3 gap-1.5">
                                        <div>
                                            <label class="text-xs text-rapanel-text-light dark:text-rapanel-text-dark opacity-60 mb-0.5 block">{{ __('Base') }}</label>
                                            <input v-model="infoTextValues.home_base_rate" type="text" maxlength="20" placeholder="100"
                                                   class="w-full rounded-lg border border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-rapanel-navy-800 px-2 py-1.5 text-sm text-rapanel-navy-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-rapanel-blue" />
                                        </div>
                                        <div>
                                            <label class="text-xs text-rapanel-text-light dark:text-rapanel-text-dark opacity-60 mb-0.5 block">{{ __('Job') }}</label>
                                            <input v-model="infoTextValues.home_job_rate" type="text" maxlength="20" placeholder="100"
                                                   class="w-full rounded-lg border border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-rapanel-navy-800 px-2 py-1.5 text-sm text-rapanel-navy-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-rapanel-blue" />
                                        </div>
                                        <div>
                                            <label class="text-xs text-rapanel-text-light dark:text-rapanel-text-dark opacity-60 mb-0.5 block">{{ __('Drop') }}</label>
                                            <input v-model="infoTextValues.home_drop_rate" type="text" maxlength="20" placeholder="100"
                                                   class="w-full rounded-lg border border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-rapanel-navy-800 px-2 py-1.5 text-sm text-rapanel-navy-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-rapanel-blue" />
                                        </div>
                                    </div>
                                </template>

                                <!-- Level -->
                                <template v-else-if="block.id === 'level'">
                                    <div class="grid grid-cols-2 gap-1.5">
                                        <div>
                                            <label class="text-xs text-rapanel-text-light dark:text-rapanel-text-dark opacity-60 mb-0.5 block">{{ __('Base') }}</label>
                                            <input v-model="infoTextValues.home_max_base_level" type="text" maxlength="10" placeholder="99"
                                                   class="w-full rounded-lg border border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-rapanel-navy-800 px-2 py-1.5 text-sm text-rapanel-navy-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-rapanel-blue" />
                                        </div>
                                        <div>
                                            <label class="text-xs text-rapanel-text-light dark:text-rapanel-text-dark opacity-60 mb-0.5 block">{{ __('Job') }}</label>
                                            <input v-model="infoTextValues.home_max_job_level" type="text" maxlength="10" placeholder="70"
                                                   class="w-full rounded-lg border border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-rapanel-navy-800 px-2 py-1.5 text-sm text-rapanel-navy-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-rapanel-blue" />
                                        </div>
                                    </div>
                                </template>

                                <!-- Mode -->
                                <template v-else-if="block.id === 'mode'">
                                    <select v-model="infoTextValues.home_game_mode"
                                            class="w-full rounded-lg border border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-rapanel-navy-800 px-2 py-1.5 text-sm text-rapanel-navy-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-rapanel-blue">
                                        <option value="">— Select —</option>
                                        <option v-for="m in ro_game_modes" :key="m.value" :value="m.value">{{ m.label }}</option>
                                    </select>
                                </template>

                                <!-- Episode -->
                                <template v-else-if="block.id === 'episode'">
                                    <select v-model="infoTextValues.home_episode"
                                            class="w-full rounded-lg border border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-rapanel-navy-800 px-2 py-1.5 text-sm text-rapanel-navy-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-rapanel-blue">
                                        <option value="">— Select —</option>
                                        <option v-for="ep in ro_episodes" :key="ep.value" :value="ep.value">{{ ep.label }}</option>
                                    </select>
                                </template>

                                <!-- International -->
                                <template v-else-if="block.id === 'intl'">
                                    <input v-model="infoTextValues.home_intl_text" type="text" maxlength="100" placeholder="EN · ES · PT · FR"
                                           class="w-full rounded-lg border border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-rapanel-navy-800 px-2 py-1.5 text-sm text-rapanel-navy-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-rapanel-blue" />
                                    <p class="text-xs text-rapanel-text-light dark:text-rapanel-text-dark opacity-40 mt-0.5">{{ __('Languages shown on the home page.') }}</p>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-5 flex justify-end">
                    <button type="button" @click="submitInfoBlocks" :disabled="infoSaving"
                            class="px-5 py-2 bg-rapanel-blue hover:bg-rapanel-blue/90 disabled:opacity-60 text-white text-sm font-bold rounded-lg transition-colors">
                        {{ infoSaving ? __('Saving...') : __('Save Server Info') }}
                    </button>
                </div>
            </div>

            <!-- ══ Highlight Cards ══ -->
            <div class="bg-white dark:bg-rapanel-navy-900 rounded-xl border border-rapanel-navy-100 dark:border-white/10 shadow-sm p-6">
                <div class="flex items-center justify-between mb-1">
                    <h2 class="text-sm font-bold text-rapanel-navy-900 dark:text-white uppercase tracking-wider">{{ __('Highlight Cards') }}</h2>
                    <span v-if="highlightSaving" class="text-xs text-rapanel-text-light dark:text-rapanel-text-dark opacity-50 animate-pulse">{{ __('Saving...') }}</span>
                </div>
                <p class="text-xs text-rapanel-text-light dark:text-rapanel-text-dark opacity-60 mb-5">{{ __('Customize the 4 content cards below the info blocks. Each card can have its own image, title, description and link.') }}</p>

                <div class="space-y-4">
                    <div v-for="(card, i) in highlightCards" :key="i"
                         class="rounded-xl border border-rapanel-navy-100 dark:border-white/10 p-4 transition-opacity"
                         :class="card.show ? '' : 'opacity-40'">

                        <div class="flex items-center justify-between mb-4">
                            <span class="text-sm font-bold text-rapanel-navy-900 dark:text-white">{{ __('Card') }} {{ i + 1 }}</span>
                            <button type="button" role="switch" :aria-checked="card.show"
                                    @click="card.show = !card.show"
                                    class="relative inline-flex h-6 w-11 flex-shrink-0 items-center rounded-full transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-rapanel-blue focus:ring-offset-2 dark:focus:ring-offset-rapanel-navy-900"
                                    :class="card.show ? 'bg-rapanel-success' : 'bg-rapanel-navy-100 dark:bg-rapanel-navy-700'">
                                <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow-md transition-transform duration-200"
                                      :class="card.show ? 'translate-x-6' : 'translate-x-1'" />
                            </button>
                        </div>

                        <!-- Image upload -->
                        <div class="flex items-start gap-4 mb-4">
                            <div class="w-24 h-16 rounded-lg border border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-rapanel-navy-800 flex items-center justify-center overflow-hidden flex-shrink-0">
                                <img v-if="highlightPreviews[i]" :src="highlightPreviews[i]" class="w-full h-full object-cover" />
                                <PhotoIcon v-else class="w-6 h-6 text-rapanel-text-light dark:text-rapanel-text-dark opacity-30" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <label class="block text-xs font-semibold text-rapanel-navy-900 dark:text-white mb-1">{{ __('Image') }}</label>
                                <input type="file" accept="image/*" @change="onHighlightFile($event, i)"
                                       class="text-xs text-rapanel-text-light dark:text-rapanel-text-dark file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-rapanel-blue/10 file:text-rapanel-blue hover:file:bg-rapanel-blue/20 cursor-pointer" />
                                <p class="mt-0.5 text-xs text-rapanel-text-light dark:text-rapanel-text-dark opacity-40">PNG/JPG, max 2 MB — 600×340 px recomendado</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-3">
                            <div>
                                <label class="block text-xs font-semibold text-rapanel-navy-900 dark:text-white mb-1">{{ __('Title') }}</label>
                                <input v-model="card.title" type="text" maxlength="80"
                                       class="w-full rounded-lg border border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-rapanel-navy-800 px-3 py-1.5 text-sm text-rapanel-navy-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-rapanel-blue" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-rapanel-navy-900 dark:text-white mb-1">{{ __('Button URL') }}</label>
                                <input v-model="card.url" type="text" maxlength="500" placeholder="/wiki  o  https://..."
                                       class="w-full rounded-lg border border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-rapanel-navy-800 px-3 py-1.5 text-sm text-rapanel-navy-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-rapanel-blue" />
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-rapanel-navy-900 dark:text-white mb-1">{{ __('Description') }}</label>
                            <textarea v-model="card.desc" rows="2" maxlength="300"
                                      class="w-full rounded-lg border border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-rapanel-navy-800 px-3 py-1.5 text-sm text-rapanel-navy-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-rapanel-blue resize-none" />
                        </div>
                    </div>
                </div>

                <div class="mt-5 flex justify-end">
                    <button type="button" @click="submitHighlightCards" :disabled="highlightSaving"
                            class="px-5 py-2 bg-rapanel-blue hover:bg-rapanel-blue/90 disabled:opacity-60 text-white text-sm font-bold rounded-lg transition-colors">
                        {{ highlightSaving ? __('Saving...') : __('Save Highlight Cards') }}
                    </button>
                </div>
            </div>

        </div>

        <!-- ========== TAB: SEO ========== -->
        <form v-else-if="activeTab === 'seo'" @submit.prevent="submitSeo" class="space-y-6 max-w-2xl">
            <div class="bg-white dark:bg-rapanel-navy-900 rounded-xl border border-rapanel-navy-100 dark:border-white/10 shadow-sm p-6 space-y-5">
                <h2 class="text-sm font-bold text-rapanel-navy-900 dark:text-white uppercase tracking-wider">{{ __('SEO & Meta') }}</h2>

                <div>
                    <label class="block text-sm font-semibold text-rapanel-navy-900 dark:text-white mb-1.5">{{ __('Page Title') }} *</label>
                    <input v-model="seoForm.seo_title" type="text" required maxlength="200"
                        class="w-full rounded-lg border border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-rapanel-navy-800 px-3 py-2 text-sm text-rapanel-navy-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-rapanel-blue" />
                    <p v-if="seoForm.errors.seo_title" class="mt-1 text-xs text-rapanel-danger">{{ seoForm.errors.seo_title }}</p>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-rapanel-navy-900 dark:text-white mb-1.5">{{ __('Meta Description') }}</label>
                    <textarea v-model="seoForm.seo_description" rows="3" maxlength="500"
                        class="w-full rounded-lg border border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-rapanel-navy-800 px-3 py-2 text-sm text-rapanel-navy-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-rapanel-blue resize-none" />
                    <p v-if="seoForm.errors.seo_description" class="mt-1 text-xs text-rapanel-danger">{{ seoForm.errors.seo_description }}</p>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-rapanel-navy-900 dark:text-white mb-1.5">{{ __('Theme Color') }}</label>
                    <div class="flex items-center gap-3">
                        <input type="color" v-model="seoForm.seo_theme_color"
                            class="h-9 w-14 rounded-lg border border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-rapanel-navy-800 cursor-pointer p-0.5" />
                        <input type="text" v-model="seoForm.seo_theme_color" maxlength="20" placeholder="#3b82f6"
                            class="w-32 rounded-lg border border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-rapanel-navy-800 px-3 py-2 text-sm text-rapanel-navy-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-rapanel-blue" />
                    </div>
                    <p v-if="seoForm.errors.seo_theme_color" class="mt-1 text-xs text-rapanel-danger">{{ seoForm.errors.seo_theme_color }}</p>
                </div>
            </div>

            <!-- Images -->
            <div class="bg-white dark:bg-rapanel-navy-900 rounded-xl border border-rapanel-navy-100 dark:border-white/10 shadow-sm p-6 space-y-5">
                <h2 class="text-sm font-bold text-rapanel-navy-900 dark:text-white uppercase tracking-wider">{{ __('Images') }}</h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <!-- Favicon -->
                    <div>
                        <label class="block text-sm font-semibold text-rapanel-navy-900 dark:text-white mb-1.5">{{ __('Favicon') }}</label>
                        <div class="flex items-center gap-3 mb-2">
                            <div v-if="faviconPreview" class="h-10 w-10 bg-rapanel-navy-50 dark:bg-rapanel-navy-800 rounded-lg flex items-center justify-center border border-rapanel-navy-100 dark:border-white/10">
                                <img :src="faviconPreview" class="h-7 w-7 object-contain" alt="Favicon" />
                            </div>
                            <div v-else class="h-10 w-10 bg-rapanel-navy-50 dark:bg-rapanel-navy-800 rounded-lg flex items-center justify-center border border-rapanel-navy-100 dark:border-white/10">
                                <PhotoIcon class="w-5 h-5 text-rapanel-text-light dark:text-rapanel-text-dark opacity-40" />
                            </div>
                        </div>
                        <input type="file" accept=".png,.jpg,.jpeg,.ico,.svg" @change="onFavicon"
                            class="text-sm text-rapanel-text-light dark:text-rapanel-text-dark file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-rapanel-blue/10 file:text-rapanel-blue hover:file:bg-rapanel-blue/20 cursor-pointer" />
                        <p class="mt-1 text-xs text-rapanel-text-light dark:text-rapanel-text-dark opacity-60">{{ __('PNG/ICO, max 512KB') }}</p>
                        <p v-if="seoForm.errors.favicon" class="mt-1 text-xs text-rapanel-danger">{{ seoForm.errors.favicon }}</p>
                    </div>

                    <!-- OG Image -->
                    <div>
                        <label class="block text-sm font-semibold text-rapanel-navy-900 dark:text-white mb-1.5">{{ __('Social Share Image (OG)') }}</label>
                        <div class="mb-2">
                            <img v-if="ogImagePreview" :src="ogImagePreview" class="h-16 w-full object-cover rounded-lg border border-rapanel-navy-100 dark:border-white/10" alt="OG Image" />
                            <div v-else class="h-16 w-full bg-rapanel-navy-50 dark:bg-rapanel-navy-800 rounded-lg flex items-center justify-center border border-rapanel-navy-100 dark:border-white/10">
                                <PhotoIcon class="w-6 h-6 text-rapanel-text-light dark:text-rapanel-text-dark opacity-40" />
                            </div>
                        </div>
                        <input type="file" accept="image/*" @change="onOgImage"
                            class="text-sm text-rapanel-text-light dark:text-rapanel-text-dark file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-rapanel-blue/10 file:text-rapanel-blue hover:file:bg-rapanel-blue/20 cursor-pointer" />
                        <p class="mt-1 text-xs text-rapanel-text-light dark:text-rapanel-text-dark opacity-60">{{ __('1200×630px recommended, max 2MB') }}</p>
                        <p v-if="seoForm.errors.seo_og_image" class="mt-1 text-xs text-rapanel-danger">{{ seoForm.errors.seo_og_image }}</p>
                    </div>
                </div>
            </div>

            <button type="submit" :disabled="seoForm.processing"
                class="px-6 py-2.5 bg-rapanel-blue hover:bg-rapanel-blue/90 disabled:opacity-60 text-white text-sm font-bold rounded-lg transition-colors">
                {{ seoForm.processing ? __('Saving...') : __('Save SEO Settings') }}
            </button>
        </form>

        <!-- ========== TAB: DANGER ZONE ========== -->
        <div v-else-if="activeTab === 'danger'" class="max-w-2xl space-y-4">
            <div class="flex items-center gap-2 mb-2">
                <ExclamationTriangleIcon class="w-5 h-5 text-rapanel-danger" />
                <p class="text-sm text-rapanel-danger font-semibold">{{ __('These actions are irreversible. Proceed with caution.') }}</p>
            </div>

            <div v-for="action in dangerActions" :key="action.key"
                class="bg-white dark:bg-rapanel-navy-900 rounded-xl border border-rapanel-navy-100 dark:border-white/10 shadow-sm p-5 flex items-center justify-between gap-4">
                <div>
                    <p class="text-sm font-bold text-rapanel-navy-900 dark:text-white">{{ __(action.title) }}</p>
                    <p class="text-xs text-rapanel-text-light dark:text-rapanel-text-dark mt-0.5">{{ __(action.description) }}</p>
                </div>
                <button type="button" @click="confirmDanger(action)"
                    :class="[
                        'shrink-0 px-4 py-2 rounded-lg text-sm font-bold text-white transition-colors',
                        action.color === 'danger' ? 'bg-rapanel-danger hover:bg-rapanel-danger/90' : 'bg-rapanel-gold hover:bg-rapanel-gold/90',
                    ]">
                    {{ __(action.title) }}
                </button>
            </div>
        </div>

        <!-- Danger Confirm Modal -->
        <Teleport to="body">
            <div v-if="dangerConfirm" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm">
                <div class="bg-white dark:bg-rapanel-navy-900 rounded-2xl shadow-2xl p-6 max-w-sm w-full mx-4 border border-rapanel-navy-100 dark:border-white/10">
                    <div class="flex items-center gap-3 mb-3">
                        <ExclamationTriangleIcon class="w-6 h-6 text-rapanel-danger shrink-0" />
                        <h3 class="text-base font-bold text-rapanel-navy-900 dark:text-white">{{ __('Are you sure?') }}</h3>
                    </div>
                    <p class="text-sm text-rapanel-text-light dark:text-rapanel-text-dark mb-5">
                        {{ __(dangerConfirm.description) }} {{ __('This cannot be undone.') }}
                    </p>
                    <div class="flex gap-3 justify-end">
                        <button type="button" @click="dangerConfirm = null"
                            class="px-4 py-2 rounded-lg text-sm font-bold border border-rapanel-navy-100 dark:border-white/10 text-rapanel-navy-900 dark:text-white hover:bg-rapanel-navy-50 dark:hover:bg-rapanel-navy-800 transition-colors">
                            {{ __('Cancel') }}
                        </button>
                        <button type="button" @click="executeDanger" :disabled="dangerForm.processing"
                            class="px-4 py-2 rounded-lg text-sm font-bold bg-rapanel-danger hover:bg-rapanel-danger/90 disabled:opacity-60 text-white transition-colors">
                            {{ dangerForm.processing ? __('Processing...') : __('Confirm') }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AdminLayout>
</template>

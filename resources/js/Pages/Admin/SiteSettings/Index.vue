<script setup>
import { ref, computed, watch } from 'vue';
import { useForm, usePage, router, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
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
    RocketLaunchIcon,
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
// Logos, favicon y color de tema se gestionan en Appearance → Brand (diseño).
const generalForm = useForm({
    site_name:          props.settings.site_name ?? '',
    discord_server_id:  props.settings.discord_server_id ?? '',
    discord_invite_url: props.settings.discord_invite_url ?? '',
});

const submitGeneral = () => {
    generalForm.post(route('admin.settings.general'));
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
    { key: 'general',     label: 'General',         icon: Cog6ToothIcon },
    { key: 'home',        label: 'Home content',     icon: HomeIcon },
    ...(sections.value.home_show_info ? [{ key: 'server-info', label: 'Server Info', icon: InformationCircleIcon }] : []),
    ...(sections.value.home_show_features ? [{ key: 'features', label: 'Panel Features', icon: Squares2X2Icon }] : []),
    ...(sections.value.home_show_cta      ? [{ key: 'cta',      label: 'Call to Action', icon: RocketLaunchIcon }] : []),
    { key: 'seo',         label: 'SEO',              icon: MagnifyingGlassIcon },
    { key: 'danger',      label: 'Danger Zone',      icon: ExclamationTriangleIcon },
]);

watch(sections, (val) => {
    if (activeTab.value === 'server-info' && !val.home_show_info)     activeTab.value = 'home';
    if (activeTab.value === 'features'    && !val.home_show_features) activeTab.value = 'home';
    if (activeTab.value === 'cta'         && !val.home_show_cta)      activeTab.value = 'home';
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
    try {
        const r = props.settings.home_info_blocks
        if (r) {
            const parsed = JSON.parse(r)
            return parsed.map((b, i) => ({ ...b, color: b.color || infoBlocksMeta[i]?.color || '#4A90E2' }))
        }
    } catch {}
    return infoBlocksMeta.map(m => ({ id: m.id, show: true, icon_type: 'icon', image: null, svg_code: null, color: m.color }))
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
        id: 'episode', label: 'Episode', color: '#a855f7',
        svg: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>`,
    },
    {
        id: 'intl', label: 'International', color: '#E74C3C',
        svg: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418"/></svg>`,
    },
]

// Acentos de las tarjetas de la home (info blocks + feature cards): se toman de
// la paleta editable en Appearance → Home → Card palette. Aquí solo se usa para
// la previsualización; el color ya no se edita por tarjeta.
const cardPalette = computed(() => {
    try {
        const p = JSON.parse(props.settings.theme || 'null')?.home?.palette;
        if (Array.isArray(p) && p.length) return p;
    } catch {}
    return ['#4A90E2', '#F1C40F', '#2ECC71', '#a855f7', '#E74C3C', '#e2e8f0'];
});

const infoBlocks   = ref(parseInfoBlocks())
const infoSaving   = ref(false)

// El icono de cada bloque (icon_type/image/svg_code) es DISEÑO y se edita en
// Appearance → Tarjetas. Aquí solo se gestiona visibilidad + valores de texto.

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
    // Solo visibilidad (el icono se edita en Appearance → Tarjetas)
    infoBlocks.value.forEach((b, i) => {
        fd.append(`blocks[${i}][show]`, b.show ? '1' : '0')
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
});

const submitHero = () => {
    heroForm.post(route('admin.settings.home-hero'));
};


// --- SEO form ---
const seoForm = useForm({
    seo_title:                props.settings.seo_title ?? '',
    seo_description:          props.settings.seo_description ?? '',
    google_site_verification: props.settings.google_site_verification ?? '',
    seo_og_image:             null,
});

const ogImagePreview = ref(
    props.settings.seo_og_image ? '/storage/' + props.settings.seo_og_image : null
);

const onOgImage = (e) => {
    const file = e.target.files[0];
    if (!file) return;
    seoForm.seo_og_image = file;
    ogImagePreview.value = URL.createObjectURL(file);
};

const submitSeo = () => {
    seoForm.post(route('admin.settings.seo'), { forceFormData: true });
};

// --- Social networks form ---
const socialNetworkDefs = [
    { id: 'discord',   label: 'Discord',    svg: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M20.317 4.37a19.791 19.791 0 0 0-4.885-1.515.074.074 0 0 0-.079.037c-.21.375-.444.864-.608 1.25a18.27 18.27 0 0 0-5.487 0 12.64 12.64 0 0 0-.617-1.25.077.077 0 0 0-.079-.037A19.736 19.736 0 0 0 3.677 4.37a.07.07 0 0 0-.032.027C.533 9.046-.32 13.58.099 18.057c.002.022.015.043.031.052a19.9 19.9 0 0 0 5.993 3.03.078.078 0 0 0 .084-.028 14.09 14.09 0 0 0 1.226-1.994.076.076 0 0 0-.041-.106 13.107 13.107 0 0 1-1.872-.892.077.077 0 0 1-.008-.128 10.2 10.2 0 0 0 .372-.292.074.074 0 0 1 .077-.01c3.928 1.793 8.18 1.793 12.062 0a.074.074 0 0 1 .078.01c.12.098.246.198.373.292a.077.077 0 0 1-.006.127 12.299 12.299 0 0 1-1.873.892.077.077 0 0 0-.041.107c.36.698.772 1.362 1.225 1.993a.076.076 0 0 0 .084.028 19.839 19.839 0 0 0 6.002-3.03.077.077 0 0 0 .032-.054c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 0 0-.031-.03zM8.02 15.33c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.956-2.419 2.157-2.419 1.21 0 2.176 1.096 2.157 2.42 0 1.333-.956 2.418-2.157 2.418zm7.975 0c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.955-2.419 2.157-2.419 1.21 0 2.176 1.096 2.157 2.42 0 1.333-.946 2.418-2.157 2.418z"/></svg>' },
    { id: 'facebook',  label: 'Facebook',   svg: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>' },
    { id: 'instagram', label: 'Instagram',  svg: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 1 0 0 12.324 6.162 6.162 0 0 0 0-12.324zM12 16a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm6.406-11.845a1.44 1.44 0 1 0 0 2.881 1.44 1.44 0 0 0 0-2.881z"/></svg>' },
    { id: 'twitter',   label: 'Twitter / X',svg: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.744l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>' },
    { id: 'youtube',   label: 'YouTube',    svg: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>' },
    { id: 'tiktok',    label: 'TikTok',     svg: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/></svg>' },
];

const parseSocialNetworks = () => {
    try {
        const raw = props.settings.social_networks;
        if (raw) return JSON.parse(raw);
    } catch {}
    return socialNetworkDefs.map(n => ({ id: n.id, label: n.label, enabled: false, url: '' }));
};

const socialNetworks = ref(parseSocialNetworks());

const submitSocialNetworks = () => {
    const networks = {};
    socialNetworks.value.forEach(n => {
        networks[n.id] = { enabled: n.enabled, url: n.url };
    });
    router.post(route('admin.settings.social-networks'), { networks }, {
        preserveScroll: true,
        onSuccess: () => {},
    });
};

// --- Panel Features ---
const defaultFeatureCards = [
    { title: 'Dashboard',    desc: 'Create and manage game accounts, view characters, reset position — all from your browser.', color: '#4A90E2', icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/></svg>` },
    { title: 'Item DB',      desc: 'Full searchable item database imported from rAthena YAML + itemInfo.lua with drop sources and card slots.', color: '#F1C40F', icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/></svg>` },
    { title: 'Wiki',         desc: 'Docs-style public knowledge base organized in sections and articles with a rich-text editor.', color: '#2ECC71', icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/></svg>` },
    { title: 'MvP Cards',   desc: 'Live MvP card browser with holder counts, item properties and illustrated card portraits.', color: '#a855f7', icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"/></svg>` },
    { title: 'Live Console', desc: 'Real-time stdout/stderr for each server process via WebSocket. Start, stop, restart with one click.', color: '#4A90E2', icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6.75 7.5l3 2.25-3 2.25m4.5 0h3m-9 8.25h13.5A2.25 2.25 0 0021 18V6a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 6v12a2.25 2.25 0 002.25 2.25z"/></svg>` },
    { title: 'Who Sell',     desc: 'Search the live vending market by item name or ID and find sellers in real time.', color: '#F1C40F', icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z"/></svg>` },
];

const parseFeatureCards = () => {
    try {
        const raw = props.settings.home_feature_cards;
        if (raw) {
            const saved = JSON.parse(raw);
            return defaultFeatureCards.map((def, i) => {
                const s = saved[i] ?? {};
                return {
                    ...def,
                    title:     s.title     ?? def.title,
                    desc:      s.desc      ?? def.desc,
                    color:     s.color     ?? def.color,
                    svg_code:  s.svg_code  ?? null,
                    image:     s.image     ?? null,
                    icon_type: s.icon_type ?? 'icon',
                    enabled:   s.enabled   ?? true,
                };
            });
        }
    } catch {}
    return defaultFeatureCards.map(c => ({ ...c, svg_code: null, image: null, icon_type: 'icon', enabled: true }));
};

const featuresTitle    = ref(props.settings.home_features_title    ?? '');
const featuresSubtitle = ref(props.settings.home_features_subtitle ?? '');
const featureCards     = ref(parseFeatureCards());
const featuresSaving   = ref(false);

// El icono de cada tarjeta (icon_type/image/svg_code) es DISEÑO y se edita en
// Appearance → Tarjetas. Aquí solo título, descripción y visibilidad.
const submitFeatures = () => {
    featuresSaving.value = true;
    const fd = new FormData();
    fd.append('features_title',    featuresTitle.value);
    fd.append('features_subtitle', featuresSubtitle.value);
    featureCards.value.forEach((c, i) => {
        fd.append(`cards[${i}][title]`,   c.title   ?? '');
        fd.append(`cards[${i}][desc]`,    c.desc    ?? '');
        fd.append(`cards[${i}][enabled]`, c.enabled ? '1' : '0');
    });
    router.post(route('admin.settings.home-features'), fd, {
        forceFormData: true, preserveScroll: true,
        onFinish: () => { featuresSaving.value = false; },
    });
};

// --- Call to Action ---
const ctaLine1    = ref(props.settings.home_cta_line1    ?? '');
const ctaLine2    = ref(props.settings.home_cta_line2    ?? '');
const ctaSubtitle = ref(props.settings.home_cta_subtitle ?? '');
const ctaBtnText  = ref(props.settings.home_cta_btn_text ?? '');
const ctaBtnUrl   = ref(props.settings.home_cta_btn_url  ?? '');
const ctaSaving   = ref(false);

const submitCta = () => {
    ctaSaving.value = true;
    router.post(route('admin.settings.home-cta'), {
        cta_line1:    ctaLine1.value,
        cta_line2:    ctaLine2.value,
        cta_subtitle: ctaSubtitle.value,
        cta_btn_text: ctaBtnText.value,
        cta_btn_url:  ctaBtnUrl.value,
    }, {
        preserveScroll: true,
        onFinish: () => { ctaSaving.value = false; },
    });
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
        <PageHeader :title="__('Site Settings')" :description="__('Manage your site identity, SEO, social networks and server presentation.')" class="mb-6">
            <div class="flex gap-1 bg-rapanel-navy-50 dark:bg-rapanel-navy-800 rounded-xl p-1 flex-wrap">
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
        </PageHeader>

        <!-- ========== TAB: GENERAL ========== -->
        <form v-if="activeTab === 'general'" @submit.prevent="submitGeneral" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Site Identity: ancho completo -->
            <div class="lg:col-span-2 bg-white dark:bg-rapanel-navy-900 rounded-xl border border-rapanel-navy-100 dark:border-white/10 shadow-sm p-6 space-y-5">
                <h2 class="text-sm font-bold text-rapanel-navy-900 dark:text-white uppercase tracking-wider">{{ __('Site Identity') }}</h2>

                <div>
                    <label class="block text-sm font-semibold text-rapanel-navy-900 dark:text-white mb-1.5">{{ __('Server Name') }} *</label>
                    <input v-model="generalForm.site_name" type="text" required maxlength="100"
                        class="w-full rounded-lg border border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-rapanel-navy-800 px-3 py-2 text-sm text-rapanel-navy-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-rapanel-blue" />
                    <p v-if="generalForm.errors.site_name" class="mt-1 text-xs text-rapanel-danger">{{ generalForm.errors.site_name }}</p>
                </div>

                <!-- Logos, favicon y color de tema → Appearance → Brand (diseño) -->
                <Link :href="route('admin.appearance.index')"
                    class="flex items-center gap-2 text-xs text-rapanel-text-light dark:text-rapanel-text-dark opacity-70 hover:opacity-100 hover:text-rapanel-blue dark:hover:text-rapanel-blue transition">
                    <svg class="w-4 h-4 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.53 16.122a3 3 0 00-5.78 1.128 2.25 2.25 0 01-2.4 2.245 4.5 4.5 0 008.4-2.245c0-.399-.078-.78-.22-1.128zm0 0a15.998 15.998 0 003.388-1.62m-5.043-.025a15.994 15.994 0 011.622-3.395m3.42 3.42a15.995 15.995 0 004.764-4.648l3.876-5.814a1.151 1.151 0 00-1.597-1.597L14.146 6.32a15.996 15.996 0 00-4.649 4.763m3.42 3.42a6.776 6.776 0 00-3.42-3.42"/></svg>
                    <span>{{ __('Logos, favicon and theme color are now set in Appearance → Brand.') }}</span>
                </Link>
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

            <div class="lg:col-span-2">
                <button type="submit" :disabled="generalForm.processing"
                    class="px-6 py-2.5 bg-rapanel-blue hover:bg-rapanel-blue/90 disabled:opacity-60 text-white text-sm font-bold rounded-lg transition-colors">
                    {{ generalForm.processing ? __('Saving...') : __('Save General Settings') }}
                </button>
            </div>
        </form>

        <!-- ========== TAB: HOME ========== -->
        <div v-else-if="activeTab === 'home'" class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">

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
            <form @submit.prevent="submitHero" class="space-y-4">
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

                    <!-- La media de fondo del hero (imagen/video) se gestiona en Appearance -->
                    <div class="border-t border-rapanel-navy-100 dark:border-white/10 pt-4">
                        <Link :href="route('admin.appearance.index')"
                            class="flex items-center gap-2 text-xs text-rapanel-text-light dark:text-rapanel-text-dark opacity-70 hover:opacity-100 hover:text-rapanel-blue dark:hover:text-rapanel-blue transition">
                            <svg class="w-4 h-4 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.53 16.122a3 3 0 00-5.78 1.128 2.25 2.25 0 01-2.4 2.245 4.5 4.5 0 008.4-2.245c0-.399-.078-.78-.22-1.128zm0 0a15.998 15.998 0 003.388-1.62m-5.043-.025a15.994 15.994 0 011.622-3.395m3.42 3.42a15.995 15.995 0 004.764-4.648l3.876-5.814a1.151 1.151 0 00-1.597-1.597L14.146 6.32a15.996 15.996 0 00-4.649 4.763m3.42 3.42a6.776 6.776 0 00-3.42-3.42"/></svg>
                            <span>{{ __('The hero background (image / video) is now set in Appearance → Home.') }}</span>
                        </Link>
                    </div>
                </div>

                <button type="submit" :disabled="heroForm.processing"
                    class="px-6 py-2.5 bg-rapanel-blue hover:bg-rapanel-blue/90 disabled:opacity-60 text-white text-sm font-bold rounded-lg transition-colors">
                    {{ heroForm.processing ? __('Saving...') : __('Save Home Settings') }}
                </button>
            </form>

        </div>

        <!-- ========== TAB: SERVER INFO ========== -->
        <div v-else-if="activeTab === 'server-info'" class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">

            <!-- ══ Info Blocks fusionados ══ -->
            <div class="bg-white dark:bg-rapanel-navy-900 rounded-xl border border-rapanel-navy-100 dark:border-white/10 shadow-sm p-6">
                <div class="flex items-center justify-between mb-1">
                    <h2 class="text-sm font-bold text-rapanel-navy-900 dark:text-white uppercase tracking-wider">{{ __('Server Info') }}</h2>
                    <span v-if="infoSaving" class="text-xs text-rapanel-text-light dark:text-rapanel-text-dark opacity-50 animate-pulse">{{ __('Saving...') }}</span>
                </div>
                <p class="text-xs text-rapanel-text-light dark:text-rapanel-text-dark opacity-60 mb-1">{{ __('Configure each info block: toggle visibility and set the display value.') }}</p>
                <Link :href="route('admin.appearance.index')"
                    class="flex items-center gap-1.5 text-xs text-rapanel-text-light dark:text-rapanel-text-dark opacity-70 hover:opacity-100 hover:text-rapanel-blue transition mb-5">
                    <svg class="w-3.5 h-3.5 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.53 16.122a3 3 0 00-5.78 1.128 2.25 2.25 0 01-2.4 2.245 4.5 4.5 0 008.4-2.245c0-.399-.078-.78-.22-1.128zm0 0a15.998 15.998 0 003.388-1.62m-5.043-.025a15.994 15.994 0 011.622-3.395m3.42 3.42a15.995 15.995 0 004.764-4.648l3.876-5.814a1.151 1.151 0 00-1.597-1.597L14.146 6.32a15.996 15.996 0 00-4.649 4.763m3.42 3.42a6.776 6.776 0 00-3.42-3.42"/></svg>
                    <span>{{ __('Icons and accent colors are set in Appearance → Cards.') }}</span>
                </Link>

                <div class="space-y-3">
                    <div v-for="(block, i) in infoBlocks" :key="block.id"
                         class="rounded-xl border border-rapanel-navy-100 dark:border-white/10 p-4 transition-opacity"
                         :class="block.show ? '' : 'opacity-40'">

                        <!-- Fila superior: nombre del bloque + toggle -->
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full flex-shrink-0"
                                      :style="`background:${cardPalette[i % cardPalette.length]}`"></span>
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

                        <div>
                            <!-- Solo valores de texto: el icono se edita en Appearance → Tarjetas -->
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

        <!-- ========== TAB: PANEL FEATURES ========== -->
        <div v-else-if="activeTab === 'features'" class="space-y-6">

            <!-- Título de sección -->
            <div class="bg-white dark:bg-rapanel-navy-900 rounded-xl border border-rapanel-navy-100 dark:border-white/10 shadow-sm p-6 space-y-4">
                <div class="flex items-center justify-between mb-1">
                    <h2 class="text-sm font-bold text-rapanel-navy-900 dark:text-white uppercase tracking-wider">{{ __('Section Header') }}</h2>
                </div>
                <p class="text-xs text-rapanel-text-light dark:text-rapanel-text-dark opacity-60">{{ __('Leave empty to use the default translated text.') }}</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-rapanel-navy-900 dark:text-white mb-1.5">{{ __('Section Title') }}</label>
                        <input v-model="featuresTitle" type="text" maxlength="200" placeholder="Panel Features"
                            class="w-full rounded-lg border border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-rapanel-navy-800 px-3 py-2 text-sm text-rapanel-navy-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-rapanel-blue" />
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-rapanel-navy-900 dark:text-white mb-1.5">{{ __('Section Subtitle') }}</label>
                        <input v-model="featuresSubtitle" type="text" maxlength="200" placeholder="Everything in one place"
                            class="w-full rounded-lg border border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-rapanel-navy-800 px-3 py-2 text-sm text-rapanel-navy-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-rapanel-blue" />
                    </div>
                </div>
            </div>

            <!-- Feature Cards -->
            <div class="bg-white dark:bg-rapanel-navy-900 rounded-xl border border-rapanel-navy-100 dark:border-white/10 shadow-sm p-6">
                <div class="flex items-center justify-between mb-1">
                    <h2 class="text-sm font-bold text-rapanel-navy-900 dark:text-white uppercase tracking-wider">{{ __('Feature Cards') }}</h2>
                    <span v-if="featuresSaving" class="text-xs text-rapanel-text-light dark:text-rapanel-text-dark opacity-50 animate-pulse">{{ __('Saving...') }}</span>
                </div>
                <p class="text-xs text-rapanel-text-light dark:text-rapanel-text-dark opacity-60 mb-1">{{ __('Toggle visibility and edit the title and description of each feature card.') }}</p>
                <Link :href="route('admin.appearance.index')"
                    class="flex items-center gap-1.5 text-xs text-rapanel-text-light dark:text-rapanel-text-dark opacity-70 hover:opacity-100 hover:text-rapanel-blue transition mb-5">
                    <svg class="w-3.5 h-3.5 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.53 16.122a3 3 0 00-5.78 1.128 2.25 2.25 0 01-2.4 2.245 4.5 4.5 0 008.4-2.245c0-.399-.078-.78-.22-1.128zm0 0a15.998 15.998 0 003.388-1.62m-5.043-.025a15.994 15.994 0 011.622-3.395m3.42 3.42a15.995 15.995 0 004.764-4.648l3.876-5.814a1.151 1.151 0 00-1.597-1.597L14.146 6.32a15.996 15.996 0 00-4.649 4.763m3.42 3.42a6.776 6.776 0 00-3.42-3.42"/></svg>
                    <span>{{ __('Icons and accent colors are set in Appearance → Cards.') }}</span>
                </Link>

                <div class="space-y-4">
                    <div v-for="(card, i) in featureCards" :key="i"
                         class="rounded-xl border border-rapanel-navy-100 dark:border-white/10 p-4 transition-opacity"
                         :class="card.enabled ? '' : 'opacity-40'">

                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-2">
                                <span class="w-3 h-3 rounded-full flex-shrink-0" :style="`background:${cardPalette[i % cardPalette.length]}`"></span>
                                <span class="text-sm font-bold text-rapanel-navy-900 dark:text-white">{{ __('Card') }} {{ i + 1 }}</span>
                            </div>
                            <button type="button" role="switch" :aria-checked="card.enabled"
                                    @click="card.enabled = !card.enabled"
                                    class="relative inline-flex h-6 w-11 flex-shrink-0 items-center rounded-full transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-rapanel-blue focus:ring-offset-2 dark:focus:ring-offset-rapanel-navy-900"
                                    :class="card.enabled ? 'bg-rapanel-success' : 'bg-rapanel-navy-100 dark:bg-rapanel-navy-700'">
                                <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow-md transition-transform duration-200"
                                      :class="card.enabled ? 'translate-x-6' : 'translate-x-1'" />
                            </button>
                        </div>

                        <!-- Solo contenido: el icono se edita en Appearance → Tarjetas -->
                        <div class="space-y-3">
                            <div>
                                <label class="block text-xs font-semibold text-rapanel-navy-900 dark:text-white mb-1">{{ __('Title') }}</label>
                                <input v-model="card.title" type="text" maxlength="80"
                                       class="w-full rounded-lg border border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-rapanel-navy-800 px-3 py-1.5 text-sm text-rapanel-navy-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-rapanel-blue" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-rapanel-navy-900 dark:text-white mb-1">{{ __('Description') }}</label>
                                <textarea v-model="card.desc" rows="2" maxlength="300"
                                          class="w-full rounded-lg border border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-rapanel-navy-800 px-3 py-1.5 text-sm text-rapanel-navy-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-rapanel-blue resize-none" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-5 flex justify-end">
                    <button type="button" @click="submitFeatures" :disabled="featuresSaving"
                            class="px-5 py-2 bg-rapanel-blue hover:bg-rapanel-blue/90 disabled:opacity-60 text-white text-sm font-bold rounded-lg transition-colors">
                        {{ featuresSaving ? __('Saving...') : __('Save Panel Features') }}
                    </button>
                </div>
            </div>
        </div>

        <!-- ========== TAB: CALL TO ACTION ========== -->
        <div v-else-if="activeTab === 'cta'" class="max-w-2xl space-y-6">

            <div class="bg-white dark:bg-rapanel-navy-900 rounded-xl border border-rapanel-navy-100 dark:border-white/10 shadow-sm p-6 space-y-5">
                <div class="flex items-center justify-between mb-1">
                    <h2 class="text-sm font-bold text-rapanel-navy-900 dark:text-white uppercase tracking-wider">{{ __('Call to Action') }}</h2>
                    <span v-if="ctaSaving" class="text-xs text-rapanel-text-light dark:text-rapanel-text-dark opacity-50 animate-pulse">{{ __('Saving...') }}</span>
                </div>
                <p class="text-xs text-rapanel-text-light dark:text-rapanel-text-dark opacity-60">{{ __('Leave any field empty to use the default translated text.') }}</p>

                <!-- Heading Line 1 -->
                <div>
                    <label class="block text-sm font-semibold text-rapanel-navy-900 dark:text-white mb-1.5">{{ __('Heading — Line 1') }}</label>
                    <input v-model="ctaLine1" type="text" maxlength="100" placeholder="Start Your"
                        class="w-full rounded-lg border border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-rapanel-navy-800 px-3 py-2 text-sm text-rapanel-navy-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-rapanel-blue" />
                    <p class="mt-1 text-xs text-rapanel-text-light dark:text-rapanel-text-dark opacity-50">{{ __('Displayed in regular color.') }}</p>
                </div>

                <!-- Heading Line 2 -->
                <div>
                    <label class="block text-sm font-semibold text-rapanel-navy-900 dark:text-white mb-1.5">{{ __('Heading — Line 2') }}</label>
                    <input v-model="ctaLine2" type="text" maxlength="100" placeholder="Adventure"
                        class="w-full rounded-lg border border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-rapanel-navy-800 px-3 py-2 text-sm text-rapanel-navy-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-rapanel-blue" />
                    <p class="mt-1 text-xs text-rapanel-text-light dark:text-rapanel-text-dark opacity-50">{{ __('Displayed with gradient color.') }}</p>
                </div>

                <!-- Subtitle -->
                <div>
                    <label class="block text-sm font-semibold text-rapanel-navy-900 dark:text-white mb-1.5">{{ __('Subtitle') }}</label>
                    <textarea v-model="ctaSubtitle" rows="3" maxlength="300"
                        placeholder="Join our community and experience Ragnarok Online the way it was meant to be played."
                        class="w-full rounded-lg border border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-rapanel-navy-800 px-3 py-2 text-sm text-rapanel-navy-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-rapanel-blue resize-none" />
                </div>

                <!-- Button text + URL -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-rapanel-navy-900 dark:text-white mb-1.5">{{ __('Button Text') }}</label>
                        <input v-model="ctaBtnText" type="text" maxlength="60" placeholder="Register Free"
                            class="w-full rounded-lg border border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-rapanel-navy-800 px-3 py-2 text-sm text-rapanel-navy-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-rapanel-blue" />
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-rapanel-navy-900 dark:text-white mb-1.5">{{ __('Button URL') }}</label>
                        <input v-model="ctaBtnUrl" type="text" maxlength="500" placeholder="/register  o  https://..."
                            class="w-full rounded-lg border border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-rapanel-navy-800 px-3 py-2 text-sm text-rapanel-navy-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-rapanel-blue" />
                        <p class="mt-1 text-xs text-rapanel-text-light dark:text-rapanel-text-dark opacity-50">{{ __('Leave empty to use the register page.') }}</p>
                    </div>
                </div>

                <!-- Preview -->
                <div class="rounded-xl bg-rapanel-navy-50 dark:bg-rapanel-navy-800 border border-rapanel-navy-100 dark:border-white/10 p-6 text-center">
                    <p class="text-xs font-bold text-rapanel-navy-900 dark:text-white uppercase tracking-wider mb-4 opacity-50">{{ __('Preview') }}</p>
                    <p class="text-2xl font-bold text-rapanel-navy-900 dark:text-white leading-tight">{{ ctaLine1 || 'Start Your' }}</p>
                    <p class="text-2xl font-bold text-rapanel-gold leading-tight mb-3">{{ ctaLine2 || 'Adventure' }}</p>
                    <p class="text-sm text-rapanel-text-light dark:text-rapanel-text-dark opacity-70 mb-4 max-w-sm mx-auto">{{ ctaSubtitle || 'Join our community and experience Ragnarok Online the way it was meant to be played.' }}</p>
                    <span class="inline-block px-6 py-2 rounded-xl bg-rapanel-blue text-white text-sm font-bold uppercase tracking-widest">{{ ctaBtnText || 'Register Free' }}</span>
                </div>
            </div>

            <div>
                <button type="button" @click="submitCta" :disabled="ctaSaving"
                        class="px-6 py-2.5 bg-rapanel-blue hover:bg-rapanel-blue/90 disabled:opacity-60 text-white text-sm font-bold rounded-lg transition-colors">
                    {{ ctaSaving ? __('Saving...') : __('Save Call to Action') }}
                </button>
            </div>
        </div>

        <!-- ========== TAB: SEO ========== -->
        <div v-else-if="activeTab === 'seo'" class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">

            <!-- Col 1: SEO form + OG Image -->
            <form @submit.prevent="submitSeo" class="space-y-6">
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
                        <label class="block text-sm font-semibold text-rapanel-navy-900 dark:text-white mb-1.5">{{ __('Google Site Verification') }}</label>
                        <input v-model="seoForm.google_site_verification" type="text" maxlength="200"
                            placeholder="ctg0fajt_rql-0aobauyO_3Elx352cUHCHNLHFM1Zvk"
                            class="w-full rounded-lg border border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-rapanel-navy-800 px-3 py-2 text-sm text-rapanel-navy-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-rapanel-blue font-mono" />
                        <p class="mt-1 text-xs text-rapanel-text-light dark:text-rapanel-text-dark opacity-60">{{ __('Content value from the Google Search Console verification meta tag.') }}</p>
                        <p v-if="seoForm.errors.google_site_verification" class="mt-1 text-xs text-rapanel-danger">{{ seoForm.errors.google_site_verification }}</p>
                    </div>
                </div>

                <!-- OG Image -->
                <div class="bg-white dark:bg-rapanel-navy-900 rounded-xl border border-rapanel-navy-100 dark:border-white/10 shadow-sm p-6 space-y-5">
                    <h2 class="text-sm font-bold text-rapanel-navy-900 dark:text-white uppercase tracking-wider">{{ __('Open Graph Image') }}</h2>

                    <div>
                        <label class="block text-sm font-semibold text-rapanel-navy-900 dark:text-white mb-1.5">{{ __('Social Share Image (OG)') }}</label>
                        <!-- Preview ratio 1200×630 = 52.5% -->
                        <div class="relative w-full mb-2 rounded-lg overflow-hidden border border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-rapanel-navy-800" style="padding-bottom: 52.5%">
                            <img v-if="ogImagePreview" :src="ogImagePreview" class="absolute inset-0 w-full h-full object-cover" alt="OG Image" />
                            <div v-else class="absolute inset-0 flex flex-col items-center justify-center gap-2">
                                <PhotoIcon class="w-8 h-8 text-rapanel-text-light dark:text-rapanel-text-dark opacity-30" />
                                <span class="text-xs text-rapanel-navy-400 dark:text-rapanel-text-dark opacity-50">1200 × 630 px</span>
                            </div>
                        </div>
                        <input type="file" accept="image/*" @change="onOgImage"
                            class="text-sm text-rapanel-text-light dark:text-rapanel-text-dark file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-rapanel-blue/10 file:text-rapanel-blue hover:file:bg-rapanel-blue/20 cursor-pointer" />
                        <p class="mt-1 text-xs text-rapanel-text-light dark:text-rapanel-text-dark opacity-60">{{ __('1200×630px recommended, max 2MB') }}</p>
                        <p v-if="seoForm.errors.seo_og_image" class="mt-1 text-xs text-rapanel-danger">{{ seoForm.errors.seo_og_image }}</p>
                    </div>
                </div>

                <button type="submit" :disabled="seoForm.processing"
                    class="px-6 py-2.5 bg-rapanel-blue hover:bg-rapanel-blue/90 disabled:opacity-60 text-white text-sm font-bold rounded-lg transition-colors">
                    {{ seoForm.processing ? __('Saving...') : __('Save SEO Settings') }}
                </button>
            </form>

            <!-- Col 2: Social Networks -->
            <div class="bg-white dark:bg-rapanel-navy-900 rounded-xl border border-rapanel-navy-100 dark:border-white/10 shadow-sm p-6 space-y-4">
                <h2 class="text-sm font-bold text-rapanel-navy-900 dark:text-white uppercase tracking-wider">{{ __('Social Networks') }}</h2>
                <p class="text-xs text-rapanel-text-light dark:text-rapanel-text-dark opacity-60">{{ __('Enable networks and set your links. Only enabled networks with a URL will appear in the footer.') }}</p>

                <div class="space-y-3">
                    <div v-for="(net, i) in socialNetworks" :key="net.id"
                        class="flex items-center gap-3 p-3 rounded-lg border border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-rapanel-navy-800">

                        <!-- Icon -->
                        <span class="w-6 h-6 flex-shrink-0 text-rapanel-navy-400 dark:text-rapanel-text-dark" v-html="socialNetworkDefs[i].svg"></span>

                        <!-- Label -->
                        <span class="text-sm font-semibold text-rapanel-navy-900 dark:text-white w-24 flex-shrink-0">{{ net.label }}</span>

                        <!-- URL -->
                        <input v-model="net.url" type="url" :placeholder="'https://...'" :disabled="!net.enabled"
                            class="flex-1 rounded-lg border border-rapanel-navy-100 dark:border-white/10 bg-white dark:bg-rapanel-navy-900 px-3 py-1.5 text-sm text-rapanel-navy-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-rapanel-blue disabled:opacity-40 disabled:cursor-not-allowed transition-opacity" />

                        <!-- Toggle -->
                        <button type="button" @click="net.enabled = !net.enabled"
                            :class="net.enabled ? 'bg-rapanel-blue' : 'bg-rapanel-navy-200 dark:bg-rapanel-navy-700'"
                            class="relative inline-flex h-5 w-9 flex-shrink-0 rounded-full transition-colors duration-200 focus:outline-none">
                            <span :class="net.enabled ? 'translate-x-4' : 'translate-x-0.5'"
                                class="inline-block h-4 w-4 mt-0.5 rounded-full bg-white shadow transform transition-transform duration-200"></span>
                        </button>
                    </div>
                </div>

                <button type="button" @click="submitSocialNetworks"
                    class="px-6 py-2.5 bg-rapanel-blue hover:bg-rapanel-blue/90 text-white text-sm font-bold rounded-lg transition-colors">
                    {{ __('Save Social Networks') }}
                </button>
            </div>
        </div>

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
            <div v-if="dangerConfirm" role="dialog" aria-modal="true" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm">
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

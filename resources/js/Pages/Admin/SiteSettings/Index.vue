<script setup>
import { ref } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import {
    Cog6ToothIcon,
    HomeIcon,
    MagnifyingGlassIcon,
    ExclamationTriangleIcon,
    PhotoIcon,
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

const tabs = [
    { key: 'general', label: 'General',    icon: Cog6ToothIcon },
    { key: 'home',    label: 'Home',        icon: HomeIcon },
    { key: 'seo',     label: 'SEO',         icon: MagnifyingGlassIcon },
    { key: 'danger',  label: 'Danger Zone', icon: ExclamationTriangleIcon },
];

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

// --- Home form ---
const homeForm = useForm({
    home_base_rate:      props.settings.home_base_rate ?? '100',
    home_job_rate:       props.settings.home_job_rate ?? '100',
    home_max_base_level: props.settings.home_max_base_level ?? '99',
    home_max_job_level:  props.settings.home_max_job_level ?? '70',
    home_episode:        props.settings.home_episode ?? '',
    home_about_text:     props.settings.home_about_text ?? '',
    home_community_text: props.settings.home_community_text ?? '',
});

const submitHome = () => {
    homeForm.post(route('admin.settings.home'));
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
                <h2 class="text-sm font-bold text-rapanel-navy-900 dark:text-white uppercase tracking-wider">Discord</h2>

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
        <form v-else-if="activeTab === 'home'" @submit.prevent="submitHome" class="space-y-6 max-w-2xl">
            <!-- Server Info -->
            <div class="bg-white dark:bg-rapanel-navy-900 rounded-xl border border-rapanel-navy-100 dark:border-white/10 shadow-sm p-6 space-y-5">
                <h2 class="text-sm font-bold text-rapanel-navy-900 dark:text-white uppercase tracking-wider">{{ __('Server Info') }}</h2>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-rapanel-navy-900 dark:text-white mb-1.5">{{ __('Base Rate') }} *</label>
                        <input v-model="homeForm.home_base_rate" type="text" required maxlength="50" placeholder="100"
                            class="w-full rounded-lg border border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-rapanel-navy-800 px-3 py-2 text-sm text-rapanel-navy-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-rapanel-blue" />
                        <p v-if="homeForm.errors.home_base_rate" class="mt-1 text-xs text-rapanel-danger">{{ homeForm.errors.home_base_rate }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-rapanel-navy-900 dark:text-white mb-1.5">{{ __('Job Rate') }} *</label>
                        <input v-model="homeForm.home_job_rate" type="text" required maxlength="50" placeholder="100"
                            class="w-full rounded-lg border border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-rapanel-navy-800 px-3 py-2 text-sm text-rapanel-navy-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-rapanel-blue" />
                        <p v-if="homeForm.errors.home_job_rate" class="mt-1 text-xs text-rapanel-danger">{{ homeForm.errors.home_job_rate }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-rapanel-navy-900 dark:text-white mb-1.5">{{ __('Max Base Level') }} *</label>
                        <input v-model="homeForm.home_max_base_level" type="text" required maxlength="20" placeholder="99"
                            class="w-full rounded-lg border border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-rapanel-navy-800 px-3 py-2 text-sm text-rapanel-navy-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-rapanel-blue" />
                        <p v-if="homeForm.errors.home_max_base_level" class="mt-1 text-xs text-rapanel-danger">{{ homeForm.errors.home_max_base_level }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-rapanel-navy-900 dark:text-white mb-1.5">{{ __('Max Job Level') }} *</label>
                        <input v-model="homeForm.home_max_job_level" type="text" required maxlength="20" placeholder="70"
                            class="w-full rounded-lg border border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-rapanel-navy-800 px-3 py-2 text-sm text-rapanel-navy-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-rapanel-blue" />
                        <p v-if="homeForm.errors.home_max_job_level" class="mt-1 text-xs text-rapanel-danger">{{ homeForm.errors.home_max_job_level }}</p>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-rapanel-navy-900 dark:text-white mb-1.5">{{ __('Episode') }}</label>
                    <input v-model="homeForm.home_episode" type="text" maxlength="200" placeholder="Episode 13.3+ — El Dicastes"
                        class="w-full rounded-lg border border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-rapanel-navy-800 px-3 py-2 text-sm text-rapanel-navy-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-rapanel-blue" />
                    <p v-if="homeForm.errors.home_episode" class="mt-1 text-xs text-rapanel-danger">{{ homeForm.errors.home_episode }}</p>
                </div>
            </div>

            <!-- About / Community texts -->
            <div class="bg-white dark:bg-rapanel-navy-900 rounded-xl border border-rapanel-navy-100 dark:border-white/10 shadow-sm p-6 space-y-5">
                <h2 class="text-sm font-bold text-rapanel-navy-900 dark:text-white uppercase tracking-wider">{{ __('Page Texts') }}</h2>
                <p class="text-xs text-rapanel-text-light dark:text-rapanel-text-dark opacity-70">{{ __('HTML is supported.') }}</p>

                <div>
                    <label class="block text-sm font-semibold text-rapanel-navy-900 dark:text-white mb-1.5">{{ __('About Us text') }}</label>
                    <textarea v-model="homeForm.home_about_text" rows="6"
                        class="w-full rounded-lg border border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-rapanel-navy-800 px-3 py-2 text-sm text-rapanel-navy-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-rapanel-blue resize-y font-mono" />
                    <p v-if="homeForm.errors.home_about_text" class="mt-1 text-xs text-rapanel-danger">{{ homeForm.errors.home_about_text }}</p>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-rapanel-navy-900 dark:text-white mb-1.5">{{ __('Community text') }}</label>
                    <textarea v-model="homeForm.home_community_text" rows="6"
                        class="w-full rounded-lg border border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-rapanel-navy-800 px-3 py-2 text-sm text-rapanel-navy-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-rapanel-blue resize-y font-mono" />
                    <p v-if="homeForm.errors.home_community_text" class="mt-1 text-xs text-rapanel-danger">{{ homeForm.errors.home_community_text }}</p>
                </div>
            </div>

            <button type="submit" :disabled="homeForm.processing"
                class="px-6 py-2.5 bg-rapanel-blue hover:bg-rapanel-blue/90 disabled:opacity-60 text-white text-sm font-bold rounded-lg transition-colors">
                {{ homeForm.processing ? __('Saving...') : __('Save Home Settings') }}
            </button>
        </form>

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
                        <label class="block text-sm font-semibold text-rapanel-navy-900 dark:text-white mb-1.5">Favicon</label>
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
                        <p class="mt-1 text-xs text-rapanel-text-light dark:text-rapanel-text-dark opacity-60">PNG/ICO, max 512KB</p>
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
                        <p class="mt-1 text-xs text-rapanel-text-light dark:text-rapanel-text-dark opacity-60">1200×630px recomendado, max 2MB</p>
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

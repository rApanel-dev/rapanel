<script setup>
import { Menu, MenuButton, MenuItems, MenuItem, Disclosure, DisclosureButton, DisclosurePanel } from '@headlessui/vue';
import { ChevronDownIcon, Bars3Icon, XMarkIcon } from '@heroicons/vue/24/outline';
import { Link, usePage } from '@inertiajs/vue3';
import LocaleSelector from '@/Components/LocaleSelector.vue';
import ThemeSelector from '@/Components/ThemeSelector.vue';
import ActionButton from '@/Components/ActionButton.vue';
import { visitorMenuItems, authMenuItems } from '@/menu.js';
import { computed, ref } from 'vue';
import { playNowMobile, closeGame, gameOverlayUrl } from '@/Composables/usePlayNow.js';

const page = usePage();

const logoSrc = computed(() => {
    const st = page.props.siteSettings ?? {};
    const isDark = typeof document !== 'undefined' && document.documentElement.classList.contains('dark');
    if (isDark && st.logo_dark)   return '/storage/' + st.logo_dark;
    if (!isDark && st.logo_light) return '/storage/' + st.logo_light;
    if (st.logo_light)            return '/storage/' + st.logo_light;
    return '/images/logo.png';
});

const __ = (key) => {
    return page.props.translations?.[key] || key;
};

const safeRoute = (routeName) => {
    try {
        return route(routeName);
    } catch (e) {
        return '#';
    }
};

const isUrl = (routeName) => {
    try {
        return route().current(routeName) || route().current(routeName + '.*');
    } catch (e) {
        return false;
    }
};

// LÓGICA MÁGICA: Une los menús si hay sesión iniciada
const currentMenuItems = computed(() => {
    return page.props.auth.user
        ? [...visitorMenuItems, ...authMenuItems]
        : visitorMenuItems;
});

const openMobileSubmenus = ref({});
const toggleMobileSubmenu = (name) => {
    openMobileSubmenus.value[name] = !openMobileSubmenus.value[name];
};

// ── Play Now mobile fullscreen overlay ──────────────────────────────────────
function playNow(event) {
    playNowMobile(event, page.props.roBrowserUrl);
}
</script>

<template>

    <!-- ═══ BOTÓN PLAY NOW — cuelga desde el top del navegador ═══ -->
    <a v-if="$page.props.roBrowserUrl"
       :href="$page.props.roBrowserUrl"
       target="_blank" rel="noopener"
       class="ro-top-play-btn"
       @click="playNow">

        <div class="ro-top-play-shimmer" aria-hidden="true"></div>

        <div class="relative z-10 flex items-center gap-2.5">
            <svg class="w-3.5 h-3.5 text-yellow-900 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path d="M6.3 2.841A1.5 1.5 0 004 4.11V15.89a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.84z"/>
            </svg>
            <span class="text-[10px] sm:text-xs font-black uppercase tracking-[0.35em] sm:tracking-[0.45em] text-yellow-900 whitespace-nowrap">
                {{ __('Play Now') }}
            </span>
        </div>
    </a>

    <!-- ═══ OVERLAY FULLSCREEN DEL JUEGO (solo móvil) ═══ -->
    <Teleport to="body">
        <div v-if="gameOverlayUrl" class="ro-game-overlay">
            <iframe
                :src="gameOverlayUrl"
                class="ro-game-iframe"
                allowfullscreen
                webkitallowfullscreen
                mozallowfullscreen
                allow="fullscreen; screen-wake-lock"
            />
            <button class="ro-game-close" @click="closeGame" aria-label="Cerrar juego">✕</button>
        </div>
    </Teleport>

    <div class="ro-nav-wrapper">
    <Disclosure as="nav" class="bg-rapanel-header shadow-sm dark:shadow-2xl transition-colors duration-200" v-slot="{ open }">
        
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-20 border-b border-rapanel-navy-100 dark:border-white/10">
                
                <Link :href="safeRoute('home')" class="shrink-0">
                    <img class="h-9 md:h-12 w-auto max-w-[150px] md:max-w-none object-contain" :src="logoSrc" :alt="page.props.siteSettings?.site_name ?? 'rApanel'" />
                </Link>

                <div class="hidden md:flex items-center gap-4">

                    <ThemeSelector />

                    <div class="flex items-center justify-center p-0">
                        <LocaleSelector />
                    </div>
                    
                    <div class="flex items-center gap-3 border-l border-rapanel-navy-100 dark:border-white/10 pl-4 h-8">
                        <template v-if="!$page.props.auth.user">
                            <Link :href="safeRoute('login')" class="font-display text-rapanel-header-text hover:text-rapanel-header-link text-sm font-medium px-3 py-2 transition">
                                {{ __('Login') }}
                            </Link>
                            <ActionButton :href="safeRoute('register')" variant="blue" fill="solid" size="lg">
                                {{ __('Register') }}
                            </ActionButton>
                        </template>
                    <template v-else>
                        <Menu as="div" class="relative inline-block text-left">
                            <MenuButton class="flex items-center gap-1.5 text-rapanel-header-text hover:text-rapanel-header-link px-3 py-2 rounded-md text-sm font-bold transition">
                                {{ $page.props.auth.user.name }}
                                <ChevronDownIcon class="w-4 h-4 opacity-50" aria-hidden="true" />
                            </MenuButton>

                            <transition enter-active-class="transition duration-100 ease-out" enter-from-class="transform scale-95 opacity-0" enter-to-class="transform scale-100 opacity-100" leave-active-class="transition duration-75 ease-in" leave-from-class="transform scale-100 opacity-100" leave-to-class="transform scale-95 opacity-0">
                                <MenuItems class="absolute right-0 mt-2 w-52 origin-top-right rounded-md bg-white dark:bg-rapanel-navy-800 border border-rapanel-navy-100 dark:border-white/10 shadow-2xl py-1 ring-1 ring-black ring-opacity-5 focus:outline-none z-50">

                                    <!-- Admin Panel link (admins only) -->
                                    <template v-if="$page.props.auth.user?.role === 'Admin'">
                                        <MenuItem v-slot="{ active }">
                                            <Link :href="safeRoute('admin.dashboard')" :class="[active ? 'bg-rapanel-gold/10 dark:bg-rapanel-gold/10' : '', 'flex items-center gap-2 px-4 py-2 text-sm font-bold text-rapanel-gold transition']">
                                                <span class="text-[10px] bg-rapanel-gold text-rapanel-navy-900 px-1.5 py-0.5 rounded font-black uppercase tracking-widest">Admin</span>
                                                {{ __('Panel') }}
                                            </Link>
                                        </MenuItem>
                                        <div class="border-t border-rapanel-navy-100 dark:border-white/10 my-1"></div>
                                    </template>

                                    <MenuItem v-slot="{ active }">
                                        <Link :href="safeRoute('dashboard')" :class="[active ? 'bg-rapanel-navy-50 dark:bg-white/5 text-rapanel-blue dark:text-white' : 'text-rapanel-blue dark:text-rapanel-blue', 'block px-4 py-2 text-sm font-bold transition']">
                                            {{ __('Master Account') }}
                                        </Link>
                                    </MenuItem>

                                    <MenuItem v-slot="{ active }">
                                        <Link :href="safeRoute('profile.edit')" :class="[active ? 'bg-rapanel-navy-50 dark:bg-white/5 text-rapanel-blue dark:text-white' : 'text-rapanel-header-text', 'block px-4 py-2 text-sm transition']">
                                            {{ __('Profile') }}
                                        </Link>
                                    </MenuItem>

                                    <div class="border-t border-rapanel-navy-100 dark:border-white/10 my-1"></div>

                                    <MenuItem v-slot="{ active }">
                                        <Link :href="safeRoute('logout')" method="post" as="button" :class="[active ? 'bg-rapanel-danger/10 dark:bg-white/5' : '', 'w-full text-left block px-4 py-2 text-sm text-rapanel-danger hover:opacity-80 font-bold uppercase tracking-wider transition']">
                                            {{ __('Logout') }}
                                        </Link>
                                    </MenuItem>

                                </MenuItems>
                            </transition>
                        </Menu>
                    </template>
                    </div>
                </div>

                <div class="flex items-center md:hidden gap-1 -me-2">
                    <ThemeSelector />
                    
                    <LocaleSelector />

                    <DisclosureButton class="inline-flex items-center justify-center p-2 rounded-md text-rapanel-header-text hover:text-rapanel-header-link hover:bg-rapanel-navy-50 dark:hover:bg-white/5 focus:outline-none transition">
                        <span class="sr-only">Open main menu</span>
                        <Bars3Icon v-if="!open" class="block h-6 w-6" aria-hidden="true" />
                        <XMarkIcon v-else class="block h-6 w-6" aria-hidden="true" />
                    </DisclosureButton>
                </div>
            </div>
        </div>

        <div class="hidden md:block">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center min-h-[56px]">
                    
                    <div class="flex items-center space-x-1 py-2">
                        <template v-for="item in currentMenuItems" :key="item.name">
                            <Link v-if="!item.children" :href="safeRoute(item.route)"
                                :class="[isUrl(item.route) ? 'text-rapanel-header-link bg-rapanel-navy-50 dark:bg-white/10' : 'text-rapanel-header-text hover:text-rapanel-header-link hover:bg-rapanel-navy-50 dark:hover:bg-white/5', 'px-3 py-2 rounded-md text-sm font-medium transition whitespace-nowrap']">
                                {{ __(item.name) }}
                            </Link>

                            <Menu v-else as="div" class="relative inline-block text-left">
                                <MenuButton :class="['flex items-center gap-1 px-3 py-2 rounded-md text-sm font-medium text-rapanel-header-text hover:text-rapanel-header-link hover:bg-rapanel-navy-50 dark:hover:bg-white/5 transition whitespace-nowrap']">
                                    {{ __(item.name) }}
                                    <ChevronDownIcon class="w-4 h-4 opacity-30" />
                                </MenuButton>
                                <transition enter-active-class="transition duration-100 ease-out" enter-from-class="transform scale-95 opacity-0" enter-to-class="transform scale-100 opacity-100" leave-active-class="transition duration-75 ease-in" leave-from-class="transform scale-100 opacity-100" leave-to-class="transform scale-95 opacity-0">
                                    <MenuItems class="absolute left-0 mt-2 w-56 origin-top-left rounded-md bg-white dark:bg-rapanel-navy-800 shadow-2xl py-1 ring-1 ring-black ring-opacity-5 focus:outline-none z-50 border border-rapanel-navy-100 dark:border-white/10">
                                        <MenuItem v-for="sub in item.children" :key="sub.name" v-slot="{ active }">
                                            <Link :href="safeRoute(sub.route)" :class="[active ? 'bg-rapanel-navy-50 dark:bg-white/5 text-rapanel-blue dark:text-white' : 'text-rapanel-header-text', 'flex items-center gap-2 px-4 py-2 text-sm transition']">
                                                <span v-if="sub.icon" class="shrink-0">{{ sub.icon }}</span>
                                                {{ __(sub.name) }}
                                            </Link>
                                        </MenuItem>
                                    </MenuItems>
                                </transition>
                            </Menu>
                        </template>
                    </div>

                    <div class="flex items-center gap-1.5 ml-4 shrink-0">
                        <!-- Chip: servidor -->
                        <div :class="[
                                'flex items-center gap-1.5 px-2.5 py-1 rounded border text-xs font-semibold transition-colors',
                                $page.props.serverStatus.online
                                    ? 'border-rapanel-success/60 bg-rapanel-success/30 text-green-700 dark:text-rapanel-success'
                                    : 'border-rapanel-danger/60 bg-rapanel-danger/30 text-red-700 dark:text-rapanel-danger',
                             ]">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 14.25h13.5m-13.5 0a3 3 0 0 1-3-3m3 3a3 3 0 1 0 0 6h13.5a3 3 0 1 0 0-6m-16.5-3a3 3 0 0 1 3-3h13.5a3 3 0 0 1 3 3m-19.5 0a4.5 4.5 0 0 1 .9-2.7L5.737 5.1a3.375 3.375 0 0 1 2.7-1.35h7.126c1.062 0 2.062.5 2.7 1.35l2.587 3.45a4.5 4.5 0 0 1 .9 2.7m0 0a3 3 0 0 1-3 3m0 3h.008v.008h-.008v-.008Zm0-6h.008v.008h-.008v-.008Zm-3 6h.008v.008h-.008v-.008Zm0-6h.008v.008h-.008v-.008Z" />
                            </svg>
                            {{ $page.props.serverStatus.online ? __('Online') : __('Offline') }}
                        </div>

                        <!-- Chip: jugadores -->
                        <div class="flex items-center gap-1.5 px-2.5 py-1 rounded border border-rapanel-blue/60 bg-rapanel-blue/30 text-blue-700 dark:text-rapanel-blue text-xs font-semibold">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                            </svg>
                            {{ $page.props.serverStatus.players }}
                        </div>

                        <!-- Chip: tiendas -->
                        <div class="flex items-center gap-1.5 px-2.5 py-1 rounded border border-rapanel-gold/60 bg-rapanel-gold/30 text-amber-700 dark:text-rapanel-gold text-xs font-semibold">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 0 1 .75-.75h3a.75.75 0 0 1 .75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349M3.75 21V9.349m0 0a3.001 3.001 0 0 0 3.75-.615A2.993 2.993 0 0 0 9.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 0 0 2.25 1.016c.896 0 1.7-.393 2.25-1.015a3.001 3.001 0 0 0 3.75.614m-16.5 0a3.004 3.004 0 0 1-.621-4.72l1.189-1.19A1.5 1.5 0 0 1 5.378 3h13.243a1.5 1.5 0 0 1 1.06.44l1.19 1.189a3 3 0 0 1-.621 4.72M6.75 18h3.75a.75.75 0 0 0 .75-.75V13.5a.75.75 0 0 0-.75-.75H6.75a.75.75 0 0 0-.75.75v3.75c0 .414.336.75.75.75Z" />
                            </svg>
                            {{ $page.props.serverStatus.vendings }}
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <transition enter-active-class="transition duration-150 ease-out" enter-from-class="transform scale-95 opacity-0" enter-to-class="transform scale-100 opacity-100" leave-active-class="transition duration-100 ease-in" leave-from-class="transform scale-100 opacity-100" leave-to-class="transform scale-95 opacity-0">
            <DisclosurePanel class="md:hidden bg-rapanel-header border-t border-rapanel-navy-100 dark:border-white/10">
                <div class="px-4 pt-2 pb-6 space-y-1">
                    
                    <template v-for="item in currentMenuItems" :key="item.name">
                        <Link v-if="!item.children" :href="safeRoute(item.route)"
                            class="block px-3 py-3 rounded-md text-base font-medium text-rapanel-header-text hover:text-rapanel-header-link hover:bg-rapanel-navy-50 dark:hover:bg-white/5 transition">
                            {{ __(item.name) }}
                        </Link>

                        <div v-else class="space-y-0.5">
                            <button @click="toggleMobileSubmenu(item.name)"
                                class="w-full flex items-center justify-between px-3 py-3 rounded-md text-base font-medium text-rapanel-header-text hover:text-rapanel-header-link hover:bg-rapanel-navy-50 dark:hover:bg-white/5 transition">
                                {{ __(item.name) }}
                                <ChevronDownIcon class="w-4 h-4 opacity-50 transition-transform duration-200"
                                    :class="{ 'rotate-180': openMobileSubmenus[item.name] }" />
                            </button>
                            <div v-show="openMobileSubmenus[item.name]" class="space-y-0.5 pb-1">
                                <Link v-for="sub in item.children" :key="sub.name" :href="safeRoute(sub.route)"
                                    class="flex items-center gap-2 pl-6 pr-3 py-2 rounded-md text-sm font-medium text-rapanel-header-text hover:text-rapanel-header-link hover:bg-rapanel-navy-50 dark:hover:bg-white/5 transition">
                                    <span v-if="sub.icon" class="shrink-0">{{ sub.icon }}</span>
                                    {{ __(sub.name) }}
                                </Link>
                            </div>
                        </div>
                    </template>

                    <div class="mt-6 pt-4 border-t border-rapanel-navy-100 dark:border-white/10">
                        <div v-if="!$page.props.auth.user" class="grid grid-cols-2 gap-3">
                            <Link :href="safeRoute('login')" class="font-display flex justify-center items-center px-4 py-3 border border-rapanel-navy-100 dark:border-white/10 rounded-md text-sm font-bold text-rapanel-header-text hover:bg-rapanel-navy-50 dark:hover:bg-white/5 transition uppercase tracking-widest">
                                {{ __('Login') }}
                            </Link>
                            <ActionButton :href="safeRoute('register')" variant="blue" fill="solid" size="lg" class="w-full">
                                {{ __('Register') }}
                            </ActionButton>
                        </div>
                        <div v-else class="bg-rapanel-navy-50 dark:bg-white/5 p-4 rounded-lg border border-rapanel-navy-100 dark:border-white/5">
                            <div class="text-[10px] text-rapanel-header-text uppercase font-bold mb-1 tracking-widest">{{ __('Logged in as') }}</div>
                            <div class="text-rapanel-header-text font-bold mb-4">{{ $page.props.auth.user.name }}</div>

                            <div class="flex flex-col gap-2 mb-4">
                                <Link v-if="$page.props.auth.user?.role === 'Admin'"
                                    :href="safeRoute('admin.dashboard')"
                                    class="flex items-center gap-2 px-3 py-2 rounded-md text-sm font-bold text-rapanel-gold bg-rapanel-gold/10 dark:bg-rapanel-gold/10 hover:bg-rapanel-gold/20 transition">
                                    <span class="text-[10px] bg-rapanel-gold text-rapanel-navy-900 px-1.5 py-0.5 rounded font-black uppercase tracking-widest">Admin</span>
                                    Panel
                                </Link>
                                <Link :href="safeRoute('dashboard')" class="block px-3 py-2 rounded-md text-sm font-bold text-rapanel-blue dark:text-rapanel-blue bg-white dark:bg-rapanel-navy-800 hover:bg-rapanel-navy-100 dark:hover:bg-white/10 transition">
                                    {{ __('Master Account') }}
                                </Link>
                                <Link :href="safeRoute('profile.edit')" class="block px-3 py-2 rounded-md text-sm font-medium text-rapanel-header-text bg-white dark:bg-rapanel-navy-800 hover:bg-rapanel-navy-100 dark:hover:bg-white/10 transition">
                                    {{ __('Profile') }}
                                </Link>
                            </div>
                            <Link :href="safeRoute('logout')" method="post" as="button" class="w-full text-center py-2 text-rapanel-danger border border-rapanel-danger/30 rounded hover:bg-rapanel-danger/10 transition uppercase text-[10px] font-bold tracking-widest">
                                {{ __('Logout') }}
                            </Link>
                        </div>
                    </div>

                    <div class="mt-4 pt-4 border-t border-rapanel-navy-100 dark:border-white/10 flex flex-wrap justify-center gap-2 px-2">
                        <!-- Chip: servidor -->
                        <div :class="[
                                'flex items-center gap-1.5 px-2.5 py-1 rounded border text-xs font-semibold',
                                $page.props.serverStatus.online
                                    ? 'border-rapanel-success/60 bg-rapanel-success/30 text-green-700 dark:text-rapanel-success'
                                    : 'border-rapanel-danger/60 bg-rapanel-danger/30 text-red-700 dark:text-rapanel-danger',
                             ]">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 14.25h13.5m-13.5 0a3 3 0 0 1-3-3m3 3a3 3 0 1 0 0 6h13.5a3 3 0 1 0 0-6m-16.5-3a3 3 0 0 1 3-3h13.5a3 3 0 0 1 3 3m-19.5 0a4.5 4.5 0 0 1 .9-2.7L5.737 5.1a3.375 3.375 0 0 1 2.7-1.35h7.126c1.062 0 2.062.5 2.7 1.35l2.587 3.45a4.5 4.5 0 0 1 .9 2.7m0 0a3 3 0 0 1-3 3m0 3h.008v.008h-.008v-.008Zm0-6h.008v.008h-.008v-.008Zm-3 6h.008v.008h-.008v-.008Zm0-6h.008v.008h-.008v-.008Z" />
                            </svg>
                            {{ $page.props.serverStatus.online ? __('Online') : __('Offline') }}
                        </div>
                        <!-- Chip: jugadores -->
                        <div class="flex items-center gap-1.5 px-2.5 py-1 rounded border border-rapanel-blue/60 bg-rapanel-blue/30 text-blue-700 dark:text-rapanel-blue text-xs font-semibold">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                            </svg>
                            {{ $page.props.serverStatus.players }}
                        </div>
                        <!-- Chip: tiendas -->
                        <div class="flex items-center gap-1.5 px-2.5 py-1 rounded border border-rapanel-gold/60 bg-rapanel-gold/30 text-amber-700 dark:text-rapanel-gold text-xs font-semibold">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 0 1 .75-.75h3a.75.75 0 0 1 .75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349M3.75 21V9.349m0 0a3.001 3.001 0 0 0 3.75-.615A2.993 2.993 0 0 0 9.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 0 0 2.25 1.016c.896 0 1.7-.393 2.25-1.015a3.001 3.001 0 0 0 3.75.614m-16.5 0a3.004 3.004 0 0 1-.621-4.72l1.189-1.19A1.5 1.5 0 0 1 5.378 3h13.243a1.5 1.5 0 0 1 1.06.44l1.19 1.189a3 3 0 0 1-.621 4.72M6.75 18h3.75a.75.75 0 0 0 .75-.75V13.5a.75.75 0 0 0-.75-.75H6.75a.75.75 0 0 0-.75.75v3.75c0 .414.336.75.75.75Z" />
                            </svg>
                            {{ $page.props.serverStatus.vendings }}
                        </div>
                    </div>

                </div>
            </DisclosurePanel>
        </transition>
    </Disclosure>
    </div>
</template>

<style scoped>
/* ─── Wrapper del nav: sticky en móvil para que el botón Play Now siempre quede anclado debajo ─── */
.ro-nav-wrapper {
    position: sticky;
    top: 0;
    z-index: 10000;
}
@media (min-width: 768px) {
    .ro-nav-wrapper {
        position: static;
        z-index: auto;
    }
}

/* ─── Botón Play Now fijo en el top del viewport ─── */
.ro-top-play-btn {
    position: fixed;
    top: calc(5rem + var(--woe-banner-h, 0px)); /* mobile: debajo del header + banner WOE */
    left: 50%;
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 7px 24px 15px;
    border-radius: 0 0 20px 20px;
    background: linear-gradient(
        180deg,
        #a16207 0%,
        #ca8a04 15%,
        #eab308 35%,
        #fde047 52%,
        #facc15 75%,
        #ca8a04 100%
    );
    text-decoration: none;
    cursor: pointer;
    overflow: hidden;
    box-shadow:
        0 4px 18px rgba(253, 224, 71, 0.65),
        0 10px 36px rgba(202, 138, 4, 0.3),
        inset 0 1px 0 rgba(255, 255, 255, 0.28);
    /* Empieza oculto arriba; la animación lo trae hacia abajo */
    transform: translateX(-50%) translateY(-100%);
    animation: roTopBtnIn 0.72s 0.2s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
    will-change: transform;
    transition: filter 0.2s ease, box-shadow 0.2s ease;
}

@media (min-width: 768px) {
    .ro-top-play-btn {
        display: flex;
        top: var(--woe-banner-h, 0px); /* desktop: cuelga debajo del banner WOE si está activo */
    }
}

.ro-top-play-btn:hover {
    filter: brightness(1.1) saturate(1.12);
    box-shadow:
        0 6px 28px rgba(253, 224, 71, 0.85),
        0 14px 50px rgba(202, 138, 4, 0.4),
        inset 0 1px 0 rgba(255, 255, 255, 0.35);
}

/* Borde inferior más oscuro — refuerza el efecto de profundidad */
.ro-top-play-btn::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 12px;
    right: 12px;
    height: 2px;
    border-radius: 0 0 2px 2px;
    background: linear-gradient(90deg, transparent, rgba(120, 53, 15, 0.55), transparent);
    pointer-events: none;
}

/* Capa shimmer — barre de izquierda a derecha cada ~4 s */
.ro-top-play-shimmer {
    position: absolute;
    inset: 0;
    background: linear-gradient(
        110deg,
        transparent 20%,
        rgba(255, 255, 255, 0.48) 50%,
        transparent 80%
    );
    transform: translateX(-200%) skewX(-15deg);
    animation: roShimmerSweep 4s ease-in-out infinite;
    animation-delay: 0.9s;
    pointer-events: none;
}

@keyframes roTopBtnIn {
    from { transform: translateX(-50%) translateY(-100%); }
    to   { transform: translateX(-50%) translateY(0);    }
}

@keyframes roShimmerSweep {
    0%   { transform: translateX(-200%) skewX(-15deg); }
    42%  { transform: translateX(350%)  skewX(-15deg); }
    100% { transform: translateX(350%)  skewX(-15deg); }
}

/* ─── Overlay fullscreen del juego (móvil) ─── */
.ro-game-overlay {
    position: fixed;
    inset: 0;
    z-index: 999999;
    background: #000;
}

.ro-game-iframe {
    width: 100%;
    height: 100%;
    border: none;
    display: block;
}

.ro-game-close {
    position: absolute;
    top: 10px;
    right: 10px;
    z-index: 1000000;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    border: none;
    background: rgba(0, 0, 0, 0.6);
    color: #fff;
    font-size: 18px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(4px);
}
</style>

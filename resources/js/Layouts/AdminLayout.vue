<script setup>
import { ref, computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import FlashMessages from '@/Components/FlashMessages.vue';
import {
    HomeIcon,
    UserCircleIcon,
    ComputerDesktopIcon,
    UserGroupIcon,
    ClipboardDocumentListIcon,
    CommandLineIcon,
    ArrowLeftIcon,
    Bars3Icon,
    XMarkIcon,
    NewspaperIcon,
    ArrowDownTrayIcon,
    TagIcon,
    TrophyIcon,
} from '@heroicons/vue/24/outline';
import ThemeSelector from '@/Components/ThemeSelector.vue';
import LocaleSelector from '@/Components/LocaleSelector.vue';

const page = usePage();
const sidebarOpen = ref(false);
const flash = computed(() => page.props.flash ?? {});

const navigation = [
    { name: 'Dashboard',          route: 'admin.dashboard',                icon: HomeIcon,                  group: null },
    { name: 'Master Accounts',    route: 'admin.users.index',              icon: UserCircleIcon,             group: 'Accounts' },
    { name: 'Game Accounts',      route: 'admin.game-accounts.index',      icon: ComputerDesktopIcon,        group: 'Accounts' },
    { name: 'Characters',         route: 'admin.characters.index',         icon: UserGroupIcon,              group: 'Accounts' },
    { name: 'News',               route: 'admin.news.index',               icon: NewspaperIcon,              group: 'Content' },
    { name: 'Downloads',          route: 'admin.downloads.index',          icon: ArrowDownTrayIcon,          group: 'Content' },
    { name: 'Download Categories',route: 'admin.download-categories.index',icon: TagIcon,                    group: 'Content' },
    { name: 'MvP Cards',          route: 'admin.mvp-cards.index',          icon: TrophyIcon,                 group: 'Content' },
    { name: 'Action Logs',        route: 'admin.logs.index',               icon: ClipboardDocumentListIcon,  group: 'System' },
    { name: 'Console',            route: 'admin.console.index',            icon: CommandLineIcon,            group: 'System' },
];

const isActive = (routeName) => {
    try {
        return route().current(routeName) || route().current(routeName + '.*');
    } catch {
        return false;
    }
};

const safeRoute = (name) => {
    try { return route(name); } catch { return '#'; }
};

const userInitial = computed(() =>
    page.props.auth.user?.name?.charAt(0)?.toUpperCase() ?? 'A'
);
</script>

<template>
    <div class="min-h-screen bg-rapanel-navy-50 dark:bg-[#080d14] flex flex-col">

        <!-- Top bar -->
        <header class="bg-rapanel-navy-900 dark:bg-[#0b1120] text-white flex items-center justify-between px-4 h-14 shrink-0 z-30 shadow-[0_4px_28px_rgba(0,0,0,0.45)] relative">
            <!-- Accent line at bottom -->
            <div class="absolute bottom-0 left-0 right-0 h-px bg-gradient-to-r from-transparent via-rapanel-blue/50 to-transparent" />

            <div class="flex items-center gap-3">
                <!-- Mobile hamburger -->
                <button @click="sidebarOpen = !sidebarOpen"
                    class="lg:hidden p-1.5 rounded-lg hover:bg-white/10 transition-colors">
                    <Bars3Icon v-if="!sidebarOpen" class="w-5 h-5" />
                    <XMarkIcon v-else class="w-5 h-5" />
                </button>

                <Link :href="safeRoute('admin.dashboard')" class="flex items-center gap-2.5">
                    <img src="/images/logo.png" class="h-7 w-auto object-contain" alt="rApanel" />
                    <span class="bg-gradient-to-r from-rapanel-gold to-amber-400 text-rapanel-navy-900 text-[10px] font-black uppercase tracking-widest px-2 py-0.5 rounded">
                        Admin
                    </span>
                </Link>
            </div>

            <div class="flex items-center gap-2 sm:gap-3">
                <ThemeSelector />
                <LocaleSelector />

                <div class="hidden sm:flex items-center gap-2.5 border-l border-white/[0.08] pl-3 ml-1">
                    <div class="w-6 h-6 rounded-full bg-rapanel-gold/[0.18] ring-1 ring-rapanel-gold/35 flex items-center justify-center shrink-0">
                        <span class="text-[10px] font-black text-rapanel-gold">{{ userInitial }}</span>
                    </div>
                    <span class="text-sm font-medium text-white/75">{{ page.props.auth.user?.name }}</span>
                </div>

                <Link :href="safeRoute('dashboard')"
                    class="flex items-center gap-1.5 text-xs font-semibold text-white/55 hover:text-white border border-white/[0.12] hover:border-white/30 px-3 py-1.5 rounded-lg transition-all duration-150">
                    <ArrowLeftIcon class="w-3.5 h-3.5" />
                    <span class="hidden sm:inline">Panel</span>
                </Link>
            </div>
        </header>

        <div class="flex flex-1 min-h-0">

            <!-- Sidebar overlay (mobile) -->
            <Transition
                enter-active-class="transition-opacity duration-200"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition-opacity duration-150"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0">
                <div v-if="sidebarOpen"
                    class="fixed inset-0 bg-black/60 backdrop-blur-sm z-20 lg:hidden"
                    @click="sidebarOpen = false" />
            </Transition>

            <!-- Sidebar -->
            <aside :class="[
                'fixed lg:static inset-y-0 left-0 z-20 w-60 bg-rapanel-navy-900 dark:bg-[#0b1120] flex flex-col transition-transform duration-200 ease-in-out border-r border-white/[0.045]',
                sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'
            ]" style="top: 3.5rem;">

                <nav class="flex-1 px-2.5 py-4 overflow-y-auto">

                    <!-- Dashboard (no group) -->
                    <template v-for="item in navigation.filter(i => !i.group)" :key="item.name">
                        <Link :href="safeRoute(item.route)"
                            @click="sidebarOpen = false"
                            :class="[
                                isActive(item.route)
                                    ? 'bg-rapanel-blue/[0.13] text-rapanel-blue font-semibold ring-1 ring-rapanel-blue/[0.22] shadow-[0_2px_12px_rgba(74,144,226,0.08)]'
                                    : 'text-white/45 hover:bg-white/[0.055] hover:text-white/85',
                                'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition-all duration-150 mb-0.5'
                            ]">
                            <div :class="[
                                'flex items-center justify-center w-6 h-6 rounded-md transition-colors duration-150',
                                isActive(item.route) ? 'bg-rapanel-blue/20' : 'bg-transparent'
                            ]">
                                <component :is="item.icon" class="w-4 h-4 shrink-0" />
                            </div>
                            <span class="truncate">{{ item.name }}</span>
                        </Link>
                    </template>

                    <!-- Grouped items -->
                    <template v-for="groupName in ['Accounts', 'Content', 'System']" :key="groupName">
                        <div class="mt-5 mb-1.5 px-3 flex items-center gap-2">
                            <span class="text-[9px] font-black uppercase tracking-[0.18em] text-white/22">{{ groupName }}</span>
                            <div class="flex-1 h-px bg-white/[0.045]"></div>
                        </div>
                        <div class="space-y-0.5">
                            <template v-for="item in navigation.filter(i => i.group === groupName)" :key="item.name">
                                <Link :href="safeRoute(item.route)"
                                    @click="sidebarOpen = false"
                                    :class="[
                                        isActive(item.route)
                                            ? 'bg-rapanel-blue/[0.13] text-rapanel-blue font-semibold ring-1 ring-rapanel-blue/[0.22] shadow-[0_2px_12px_rgba(74,144,226,0.08)]'
                                            : 'text-white/45 hover:bg-white/[0.055] hover:text-white/85',
                                        'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition-all duration-150'
                                    ]">
                                    <div :class="[
                                        'flex items-center justify-center w-6 h-6 rounded-md transition-colors duration-150',
                                        isActive(item.route) ? 'bg-rapanel-blue/20' : 'bg-transparent'
                                    ]">
                                        <component :is="item.icon" class="w-4 h-4 shrink-0" />
                                    </div>
                                    <span class="truncate">{{ item.name }}</span>
                                </Link>
                            </template>
                        </div>
                    </template>
                </nav>

                <!-- User card -->
                <div class="px-2.5 pb-4 pt-3 border-t border-white/[0.045]">
                    <div class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg bg-rapanel-blue/[0.055] ring-1 ring-rapanel-blue/[0.12]">
                        <div class="w-7 h-7 rounded-full bg-rapanel-gold/[0.15] ring-1 ring-rapanel-gold/30 flex items-center justify-center shrink-0">
                            <span class="text-[11px] font-black text-rapanel-gold">{{ userInitial }}</span>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[9px] font-black uppercase tracking-widest text-white/25 leading-none mb-0.5">Administrator</p>
                            <p class="text-xs text-white/75 font-semibold truncate">{{ page.props.auth.user?.name }}</p>
                        </div>
                    </div>
                </div>
            </aside>

            <!-- Main content -->
            <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 min-w-0">
                <div class="mb-5">
                    <FlashMessages :success="flash.success" :error="flash.error" :warning="flash.warning" />
                </div>
                <slot />
            </main>
        </div>
    </div>
</template>

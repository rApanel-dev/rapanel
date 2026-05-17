<script setup>
import { ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import {
    HomeIcon,
    UsersIcon,
    ComputerDesktopIcon,
    ClipboardDocumentListIcon,
    CommandLineIcon,
    ArrowLeftIcon,
    Bars3Icon,
    XMarkIcon,
    ShieldCheckIcon,
} from '@heroicons/vue/24/outline';
import ThemeSelector from '@/Components/ThemeSelector.vue';
import LocaleSelector from '@/Components/LocaleSelector.vue';

const page = usePage();
const sidebarOpen = ref(false);

const navigation = [
    { name: 'Dashboard',     route: 'admin.dashboard',           icon: HomeIcon },
    { name: 'Users',         route: 'admin.users.index',         icon: UsersIcon },
    { name: 'Game Accounts', route: 'admin.game-accounts.index', icon: ComputerDesktopIcon },
    { name: 'Action Logs',   route: 'admin.logs.index',          icon: ClipboardDocumentListIcon },
    { name: 'Console',       route: 'admin.console.index',       icon: CommandLineIcon },
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
</script>

<template>
    <div class="min-h-screen bg-rapanel-navy-50 dark:bg-rapanel-navy-900 flex flex-col">

        <!-- Top bar -->
        <header class="bg-rapanel-navy-900 dark:bg-rapanel-navy-950 text-white flex items-center justify-between px-4 h-14 shrink-0 z-30 shadow-lg border-b border-white/5">
            <div class="flex items-center gap-3">
                <!-- Mobile hamburger -->
                <button @click="sidebarOpen = !sidebarOpen"
                    class="lg:hidden p-1.5 rounded hover:bg-white/10 transition">
                    <Bars3Icon v-if="!sidebarOpen" class="w-5 h-5" />
                    <XMarkIcon v-else class="w-5 h-5" />
                </button>

                <Link :href="safeRoute('admin.dashboard')" class="flex items-center gap-2">
                    <img src="/images/logo.png" class="h-7 w-auto object-contain" alt="rApanel" />
                    <span class="bg-rapanel-gold text-rapanel-navy-900 text-[10px] font-black uppercase tracking-widest px-2 py-0.5 rounded">
                        Admin
                    </span>
                </Link>
            </div>

            <div class="flex items-center gap-2 sm:gap-3">
                <!-- Theme & Locale selectors -->
                <ThemeSelector />
                <LocaleSelector />

                <div class="hidden sm:flex items-center gap-2 border-l border-white/10 pl-3 ml-1">
                    <ShieldCheckIcon class="w-4 h-4 text-rapanel-gold shrink-0" />
                    <span class="text-sm font-medium text-white/80">{{ page.props.auth.user?.name }}</span>
                </div>

                <Link :href="safeRoute('dashboard')"
                    class="flex items-center gap-1.5 text-xs font-semibold text-white/70 hover:text-white border border-white/20 hover:border-white/40 px-3 py-1.5 rounded transition">
                    <ArrowLeftIcon class="w-4 h-4" />
                    <span class="hidden sm:inline">Back to Panel</span>
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
                    class="fixed inset-0 bg-black/50 z-20 lg:hidden"
                    @click="sidebarOpen = false" />
            </Transition>

            <!-- Sidebar -->
            <aside :class="[
                'fixed lg:static inset-y-0 left-0 z-20 w-60 bg-rapanel-navy-900 dark:bg-rapanel-navy-950 flex flex-col transition-transform duration-200 ease-in-out border-r border-white/5',
                sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'
            ]" style="top: 3.5rem;">
                <nav class="flex-1 px-3 py-4 space-y-0.5 overflow-y-auto">
                    <template v-for="item in navigation" :key="item.name">
                        <Link :href="safeRoute(item.route)"
                            @click="sidebarOpen = false"
                            :class="[
                                isActive(item.route)
                                    ? 'bg-rapanel-blue text-white shadow-md'
                                    : 'text-white/50 hover:bg-white/10 hover:text-white',
                                'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition'
                            ]">
                            <component :is="item.icon" class="w-5 h-5 shrink-0" />
                            {{ item.name }}
                        </Link>
                    </template>
                </nav>

                <div class="px-3 pb-4 border-t border-white/10 pt-3">
                    <div class="text-[10px] text-white/30 uppercase tracking-widest font-bold mb-1.5 px-1">
                        Logged in as
                    </div>
                    <div class="flex items-center gap-2 px-3 py-2 rounded-lg bg-white/5 border border-white/5">
                        <ShieldCheckIcon class="w-4 h-4 text-rapanel-gold shrink-0" />
                        <span class="text-sm text-white font-medium truncate">{{ page.props.auth.user?.name }}</span>
                    </div>
                </div>
            </aside>

            <!-- Main content -->
            <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 min-w-0">
                <slot />
            </main>
        </div>
    </div>
</template>

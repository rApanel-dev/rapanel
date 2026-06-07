<script setup>
import { ref, computed, watch } from 'vue';
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import FlashMessages from '@/Components/FlashMessages.vue';
import InactivityWarning from '@/Components/InactivityWarning.vue';
import ShortcutsHelp from '@/Components/ShortcutsHelp.vue';
import { useInactivityTimer } from '@/Composables/useInactivityTimer';
import { useAdminShortcuts } from '@/Composables/useAdminShortcuts.js';
import {
    HomeIcon,
    UserCircleIcon,
    ComputerDesktopIcon,
    UserGroupIcon,
    ClipboardDocumentListIcon,
    CommandLineIcon,
    BeakerIcon,
    ArrowLeftStartOnRectangleIcon,
    Bars3Icon,
    XMarkIcon,
    NewspaperIcon,
    ArrowDownTrayIcon,
    TagIcon,
    TrophyIcon,
    BookOpenIcon,
    CpuChipIcon,
    MapPinIcon,
    Cog6ToothIcon,
    ChartBarIcon,
    DocumentTextIcon,
    Squares2X2Icon,
    ChevronRightIcon,
} from '@heroicons/vue/24/outline';
import ThemeSelector from '@/Components/ThemeSelector.vue';
import LocaleSelector from '@/Components/LocaleSelector.vue';

const page = usePage();
const sidebarOpen      = ref(false);
const isDesktop        = () => window.innerWidth >= 1024;
const sidebarCollapsed = ref(isDesktop() ? localStorage.getItem('admin_sidebar_collapsed') === 'true' : true);
watch(sidebarCollapsed, (v) => { if (isDesktop()) localStorage.setItem('admin_sidebar_collapsed', String(v)); });
const __ = (key, rep = {}) => {
    let t = page.props.translations?.[key] || key;
    Object.entries(rep).forEach(([k, v]) => { t = t.replace(`:${k}`, v); });
    return t;
};
const flash = computed(() => page.props.flash ?? {});

const groups = [
    {
        label: 'Accounts',
        icon: UserGroupIcon,
        items: [
            { name: 'Master Accounts',  route: 'admin.users.index',         icon: UserCircleIcon        },
            { name: 'Game Accounts',    route: 'admin.game-accounts.index', icon: ComputerDesktopIcon   },
            { name: 'Characters',       route: 'admin.characters.index',    icon: UserGroupIcon         },
        ],
    },
    {
        label: 'News',
        icon: NewspaperIcon,
        items: [
            { name: 'News',             route: 'admin.news.index',          icon: NewspaperIcon         },
        ],
    },
    {
        label: 'Wiki',
        icon: BookOpenIcon,
        items: [
            { name: 'Sections',         route: 'admin.wiki.sections.index', icon: Squares2X2Icon        },
            { name: 'Articles',         route: 'admin.wiki.articles.index', icon: DocumentTextIcon      },
        ],
    },
    {
        label: 'Downloads',
        icon: ArrowDownTrayIcon,
        items: [
            { name: 'Files',            route: 'admin.downloads.index',              icon: ArrowDownTrayIcon },
            { name: 'Categories',       route: 'admin.download-categories.index',    icon: TagIcon           },
        ],
    },
    {
        label: 'Emulator Data',
        icon: CpuChipIcon,
        items: [
            { name: 'Item DB',          route: 'admin.item-db.index',       icon: BookOpenIcon          },
            { name: 'Mob DB',           route: 'admin.mob-db.index',        icon: CpuChipIcon           },
            { name: 'Map DB',           route: 'admin.map-db.index',        icon: MapPinIcon            },
            { name: 'Drop Rates',       route: 'admin.drop-rates.index',    icon: ChartBarIcon          },
            { name: 'MvP Cards',        route: 'admin.mvp-cards.index',     icon: TrophyIcon            },
        ],
    },
    {
        label: 'System',
        icon: Cog6ToothIcon,
        items: [
            { name: 'Health Check',     route: 'admin.health.index',        icon: BeakerIcon                },
            { name: 'Action Logs',      route: 'admin.logs.index',          icon: ClipboardDocumentListIcon },
            { name: 'Console',          route: 'admin.console.index',       icon: CommandLineIcon           },
            { name: 'Settings',         route: 'admin.settings.index',      icon: Cog6ToothIcon             },
        ],
    },
];

const isActive = (routeName) => {
    try { return route().current(routeName) || route().current(routeName + '.*'); }
    catch { return false; }
};

const safeRoute = (name) => {
    try { return route(name); } catch { return '#'; }
};

const userInitial = computed(() =>
    page.props.auth.user?.name?.charAt(0)?.toUpperCase() ?? 'A'
);

// Collapsible groups — auto-open the group containing the active route
const openGroups = ref(new Set());

// Watch URL changes (Inertia navigation) instead of watchEffect,
// to avoid re-running when openGroups itself changes (which would prevent manual close)
watch(
    () => page.url,
    () => {
        for (const group of groups) {
            if (group.items.some(item => isActive(item.route)) && !openGroups.value.has(group.label)) {
                const s = new Set(openGroups.value);
                s.add(group.label);
                openGroups.value = s;
            }
        }
    },
    { immediate: true },
);

const toggleGroup = (label) => {
    const s = new Set(openGroups.value);
    s.has(label) ? s.delete(label) : s.add(label);
    openGroups.value = s;
};

const timeout = computed(() => page.props.inactivityTimeout ?? 30);
const { showWarning, countdown, stayActive } = useInactivityTimer(timeout.value, true);
const doLogout = () => router.post(route('logout'));

const { showHelp, pendingKey } = useAdminShortcuts();
</script>

<template>
    <Head title="Admin" />

    <div class="min-h-screen bg-rapanel-navy-50 dark:bg-rapanel-base-dark flex flex-col">

        <!-- ── Mobile top bar (hidden on lg+) ───────────────────────────────── -->
        <header class="lg:hidden fixed top-0 inset-x-0 z-30 h-12 bg-rapanel-navy-900 dark:bg-rapanel-surface-deep border-b border-white/[0.06] flex items-center justify-between px-4 shrink-0">
            <div class="flex items-center gap-3">
                <button @click="sidebarOpen = !sidebarOpen"
                        :aria-label="sidebarOpen ? __('Close navigation') : __('Open navigation')"
                        :aria-expanded="sidebarOpen"
                        aria-controls="admin-sidebar"
                        class="p-1.5 rounded-lg hover:bg-white/10 transition-colors text-rapanel-text-dark">
                    <Bars3Icon v-if="!sidebarOpen" class="w-5 h-5" aria-hidden="true" />
                    <XMarkIcon v-else               class="w-5 h-5" aria-hidden="true" />
                </button>
                <Link :href="safeRoute('admin.dashboard')" class="flex items-center gap-2">
                    <img src="/images/logo.png" class="h-6 w-auto" alt="rApanel" />
                    <span class="bg-rapanel-gold/90 text-rapanel-navy-900 text-[9px] font-black uppercase tracking-widest px-1.5 py-0.5 rounded">Admin</span>
                </Link>
            </div>
            <div class="flex items-center gap-1">
                <ThemeSelector :compact="true" />
                <div class="w-7 h-7 rounded-full bg-rapanel-gold/[0.15] ring-1 ring-rapanel-gold/30 flex items-center justify-center">
                    <span class="text-[10px] font-black text-rapanel-gold">{{ userInitial }}</span>
                </div>
            </div>
        </header>

        <!-- ── Sidebar + Main row ───────────────────────────────────────────── -->
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
                 aria-hidden="true"
                 @click="sidebarOpen = false" />
        </Transition>

        <!-- ── Sidebar ───────────────────────────────────────────────────────── -->
        <aside id="admin-sidebar"
               :class="[
                   'fixed top-12 bottom-0 lg:static lg:top-auto lg:bottom-auto left-0 z-20 shrink-0 flex flex-col overflow-x-clip',
                   'bg-rapanel-navy-900 dark:bg-rapanel-surface-deep border-r border-white/[0.06]',
                   'transition-[width,transform] duration-200 ease-in-out',
                   sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
                   sidebarCollapsed ? 'w-64 lg:w-14' : 'w-64',
               ]">

            <!-- ── Top: cuenta admin ───────────────────────────────────────── -->
            <!-- Expandido -->
            <div v-if="!sidebarCollapsed" class="shrink-0 px-3 py-4 border-b border-white/[0.06]">
                <!-- User card + botón colapsar -->
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-lg bg-rapanel-gold/[0.12] ring-1 ring-rapanel-gold/25 flex items-center justify-center shrink-0">
                        <span class="text-sm font-black text-rapanel-gold">{{ userInitial }}</span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="text-sm font-semibold text-rapanel-text-dark truncate">{{ page.props.auth.user?.name }}</div>
                        <div class="text-[11px] text-rapanel-gold/70 font-medium">{{ __('Administrator') }}</div>
                    </div>
                    <button @click="sidebarCollapsed = true"
                            :aria-label="__('Collapse sidebar')"
                            :title="__('Collapse sidebar')"
                            class="p-1.5 rounded-md text-rapanel-text-dark hover:text-rapanel-text-dark hover:bg-white/[0.07] transition-colors shrink-0">
                        <ChevronRightIcon class="w-4 h-4 rotate-180" aria-hidden="true" />
                    </button>
                </div>
                <div class="flex items-center justify-end gap-2 mt-3">
                    <ThemeSelector :compact="true" />
                    <LocaleSelector :compact="true" />
                    <button @click="showHelp = !showHelp"
                            :aria-label="__('Keyboard Shortcuts')"
                            :aria-expanded="showHelp"
                            class="flex items-center justify-center w-7 h-7 rounded-md border border-white/[0.1] hover:border-white/25 text-rapanel-text-dark hover:text-rapanel-text-dark transition-all text-[11px] font-mono font-bold shrink-0">
                        <span aria-hidden="true">?</span>
                    </button>
                    <button @click="doLogout"
                            :aria-label="__('Logout')"
                            :title="__('Logout')"
                            class="p-1.5 rounded-md text-rapanel-text-dark hover:text-rapanel-danger hover:bg-rapanel-danger/[0.07] transition-colors shrink-0">
                        <ArrowLeftStartOnRectangleIcon class="w-4 h-4" aria-hidden="true" />
                    </button>
                </div>
            </div>
            <!-- Colapsado: logo + avatar + botón expandir -->
            <div v-else class="shrink-0 flex flex-col items-center gap-2 pt-3 pb-2 border-b border-white/[0.06]">
                <Link :href="safeRoute('admin.dashboard')">
                    <img src="/images/logo.png" class="h-5 w-auto object-contain" alt="rApanel" />
                </Link>
                <div class="w-8 h-8 rounded-lg bg-rapanel-gold/[0.12] ring-1 ring-rapanel-gold/25 flex items-center justify-center"
                     :title="page.props.auth.user?.name">
                    <span class="text-xs font-black text-rapanel-gold">{{ userInitial }}</span>
                </div>
                <!-- Botón expandir — visible y claro -->
                <button @click="sidebarCollapsed = false"
                        :aria-label="__('Expand sidebar')"
                        :title="__('Expand sidebar')"
                        class="mt-0.5 p-1.5 rounded-md text-rapanel-text-dark hover:text-rapanel-text-dark hover:bg-white/[0.08] transition-colors">
                    <ChevronRightIcon class="w-4 h-4" aria-hidden="true" />
                </button>
            </div>

            <!-- ── Navegación ──────────────────────────────────────────────── -->
            <nav class="flex-1 overflow-y-auto py-2"
                 :class="sidebarCollapsed ? 'px-1.5' : 'px-2'"
                 :aria-label="__('Admin navigation')">

                <!-- Dashboard -->
                <Link :href="safeRoute('admin.dashboard')"
                      @click="sidebarOpen = false"
                      :title="sidebarCollapsed ? __('Dashboard') : undefined"
                      :aria-current="isActive('admin.dashboard') ? 'page' : undefined"
                      :class="[
                          'flex items-center rounded-md transition-colors duration-150 mb-1',
                          sidebarCollapsed ? 'justify-center p-2.5' : 'gap-2 px-2.5 py-1.5',
                          isActive('admin.dashboard')
                              ? 'bg-rapanel-blue/[0.13] text-rapanel-blue font-semibold'
                              : 'text-rapanel-text-dark hover:text-rapanel-text-dark hover:bg-white/[0.05]',
                      ]">
                    <HomeIcon class="w-4 h-4 shrink-0" aria-hidden="true" />
                    <span v-if="!sidebarCollapsed" class="text-sm font-semibold uppercase tracking-wider">{{ __('Dashboard') }}</span>
                </Link>

                <!-- Expandido: grupos colapsables -->
                <template v-if="!sidebarCollapsed">
                    <div v-for="group in groups" :key="group.label" class="mt-1">
                        <button @click="toggleGroup(group.label)"
                                :aria-expanded="openGroups.has(group.label)"
                                class="flex items-center justify-between w-full px-2.5 py-1.5 rounded-md transition-colors duration-150"
                                :class="group.items.some(i => isActive(i.route))
                                    ? 'text-rapanel-text-dark'
                                    : 'text-rapanel-text-dark hover:text-rapanel-text-dark'">
                            <span class="flex items-center gap-2">
                                <component :is="group.icon" class="w-4 h-4 shrink-0" aria-hidden="true" />
                                <span class="text-sm font-semibold uppercase tracking-wider">{{ __(group.label) }}</span>
                            </span>
                            <ChevronRightIcon :class="[
                                'w-3 h-3 shrink-0 transition-transform duration-200',
                                openGroups.has(group.label) ? 'rotate-90' : '',
                            ]" aria-hidden="true" />
                        </button>
                        <div v-show="openGroups.has(group.label)" class="mt-0.5 space-y-0.5">
                            <Link v-for="item in group.items" :key="item.route"
                                  :href="safeRoute(item.route)"
                                  @click="sidebarOpen = false"
                                  :aria-current="isActive(item.route) ? 'page' : undefined"
                                  :class="[
                                      'flex items-center gap-2.5 pl-6 pr-2.5 py-2 rounded-md text-sm transition-colors duration-150',
                                      isActive(item.route)
                                          ? 'bg-rapanel-blue/[0.13] text-rapanel-blue font-semibold'
                                          : 'text-rapanel-text-dark hover:text-rapanel-text-dark hover:bg-white/[0.05]',
                                  ]">
                                <component :is="item.icon" class="w-4 h-4 shrink-0" aria-hidden="true" />
                                <span class="truncate">{{ __(item.name) }}</span>
                            </Link>
                        </div>
                    </div>
                </template>

                <!-- Colapsado: ícono por ítem hijo con link directo -->
                <template v-else>
                    <div v-for="group in groups" :key="group.label" class="mt-2">
                        <div class="border-t border-white/[0.06] mx-1 mb-1"></div>
                        <Link v-for="item in group.items" :key="item.route"
                              :href="safeRoute(item.route)"
                              @click="sidebarOpen = false"
                              :title="__(item.name)"
                              :aria-label="__(item.name)"
                              :aria-current="isActive(item.route) ? 'page' : undefined"
                              :class="[
                                  'flex items-center justify-center w-full p-2.5 rounded-md transition-colors duration-150',
                                  isActive(item.route)
                                      ? 'bg-rapanel-blue/[0.13] text-rapanel-blue'
                                      : 'text-rapanel-text-dark hover:text-rapanel-text-dark hover:bg-white/[0.05]',
                              ]">
                            <component :is="item.icon" class="w-5 h-5 shrink-0" aria-hidden="true" />
                        </Link>
                    </div>
                </template>

            </nav>

            <!-- ── Controles inferiores ────────────────────────────────────── -->
            <div class="shrink-0 border-t border-white/[0.06] px-2 pt-4 pb-3">

                <!-- Expandido -->
                <template v-if="!sidebarCollapsed">
                    <div class="flex justify-center mb-2">
                        <Link :href="safeRoute('admin.dashboard')" class="flex items-center gap-2">
                            <img src="/images/logo.png" class="h-5 w-auto object-contain" alt="rApanel" />
                            <span class="bg-rapanel-gold/90 text-rapanel-navy-900 text-[9px] font-black uppercase tracking-widest px-1.5 py-0.5 rounded whitespace-nowrap">Admin</span>
                        </Link>
                    </div>
                </template>

                <!-- Colapsado: solo logout centrado -->
                <template v-else>
                    <div class="flex flex-col items-center gap-1">
                        <button @click="doLogout"
                                :aria-label="__('Logout')"
                                :title="__('Logout')"
                                class="p-2 rounded-md text-rapanel-text-dark hover:text-rapanel-danger hover:bg-rapanel-danger/[0.07] transition-colors">
                            <ArrowLeftStartOnRectangleIcon class="w-4 h-4" aria-hidden="true" />
                        </button>
                    </div>
                </template>

            </div>
        </aside>

        <!-- ── Main content ──────────────────────────────────────────────────── -->
        <main class="flex-1 overflow-y-auto pt-12 lg:pt-0 p-4 sm:p-6 lg:p-8 min-w-0">
            <div class="mb-5">
                <FlashMessages :success="flash.success" :error="flash.error" :warning="flash.warning" />
            </div>
            <slot />
        </main>

        </div><!-- end flex row -->
    </div>

    <InactivityWarning
        v-if="showWarning"
        :countdown="countdown"
        :on-stay="stayActive"
        :on-logout="doLogout"
    />

    <!-- Keyboard prefix indicator -->
    <Transition
        enter-active-class="transition ease-out duration-100"
        enter-from-class="opacity-0 translate-y-1"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition ease-in duration-75"
        leave-from-class="opacity-100 translate-y-0"
        leave-to-class="opacity-0 translate-y-1">
        <div v-if="pendingKey"
             role="status"
             aria-live="polite"
             class="fixed bottom-6 right-6 z-50 flex items-center gap-2 px-3 py-2 rounded-lg bg-rapanel-navy-900 dark:bg-rapanel-surface-deep border border-white/[0.12] shadow-[0_8px_32px_rgba(0,0,0,0.5)] text-white text-xs font-mono pointer-events-none">
            <kbd class="inline-flex items-center justify-center min-w-[1.5rem] h-6 px-1.5 rounded bg-rapanel-blue/20 border border-rapanel-blue/40 text-rapanel-blue font-bold">
                {{ pendingKey }}
            </kbd>
            <span class="text-rapanel-text-dark">then&hellip;</span>
        </div>
    </Transition>

    <ShortcutsHelp :show="showHelp" @close="showHelp = false" />
</template>

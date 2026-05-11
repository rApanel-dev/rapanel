<script setup>
import { Menu, MenuButton, MenuItems, MenuItem, Disclosure, DisclosureButton, DisclosurePanel } from '@headlessui/vue';
import { ChevronDownIcon, Bars3Icon, XMarkIcon } from '@heroicons/vue/24/outline';
import { Link, usePage } from '@inertiajs/vue3';
import LocaleSelector from '@/Components/LocaleSelector.vue'; 
import ThemeSelector from '@/Components/ThemeSelector.vue';
import { visitorMenuItems, authMenuItems } from '@/menu.js';
import { computed } from 'vue'; // Importamos computed para hacer la lógica dinámica

const page = usePage();

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
        ? [...visitorMenuItems, ...authMenuItems] // Une el menú público y el privado
        : visitorMenuItems;                       // Solo muestra el público
});
</script>

<template>
    <Disclosure as="nav" class="bg-gray-900 shadow-2xl" v-slot="{ open }">
        
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-20 border-b border-gray-800/60">
                
                <Link :href="safeRoute('home')" class="shrink-0">
                    <img class="h-12 w-auto" src="/images/logo.png" alt="rApanel" />
                </Link>

                <div class="hidden md:flex items-center gap-4">

                    <ThemeSelector />

                    <div class="flex items-center justify-center p-0">
                        <LocaleSelector />
                    </div>
                    
                    <div class="flex items-center gap-3 border-l border-gray-800 pl-4 h-8">
                        <template v-if="!$page.props.auth.user">
                            <Link :href="safeRoute('login')" class="text-gray-400 hover:text-white text-sm font-medium px-3 py-2 transition">
                                {{ __('Login') }}
                            </Link>
                            <Link :href="safeRoute('register')" class="bg-blue-600 hover:bg-blue-500 text-white px-4 py-2 rounded-md text-sm font-medium transition shadow-lg">
                                {{ __('Register') }}
                            </Link>
                        </template>
                    <template v-else>
                        <Menu as="div" class="relative inline-block text-left">
                            <MenuButton class="flex items-center gap-1.5 text-gray-300 hover:text-white px-3 py-2 rounded-md text-sm font-bold transition">
                                {{ $page.props.auth.user.name }}
                                <ChevronDownIcon class="w-4 h-4 opacity-50" aria-hidden="true" />
                            </MenuButton>

                            <transition enter-active-class="transition duration-100 ease-out" enter-from-class="transform scale-95 opacity-0" enter-to-class="transform scale-100 opacity-100" leave-active-class="transition duration-75 ease-in" leave-from-class="transform scale-100 opacity-100" leave-to-class="transform scale-95 opacity-0">
                                <MenuItems class="absolute right-0 mt-2 w-48 origin-top-right rounded-md bg-gray-800 border border-gray-700 shadow-2xl py-1 ring-1 ring-black ring-opacity-5 focus:outline-none z-50">
                                    
                                    <MenuItem v-slot="{ active }">
                                        <Link :href="safeRoute('dashboard')" :class="[active ? 'bg-gray-700 text-white' : 'text-blue-400', 'block px-4 py-2 text-sm font-bold transition']">
                                            {{ __('Master Account') }}
                                        </Link>
                                    </MenuItem>

                                    <MenuItem v-slot="{ active }">
                                        <Link :href="safeRoute('profile.edit')" :class="[active ? 'bg-gray-700 text-white' : 'text-gray-300', 'block px-4 py-2 text-sm transition']">
                                            {{ __('Profile') }}
                                        </Link>
                                    </MenuItem>

                                    <div class="border-t border-gray-700 my-1"></div>

                                    <MenuItem v-slot="{ active }">
                                        <Link :href="safeRoute('logout')" method="post" as="button" :class="[active ? 'bg-gray-700' : '', 'w-full text-left block px-4 py-2 text-sm text-red-400 hover:text-red-300 font-bold uppercase tracking-wider transition']">
                                            {{ __('Logout') }}
                                        </Link>
                                    </MenuItem>
                                    
                                </MenuItems>
                            </transition>
                        </Menu>
                    </template>
                    </div>
                </div>

                <div class="flex items-center md:hidden gap-2">
                    <ThemeSelector />
                    
                    <LocaleSelector />

                    <DisclosureButton class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-white hover:bg-gray-800 focus:outline-none transition">
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
                                :class="[isUrl(item.route) ? 'text-white bg-gray-800' : 'text-gray-400 hover:text-white hover:bg-gray-800', 'px-3 py-2 rounded-md text-sm font-medium transition whitespace-nowrap']">
                                {{ __(item.name) }}
                            </Link>

                            <Menu v-else as="div" class="relative inline-block text-left">
                                <MenuButton :class="['flex items-center gap-1 px-3 py-2 rounded-md text-sm font-medium text-gray-400 hover:text-white hover:bg-gray-800 transition whitespace-nowrap']">
                                    {{ __(item.name) }}
                                    <ChevronDownIcon class="w-4 h-4 opacity-30" />
                                </MenuButton>
                                <transition enter-active-class="transition duration-100 ease-out" enter-from-class="transform scale-95 opacity-0" enter-to-class="transform scale-100 opacity-100" leave-active-class="transition duration-75 ease-in" leave-from-class="transform scale-100 opacity-100" leave-to-class="transform scale-95 opacity-0">
                                    <MenuItems class="absolute left-0 mt-2 w-56 origin-top-left rounded-md bg-gray-800 shadow-2xl py-1 ring-1 ring-black ring-opacity-5 focus:outline-none z-50 border border-gray-700">
                                        <MenuItem v-for="sub in item.children" :key="sub.name" v-slot="{ active }">
                                            <Link :href="safeRoute(sub.route)" :class="[active ? 'bg-gray-700 text-white' : 'text-gray-300', 'block px-4 py-2 text-sm transition']">
                                                {{ __(sub.name) }}
                                            </Link>
                                        </MenuItem>
                                    </MenuItems>
                                </transition>
                            </Menu>
                        </template>
                    </div>

                    <div class="flex items-center gap-6 text-[10px] uppercase tracking-[0.2em] font-bold ml-4 shrink-0">
                        <div class="flex items-center gap-2">
                            <span class="text-gray-600">{{ __('Server Status:') }}</span>
                            
                            <span v-if="$page.props.serverStatus.online" class="flex items-center gap-1.5 text-green-500">
                                <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span>
                                {{ __('Online') }}
                            </span>
                            
                            <span v-else class="flex items-center gap-1.5 text-red-500">
                                <span class="w-1.5 h-1.5 rounded-full bg-red-600"></span>
                                {{ __('Offline') }}
                            </span>
                        </div>

                        <div class="flex items-center gap-2 border-l border-gray-800 pl-6 h-4">
                            <span class="text-gray-600">{{ __('Players Online:') }}</span>
                            <span class="text-blue-400">{{ $page.props.serverStatus.players }}</span>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <transition enter-active-class="transition duration-150 ease-out" enter-from-class="transform scale-95 opacity-0" enter-to-class="transform scale-100 opacity-100" leave-active-class="transition duration-100 ease-in" leave-from-class="transform scale-100 opacity-100" leave-to-class="transform scale-95 opacity-0">
            <DisclosurePanel class="md:hidden bg-gray-900 border-t border-gray-800">
                <div class="px-4 pt-2 pb-6 space-y-1">
                    
                    <template v-for="item in currentMenuItems" :key="item.name">
                        <Link v-if="!item.children" :href="safeRoute(item.route)"
                            class="block px-3 py-3 rounded-md text-base font-medium text-gray-300 hover:text-white hover:bg-gray-800 transition">
                            {{ __(item.name) }}
                        </Link>

                        <div v-else class="space-y-1">
                            <div class="px-3 py-3 text-xs font-bold text-gray-600 uppercase tracking-widest">
                                {{ __(item.name) }}
                            </div>
                            <Link v-for="sub in item.children" :key="sub.name" :href="safeRoute(sub.route)"
                                class="block pl-6 pr-3 py-2 rounded-md text-base font-medium text-gray-400 hover:text-white hover:bg-gray-800 transition">
                                {{ __(sub.name) }}
                            </Link>
                        </div>
                    </template>

                    <div class="mt-6 pt-4 border-t border-gray-800">
                        <div v-if="!$page.props.auth.user" class="grid grid-cols-2 gap-3">
                            <Link :href="safeRoute('login')" class="flex justify-center items-center px-4 py-3 border border-gray-700 rounded-md text-sm font-bold text-white hover:bg-gray-800 transition uppercase tracking-widest">
                                {{ __('Login') }}
                            </Link>
                            <Link :href="safeRoute('register')" class="flex justify-center items-center px-4 py-3 bg-blue-600 rounded-md text-sm font-bold text-white hover:bg-blue-500 transition shadow-lg uppercase tracking-widest">
                                {{ __('Register') }}
                            </Link>
                        </div>
                        <div v-else class="bg-gray-800/50 p-4 rounded-lg border border-gray-800">
                            <div class="text-[10px] text-gray-500 uppercase font-bold mb-1 tracking-widest">{{ __('Logged in as') }}</div>
                            <div class="text-white font-bold mb-4">{{ $page.props.auth.user.name }}</div>

                            <div class="flex flex-col gap-2 mb-4">
                                <Link :href="safeRoute('dashboard')" class="block px-3 py-2 rounded-md text-sm font-bold text-blue-400 bg-gray-800 hover:bg-gray-700 transition">
                                    {{ __('Master Account') }}
                                </Link>
                                <Link :href="safeRoute('profile.edit')" class="block px-3 py-2 rounded-md text-sm font-medium text-gray-300 bg-gray-800 hover:bg-gray-700 transition">
                                    {{ __('Profile') }}
                                </Link>
                            </div>
                            <Link :href="safeRoute('logout')" method="post" as="button" class="w-full text-center py-2 text-red-400 border border-red-900/30 rounded hover:bg-red-900/20 transition uppercase text-[10px] font-bold tracking-widest">
                                {{ __('Logout') }}
                            </Link>
                        </div>
                    </div>

                    <div class="mt-4 pt-4 border-t border-gray-800/50 space-y-3 px-2">
                        <div class="flex items-center justify-between text-[10px] uppercase tracking-widest font-bold">
                            <span class="text-gray-600">{{ __('Server Status:') }}</span>
                            <span v-if="$page.props.serverStatus.online" class="flex items-center gap-1.5 text-green-500">
                                <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span>
                                {{ __('Online') }}
                            </span>
                            <span v-else class="flex items-center gap-1.5 text-red-500">
                                <span class="w-1.5 h-1.5 rounded-full bg-red-600"></span>
                                {{ __('Offline') }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between text-[10px] uppercase tracking-widest font-bold">
                            <span class="text-gray-600">{{ __('Players Online:') }}</span>
                            <span class="text-blue-400">{{ $page.props.serverStatus.players }}</span>
                        </div>
                    </div>

                </div>
            </DisclosurePanel>
        </transition>
    </Disclosure>
</template>

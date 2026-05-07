<script setup>
import { Disclosure, DisclosureButton, DisclosurePanel } from "@headlessui/vue";
import { Bars3Icon, XMarkIcon } from "@heroicons/vue/24/outline";
import { Link, usePage } from "@inertiajs/vue3";
import LocaleSelector from "@/Components/LocaleSelector.vue";
import ApplicationLogo from "@/Components/ApplicationLogo.vue";

const page = usePage();

// Definimos los links de navegación
// Aquí podrías filtrar si el usuario está logueado o no
const __ = (key) => {
    return usePage().props.translations?.[key] || key;
};

const navigation = [
    { name: __('Dashboard'), href: route('dashboard'), auth: true },
    { name: __('Login'), href: route('login'), auth: false },
    { name: __('Register'), href: route('register'), auth: false },
];

const logout = () => {
    router.post(route('logout'));
};
</script>

<template>
    <Disclosure as="nav" class="bg-white dark:bg-gray-900 border-b border-gray-100 dark:border-gray-800" v-slot="{ open }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <div class="shrink-0 flex items-center">
                        <Link :href="'/'">
                            <ApplicationLogo class="block h-9 w-auto fill-current text-gray-800 dark:text-gray-200" />
                        </Link>
                    </div>

                    <div class="hidden space-x-8 sm:-my-px sm:ml-10 sm:flex">
                        <template v-for="item in navigation" :key="item.name">
                            <Link 
                                v-if="item.auth === !!$page.props.auth.user || item.name === 'Dashboard'"
                                :href="item.href"
                                class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium leading-5 transition duration-150 ease-in-out"
                                :class="route().current(item.name.toLowerCase()) ? 'border-indigo-400 text-gray-900 dark:text-gray-100' : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'"
                            >
                                {{ item.name }}
                            </Link>
                        </template>
                    </div>
                </div>

                <div class="hidden sm:flex sm:items-center sm:ml-6 space-x-4">
                    <LocaleSelector />
                    
                    <template v-if="$page.props.auth.user">
                        <span class="text-sm text-gray-500">{{ $page.props.auth.user.name }}</span>
                    </template>
                </div>

                <div class="-mr-2 flex items-center sm:hidden">
                    <DisclosureButton class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none transition">
                        <Bars3Icon v-if="!open" class="block h-6 w-6" />
                        <XMarkIcon v-else class="block h-6 w-6" />
                    </DisclosureButton>
                </div>
            </div>
        </div>

        <DisclosurePanel class="sm:hidden">
            <div class="pt-2 pb-3 space-y-1 text-center">
                <LocaleSelector class="mb-4" />
                </div>
        </DisclosurePanel>
    </Disclosure>
</template>

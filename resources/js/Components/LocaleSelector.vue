<script setup>
import { Menu, MenuButton, MenuItem, MenuItems } from "@headlessui/vue";
import { router, usePage } from "@inertiajs/vue3";
import { computed } from "vue";

// 1. IMPORTANTE: Importamos la librería directamente aquí para que Vite no la ignore
import CountryFlag from 'vue-country-flag-next';

// Obtenemos el idioma actual desde las props globales de Inertia (con un fallback a 'es' por seguridad)
const currentLocale = computed(() => usePage().props.locale || 'en');

const languages = [
    { code: 'en', name: 'English', flag: 'us' },
    { code: 'es', name: 'Español', flag: 'es' },
    { code: 'pt', name: 'Português', flag: 'pt' },
    { code: 'fr', name: 'Français', flag: 'fr' },
];

const changeLocale = (locale) => {
    // Enviamos el cambio al servidor y recargamos solo los datos necesarios
    router.get(route('setlang', locale), {}, {
        preserveScroll: true,
        onSuccess: () => {
            // Guardamos en localStorage como respaldo para la carga inicial
            localStorage.setItem("lang", locale);
        },
    });
};
</script>

<template>
    <Menu as="div" class="relative inline-block text-left">
        <MenuButton class="flex items-center rounded-md p-1 hover:bg-gray-100 dark:hover:bg-gray-800 transition">
            <CountryFlag :country="currentLocale === 'en' ? 'us' : currentLocale" size="normal" class="-mt-1" />
        </MenuButton>

        <transition
            enter-active-class="transition duration-100 ease-out"
            enter-from-class="transform scale-95 opacity-0"
            enter-to-class="transform scale-100 opacity-100"
            leave-active-class="transition duration-75 ease-in"
            leave-from-class="transform scale-100 opacity-100"
            leave-to-class="transform scale-95 opacity-0"
        >
            <MenuItems class="absolute right-0 mt-2 w-40 origin-top-right rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none dark:bg-gray-700 z-50">
                <div class="py-1">
                    <MenuItem v-for="lang in languages" :key="lang.code" v-slot="{ active }">
                        <button
                            @click="changeLocale(lang.code)"
                            :class="[
                                active ? 'bg-gray-100 dark:bg-gray-600 text-gray-900 dark:text-white' : 'text-gray-700 dark:text-gray-300',
                                'group flex w-full items-center px-4 py-2 text-sm transition'
                            ]"
                        >
                            <CountryFlag :country="lang.flag" size="small" class="mr-2 -mt-1" />
                            {{ lang.name }}
                        </button>
                    </MenuItem>
                </div>
            </MenuItems>
        </transition>
    </Menu>
</template>

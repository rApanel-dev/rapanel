<script setup>
import { Menu, MenuButton, MenuItem, MenuItems } from "@headlessui/vue";
import { router, usePage } from "@inertiajs/vue3";
import { computed } from "vue";
import CountryFlag from 'vue-country-flag-next';

defineProps({ compact: { type: Boolean, default: false } });

const currentLocale = computed(() => usePage().props.locale || 'en');

const languages = [
    { code: 'en',    name: 'English',              flag: 'us' },
    { code: 'es',    name: 'Español',              flag: 'es' },
    { code: 'pt',    name: 'Português',            flag: 'pt' },
    { code: 'pt-BR', name: 'Português (BR)',        flag: 'br' },
    { code: 'fr',    name: 'Français',             flag: 'fr' },
    { code: 'de',    name: 'Deutsch',              flag: 'de' },
    { code: 'ru',    name: 'Русский',              flag: 'ru' },
];

const currentFlag = computed(() => languages.find(l => l.code === currentLocale.value)?.flag ?? 'us');

const changeLocale = (locale) => {
    router.get(route('setlang', locale), {}, {
        preserveScroll: true,
        onSuccess: () => localStorage.setItem("lang", locale),
    });
};
</script>

<template>
    <Menu as="div" class="relative inline-block text-left">
        <MenuButton :class="compact
            ? 'flex items-center justify-center w-7 h-7 rounded-md hover:bg-rapanel-navy-100 dark:hover:bg-rapanel-navy-800 transition'
            : 'flex items-center justify-center w-10 h-10 rounded-md hover:bg-rapanel-navy-100 dark:hover:bg-rapanel-navy-800 transition'">
            <div style="width:28px;height:21px;overflow:hidden;border-radius:3px;flex-shrink:0">
                <CountryFlag
                    :country="currentFlag"
                    size="big"
                    :style="{ margin: '0', transform: 'scale(0.538)', transformOrigin: 'top left', display: 'block' }"
                />
            </div>
        </MenuButton>

        <transition
            enter-active-class="transition duration-100 ease-out"
            enter-from-class="transform scale-95 opacity-0"
            enter-to-class="transform scale-100 opacity-100"
            leave-active-class="transition duration-75 ease-in"
            leave-from-class="transform scale-100 opacity-100"
            leave-to-class="transform scale-95 opacity-0"
        >
            <MenuItems class="absolute top-full mt-2 right-0 w-44 origin-top-right rounded-md bg-white dark:bg-rapanel-navy-800 border border-rapanel-navy-100 dark:border-rapanel-navy-900 shadow-2xl ring-1 ring-black ring-opacity-5 focus:outline-none z-50">
                <div class="py-1">
                    <MenuItem v-for="lang in languages" :key="lang.code" v-slot="{ active }">
                        <button
                            @click="changeLocale(lang.code)"
                            :class="[
                                active ? 'bg-rapanel-navy-50 dark:bg-rapanel-navy-900 text-rapanel-text-light dark:text-white' : 'text-rapanel-text-light/70 dark:text-rapanel-text-dark',
                                'group flex w-full items-center px-4 py-2 text-sm transition'
                            ]"
                        >
                            <div style="width:32px;height:24px;overflow:hidden;border-radius:3px;flex-shrink:0;margin-right:12px">
                                <CountryFlag
                                    :country="lang.flag"
                                    size="big"
                                    :style="{ margin: '0', transform: 'scale(0.615)', transformOrigin: 'top left', display: 'block' }"
                                />
                            </div>
                            {{ lang.name }}
                        </button>
                    </MenuItem>
                </div>
            </MenuItems>
        </transition>
    </Menu>
</template>

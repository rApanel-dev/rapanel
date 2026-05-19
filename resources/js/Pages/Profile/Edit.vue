<script setup>
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head, usePage } from '@inertiajs/vue3';
import Header from '@/Components/Header.vue';
import BgMain from '@/Components/BgMain.vue';
import Footer from '@/Components/Footer.vue';

defineProps({
    mustVerifyEmail: { type: Boolean },
    status:          { type: String },
});

const page = usePage();
const __ = (key) => page.props.translations?.[key] || key;
</script>

<template>
    <Head :title="__('Profile')" />

    <div class="min-h-screen text-rapanel-text-light dark:text-rapanel-text-dark font-sans antialiased">
        <BgMain />
        <Header />

        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">

            <div class="mb-6 border-b border-rapanel-navy-100 dark:border-white/10 pb-4">
                <h1 class="text-2xl font-display font-bold text-rapanel-navy-900 dark:text-rapanel-text-dark uppercase tracking-widest">
                    {{ __('Profile') }}
                </h1>
            </div>

            <div class="space-y-6">
                <div class="bg-white dark:bg-rapanel-navy-900 border border-rapanel-navy-100 dark:border-white/10 p-4 shadow-xl sm:rounded-lg sm:p-8">
                    <UpdateProfileInformationForm
                        :must-verify-email="mustVerifyEmail"
                        :status="status"
                        class="max-w-xl"
                    />
                </div>

                <div class="bg-white dark:bg-rapanel-navy-900 border border-rapanel-navy-100 dark:border-white/10 p-4 shadow-xl sm:rounded-lg sm:p-8">
                    <UpdatePasswordForm class="max-w-xl" />
                </div>

                <div class="bg-white dark:bg-rapanel-navy-900 border border-rapanel-navy-100 dark:border-white/10 p-4 shadow-xl sm:rounded-lg sm:p-8">
                    <DeleteUserForm class="max-w-xl" />
                </div>
            </div>
        </main>
    </div>

    <Footer />
</template>

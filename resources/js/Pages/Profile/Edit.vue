<script setup>
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
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

        <main class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-6">

            <!-- ===== CARD: PROFILE INFORMATION ===== -->
            <div class="bg-white dark:bg-rapanel-navy-900 border border-rapanel-navy-100 dark:border-white/10 rounded-xl shadow-xl dark:shadow-black/30 overflow-hidden">

                <!-- Header: back button + page title -->
                <div class="px-6 py-5 border-b border-rapanel-navy-100 dark:border-white/10 bg-white dark:bg-rapanel-navy-900">
                    <div class="flex items-center gap-3">
                        <Link
                            :href="route('dashboard')"
                            class="flex items-center justify-center w-8 h-8 rounded-lg bg-rapanel-navy-100 dark:bg-gray-700 hover:bg-rapanel-blue hover:text-white transition-all shrink-0"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                            </svg>
                        </Link>
                        <div>
                            <p class="text-[10px] uppercase tracking-widest font-bold text-rapanel-text-light/40 dark:text-rapanel-text-dark/40">
                                {{ __('Master Account') }}
                            </p>
                            <h1 class="text-xl font-display font-bold text-rapanel-navy-900 dark:text-rapanel-text-dark">
                                {{ __('Profile') }}
                            </h1>
                        </div>
                    </div>
                </div>

                <!-- Sub-header: section title -->
                <div class="px-6 py-4 border-b border-rapanel-navy-100 dark:border-white/10">
                    <h3 class="text-base font-display font-bold uppercase tracking-widest text-rapanel-navy-900 dark:text-white">
                        {{ __('Profile Information') }}
                    </h3>
                    <p class="text-xs text-rapanel-text-light/50 dark:text-rapanel-text-dark/50 mt-0.5">
                        {{ __("Update your account's profile information and email address.") }}
                    </p>
                </div>

                <div class="p-6">
                    <UpdateProfileInformationForm
                        :must-verify-email="mustVerifyEmail"
                        :status="status"
                    />
                </div>
            </div>

            <!-- ===== CARD: UPDATE PASSWORD ===== -->
            <div class="bg-white dark:bg-rapanel-navy-900 border border-rapanel-navy-100 dark:border-white/10 rounded-xl shadow-xl dark:shadow-black/30 overflow-hidden">
                <div class="px-6 py-4 border-b border-rapanel-navy-100 dark:border-white/10">
                    <h3 class="text-base font-display font-bold uppercase tracking-widest text-rapanel-navy-900 dark:text-white">
                        {{ __('Update Password') }}
                    </h3>
                    <p class="text-xs text-rapanel-text-light/50 dark:text-rapanel-text-dark/50 mt-0.5">
                        {{ __('Ensure your account is using a long, random password to stay secure.') }}
                    </p>
                </div>
                <div class="p-6">
                    <UpdatePasswordForm />
                </div>
            </div>

            <!-- ===== CARD: DELETE ACCOUNT ===== -->
            <div class="bg-white dark:bg-rapanel-navy-900 border border-rapanel-danger/30 dark:border-rapanel-danger/20 rounded-xl shadow-xl dark:shadow-black/30 overflow-hidden">
                <div class="px-6 py-4 border-b border-rapanel-danger/20 bg-rapanel-danger/5 dark:bg-rapanel-danger/10">
                    <h3 class="text-base font-display font-bold uppercase tracking-widest text-rapanel-danger">
                        {{ __('Delete Account') }}
                    </h3>
                    <p class="text-xs text-rapanel-text-light/50 dark:text-rapanel-text-dark/50 mt-0.5">
                        {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.') }}
                    </p>
                </div>
                <div class="p-6">
                    <DeleteUserForm />
                </div>
            </div>

        </main>
    </div>

    <Footer />
</template>

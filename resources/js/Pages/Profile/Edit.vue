<script setup>
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head, usePage } from '@inertiajs/vue3';
import Header from '@/Components/Header.vue';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const page = usePage();

// Helper de traducción
const __ = (key) => {
    return page.props.translations?.[key] || key;
};
</script>

<template>
    <Head :title="__('Profile')" />

    <div class="min-h-screen bg-black text-gray-100 font-sans antialiased">
        
        <Header />

        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            
            <div class="mb-6 border-b border-gray-800 pb-4">
                <h1 class="text-2xl font-bold text-white uppercase tracking-widest">
                    {{ __('Profile') }}
                </h1>
            </div>

            <div class="space-y-6">
                
                <div class="bg-gray-900 border border-gray-800 p-4 shadow-2xl sm:rounded-lg sm:p-8">
                    <UpdateProfileInformationForm
                        :must-verify-email="mustVerifyEmail"
                        :status="status"
                        class="max-w-xl"
                    />
                </div>

                <div class="bg-gray-900 border border-gray-800 p-4 shadow-2xl sm:rounded-lg sm:p-8">
                    <UpdatePasswordForm class="max-w-xl" />
                </div>

                <div class="bg-gray-900 border border-gray-800 p-4 shadow-2xl sm:rounded-lg sm:p-8">
                    <DeleteUserForm class="max-w-xl" />
                </div>
                
            </div>
        </main>
    </div>
</template>

<script setup>
import { Head, usePage } from '@inertiajs/vue3';
import Header from '@/Components/Header.vue';

const page = usePage();

// Helper de traducción
const __ = (key) => {
    return page.props.translations?.[key] || key;
};
</script>

<template>
    <Head :title="__('Master Account')" />

    <div class="min-h-screen bg-black text-gray-100 font-sans antialiased">
        
        <Header />

        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            
            <div class="bg-gray-900 border border-gray-800 rounded-lg p-6 md:p-10 shadow-2xl">
                
                <div class="border-b border-gray-800 pb-4 mb-6">
                    <h1 class="text-2xl font-bold text-white uppercase tracking-widest">
                        {{ __('Master Account') }}
                    </h1>
                    <p class="text-gray-400 text-sm mt-1">
                        {{ __('Welcome to your central control panel,') }} <span class="text-blue-400 font-bold">{{ page.props.auth.user.name }}</span>.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-gray-800/50 border border-gray-700/50 rounded p-4">
                        <div class="text-xs text-gray-500 uppercase tracking-widest font-bold mb-2">{{ __('Game Accounts') }}</div>
                        <div class="text-3xl font-bold text-white">0</div>
                    </div>
                    
                    <div class="bg-gray-800/50 border border-gray-700/50 rounded p-4">
                        <div class="text-xs text-gray-500 uppercase tracking-widest font-bold mb-2">{{ __('Vote Points') }}</div>
                        <div class="text-3xl font-bold text-yellow-500">0</div>
                    </div>
                    
                    <div class="bg-gray-800/50 border border-gray-700/50 rounded p-4">
                        <div class="text-xs text-gray-500 uppercase tracking-widest font-bold mb-2">{{ __('Cash Points') }}</div>
                        <div class="text-3xl font-bold text-green-500">0</div>
                    </div>
                </div>
                
            </div>
            
        </main>
    </div>
</template>

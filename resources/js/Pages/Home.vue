<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import Footer from '@/Components/Footer.vue';

const page = usePage();
const __ = (key) => page.props.translations?.[key] || key;
const safeRoute = (routeName, params = {}) => {
    try { return route(routeName, params); } catch { return '#'; }
};

const scrollTo = (id) => {
    document.getElementById(id)?.scrollIntoView({ behavior: 'smooth' });
};
</script>

<template>
    <Head :title="__('Home')" />

    <MainLayout>

        <!-- ══════════════════════ HERO ══════════════════════ -->
        <!-- header: 80px (top) + 56px (nav) = 136px = 8.5rem desktop / 5rem mobile -->
        <section class="relative overflow-hidden h-[calc(100vh-5rem)] md:h-[calc(100vh-8.5rem)]">

            <video autoplay loop muted playsinline
                   class="absolute inset-0 w-full h-full object-cover dark:filter dark:brightness-50">
                <source src="/images/ez.mp4" type="video/mp4" />
            </video>

            <!-- flex-col: content centered, button pinned to bottom -->
            <div class="relative z-10 h-full flex flex-col">

                <div class="flex-1 flex items-center justify-center px-6">
                    <div class="flex flex-col items-center text-center max-w-2xl">

                        <img src="/images/logo.png"
                             class="w-48 md:w-72 mb-6 drop-shadow-lg"
                             alt="Server title" />

                        <h1 class="text-4xl sm:text-5xl font-bold tracking-tight text-gray-900 dark:text-white drop-shadow">
                            {{ __('Welcome to') }}
                            <span class="text-rapanel-blue">rApanel</span>
                        </h1>

                        <p class="mt-6 text-xl leading-8 text-gray-800 dark:text-white drop-shadow">
                            {{ __('A classic Ragnarok Online experience, reimagined for a new era.') }}
                        </p>

                        <div class="mt-10 flex flex-wrap items-center justify-center gap-x-6 gap-y-3">
                            <Link :href="safeRoute('register')"
                                  class="rounded-md bg-rapanel-blue px-5 py-3 text-lg font-semibold text-white shadow-sm hover:opacity-90 transition duration-150">
                                {{ __('Register Now') }}
                            </Link>
                            <button @click="scrollTo('about')"
                                    class="text-lg font-semibold leading-6 text-gray-900 dark:text-white hover:text-rapanel-blue dark:hover:text-rapanel-blue transition">
                                {{ __('Server Info') }} →
                            </button>
                        </div>

                    </div>
                </div>

                <!-- Next section button — inside flex column, always visible -->
                <div class="flex justify-center pb-8 shrink-0">
                    <button @click="scrollTo('about')"
                            class="next-btn w-12 h-12 rounded-full flex items-center justify-center
                                   bg-white/20 hover:bg-white/35 border border-white/40
                                   backdrop-blur-sm transition duration-200"
                            :aria-label="__('Next section')">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                </div>

            </div>
        </section>

        <!-- ══════════════════════ STAY UPDATE ══════════════════════ -->
        <section id="about" class="min-h-screen relative overflow-hidden scroll-mt-[5rem] md:scroll-mt-[8.5rem]">

            <video autoplay muted loop playsinline
                   class="absolute inset-0 w-full h-full object-cover dark:filter dark:brightness-50">
                <source src="/images/about.mp4" type="video/mp4" />
            </video>

            <div class="relative z-10 container mx-auto py-20 px-6">

                <h1 class="text-4xl lg:text-5xl font-bold tracking-tight text-white drop-shadow text-center mb-16">
                    {{ __('Stay Update') }}
                </h1>

                <!-- 3-column grid — todas las columnas con h-[520px] para altura uniforme -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 lg:items-stretch">

                    <!-- ── Column 1: Discord Widget ── -->
                    <div class="rounded-2xl overflow-hidden border border-white/20 shadow-xl h-[520px]">
                        <iframe v-if="$page.props.discordServerId"
                                :src="`https://discord.com/widget?id=${$page.props.discordServerId}&theme=dark`"
                                width="100%" height="520"
                                allowtransparency="true"
                                frameborder="0"
                                sandbox="allow-popups allow-popups-to-escape-sandbox allow-same-origin allow-scripts"
                                class="block w-full h-full" />
                        <div v-else class="bg-[#36393f] w-full h-full flex flex-col items-center justify-center gap-4 p-8">
                            <img src="/images/discord.png" class="h-12 w-auto opacity-40" alt="Discord" />
                            <p class="text-white/40 text-sm text-center">
                                {{ __('Set DISCORD_SERVER_ID in .env to show the widget.') }}
                            </p>
                        </div>
                    </div>

                    <!-- ── Column 2: Latest News ── -->
                    <div class="bg-white/10 dark:bg-black/30 backdrop-blur-sm rounded-2xl overflow-hidden border border-white/20 shadow-xl flex flex-col h-[520px]">
                        <div class="px-6 pt-5 pb-3 shrink-0">
                            <h2 class="text-xl font-bold text-white uppercase">{{ __('Latest News') }}</h2>
                        </div>

                        <div v-if="$page.props.latestNews?.length" class="flex flex-col divide-y divide-white/10 overflow-y-auto">
                            <Link v-for="item in $page.props.latestNews" :key="item.id"
                                  :href="safeRoute('news.show', item.slug)"
                                  class="group flex items-center gap-3 px-5 py-4 transition-colors hover:bg-white/10 shrink-0">

                                <!-- Thumbnail -->
                                <div class="relative shrink-0 w-16 h-12 rounded-lg overflow-hidden border border-white/15 bg-white/10">
                                    <img v-if="item.featured_image"
                                         :src="item.featured_image" :alt="item.title"
                                         class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105" />
                                    <div v-else class="w-full h-full flex items-center justify-center text-white/20 text-lg">📰</div>
                                    <!-- Pin indicator -->
                                    <div v-if="item.is_pinned"
                                         class="absolute top-0.5 right-0.5 w-4 h-4 rounded-full bg-rapanel-danger flex items-center justify-center shadow-sm"
                                         title="Pinned">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="white" class="w-2.5 h-2.5">
                                            <path d="M9.828.722a.5.5 0 0 1 .354.146l4.95 4.95a.5.5 0 0 1 0 .707c-.48.48-1.072.588-1.503.588-.177 0-.335-.018-.46-.039l-3.134 3.134a5.927 5.927 0 0 1 .16 1.013c.046.702-.032 1.687-.72 2.375a.5.5 0 0 1-.707 0l-2.829-2.828-3.182 3.182a.5.5 0 0 1-.707-.707l3.182-3.182-2.828-2.829a.5.5 0 0 1 0-.707c.688-.688 1.673-.767 2.375-.72a5.927 5.927 0 0 1 1.013.16l3.134-3.133a2.773 2.773 0 0 1-.04-.461c0-.43.108-1.022.589-1.503a.5.5 0 0 1 .353-.146z"/>
                                        </svg>
                                    </div>
                                </div>

                                <!-- Content -->
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-semibold text-white leading-snug line-clamp-2
                                               group-hover:text-rapanel-blue transition-colors duration-150">
                                        {{ item.title }}
                                    </p>
                                    <div class="mt-1.5 flex items-center gap-2 flex-wrap">
                                        <span class="text-white/40 text-xs">{{ item.created_at }}</span>
                                        <span class="w-1 h-1 rounded-full bg-white/30 shrink-0" />
                                        <span :class="[
                                            'text-[10px] font-bold uppercase px-1.5 py-0.5 rounded border leading-none',
                                            item.type === 1 ? 'bg-rapanel-blue/30 text-rapanel-blue border-rapanel-blue/40' :
                                            item.type === 2 ? 'bg-rapanel-gold/30 text-rapanel-gold border-rapanel-gold/40' :
                                            'bg-white/10 text-white/50 border-white/20'
                                        ]">{{ item.type_label }}</span>
                                    </div>
                                </div>

                                <!-- Arrow centrada verticalmente -->
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor"
                                     class="w-3.5 h-3.5 shrink-0 text-white/40 opacity-0 group-hover:opacity-100 transition-opacity self-center">
                                    <path fill-rule="evenodd"
                                          d="M6.22 4.22a.75.75 0 0 1 1.06 0l3.25 3.25a.75.75 0 0 1 0 1.06l-3.25 3.25a.75.75 0 0 1-1.06-1.06L8.94 8 6.22 5.28a.75.75 0 0 1 0-1.06Z"
                                          clip-rule="evenodd" />
                                </svg>
                            </Link>
                        </div>

                        <div v-else class="flex-1 flex items-center justify-center text-white/40 text-sm">
                            {{ __('No news published yet.') }}
                        </div>
                    </div>

                    <!-- ── Column 3: About Us + Community ── -->
                    <div class="bg-white/10 dark:bg-black/30 backdrop-blur-sm rounded-2xl p-6 border border-white/20 shadow-xl flex flex-col gap-8 h-[520px] overflow-y-auto">

                        <!-- About Us -->
                        <div>
                            <h2 class="text-xl uppercase font-bold text-white mb-4">{{ __('About Us') }}</h2>
                            <p class="text-base text-white/85 leading-relaxed">
                                <span class="font-bold text-rapanel-blue">{{ $page.props.serverName }}</span>
                                {{ __('is a private server of Ragnarok Online, we are a community that aims to bring back the good old times! We have many custom additions that will get you thrilled and busy! In our community you will find infinity of surprises that you will not be able to see in the rest of the servers and Staff committed in providing the best support.') }}
                            </p>
                        </div>

                        <!-- Divider -->
                        <div class="border-t border-white/20" />

                        <!-- Community -->
                        <div>
                            <h2 class="text-xl uppercase font-bold text-white mb-4">{{ __('Community') }}</h2>
                            <p class="text-base text-white/85 leading-relaxed">
                                {{ __('We see no reason to have dead forums, we communicate in real time using Discord. Do you have any questions, suggestions, or ideas? Feel free to ask us on our Discord channel.') }}
                            </p>
                        </div>

                    </div>

                </div>

                <!-- Next section button -->
                <div class="flex justify-center pt-12 pb-10">
                    <button @click="scrollTo('server-info')"
                            class="next-btn w-12 h-12 rounded-full flex items-center justify-center
                                   bg-white/20 hover:bg-white/35 border border-white/40
                                   backdrop-blur-sm transition duration-200"
                            :aria-label="__('Next section')">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                </div>

            </div>

        </section>

        <!-- ══════════════════════ SERVER INFO + FEATURES ══════════════════════ -->
        <div id="server-info" class="relative scroll-mt-[5rem] md:scroll-mt-[8.5rem]">

            <video autoplay muted loop playsinline
                   class="absolute inset-0 w-full h-full object-cover dark:filter dark:brightness-50">
                <source src="/images/info.mp4" type="video/mp4" />
            </video>

            <section class="relative z-10 container mx-auto py-16 px-6">

                <div class="flex items-center flex-col mb-16">
                    <h1 class="mt-4 text-4xl sm:text-5xl font-bold tracking-tight text-white drop-shadow text-center">
                        {{ __('Server Information') }}
                    </h1>
                </div>

                <!-- 4 info items -->
                <div class="flex flex-wrap justify-center gap-8 mb-16 px-4">

                    <div class="flex items-center sm:w-full md:w-5/12">
                        <div class="w-28 h-28 shrink-0 mr-5">
                            <img src="/images/exp.png" alt="EXP" class="w-full h-full object-contain" />
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-white drop-shadow">Rates 100x / 100x</h2>
                            <p class="text-base text-white/85 mt-1 leading-snug">
                                {{ __('Average rates for you to have fun and level up your character quickly.') }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center sm:w-full md:w-5/12">
                        <div class="w-28 h-28 shrink-0 mr-5">
                            <img src="/images/lvl.png" alt="Level" class="w-full h-full object-contain" />
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-white drop-shadow">{{ __('Max Level') }}: 99/70</h2>
                            <p class="text-base text-white/85 mt-1 leading-snug">
                                {{ __('Pre-Renewal server. Level up to 99/70 with new skills and exclusive items.') }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center sm:w-full md:w-5/12">
                        <div class="w-28 h-28 shrink-0 mr-5">
                            <img src="/images/epi.png" alt="Episode" class="w-full h-full object-contain" />
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-white drop-shadow">{{ __('Episode') }}: 13.3+ — El Dicastes</h2>
                            <p class="text-base text-white/85 mt-1 leading-snug">
                                {{ __("Explore Nidhoggr's episode with new instances and exclusive items.") }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center sm:w-full md:w-5/12">
                        <div class="w-28 h-28 shrink-0 mr-5">
                            <img src="/images/inter.png" alt="International" class="w-full h-full object-contain" />
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-white drop-shadow">{{ __('International Server') }}</h2>
                            <p class="text-base text-white/85 mt-1 leading-snug">
                                {{ __('We support multiple languages so the language barrier is never a problem.') }}
                            </p>
                        </div>
                    </div>

                </div>

                <!-- Feature cards -->
                <div class="flex flex-wrap justify-center gap-6 pb-16">

                    <div class="w-full max-w-xs">
                        <div class="flex flex-col h-full bg-white dark:bg-gray-800 shadow-lg rounded-xl overflow-hidden">
                            <figure class="relative h-0 pb-[56.25%] overflow-hidden">
                                <img class="absolute inset-0 w-full h-full object-cover transform hover:scale-105 transition duration-700 ease-out"
                                     src="/images/bg.gif" alt="Battlegrounds" />
                            </figure>
                            <div class="flex flex-col flex-grow p-5 text-center">
                                <div class="flex-grow">
                                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-3">{{ __('New Battlegrounds') }}</h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-300 mb-6 leading-relaxed">
                                        {{ __('Discover new Battleground modes designed to improve GvG and gameplay, not found on other servers!') }}
                                    </p>
                                </div>
                                <div class="flex justify-center">
                                    <a href="#" class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-semibold text-white bg-rapanel-blue hover:opacity-90 shadow transition">
                                        {{ __('More Info') }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="w-full max-w-xs">
                        <div class="flex flex-col h-full bg-white dark:bg-gray-800 shadow-lg rounded-xl overflow-hidden">
                            <figure class="relative h-0 pb-[56.25%] overflow-hidden">
                                <img class="absolute inset-0 w-full h-full object-cover transform hover:scale-105 transition duration-700 ease-out"
                                     src="/images/battlepass.gif" alt="Battle Pass" />
                            </figure>
                            <div class="flex flex-col flex-grow p-5 text-center">
                                <div class="flex-grow">
                                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-3">{{ __('Battle Pass') }}</h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-300 mb-6 leading-relaxed">
                                        {{ __('A monthly system that rewards players with exclusive items based on level progression.') }}
                                    </p>
                                </div>
                                <div class="flex justify-center">
                                    <a href="#" class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-semibold text-white bg-rapanel-blue hover:opacity-90 shadow transition">
                                        {{ __('More Info') }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="w-full max-w-xs">
                        <div class="flex flex-col h-full bg-white dark:bg-gray-800 shadow-lg rounded-xl overflow-hidden">
                            <figure class="relative h-0 pb-[56.25%] overflow-hidden">
                                <img class="absolute inset-0 w-full h-full object-cover transform hover:scale-105 transition duration-700 ease-out"
                                     src="/images/championmob.gif" alt="Champion Mobs" />
                            </figure>
                            <div class="flex flex-col flex-grow p-5 text-center">
                                <div class="flex-grow">
                                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-3">{{ __('Champion Mobs') }}</h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-300 mb-6 leading-relaxed">
                                        {{ __('Face a greater threat: Champion Monsters with exclusive drops and special rewards!') }}
                                    </p>
                                </div>
                                <div class="flex justify-center">
                                    <a href="#" class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-semibold text-white bg-rapanel-blue hover:opacity-90 shadow transition">
                                        {{ __('More Info') }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="w-full max-w-xs">
                        <div class="flex flex-col h-full bg-white dark:bg-gray-800 shadow-lg rounded-xl overflow-hidden">
                            <figure class="relative h-0 pb-[56.25%] overflow-hidden">
                                <img class="absolute inset-0 w-full h-full object-cover transform hover:scale-105 transition duration-700 ease-out"
                                     src="/images/pet.gif" alt="Pet Evolution" />
                            </figure>
                            <div class="flex flex-col flex-grow p-5 text-center">
                                <div class="flex-grow">
                                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-3">{{ __('Pet Evolution') }}</h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-300 mb-6 leading-relaxed">
                                        {{ __('Evolve your pets to improve their stats and unlock new appearances!') }}
                                    </p>
                                </div>
                                <div class="flex justify-center">
                                    <a href="#" class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-semibold text-white bg-rapanel-blue hover:opacity-90 shadow transition">
                                        {{ __('More Info') }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </section>
        </div>

        <Footer />

    </MainLayout>
</template>

<style scoped>
/* Pulsing animation for the next-section buttons */
.next-btn {
    animation: btnPulse 2.5s ease-in-out infinite;
}
@keyframes btnPulse {
    0%, 100% { transform: translateY(0);   opacity: 0.85; }
    50%       { transform: translateY(6px); opacity: 1;    }
}
</style>

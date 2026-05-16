<script setup>
import { usePage } from '@inertiajs/vue3';
import { getJobName, isCartClass, formatNum, formatMap, onImgError, itemLabel } from '@/Composables/useRoHelpers';

const props = defineProps({
    char:      { type: Object, default: null },
    cardNames: { type: Object, default: () => ({}) },
});

const emit = defineEmits(['close', 'reset-look', 'reset-position']);

const page = usePage();
const __ = (key) => page.props.translations?.[key] || key;

const getCardName = (id) => {
    if (!id || id <= 0 || id === 254 || id === 255) return null;
    return props.cardNames?.[id] || null;
};
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="ease-out duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="ease-in duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="char" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-rapanel-navy-900/80 backdrop-blur-sm">
                <div class="relative w-full max-w-5xl max-h-[92vh] overflow-y-auto bg-white dark:bg-rapanel-navy-800 rounded-xl shadow-2xl ring-1 ring-rapanel-navy-100 dark:ring-gray-700/50">

                    <!-- Header -->
                    <div class="sticky top-0 z-10 flex items-center justify-between px-6 py-4 bg-white dark:bg-rapanel-navy-800 border-b border-rapanel-navy-100 dark:border-gray-700 rounded-t-xl">
                        <div>
                            <p class="text-[10px] uppercase tracking-widest font-bold text-rapanel-text-light/40 dark:text-rapanel-text-dark/40">{{ __('Viewing Character') }}</p>
                            <h2 class="text-lg font-bold text-rapanel-navy-900 dark:text-white uppercase tracking-wide">{{ char.name }}</h2>
                        </div>
                        <div class="flex items-center gap-2">
                            <button @click="emit('reset-look', char)" :disabled="char.online > 0"
                                class="text-xs px-3 py-1.5 rounded-lg font-bold transition-all"
                                :class="char.online > 0 ? 'opacity-30 cursor-not-allowed bg-gray-100 dark:bg-gray-700 text-gray-400' : 'bg-rapanel-danger/10 text-rapanel-danger border border-rapanel-danger/20 hover:bg-rapanel-danger hover:text-white dark:bg-rapanel-gold/10 dark:text-rapanel-gold dark:border-rapanel-gold/20 dark:hover:bg-rapanel-gold dark:hover:text-rapanel-navy-900'"
                            >{{ __('Reset Look') }}</button>
                            <button @click="emit('reset-position', char)" :disabled="char.online > 0"
                                class="text-xs px-3 py-1.5 rounded-lg font-bold transition-all"
                                :class="char.online > 0 ? 'opacity-30 cursor-not-allowed bg-gray-100 dark:bg-gray-700 text-gray-400' : 'bg-rapanel-blue/10 text-rapanel-blue border border-rapanel-blue/20 hover:bg-rapanel-blue hover:text-white'"
                            >{{ __('Reset Position') }}</button>
                            <button @click="emit('close')" class="w-8 h-8 flex items-center justify-center rounded-lg bg-rapanel-navy-100 dark:bg-gray-700 hover:bg-rapanel-danger hover:text-white transition-all">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </div>

                    <div class="p-6 space-y-6">

                        <!-- CHARACTER INFORMATION -->
                        <section>
                            <h3 class="text-xs font-bold uppercase tracking-widest text-rapanel-text-light/40 dark:text-rapanel-text-dark/40 mb-3">{{ __('Character Information for') }} {{ char.name }}</h3>
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-px bg-rapanel-navy-100 dark:bg-gray-700/30 rounded-xl overflow-hidden border border-rapanel-navy-100 dark:border-gray-700/30">
                                <div v-for="row in [
                                    [__('Character ID'), char.char_id, __('Character Slot'), (char.char_num??0)+1],
                                    [__('Character'), char.name, __('Job Class'), getJobName(char.class??0)],
                                    [__('Base Level'), char.base_level, __('B. Experience'), formatNum(char.base_exp)],
                                    [__('Job Level'), char.job_level, __('J. Experience'), formatNum(char.job_exp)],
                                    [__('Current HP'), formatNum(char.hp), __('Max HP'), formatNum(char.max_hp)],
                                    [__('Current SP'), formatNum(char.sp), __('Max SP'), formatNum(char.max_sp)],
                                    [__('Zeny'), formatNum(char.zeny)+' z', __('Status Points'), char.status_point??0],
                                    [__('Skill Points'), char.skill_point??0, __('Death Count'), formatNum(char.death_count)],
                                    [__('Partner'), char.partner_name||__('None'), __('Child'), char.child_name||__('None')],
                                    [__('Mother'), char.mother_name||__('None'), __('Father'), char.father_name||__('None')],
                                    [__('Guild Name'), char.guild_name||__('None'), __('Guild Position'), char.guild_position||__('None')],
                                    [__('Tax Level'), (char.guild_tax??0)+'%', __('Party Name'), char.party_name||__('None')],
                                    [__('Party Leader'), char.party_leader_name||__('None'), __('Online Status'), char.online > 0 ? __('Online') : __('Offline')],
                                    [__('Last Map'), formatMap(char.last_map), __('Position'), `(${char.last_x}, ${char.last_y})`],
                                ]" :key="row[0]"
                                    class="flex flex-col sm:flex-row bg-white dark:bg-rapanel-navy-800"
                                >
                                    <div class="flex-1 px-3 py-2 border-b sm:border-b-0 sm:border-r border-rapanel-navy-100 dark:border-gray-700/30">
                                        <p class="text-[10px] uppercase tracking-widest font-bold text-rapanel-text-light/40 dark:text-rapanel-text-dark/40">{{ row[0] }}</p>
                                        <p class="font-semibold text-rapanel-navy-900 dark:text-white text-sm">{{ row[1] }}</p>
                                    </div>
                                    <div class="flex-1 px-3 py-2">
                                        <p class="text-[10px] uppercase tracking-widest font-bold text-rapanel-text-light/40 dark:text-rapanel-text-dark/40">{{ row[2] }}</p>
                                        <p class="font-semibold text-rapanel-navy-900 dark:text-white text-sm">{{ row[3] }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Stats -->
                            <div class="mt-3 flex flex-wrap gap-2">
                                <div v-for="stat in [{l:'STR',v:char.str},{l:'AGI',v:char.agi},{l:'VIT',v:char.vit},{l:'INT',v:char.int},{l:'DEX',v:char.dex},{l:'LUK',v:char.luk}]" :key="stat.l"
                                    class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-rapanel-navy-50 dark:bg-black/20 border border-rapanel-navy-100 dark:border-gray-700/30"
                                >
                                    <span class="text-[10px] uppercase tracking-widest font-bold text-rapanel-text-light/40 dark:text-rapanel-text-dark/40">{{ stat.l }}</span>
                                    <span class="font-bold text-rapanel-navy-900 dark:text-white">{{ stat.v ?? 0 }}</span>
                                </div>
                            </div>
                        </section>

                        <!-- OTHER PARTY MEMBERS -->
                        <section v-if="char.party_name && char.party_members?.length > 0">
                            <h3 class="text-xs font-bold uppercase tracking-widest text-rapanel-text-light/40 dark:text-rapanel-text-dark/40 mb-3">
                                {{ __('Other Party Members of') }} <span class="text-rapanel-blue uppercase">{{ char.party_name }}</span>
                            </h3>
                            <div class="rounded-xl overflow-hidden border border-rapanel-navy-100 dark:border-gray-700/30">
                                <table class="w-full text-xs text-left">
                                    <thead class="bg-rapanel-navy-100 dark:bg-black/30 text-[10px] uppercase tracking-widest font-bold text-rapanel-text-light/50 dark:text-rapanel-text-dark/50">
                                        <tr>
                                            <th class="px-3 py-2">{{ __('Character Name') }}</th>
                                            <th class="px-3 py-2">{{ __('Job Class') }}</th>
                                            <th class="px-3 py-2 text-center">{{ __('Base Lv') }}</th>
                                            <th class="px-3 py-2 text-center">{{ __('Job Lv') }}</th>
                                            <th class="px-3 py-2 text-center">{{ __('Guild') }}</th>
                                            <th class="px-3 py-2 text-center">{{ __('Status') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-rapanel-navy-100/50 dark:divide-gray-700/30">
                                        <tr v-for="m in char.party_members" :key="m.char_id" class="bg-white dark:bg-rapanel-navy-800 hover:bg-rapanel-navy-50/30 dark:hover:bg-gray-700/20">
                                            <td class="px-3 py-2 font-medium text-rapanel-navy-900 dark:text-white">{{ m.name }}</td>
                                            <td class="px-3 py-2 text-rapanel-text-light/60 dark:text-rapanel-text-dark/60">{{ getJobName(m.class) }}</td>
                                            <td class="px-3 py-2 text-center font-bold text-rapanel-navy-900 dark:text-white">{{ m.base_level }}</td>
                                            <td class="px-3 py-2 text-center font-bold text-rapanel-navy-900 dark:text-white">{{ m.job_level }}</td>
                                            <td class="px-3 py-2 text-center text-rapanel-text-light/50 dark:text-rapanel-text-dark/50">{{ m.guild_name || __('None') }}</td>
                                            <td class="px-3 py-2 text-center">
                                                <span :class="m.online > 0 ? 'text-rapanel-success' : 'text-gray-400'" class="font-bold text-[10px]">{{ m.online > 0 ? __('Online') : __('Offline') }}</span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </section>

                        <!-- FRIENDS -->
                        <section v-if="char.friends?.length > 0">
                            <h3 class="text-xs font-bold uppercase tracking-widest text-rapanel-text-light/40 dark:text-rapanel-text-dark/40 mb-3">
                                {{ __('Friends of') }} <span class="text-rapanel-blue uppercase">{{ char.name }}</span>
                                <span class="ml-1 font-normal">({{ char.friends.length }})</span>
                            </h3>
                            <div class="rounded-xl overflow-hidden border border-rapanel-navy-100 dark:border-gray-700/30">
                                <table class="w-full text-xs text-left">
                                    <thead class="bg-rapanel-navy-100 dark:bg-black/30 text-[10px] uppercase tracking-widest font-bold text-rapanel-text-light/50 dark:text-rapanel-text-dark/50">
                                        <tr>
                                            <th class="px-3 py-2">{{ __('Character Name') }}</th>
                                            <th class="px-3 py-2">{{ __('Job Class') }}</th>
                                            <th class="px-3 py-2 text-center">{{ __('Base Lv') }}</th>
                                            <th class="px-3 py-2 text-center">{{ __('Job Lv') }}</th>
                                            <th class="px-3 py-2 text-center">{{ __('Guild') }}</th>
                                            <th class="px-3 py-2 text-center">{{ __('Status') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-rapanel-navy-100/50 dark:divide-gray-700/30">
                                        <tr v-for="f in char.friends" :key="f.char_id" class="bg-white dark:bg-rapanel-navy-800 hover:bg-rapanel-navy-50/30 dark:hover:bg-gray-700/20">
                                            <td class="px-3 py-2 font-medium text-rapanel-navy-900 dark:text-white">{{ f.name }}</td>
                                            <td class="px-3 py-2 text-rapanel-text-light/60 dark:text-rapanel-text-dark/60">{{ getJobName(f.class) }}</td>
                                            <td class="px-3 py-2 text-center font-bold text-rapanel-navy-900 dark:text-white">{{ f.base_level }}</td>
                                            <td class="px-3 py-2 text-center font-bold text-rapanel-navy-900 dark:text-white">{{ f.job_level }}</td>
                                            <td class="px-3 py-2 text-center text-rapanel-text-light/50 dark:text-rapanel-text-dark/50">{{ f.guild_name || __('None') }}</td>
                                            <td class="px-3 py-2 text-center">
                                                <span :class="f.online > 0 ? 'text-rapanel-success' : 'text-gray-400'" class="font-bold text-[10px]">{{ f.online > 0 ? __('Online') : __('Offline') }}</span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </section>
                        <section v-else>
                            <p class="text-xs text-rapanel-text-light/30 dark:text-rapanel-text-dark/30 italic">{{ char.name }} {{ __('has no friends.') }}</p>
                        </section>

                        <!-- INVENTORY ITEMS -->
                        <section>
                            <h3 class="text-xs font-bold uppercase tracking-widest text-rapanel-text-light/40 dark:text-rapanel-text-dark/40 mb-3">
                                {{ __('Inventory Items of') }} <span class="text-rapanel-blue uppercase">{{ char.name }}</span>
                                <span class="ml-1 font-normal">({{ char.inventory?.length ?? 0 }})</span>
                            </h3>
                            <div v-if="!char.inventory?.length" class="text-xs italic text-rapanel-text-light/30 dark:text-rapanel-text-dark/30 py-4 text-center">{{ __('No items found.') }}</div>
                            <div v-else class="rounded-xl overflow-hidden border border-rapanel-navy-100 dark:border-gray-700/30 overflow-x-auto">
                                <table class="w-full text-xs text-left">
                                    <thead class="bg-rapanel-navy-100 dark:bg-black/30 text-[10px] uppercase tracking-widest font-bold text-rapanel-text-light/50 dark:text-rapanel-text-dark/50">
                                        <tr>
                                            <th class="px-2 py-2 w-8">{{ __('Icon') }}</th>
                                            <th class="px-3 py-2">{{ __('Item Name') }}</th>
                                            <th class="px-3 py-2 text-center">{{ __('Amount') }}</th>
                                            <th class="px-3 py-2 text-center">{{ __('Identified') }}</th>
                                            <th class="px-3 py-2 text-center">{{ __('Slot 1') }}</th>
                                            <th class="px-3 py-2 text-center">{{ __('Slot 2') }}</th>
                                            <th class="px-3 py-2 text-center">{{ __('Slot 3') }}</th>
                                            <th class="px-3 py-2 text-center">{{ __('Slot 4') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-rapanel-navy-100/50 dark:divide-gray-700/30">
                                        <tr v-for="(item, idx) in char.inventory" :key="idx"
                                            class="bg-white dark:bg-rapanel-navy-800 hover:bg-rapanel-navy-50/30 dark:hover:bg-gray-700/20"
                                            :class="{ 'bg-rapanel-blue/5': item.equip > 0 }"
                                        >
                                            <td class="px-2 py-1.5 text-center">
                                                <img :src="`/data/items/icons/${item.nameid}.png`" @error="onImgError" class="w-7 h-7 object-contain mx-auto" />
                                            </td>
                                            <td class="px-3 py-1.5">
                                                <span v-if="item.refine > 0" class="font-bold text-rapanel-danger dark:text-rapanel-gold mr-1">+{{ item.refine }}</span>
                                                <span class="font-medium text-rapanel-navy-900 dark:text-white">{{ itemLabel(item) }}</span>
                                                <span v-if="item.equip > 0" class="ml-1.5 inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold bg-rapanel-blue/10 text-rapanel-blue border border-rapanel-blue/20">{{ __('Equipped') }}</span>
                                                <span v-if="item.attribute > 0" class="ml-1 text-rapanel-danger font-bold text-[10px]">[{{ __('Broken') }}]</span>
                                            </td>
                                            <td class="px-3 py-1.5 text-center text-rapanel-text-light/60 dark:text-rapanel-text-dark/60">{{ item.amount }}</td>
                                            <td class="px-3 py-1.5 text-center">
                                                <span :class="item.identify ? 'text-rapanel-success' : 'text-rapanel-danger'" class="font-bold">{{ item.identify ? __('Yes') : __('No') }}</span>
                                            </td>
                                            <td v-for="slot in ['card0','card1','card2','card3']" :key="slot" class="px-3 py-1.5 text-center text-rapanel-text-light/40 dark:text-rapanel-text-dark/30 italic">
                                                <span v-if="getCardName(item[slot])" class="text-rapanel-blue not-italic font-medium">{{ getCardName(item[slot]) }}</span>
                                                <span v-else>{{ __('None') }}</span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </section>

                        <!-- CART INVENTORY -->
                        <section v-if="isCartClass(char.class)">
                            <h3 class="text-xs font-bold uppercase tracking-widest text-rapanel-text-light/40 dark:text-rapanel-text-dark/40 mb-3">
                                {{ __('Cart Inventory Items of') }} <span class="text-rapanel-gold uppercase">{{ char.name }}</span>
                                <span class="ml-1 font-normal">({{ char.cart_inventory?.length ?? 0 }})</span>
                            </h3>
                            <div v-if="!char.cart_inventory?.length" class="text-xs italic text-rapanel-text-light/30 dark:text-rapanel-text-dark/30 py-4 text-center">{{ __('No cart items found.') }}</div>
                            <div v-else class="rounded-xl overflow-hidden border border-rapanel-navy-100 dark:border-gray-700/30 overflow-x-auto">
                                <table class="w-full text-xs text-left">
                                    <thead class="bg-rapanel-navy-100 dark:bg-black/30 text-[10px] uppercase tracking-widest font-bold text-rapanel-text-light/50 dark:text-rapanel-text-dark/50">
                                        <tr>
                                            <th class="px-2 py-2 w-8">{{ __('Icon') }}</th>
                                            <th class="px-3 py-2">{{ __('Item Name') }}</th>
                                            <th class="px-3 py-2 text-center">{{ __('Amount') }}</th>
                                            <th class="px-3 py-2 text-center">{{ __('Identified') }}</th>
                                            <th class="px-3 py-2 text-center">{{ __('Slot 1') }}</th>
                                            <th class="px-3 py-2 text-center">{{ __('Slot 2') }}</th>
                                            <th class="px-3 py-2 text-center">{{ __('Slot 3') }}</th>
                                            <th class="px-3 py-2 text-center">{{ __('Slot 4') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-rapanel-navy-100/50 dark:divide-gray-700/30">
                                        <tr v-for="(item, idx) in char.cart_inventory" :key="idx"
                                            class="bg-white dark:bg-rapanel-navy-800 hover:bg-rapanel-navy-50/30 dark:hover:bg-gray-700/20"
                                        >
                                            <td class="px-2 py-1.5 text-center">
                                                <img :src="`/data/items/icons/${item.nameid}.png`" @error="onImgError" class="w-7 h-7 object-contain mx-auto" />
                                            </td>
                                            <td class="px-3 py-1.5">
                                                <span v-if="item.refine > 0" class="font-bold text-rapanel-danger dark:text-rapanel-gold mr-1">+{{ item.refine }}</span>
                                                <span class="font-medium text-rapanel-navy-900 dark:text-white">{{ itemLabel(item) }}</span>
                                                <span v-if="item.attribute > 0" class="ml-1 text-rapanel-danger font-bold text-[10px]">[{{ __('Broken') }}]</span>
                                            </td>
                                            <td class="px-3 py-1.5 text-center text-rapanel-text-light/60 dark:text-rapanel-text-dark/60">{{ item.amount }}</td>
                                            <td class="px-3 py-1.5 text-center">
                                                <span :class="item.identify ? 'text-rapanel-success' : 'text-rapanel-danger'" class="font-bold">{{ item.identify ? __('Yes') : __('No') }}</span>
                                            </td>
                                            <td v-for="slot in ['card0','card1','card2','card3']" :key="slot" class="px-3 py-1.5 text-center text-rapanel-text-light/40 dark:text-rapanel-text-dark/30 italic">
                                                <span v-if="getCardName(item[slot])" class="text-rapanel-blue not-italic font-medium">{{ getCardName(item[slot]) }}</span>
                                                <span v-else>{{ __('None') }}</span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </section>

                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

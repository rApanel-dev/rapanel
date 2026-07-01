<script setup>
import { ref } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import ActionButton from '@/Components/ActionButton.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    files:         Array,
    listContent:   String,
    listFilename:  String,
    settings:      Object,
    publicBaseUrl: String,
    maxUploadMb:   Number,
});

const page = usePage();
const __ = (key, repl = {}) => {
    let s = page.props.translations?.[key] || key;
    for (const k in repl) s = s.replace(`:${k}`, repl[k]);
    return s;
};

// ---- Subida ----
const uploadForm = useForm({ file: null });
const fileInput = ref(null);

const submitUpload = () => {
    if (!uploadForm.file) return;
    uploadForm.post(route('admin.patches.upload'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => { uploadForm.reset('file'); if (fileInput.value) fileInput.value.value = ''; },
    });
};

// ---- Editor de lista ----
const listForm = useForm({ content: props.listContent ?? '' });
const saveList = () => listForm.put(route('admin.patches.save-list'), { preserveScroll: true });

// ---- Settings ----
const settingsForm = useForm({
    patch_list_filename: props.settings?.patch_list_filename ?? 'patch_main.txt',
    patcher_type:        props.settings?.patcher_type ?? 'elurair',
});
const onPatcherTypeChange = () => {
    if (settingsForm.patcher_type === 'thor' && settingsForm.patch_list_filename === 'patch_main.txt') {
        settingsForm.patch_list_filename = 'plist.txt';
    } else if (settingsForm.patcher_type === 'elurair' && settingsForm.patch_list_filename === 'plist.txt') {
        settingsForm.patch_list_filename = 'patch_main.txt';
    }
};
const saveSettings = () => settingsForm.put(route('admin.patches.settings'), { preserveScroll: true });

// ---- Renombrar ----
const renameForm = useForm({ old: '', new: '' });
const renaming = ref(false);
const openRename = (name) => {
    renameForm.clearErrors();
    renameForm.old = name;
    renameForm.new = name;
    renaming.value = true;
};
const submitRename = () => {
    renameForm.put(route('admin.patches.rename'), {
        preserveScroll: true,
        onSuccess: () => { renaming.value = false; },
    });
};

// ---- Borrado ----
const confirmState = ref(null);
const askDelete = (name) => {
    confirmState.value = {
        title:        __('Delete Patch File'),
        entity:       name,
        confirmLabel: __('Delete'),
        variant:      'danger',
        action:       () => router.delete(route('admin.patches.delete-file'), {
            data: { name }, preserveScroll: true,
        }),
    };
};
const doConfirm   = () => { confirmState.value?.action?.(); confirmState.value = null; };
const closeConfirm = () => { confirmState.value = null; };

// ---- Helpers ----
const formatSize = (bytes) => {
    if (bytes === 0) return '0 B';
    const u = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(1024));
    return (bytes / Math.pow(1024, i)).toFixed(i ? 1 : 0) + ' ' + u[i];
};

const copyName = async (name) => {
    try { await navigator.clipboard.writeText(name); } catch (e) { /* noop */ }
};
</script>

<template>
    <AdminLayout>
        <PageHeader :title="__('Patcher')" :description="__('Manage client patch files and the patch list served to the patcher.')" class="mb-6" />

        <!-- Info endpoint -->
        <div class="bg-rapanel-navy-50 dark:bg-rapanel-surface rounded-xl border border-rapanel-navy-100 dark:border-white/10 p-5 mb-6">
            <p class="text-xs text-rapanel-text-light dark:text-rapanel-text-dark opacity-80 leading-relaxed">
                {{ __('The patcher downloads files from this public folder:') }}
                <code class="px-1.5 py-0.5 rounded bg-rapanel-navy-100 dark:bg-white/10 text-rapanel-blue font-mono">{{ publicBaseUrl }}/</code>
            </p>
            <p class="text-xs text-rapanel-text-light dark:text-rapanel-text-dark opacity-60 mt-1.5">
                {{ __('Patch list file:') }}
                <code class="px-1.5 py-0.5 rounded bg-rapanel-navy-100 dark:bg-white/10 font-mono">{{ publicBaseUrl }}/{{ listFilename }}</code>
            </p>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
            <!-- Card: Archivos -->
            <div class="bg-white dark:bg-rapanel-surface rounded-xl border border-rapanel-navy-100 dark:border-white/[0.07] shadow-[0_4px_20px_rgba(0,0,0,0.22)] dark:shadow-[0_4px_28px_rgba(0,0,0,0.5)] overflow-hidden">
                <div class="px-5 py-4 border-b border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-white/5">
                    <h2 class="text-sm font-black uppercase tracking-wider text-rapanel-navy-900 dark:text-white">{{ __('Patch Files') }}</h2>
                </div>

                <!-- Uploader -->
                <div class="p-5 border-b border-rapanel-navy-100 dark:border-white/10">
                    <div class="flex flex-wrap items-center gap-3">
                        <input ref="fileInput" type="file" @change="e => uploadForm.file = e.target.files[0]"
                            class="text-sm text-rapanel-text-light dark:text-rapanel-text-dark file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-rapanel-blue/10 file:text-rapanel-blue hover:file:bg-rapanel-blue/20 cursor-pointer" />
                        <button @click="submitUpload" :disabled="!uploadForm.file || uploadForm.processing"
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-rapanel-blue text-white text-sm font-bold hover:opacity-90 transition disabled:opacity-40 disabled:cursor-not-allowed">
                            <span v-if="uploadForm.processing">{{ __('Uploading…') }}</span>
                            <span v-else>↑ {{ __('Upload') }}</span>
                        </button>
                    </div>
                    <p v-if="uploadForm.progress" class="mt-2 text-xs text-rapanel-text-light dark:text-white/60">
                        {{ uploadForm.progress.percentage }}%
                    </p>
                    <p v-if="uploadForm.errors.file" class="mt-1 text-xs text-rapanel-danger">{{ uploadForm.errors.file }}</p>
                    <p class="mt-2 text-[11px] text-rapanel-text-light/60 dark:text-white/40">
                        {{ __('Allowed: .gpf .rgz .zip .7z .grf .thor — max :max MB.', { max: maxUploadMb }) }}
                    </p>
                </div>

                <!-- Lista de archivos -->
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-white/5">
                            <th class="px-4 py-2.5 text-left text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40">{{ __('Name') }}</th>
                            <th class="px-4 py-2.5 text-right text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40">{{ __('Size') }}</th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40 hidden sm:table-cell">{{ __('Modified') }}</th>
                            <th class="px-4 py-2.5 text-center text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-rapanel-navy-100 dark:divide-white/5">
                        <tr v-for="f in files" :key="f.name" class="hover:bg-rapanel-navy-50 dark:hover:bg-white/5 transition-colors">
                            <td class="px-4 py-2.5 font-mono text-xs text-rapanel-navy-900 dark:text-white break-all">{{ f.name }}</td>
                            <td class="px-4 py-2.5 text-right text-xs text-rapanel-text-light dark:text-white/70 whitespace-nowrap">{{ formatSize(f.size) }}</td>
                            <td class="px-4 py-2.5 text-xs text-rapanel-text-light/70 dark:text-white/50 hidden sm:table-cell whitespace-nowrap">{{ f.modified_at }}</td>
                            <td class="px-4 py-2.5">
                                <div class="flex items-center justify-center gap-1">
                                    <ActionButton variant="navy" size="icon" :title="__('Copy file name')" @click="copyName(f.name)">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 01-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 011.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 00-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 01-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 00-3.375-3.375h-1.5a1.125 1.125 0 01-1.125-1.125v-1.5a3.375 3.375 0 00-3.375-3.375H9.75"/></svg>
                                    </ActionButton>
                                    <ActionButton variant="navy" size="icon" :title="__('Rename')" @click="openRename(f.name)">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/></svg>
                                    </ActionButton>
                                    <ActionButton variant="danger" size="icon" :title="__('Delete')" @click="askDelete(f.name)">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                    </ActionButton>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!files.length">
                            <td colspan="4" class="px-5 py-10 text-center text-sm text-rapanel-text-light dark:text-rapanel-text-dark">{{ __('No patch files uploaded yet.') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Card: Editor de lista + Settings -->
            <div class="space-y-6">
                <!-- Editor lista -->
                <div class="bg-white dark:bg-rapanel-surface rounded-xl border border-rapanel-navy-100 dark:border-white/[0.07] shadow-[0_4px_20px_rgba(0,0,0,0.22)] dark:shadow-[0_4px_28px_rgba(0,0,0,0.5)] overflow-hidden">
                    <div class="px-5 py-4 border-b border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-white/5 flex items-center justify-between gap-2">
                        <h2 class="text-sm font-black uppercase tracking-wider text-rapanel-navy-900 dark:text-white">{{ __('Patch List') }} <span class="font-mono text-rapanel-blue normal-case">{{ listFilename }}</span></h2>
                        <button @click="saveList" :disabled="listForm.processing"
                            class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg bg-rapanel-success text-white text-xs font-bold hover:opacity-90 transition disabled:opacity-40">
                            {{ listForm.processing ? __('Saving…') : __('Save List') }}
                        </button>
                    </div>
                    <div class="p-5">
                        <textarea v-model="listForm.content" rows="12" spellcheck="false"
                            class="w-full font-mono text-xs rounded-lg border border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50/50 dark:bg-black/30 text-rapanel-navy-900 dark:text-white p-3 focus:ring-2 focus:ring-rapanel-blue focus:border-rapanel-blue outline-none resize-y"
                            :placeholder="'1 2026-update.gpf\n2 sprites.gpf\n// 3 evento.gpf  (comentado)'"></textarea>
                        <p v-if="listForm.errors.content" class="mt-1 text-xs text-rapanel-danger">{{ listForm.errors.content }}</p>
                        <p class="mt-2 text-[11px] text-rapanel-text-light/60 dark:text-white/40 leading-relaxed">
                            {{ __('Format: one line per patch — <counter> <filename>. Lines starting with // are ignored. A .bak backup is kept on each save.') }}
                        </p>
                    </div>
                </div>

                <!-- Settings -->
                <div class="bg-white dark:bg-rapanel-surface rounded-xl border border-rapanel-navy-100 dark:border-white/[0.07] shadow-[0_4px_20px_rgba(0,0,0,0.22)] dark:shadow-[0_4px_28px_rgba(0,0,0,0.5)] overflow-hidden">
                    <div class="px-5 py-4 border-b border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-white/5">
                        <h2 class="text-sm font-black uppercase tracking-wider text-rapanel-navy-900 dark:text-white">{{ __('Patcher Settings') }}</h2>
                    </div>
                    <div class="p-5 space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-rapanel-navy-900 dark:text-white mb-1">{{ __('Patcher type') }}</label>
                            <select v-model="settingsForm.patcher_type" @change="onPatcherTypeChange"
                                class="w-full text-sm rounded-lg border border-rapanel-navy-100 dark:border-white/10 bg-white dark:bg-black/30 text-rapanel-navy-900 dark:text-white px-3 py-2 focus:ring-2 focus:ring-rapanel-blue outline-none">
                                <option value="elurair">eluRair</option>
                                <option value="thor">Thor</option>
                                <option value="other">{{ __('Other') }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-rapanel-navy-900 dark:text-white mb-1">{{ __('Patch list filename') }}</label>
                            <input v-model="settingsForm.patch_list_filename" type="text"
                                class="w-full font-mono text-sm rounded-lg border border-rapanel-navy-100 dark:border-white/10 bg-white dark:bg-black/30 text-rapanel-navy-900 dark:text-white px-3 py-2 focus:ring-2 focus:ring-rapanel-blue outline-none" />
                            <p v-if="settingsForm.errors.patch_list_filename" class="mt-1 text-xs text-rapanel-danger">{{ settingsForm.errors.patch_list_filename }}</p>
                            <p class="mt-1 text-[11px] text-rapanel-text-light/60 dark:text-white/40">{{ __('eluRair: patch_main.txt — Thor: plist.txt') }}</p>
                        </div>
                        <button @click="saveSettings" :disabled="settingsForm.processing"
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-rapanel-blue text-white text-sm font-bold hover:opacity-90 transition disabled:opacity-40">
                            {{ settingsForm.processing ? __('Saving…') : __('Save Settings') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>

    <!-- Modal renombrar -->
    <Modal :show="renaming" max-width="md" @close="renaming = false">
        <div class="p-6">
            <h3 class="text-base font-black text-rapanel-navy-900 dark:text-white mb-1">{{ __('Rename Patch File') }}</h3>
            <p class="text-xs text-rapanel-text-light dark:text-white/50 mb-4 font-mono break-all">{{ renameForm.old }}</p>
            <input v-model="renameForm.new" type="text" spellcheck="false"
                @keyup.enter="submitRename"
                class="w-full font-mono text-sm rounded-lg border border-rapanel-navy-100 dark:border-white/10 bg-white dark:bg-black/30 text-rapanel-navy-900 dark:text-white px-3 py-2 focus:ring-2 focus:ring-rapanel-blue outline-none" />
            <p v-if="renameForm.errors.new" class="mt-1 text-xs text-rapanel-danger">{{ renameForm.errors.new }}</p>
            <p class="mt-2 text-[11px] text-rapanel-text-light/60 dark:text-white/40">{{ __('Keep the extension (.gpf, .grf, …). Renaming here does not update the patch list.') }}</p>
            <div class="mt-5 flex justify-end gap-2">
                <button @click="renaming = false"
                    class="px-4 py-2 rounded-lg text-sm font-bold text-rapanel-text-light dark:text-white/70 hover:bg-rapanel-navy-50 dark:hover:bg-white/5 transition">
                    {{ __('Cancel') }}
                </button>
                <button @click="submitRename" :disabled="renameForm.processing || !renameForm.new"
                    class="px-4 py-2 rounded-lg bg-rapanel-blue text-white text-sm font-bold hover:opacity-90 transition disabled:opacity-40">
                    {{ renameForm.processing ? __('Saving…') : __('Rename') }}
                </button>
            </div>
        </div>
    </Modal>

    <ConfirmModal
        :show="!!confirmState"
        :title="confirmState?.title ?? ''"
        :entity="confirmState?.entity ?? ''"
        :confirm-label="confirmState?.confirmLabel ?? ''"
        :variant="confirmState?.variant ?? 'danger'"
        @confirm="doConfirm"
        @close="closeConfirm"
    />
</template>

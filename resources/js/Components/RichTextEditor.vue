<script setup>
import { useEditor, EditorContent } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import Underline from '@tiptap/extension-underline';
import Link from '@tiptap/extension-link';
import Image from '@tiptap/extension-image';
import { watch, onBeforeUnmount } from 'vue';

const props = defineProps({
    modelValue: { type: String, default: '' },
});
const emit = defineEmits(['update:modelValue']);

const editor = useEditor({
    content: props.modelValue,
    extensions: [
        StarterKit,
        Underline,
        Link.configure({ openOnClick: false }),
        Image.configure({ inline: false }),
    ],
    onUpdate: () => {
        emit('update:modelValue', editor.value.getHTML());
    },
});

watch(() => props.modelValue, (val) => {
    if (editor.value && editor.value.getHTML() !== val) {
        editor.value.commands.setContent(val ?? '', false);
    }
});

onBeforeUnmount(() => editor.value?.destroy());

const setLink = () => {
    const prev = editor.value?.getAttributes('link').href ?? '';
    const url = window.prompt('URL del enlace:', prev);
    if (url === null) return;
    if (url === '') {
        editor.value?.chain().focus().extendMarkRange('link').unsetLink().run();
    } else {
        editor.value?.chain().focus().extendMarkRange('link').setLink({ href: url, target: '_blank' }).run();
    }
};

const addImage = () => {
    const url = window.prompt('URL de la imagen:');
    if (url) editor.value?.chain().focus().setImage({ src: url }).run();
};

const toolbarBtn = (active) =>
    ['inline-flex items-center justify-center w-7 h-7 rounded text-xs font-bold transition-colors',
     active
        ? 'bg-rapanel-blue text-white'
        : 'text-rapanel-text-light dark:text-white/70 hover:bg-rapanel-navy-100 dark:hover:bg-white/10'
    ].join(' ');
</script>

<template>
    <div class="rounded-lg border border-rapanel-navy-100 dark:border-white/10 overflow-hidden">
        <!-- Toolbar -->
        <div class="flex flex-wrap items-center gap-0.5 px-2 py-1.5 bg-rapanel-navy-50 dark:bg-rapanel-navy-900/60 border-b border-rapanel-navy-100 dark:border-white/10">

            <!-- Headings -->
            <button type="button" :class="toolbarBtn(editor?.isActive('heading', { level: 1 }))"
                title="Heading 1" @click="editor?.chain().focus().toggleHeading({ level: 1 }).run()">H1</button>
            <button type="button" :class="toolbarBtn(editor?.isActive('heading', { level: 2 }))"
                title="Heading 2" @click="editor?.chain().focus().toggleHeading({ level: 2 }).run()">H2</button>
            <button type="button" :class="toolbarBtn(editor?.isActive('heading', { level: 3 }))"
                title="Heading 3" @click="editor?.chain().focus().toggleHeading({ level: 3 }).run()">H3</button>

            <span class="w-px h-5 bg-rapanel-navy-100 dark:bg-white/10 mx-1" />

            <!-- Inline format -->
            <button type="button" :class="toolbarBtn(editor?.isActive('bold'))"
                title="Bold" @click="editor?.chain().focus().toggleBold().run()">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M15.6 10.79c.97-.67 1.65-1.77 1.65-2.79 0-2.26-1.75-4-4-4H7v14h7.04c2.09 0 3.71-1.7 3.71-3.79 0-1.52-.86-2.82-2.15-3.42zM10 6.5h3c.83 0 1.5.67 1.5 1.5s-.67 1.5-1.5 1.5h-3v-3zm3.5 9H10v-3h3.5c.83 0 1.5.67 1.5 1.5s-.67 1.5-1.5 1.5z"/></svg>
            </button>
            <button type="button" :class="toolbarBtn(editor?.isActive('italic'))"
                title="Italic" @click="editor?.chain().focus().toggleItalic().run()">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M10 4v3h2.21l-3.42 8H6v3h8v-3h-2.21l3.42-8H18V4z"/></svg>
            </button>
            <button type="button" :class="toolbarBtn(editor?.isActive('underline'))"
                title="Underline" @click="editor?.chain().focus().toggleUnderline().run()">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M12 17c3.31 0 6-2.69 6-6V3h-2.5v8c0 1.93-1.57 3.5-3.5 3.5S8.5 12.93 8.5 11V3H6v8c0 3.31 2.69 6 6 6zm-7 2v2h14v-2H5z"/></svg>
            </button>
            <button type="button" :class="toolbarBtn(editor?.isActive('strike'))"
                title="Strikethrough" @click="editor?.chain().focus().toggleStrike().run()">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M10 19h4v-3h-4v3zM5 4v3h5v3h4V7h5V4H5zM3 14h18v-2H3v2z"/></svg>
            </button>

            <span class="w-px h-5 bg-rapanel-navy-100 dark:bg-white/10 mx-1" />

            <!-- Lists -->
            <button type="button" :class="toolbarBtn(editor?.isActive('bulletList'))"
                title="Bullet list" @click="editor?.chain().focus().toggleBulletList().run()">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M4 10.5c-.83 0-1.5.67-1.5 1.5s.67 1.5 1.5 1.5 1.5-.67 1.5-1.5-.67-1.5-1.5-1.5zm0-6c-.83 0-1.5.67-1.5 1.5S3.17 7.5 4 7.5 5.5 6.83 5.5 6 4.83 4.5 4 4.5zm0 12c-.83 0-1.5.68-1.5 1.5s.68 1.5 1.5 1.5 1.5-.68 1.5-1.5-.67-1.5-1.5-1.5zM7 19h14v-2H7v2zm0-6h14v-2H7v2zm0-8v2h14V5H7z"/></svg>
            </button>
            <button type="button" :class="toolbarBtn(editor?.isActive('orderedList'))"
                title="Ordered list" @click="editor?.chain().focus().toggleOrderedList().run()">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M2 17h2v.5H3v1h1v.5H2v1h3v-4H2v1zm1-9h1V4H2v1h1v3zm-1 3h1.8L2 13.1v.9h3v-1H3.2L5 10.9V10H2v1zm5-6v2h14V5H7zm0 14h14v-2H7v2zm0-6h14v-2H7v2z"/></svg>
            </button>

            <span class="w-px h-5 bg-rapanel-navy-100 dark:bg-white/10 mx-1" />

            <!-- Link & Image -->
            <button type="button" :class="toolbarBtn(editor?.isActive('link'))"
                title="Insert link" @click="setLink">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M3.9 12c0-1.71 1.39-3.1 3.1-3.1h4V7H7c-2.76 0-5 2.24-5 5s2.24 5 5 5h4v-1.9H7c-1.71 0-3.1-1.39-3.1-3.1zM8 13h8v-2H8v2zm9-6h-4v1.9h4c1.71 0 3.1 1.39 3.1 3.1s-1.39 3.1-3.1 3.1h-4V17h4c2.76 0 5-2.24 5-5s-2.24-5-5-5z"/></svg>
            </button>
            <button type="button" :class="toolbarBtn(false)"
                title="Insert image" @click="addImage">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/></svg>
            </button>

            <span class="w-px h-5 bg-rapanel-navy-100 dark:bg-white/10 mx-1" />

            <!-- Clear format -->
            <button type="button" :class="toolbarBtn(false)"
                title="Clear formatting" @click="editor?.chain().focus().clearNodes().unsetAllMarks().run()">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M6 22l4-4H7l-.7-2H4l-.7 2H1l4-9h2l1.1 3.1L12 9l9 9-1.4 1.4-2.79-2.79L15 19h-2l.45-1.27L11 15.46l-1.8 1.8L11 19H9l-.89-2.52L6.26 18.2 6 22zM9.93 8L8 3h2l.93 2.28L12.87 3H15l-3.2 3.2 2.07 2.07L12.43 9.7 9.93 8zM19 3l2 2-7 7-2-2 7-7z"/></svg>
            </button>
        </div>

        <!-- Editor content -->
        <EditorContent :editor="editor" class="rte-content text-rapanel-text-light dark:text-rapanel-text-dark" />
    </div>
</template>

<style scoped>
.rte-content :deep(.ProseMirror) {
    min-height: 14rem;
    padding: 0.75rem 1rem;
    outline: none;
    font-size: 0.875rem;
    line-height: 1.6;
    color: inherit;
}

.rte-content :deep(.ProseMirror h1) { font-size: 1.5rem; font-weight: 700; margin: 0.75rem 0 0.25rem; }
.rte-content :deep(.ProseMirror h2) { font-size: 1.25rem; font-weight: 700; margin: 0.75rem 0 0.25rem; }
.rte-content :deep(.ProseMirror h3) { font-size: 1.1rem;  font-weight: 600; margin: 0.5rem 0 0.25rem; }

.rte-content :deep(.ProseMirror p)  { margin: 0.25rem 0; }

.rte-content :deep(.ProseMirror ul) { list-style: disc;    padding-left: 1.25rem; margin: 0.25rem 0; }
.rte-content :deep(.ProseMirror ol) { list-style: decimal; padding-left: 1.25rem; margin: 0.25rem 0; }

.rte-content :deep(.ProseMirror a)  { color: #3b82f6; text-decoration: underline; }

.rte-content :deep(.ProseMirror img) {
    max-width: 100%;
    height: auto;
    border-radius: 0.375rem;
    margin: 0.5rem 0;
}

.rte-content :deep(.ProseMirror p.is-editor-empty:first-child::before) {
    content: attr(data-placeholder);
    float: left;
    color: #9ca3af;
    pointer-events: none;
    height: 0;
}
</style>

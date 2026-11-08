<script setup>
import { watch, onBeforeUnmount } from 'vue';
import { useEditor, EditorContent } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import Underline from '@tiptap/extension-underline';
import Link from '@tiptap/extension-link';

const props = defineProps({
    modelValue: {
        type: String,
        default: '',
    },
    placeholder: {
        type: String,
        default: 'Write content here...',
    },
    minHeight: {
        type: String,
        default: '260px',
    },
});

const emit = defineEmits(['update:modelValue']);

const editor = useEditor({
    content: props.modelValue,
    extensions: [
        StarterKit.configure({
            heading: {
                levels: [1, 2, 3],
            },
        }),
        Underline,
        Link.configure({
            openOnClick: false,
            HTMLAttributes: {
                class: 'text-indigo-600 underline font-medium',
            },
        }),
    ],
    editorProps: {
        attributes: {
            class: 'prose prose-slate prose-sm max-w-none focus:outline-none p-4',
            style: `min-height: ${props.minHeight};`,
        },
    },
    onUpdate: () => {
        if (!editor.value) return;
        emit('update:modelValue', editor.value.getHTML());
    },
});

watch(
    () => props.modelValue,
    (newValue) => {
        if (!editor.value) return;
        const isSame = editor.value.getHTML() === newValue;
        if (!isSame) {
            editor.value.commands.setContent(newValue || '', false);
        }
    }
);

onBeforeUnmount(() => {
    if (editor.value) {
        editor.value.destroy();
    }
});

function setLink() {
    if (!editor.value) return;
    const previousUrl = editor.value.getAttributes('link').href;
    const url = window.prompt('Enter URL:', previousUrl);

    if (url === null) return;
    if (url === '') {
        editor.value.chain().focus().extendMarkRange('link').unsetLink().run();
        return;
    }

    editor.value.chain().focus().extendMarkRange('link').setLink({ href: url }).run();
}
</script>

<template>
    <div class="border border-slate-300 rounded-xl overflow-hidden focus-within:border-indigo-500 focus-within:ring-1 focus-within:ring-indigo-500 transition shadow-sm bg-white">
        <!-- Toolbar Strip -->
        <div v-if="editor" class="flex flex-wrap items-center gap-1 p-2 bg-slate-50 border-b border-slate-200 text-xs">
            <!-- Headings -->
            <button
                type="button"
                @click="editor.chain().focus().toggleHeading({ level: 1 }).run()"
                :class="[
                    'px-2 py-1 rounded font-bold transition',
                    editor.isActive('heading', { level: 1 })
                        ? 'bg-indigo-600 text-white shadow-sm'
                        : 'text-slate-700 hover:bg-slate-200'
                ]"
                title="Heading 1"
            >
                H1
            </button>
            <button
                type="button"
                @click="editor.chain().focus().toggleHeading({ level: 2 }).run()"
                :class="[
                    'px-2 py-1 rounded font-bold transition',
                    editor.isActive('heading', { level: 2 })
                        ? 'bg-indigo-600 text-white shadow-sm'
                        : 'text-slate-700 hover:bg-slate-200'
                ]"
                title="Heading 2"
            >
                H2
            </button>
            <button
                type="button"
                @click="editor.chain().focus().toggleHeading({ level: 3 }).run()"
                :class="[
                    'px-2 py-1 rounded font-bold transition',
                    editor.isActive('heading', { level: 3 })
                        ? 'bg-indigo-600 text-white shadow-sm'
                        : 'text-slate-700 hover:bg-slate-200'
                ]"
                title="Heading 3"
            >
                H3
            </button>
            <button
                type="button"
                @click="editor.chain().focus().setParagraph().run()"
                :class="[
                    'px-2 py-1 rounded font-medium transition',
                    editor.isActive('paragraph')
                        ? 'bg-slate-300 text-slate-900 font-semibold'
                        : 'text-slate-700 hover:bg-slate-200'
                ]"
                title="Normal Paragraph"
            >
                Paragraph
            </button>

            <span class="w-px h-4 bg-slate-300 mx-1"></span>

            <!-- Formatting Marks -->
            <button
                type="button"
                @click="editor.chain().focus().toggleBold().run()"
                :class="[
                    'w-7 h-7 rounded font-bold flex items-center justify-center transition',
                    editor.isActive('bold')
                        ? 'bg-indigo-600 text-white shadow-sm'
                        : 'text-slate-700 hover:bg-slate-200'
                ]"
                title="Bold (Ctrl+B)"
            >
                B
            </button>
            <button
                type="button"
                @click="editor.chain().focus().toggleItalic().run()"
                :class="[
                    'w-7 h-7 rounded italic font-serif font-bold flex items-center justify-center transition',
                    editor.isActive('italic')
                        ? 'bg-indigo-600 text-white shadow-sm'
                        : 'text-slate-700 hover:bg-slate-200'
                ]"
                title="Italic (Ctrl+I)"
            >
                I
            </button>
            <button
                type="button"
                @click="editor.chain().focus().toggleUnderline().run()"
                :class="[
                    'w-7 h-7 rounded underline font-medium flex items-center justify-center transition',
                    editor.isActive('underline')
                        ? 'bg-indigo-600 text-white shadow-sm'
                        : 'text-slate-700 hover:bg-slate-200'
                ]"
                title="Underline (Ctrl+U)"
            >
                U
            </button>
            <button
                type="button"
                @click="editor.chain().focus().toggleStrike().run()"
                :class="[
                    'w-7 h-7 rounded line-through font-medium flex items-center justify-center transition',
                    editor.isActive('strike')
                        ? 'bg-indigo-600 text-white shadow-sm'
                        : 'text-slate-700 hover:bg-slate-200'
                ]"
                title="Strikethrough"
            >
                S
            </button>

            <span class="w-px h-4 bg-slate-300 mx-1"></span>

            <!-- Lists -->
            <button
                type="button"
                @click="editor.chain().focus().toggleBulletList().run()"
                :class="[
                    'px-2 py-1 rounded flex items-center gap-1 transition',
                    editor.isActive('bulletList')
                        ? 'bg-indigo-600 text-white shadow-sm'
                        : 'text-slate-700 hover:bg-slate-200'
                ]"
                title="Bullet List"
            >
                <span>&bull;</span>
                <span>List</span>
            </button>
            <button
                type="button"
                @click="editor.chain().focus().toggleOrderedList().run()"
                :class="[
                    'px-2 py-1 rounded flex items-center gap-1 transition',
                    editor.isActive('orderedList')
                        ? 'bg-indigo-600 text-white shadow-sm'
                        : 'text-slate-700 hover:bg-slate-200'
                ]"
                title="Numbered List"
            >
                <span>1.</span>
                <span>List</span>
            </button>

            <span class="w-px h-4 bg-slate-300 mx-1"></span>

            <!-- Special Blocks -->
            <button
                type="button"
                @click="editor.chain().focus().toggleBlockquote().run()"
                :class="[
                    'px-2 py-1 rounded transition',
                    editor.isActive('blockquote')
                        ? 'bg-indigo-600 text-white shadow-sm'
                        : 'text-slate-700 hover:bg-slate-200'
                ]"
                title="Quote"
            >
                &ldquo; Quote
            </button>
            <button
                type="button"
                @click="editor.chain().focus().setHorizontalRule().run()"
                class="px-2 py-1 rounded text-slate-700 hover:bg-slate-200 transition"
                title="Divider Line"
            >
                &mdash; Divider
            </button>
            <button
                type="button"
                @click="setLink"
                :class="[
                    'px-2 py-1 rounded transition',
                    editor.isActive('link')
                        ? 'bg-indigo-600 text-white shadow-sm'
                        : 'text-slate-700 hover:bg-slate-200'
                ]"
                title="Add / Edit Link"
            >
                &#128279; Link
            </button>

            <!-- Undo / Redo -->
            <div class="ml-auto flex items-center gap-1">
                <button
                    type="button"
                    @click="editor.chain().focus().undo().run()"
                    :disabled="!editor.can().undo()"
                    class="w-7 h-7 rounded text-slate-600 hover:bg-slate-200 disabled:opacity-40 flex items-center justify-center transition"
                    title="Undo (Ctrl+Z)"
                >
                    &larr;
                </button>
                <button
                    type="button"
                    @click="editor.chain().focus().redo().run()"
                    :disabled="!editor.can().redo()"
                    class="w-7 h-7 rounded text-slate-600 hover:bg-slate-200 disabled:opacity-40 flex items-center justify-center transition"
                    title="Redo (Ctrl+Y)"
                >
                    &rarr;
                </button>
            </div>
        </div>

        <!-- Editor Content Area -->
        <editor-content :editor="editor" />
    </div>
</template>

<style>
/* Style Prosemirror Content */
.ProseMirror {
    min-height: inherit;
    outline: none;
}
.ProseMirror p.is-editor-empty:first-child::before {
    content: attr(data-placeholder);
    float: left;
    color: #94a3b8;
    pointer-events: none;
    height: 0;
}
.ProseMirror hr {
    border-color: #cbd5e1;
    margin: 1.5rem 0;
}
.ProseMirror blockquote {
    border-left: 3px solid #6366f1;
    padding-left: 1rem;
    color: #475569;
    font-style: italic;
}
</style>


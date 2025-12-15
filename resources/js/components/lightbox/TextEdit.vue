<template>
    <div
        @click="emit('close')"
        class="fixed inset-0 bg-black/60 flex items-center justify-center z-50 p-4"
    >
        <div
            @click.stop
            class="bg-white w-full max-w-2xl max-h-[90vh] flex flex-col rounded-md"
        >
            <div class="flex justify-between items-center p-4 border-b border-gray-200 relative">
                <h2 class="text-lg font-semibold text-black">Textblock bearbeiten</h2>
                <button
                    @click="emit('close')"
                    class="text-gray-400 w-8 h-8 absolute top-2 right-2 flex items-center justify-center hover:text-black transition-colors cursor-pointer rounded-full"
                >
                    <PhX class="w-5 h-5" />
                </button>
            </div>

            <div class="overflow-y-auto flex-1 p-4 space-y-4">
                <div>
                    <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">Typ</label>
                    <select
                        v-model="form.type"
                        class="w-full border border-gray-200 px-3 py-2 text-sm outline-0 focus:ring-1 focus:ring-gray-300 focus:border-gray-300 transition-colors rounded-sm"
                    >
                        <option value="text">Text</option>
                        <option value="text_large">Text (gross)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">Text</label>

                    <!-- Tiptap Toolbar -->
                    <div v-if="editor" class="border border-gray-200 border-b-0 rounded-t-sm bg-gray-50 p-1 flex flex-wrap gap-1">
                        <button
                            @click="editor.chain().focus().toggleBold().run()"
                            :class="[
                                'p-2 rounded text-sm transition-colors',
                                editor.isActive('bold') ? 'bg-gray-200 text-black' : 'text-gray-600 hover:bg-gray-200'
                            ]"
                            type="button"
                            title="Fett"
                        >
                            <PhTextB class="w-4 h-4" weight="bold" />
                        </button>
                        <button
                            @click="editor.chain().focus().toggleItalic().run()"
                            :class="[
                                'p-2 rounded text-sm transition-colors',
                                editor.isActive('italic') ? 'bg-gray-200 text-black' : 'text-gray-600 hover:bg-gray-200'
                            ]"
                            type="button"
                            title="Kursiv"
                        >
                            <PhTextItalic class="w-4 h-4" />
                        </button>

                        <div class="w-px bg-gray-300 mx-1"></div>

                        <button
                            @click="editor.chain().focus().toggleBulletList().run()"
                            :class="[
                                'p-2 rounded text-sm transition-colors',
                                editor.isActive('bulletList') ? 'bg-gray-200 text-black' : 'text-gray-600 hover:bg-gray-200'
                            ]"
                            type="button"
                            title="Aufzählung"
                        >
                            <PhListBullets class="w-4 h-4" />
                        </button>
                        <button
                            @click="editor.chain().focus().toggleOrderedList().run()"
                            :class="[
                                'p-2 rounded text-sm transition-colors',
                                editor.isActive('orderedList') ? 'bg-gray-200 text-black' : 'text-gray-600 hover:bg-gray-200'
                            ]"
                            type="button"
                            title="Nummerierte Liste"
                        >
                            <PhListNumbers class="w-4 h-4" />
                        </button>

                        <div class="w-px bg-gray-300 mx-1"></div>

                        <button
                            @click="setLink"
                            :class="[
                                'p-2 rounded text-sm transition-colors',
                                editor.isActive('link') ? 'bg-gray-200 text-black' : 'text-gray-600 hover:bg-gray-200'
                            ]"
                            type="button"
                            title="Link"
                        >
                            <PhLink class="w-4 h-4" />
                        </button>
                        <button
                            v-if="editor.isActive('link')"
                            @click="editor.chain().focus().unsetLink().run()"
                            class="p-2 rounded text-sm text-gray-600 hover:bg-gray-200 transition-colors"
                            type="button"
                            title="Link entfernen"
                        >
                            <PhLinkBreak class="w-4 h-4" />
                        </button>

                        <div class="w-px bg-gray-300 mx-1"></div>

                        <button
                            @click="editor.chain().focus().undo().run()"
                            :disabled="!editor.can().undo()"
                            class="p-2 rounded text-sm text-gray-600 hover:bg-gray-200 transition-colors disabled:opacity-30"
                            type="button"
                            title="Rückgängig"
                        >
                            <PhArrowCounterClockwise class="w-4 h-4" />
                        </button>
                        <button
                            @click="editor.chain().focus().redo().run()"
                            :disabled="!editor.can().redo()"
                            class="p-2 rounded text-sm text-gray-600 hover:bg-gray-200 transition-colors disabled:opacity-30"
                            type="button"
                            title="Wiederholen"
                        >
                            <PhArrowClockwise class="w-4 h-4" />
                        </button>
                    </div>

                    <!-- Tiptap Editor -->
                    <EditorContent
                        :editor="editor"
                        class="border border-gray-200 rounded-b-sm min-h-[200px] prose prose-sm max-w-none focus-within:border-black transition-colors"
                    />
                </div>

            </div>

            <div class="p-4 border-t border-gray-200 flex gap-3 justify-end">
                <button
                    @click="emit('close')"
                    class="px-4 py-2 text-sm text-gray-600 hover:text-black transition-colors cursor-pointer rounded-sm"
                >
                    Abbrechen
                </button>
                <button
                    @click="save"
                    class="px-4 py-2 text-sm bg-black text-white hover:bg-gray-800 transition-colors cursor-pointer rounded-sm"
                >
                    Speichern
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { PhX, PhTextB, PhTextItalic, PhListBullets, PhListNumbers, PhLink, PhLinkBreak, PhArrowCounterClockwise, PhArrowClockwise } from '@phosphor-icons/vue';
import { useEditor, EditorContent } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import Link from '@tiptap/extension-link';

const props = defineProps({
    text: {
        type: Object,
        required: true
    }
});

const emit = defineEmits(['save', 'close']);

const form = ref({
    id: null,
    type: 'text',
    text: '',
    custom_css: ''
});

const editor = useEditor({
    extensions: [
        StarterKit,
        Link.configure({
            openOnClick: false,
            HTMLAttributes: {
                target: '_blank',
                rel: 'noopener noreferrer'
            }
        })
    ],
    content: '',
    editorProps: {
        attributes: {
            class: 'px-3 py-2 min-h-[200px] focus:outline-none'
        }
    }
});

const setLink = () => {
    const previousUrl = editor.value?.getAttributes('link').href;
    const url = window.prompt('URL eingeben:', previousUrl || 'https://');

    if (url === null) return;

    if (url === '') {
        editor.value?.chain().focus().unsetLink().run();
        return;
    }

    editor.value?.chain().focus().setLink({ href: url }).run();
};

const save = () => {
    emit('save', {
        ...form.value,
        text: editor.value?.getHTML() || ''
    });
};

onMounted(() => {
    form.value = {
        id: props.text.id,
        type: props.text.type || 'text',
        text: props.text.text || '',
        custom_css: props.text.custom_css || ''
    };

    // Set initial content
    if (editor.value) {
        editor.value.commands.setContent(form.value.text);
    }
});

onBeforeUnmount(() => {
    editor.value?.destroy();
});
</script>

<style>
.ProseMirror {
    min-height: 200px;
}
.ProseMirror p {
    margin: 0.5em 0;
}
.ProseMirror ul,
.ProseMirror ol {
    padding-left: 1.5em;
    margin: 0.5em 0;
}
.ProseMirror a {
    color: #2563eb;
    text-decoration: underline;
}
</style>

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
                        class="w-full border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:border-black transition-colors rounded-sm"
                    >
                        <option value="text">Text</option>
                        <option value="text_large">Text (gross)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">Text</label>
                    <textarea
                        v-model="form.text"
                        rows="8"
                        class="w-full border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:border-black transition-colors rounded-sm resize-y"
                    ></textarea>
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
import { ref, onMounted } from 'vue';
import { PhX } from '@phosphor-icons/vue';

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

const save = () => {
    emit('save', { ...form.value });
};

onMounted(() => {
    form.value = {
        id: props.text.id,
        type: props.text.type || 'text',
        text: props.text.text || '',
        custom_css: props.text.custom_css || ''
    };
});
</script>

<template>
    <div
        @click="emit('close')"
        class="fixed inset-0 bg-black/60 flex items-center justify-center z-50 p-4"
    >
        <div
            @click.stop
            class="bg-white w-full max-w-3xl max-h-[90vh] flex flex-col rounded-md"
        >
            <div class="flex justify-between items-center p-4 border-b border-gray-200 relative">
                <h2 class="text-lg font-semibold text-black">Bild bearbeiten</h2>
                <button
                    @click="emit('close')"
                    class="text-gray-400 w-8 h-8 absolute top-2 right-2 flex items-center justify-center hover:text-black transition-colors cursor-pointer rounded-full"
                >
                    <PhX class="w-5 h-5" />
                </button>
            </div>

            <div class="overflow-y-auto flex-1 p-4">
                <div class="grid grid-cols-2 gap-6">
                    <!-- Image Preview -->
                    <div>
                        <div class="aspect-square bg-gray-100 overflow-hidden">
                            <img
                                :src="getImageUrl()"
                                :alt="form.alt || image.filename"
                                class="w-full h-full object-contain"
                            />
                        </div>
                        <p class="mt-2 text-xs text-gray-400 text-center">{{ image.filename }}</p>
                    </div>

                    <!-- Form -->
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">Titel</label>
                            <input
                                v-model="form.title"
                                type="text"
                                class="w-full border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:border-black transition-colors rounded-sm"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">Alt-Text</label>
                            <input
                                v-model="form.alt"
                                type="text"
                                class="w-full border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:border-black transition-colors rounded-sm"
                            />
                            <p class="mt-1 text-xs text-gray-400">Beschreibung für Screenreader</p>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">Bildunterschrift</label>
                            <textarea
                                v-model="form.caption"
                                rows="3"
                                class="w-full border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:border-black transition-colors rounded-sm resize-y"
                            ></textarea>
                        </div>

                        <div>
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input
                                    v-model="form.is_featured"
                                    type="checkbox"
                                    class="w-[0.875rem] h-[0.875rem] cursor-pointer accent-black"
                                />
                                <span class="flex items-center gap-2 text-sm text-gray-700">
                                    <PhStar class="w-4 h-4" :weight="form.is_featured ? 'fill' : 'regular'" />
                                    Als Hauptbild verwenden
                                </span>
                            </label>
                        </div>
                    </div>
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
import { PhX, PhStar } from '@phosphor-icons/vue';

const props = defineProps({
    image: {
        type: Object,
        required: true
    }
});

const emit = defineEmits(['save', 'close']);

const form = ref({
    id: null,
    title: '',
    alt: '',
    caption: '',
    is_featured: false
});

const save = () => {
    emit('save', { ...form.value });
};

const getImageUrl = () => {
    if (props.image.sizes?.large?.file) {
        return `/storage/uploads/${props.image.sizes.large.file}`;
    }
    return `/storage/uploads/${props.image.filename}`;
};

onMounted(() => {
    form.value = {
        id: props.image.id,
        title: props.image.title || '',
        alt: props.image.alt || '',
        caption: props.image.caption || '',
        is_featured: props.image.is_featured || false
    };
});
</script>

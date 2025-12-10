<script setup>
import { ref, onMounted, inject } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import draggable from 'vuedraggable';
import {
    PhArrowLeft,
    PhFloppyDisk,
    PhPencil,
    PhTrash,
    PhPlus,
    PhDotsSixVertical,
    PhStar,
    PhImage
} from '@phosphor-icons/vue';
import ImageEdit from '../lightbox/ImageEdit.vue';
import TextEdit from '../lightbox/TextEdit.vue';
import ImageGallery from '../lightbox/ImageGallery.vue';

const route = useRoute();
const router = useRouter();
const showToast = inject('showToast');

const project = ref(null);
const categories = ref([]);
const loading = ref(true);
const saving = ref(false);

const editingImage = ref(null);
const editingText = ref(null);
const showGallery = ref(false);

const fetchProject = async () => {
    loading.value = true;
    try {
        const response = await axios.get(`/api/projects/${route.params.id}`);
        project.value = response.data;
    } catch (error) {
        console.error('Error fetching project:', error);
        showToast('Fehler beim Laden des Projekts', 'error');
        router.push({ name: 'projects.index' });
    } finally {
        loading.value = false;
    }
};

const fetchCategories = async () => {
    try {
        const response = await axios.get('/api/categories');
        categories.value = response.data;
    } catch (error) {
        console.error('Error fetching categories:', error);
    }
};

const saveProject = async () => {
    saving.value = true;
    try {
        const data = {
            title: project.value.title,
            slug: project.value.slug,
            year: project.value.year,
            status: project.value.status,
            steckbrief: project.value.steckbrief,
            publish_status: project.value.publish_status,
            categories: project.value.categories.map(c => c.id)
        };
        await axios.put(`/api/projects/${project.value.id}`, data);
        showToast('Projekt gespeichert', 'success');
    } catch (error) {
        console.error('Error saving project:', error);
        showToast('Fehler beim Speichern', 'error');
    } finally {
        saving.value = false;
    }
};

const toggleCategory = (category) => {
    const index = project.value.categories.findIndex(c => c.id === category.id);
    if (index > -1) {
        project.value.categories.splice(index, 1);
    } else {
        project.value.categories.push(category);
    }
};

const isCategorySelected = (category) => {
    return project.value.categories.some(c => c.id === category.id);
};

// Text blocks
const addTextBlock = async () => {
    try {
        const response = await axios.post(`/api/projects/${project.value.id}/texts`, {
            type: 'text',
            text: ''
        });
        project.value.texts.push(response.data);
        editingText.value = response.data;
    } catch (error) {
        console.error('Error adding text block:', error);
        showToast('Fehler beim Hinzufügen', 'error');
    }
};

const saveText = async (text) => {
    try {
        await axios.put(`/api/projects/${project.value.id}/texts/${text.id}`, text);
        const index = project.value.texts.findIndex(t => t.id === text.id);
        if (index > -1) {
            project.value.texts[index] = text;
        }
        editingText.value = null;
        showToast('Textblock gespeichert', 'success');
    } catch (error) {
        console.error('Error saving text:', error);
        showToast('Fehler beim Speichern', 'error');
    }
};

const deleteText = async (text) => {
    if (!confirm('Textblock wirklich löschen?')) return;
    try {
        await axios.delete(`/api/projects/${project.value.id}/texts/${text.id}`);
        project.value.texts = project.value.texts.filter(t => t.id !== text.id);
        showToast('Textblock gelöscht', 'success');
    } catch (error) {
        console.error('Error deleting text:', error);
        showToast('Fehler beim Löschen', 'error');
    }
};

// Images
const saveImage = async (image) => {
    try {
        await axios.put(`/api/projects/${project.value.id}/images/${image.id}`, image);
        const index = project.value.images.findIndex(i => i.id === image.id);
        if (index > -1) {
            // Merge saved data with existing image to preserve filename, sizes, etc.
            project.value.images[index] = { ...project.value.images[index], ...image };
        }
        // Update featured image if changed
        if (image.is_featured) {
            project.value.images.forEach((img, i) => {
                if (img.id !== image.id) {
                    project.value.images[i].is_featured = false;
                }
            });
        }
        editingImage.value = null;
        showToast('Bild gespeichert', 'success');
    } catch (error) {
        console.error('Error saving image:', error);
        showToast('Fehler beim Speichern', 'error');
    }
};

const deleteImage = async (image) => {
    if (!confirm('Bild wirklich löschen?')) return;
    try {
        await axios.delete(`/api/projects/${project.value.id}/images/${image.id}`);
        project.value.images = project.value.images.filter(i => i.id !== image.id);
        showToast('Bild gelöscht', 'success');
    } catch (error) {
        console.error('Error deleting image:', error);
        showToast('Fehler beim Löschen', 'error');
    }
};

const onImagesReorder = async () => {
    try {
        const reordered = project.value.images.map((img, index) => ({
            id: img.id,
            position: index
        }));
        await axios.put(`/api/projects/${project.value.id}/images/reorder`, { images: reordered });
    } catch (error) {
        console.error('Error reordering images:', error);
    }
};

const getImageUrl = (image, size = 'medium') => {
    if (image.sizes?.[size]?.file) {
        return `/storage/uploads/${image.sizes[size].file}`;
    }
    return `/storage/uploads/${image.filename}`;
};

onMounted(() => {
    fetchProject();
    fetchCategories();
});
</script>

<template>
    <div class="flex-1 container mx-auto p-6">
        <!-- Header -->
        <div class="mb-8 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <button
                    @click="router.push({ name: 'projects.index' })"
                    class="cursor-pointer text-gray-400 hover:text-gray-600 transition-colors">
                    <PhArrowLeft class="h-5 w-5" />
                </button>
                <h1 class="text-2xl font-semibold text-gray-900">
                    {{ project?.title || 'Projekt bearbeiten' }}
                </h1>
            </div>
            <button
                @click="saveProject"
                :disabled="saving"
                class="flex items-center gap-2 px-4 py-2 bg-black text-white rounded-sm hover:bg-gray-800 disabled:opacity-50 transition-colors"
            >
                {{ saving ? 'Speichern...' : 'Speichern' }}
            </button>
        </div>

        <div v-if="loading" class="text-center py-12 text-gray-500">
            Laden...
        </div>

        <div v-else-if="project" class="grid grid-cols-3 gap-12">
            <!-- Main Content -->
            <div class="col-span-2 space-y-12">
                <!-- Basic Info -->
                <div class="bg-gray-50 p-4 pt-2 rounded-sm">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">Daten</h2>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Titel</label>
                            <input
                                v-model="project.title"
                                type="text"
                                class="w-full px-3 py-2 border border-gray-300 rounded-sm focus:ring-1 focus:ring-black focus:border-black"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Slug</label>
                            <input
                                v-model="project.slug"
                                type="text"
                                class="w-full px-3 py-2 border border-gray-300 rounded-sm focus:ring-1 focus:ring-black focus:border-black"
                            />
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Jahr</label>
                                <input
                                    v-model="project.year"
                                    type="text"
                                    maxlength="4"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-sm focus:ring-1 focus:ring-black focus:border-black"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                <input
                                    v-model="project.status"
                                    type="text"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-sm focus:ring-1 focus:ring-black focus:border-black"
                                />
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Steckbrief</label>
                            <textarea
                                v-model="project.steckbrief"
                                rows="6"
                                class="w-full px-3 py-2 border border-gray-300 rounded-sm focus:ring-1 focus:ring-black focus:border-black"
                            ></textarea>
                        </div>
                    </div>
                </div>

                <!-- Text Blocks -->
                <div class="bg-gray-50 p-4 pt-2 rounded-sm">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-medium text-gray-900">Textblöcke</h2>
                        <button
                            @click="addTextBlock"
                            class="flex items-center gap-2 px-3 py-1.5 text-sm border border-gray-300 rounded-sm hover:bg-gray-50 transition-colors"
                        >
                            <PhPlus class="h-4 w-4" />
                            Hinzufügen
                        </button>
                    </div>
                    <div v-if="project.texts.length === 0" class="text-center py-8 text-gray-500">
                        Keine Textblöcke vorhanden
                    </div>
                    <div v-else class="space-y-4 divide-y divide-gray-200">
                        <div
                            v-for="text in project.texts"
                            :key="text.id"
                            class="flex items-start gap-3 pb-6"
                        >
                            <div class="flex-1">
                                <span class="text-xs font-medium text-gray-500 uppercase">{{ text.type }}</span>
                                <p class="text-sm text-gray-900 mt-1 line-clamp-2">
                                    {{ text.text || '(Leer)' }}
                                </p>
                            </div>
                            <div class="flex items-center gap-1">
                                <button
                                    @click="editingText = text"
                                    class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-white rounded transition-colors"
                                >
                                    <PhPencil class="h-4 w-4" />
                                </button>
                                <button
                                    @click="deleteText(text)"
                                    class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-white rounded transition-colors"
                                >
                                    <PhTrash class="h-4 w-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Images -->
                <div class="bg-gray-50 p-4 pt-2 rounded-sm">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-medium text-gray-900">Bilder ({{ project.images.length }})</h2>
                        <button
                            @click="showGallery = true"
                            class="flex items-center gap-2 px-3 py-1.5 text-sm border border-gray-300 rounded-sm hover:bg-gray-50 transition-colors"
                        >
                            <PhImage class="h-4 w-4" />
                            Galerie
                        </button>
                    </div>
                    <div v-if="project.images.length === 0" class="text-center py-8 text-gray-500">
                        Keine Bilder vorhanden
                    </div>
                    <draggable
                        v-else
                        v-model="project.images"
                        item-key="id"
                        class="grid grid-cols-8 gap-4"
                        @end="onImagesReorder"
                    >
                        <template #item="{ element: image }">
                            <div class="relative group cursor-move">
                                <div class="aspect-square bg-gray-100 rounded-sm overflow-hidden">
                                    <img
                                        :src="getImageUrl(image, 'thumbnail')"
                                        :alt="image.alt || image.title"
                                        class="w-full h-full object-cover"
                                    />
                                </div>
                                <div
                                    v-if="image.is_featured"
                                    class="absolute top-2 left-2 p-1 bg-yellow-400 rounded"
                                >
                                    <PhStar class="h-3 w-3 text-white" weight="fill" />
                                </div>
                                <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity rounded-sm flex items-center justify-center gap-2">
                                    <button
                                        @click="editingImage = image"
                                        class="p-2 bg-white rounded-sm text-gray-700 hover:bg-gray-100"
                                    >
                                        <PhPencil class="h-4 w-4" />
                                    </button>
                                    <button
                                        @click="deleteImage(image)"
                                        class="p-2 bg-white rounded-sm text-red-600 hover:bg-red-50"
                                    >
                                        <PhTrash class="h-4 w-4" />
                                    </button>
                                </div>
                            </div>
                        </template>
                    </draggable>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Publish Status -->
                <div class="bg-gray-50 p-4 pt-2 rounded-sm">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">Veröffentlichung</h2>
                    <div class="space-y-2">
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input
                                v-model="project.publish_status"
                                type="radio"
                                value="publish"
                                class="w-4 h-4 text-black focus:ring-black"
                            />
                            <span class="text-sm text-gray-900">Veröffentlicht</span>
                        </label>
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input
                                v-model="project.publish_status"
                                type="radio"
                                value="draft"
                                class="w-4 h-4 text-black focus:ring-black"
                            />
                            <span class="text-sm text-gray-900">Entwurf</span>
                        </label>
                    </div>
                </div>

                <!-- Categories -->
                <div class="bg-gray-50 p-4 pt-2 rounded-sm">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">Kategorien</h2>
                    <div class="space-y-2">
                        <label
                            v-for="category in categories"
                            :key="category.id"
                            class="flex items-center gap-3 cursor-pointer"
                        >
                            <input
                                type="checkbox"
                                :checked="isCategorySelected(category)"
                                @change="toggleCategory(category)"
                                class="w-4 h-4 text-black focus:ring-black rounded"
                            />
                            <span class="text-sm text-gray-900">{{ category.name }}</span>
                        </label>
                    </div>
                </div>

            </div>
        </div>

        <!-- Lightboxes -->
        <ImageEdit
            v-if="editingImage"
            :image="editingImage"
            @save="saveImage"
            @close="editingImage = null"
        />

        <TextEdit
            v-if="editingText"
            :text="editingText"
            @save="saveText"
            @close="editingText = null"
        />

        <ImageGallery
            v-if="showGallery"
            :images="project.images"
            @close="showGallery = false"
        />
    </div>
</template>

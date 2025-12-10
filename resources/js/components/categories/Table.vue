<template>
    <div class="flex-1 container mx-auto p-6">
        <!-- Header -->
        <div class="mb-8 flex justify-between items-start">
            <div>
                <h1 class="text-2xl font-semibold text-black mb-1">Kategorien</h1>
                <p class="text-sm text-gray-500">{{ categories.length }} Kategorien</p>
            </div>
            <router-link
                :to="{ name: 'categories.create' }"
                class="bg-black text-white text-sm px-3 py-2 hover:bg-gray-800 transition-colors cursor-pointer rounded-sm flex items-center gap-1"
            >
                <PhPlus class="w-4 h-4" />
                Hinzufügen
            </router-link>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="text-center py-16 text-gray-400">
            Lädt...
        </div>

        <!-- Table -->
        <div v-else class="overflow-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="py-3 pr-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="py-3 px-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Slug</th>
                        <th class="py-3 px-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Projekte</th>
                        <th class="py-3 pl-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider w-24"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <tr
                        v-for="category in categories"
                        :key="category.id"
                        class="hover:bg-gray-50 transition-colors"
                    >
                        <td class="py-4 pr-2">
                            <span class="text-sm font-medium text-black">{{ category.name }}</span>
                        </td>
                        <td class="py-4 px-2 text-sm text-gray-500">
                            {{ category.slug }}
                        </td>
                        <td class="py-4 px-2 text-sm text-right text-gray-900 tabular-nums">
                            {{ category.projects_count || 0 }}
                        </td>
                        <td class="py-4 pl-2 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <router-link
                                    :to="{ name: 'categories.edit', params: { id: category.id } }"
                                    class="text-gray-400 hover:text-black transition-colors cursor-pointer"
                                    title="Bearbeiten"
                                >
                                    <PhPencilSimple class="w-5 h-5" />
                                </router-link>
                                <button
                                    @click="confirmDelete(category)"
                                    class="text-gray-400 hover:text-red-500 transition-colors cursor-pointer"
                                    title="Löschen"
                                >
                                    <PhTrash class="w-5 h-5" />
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
            <div v-if="categories.length === 0" class="text-center py-16 text-gray-400">
                Keine Kategorien vorhanden
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Lightbox -->
    <div
        v-if="deleteConfirmation"
        class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
        @click.self="deleteConfirmation = null"
    >
        <div class="bg-white p-6 rounded-sm shadow-lg max-w-md w-full mx-4">
            <h3 class="text-lg font-semibold text-black mb-4">Kategorie löschen</h3>
            <p class="text-sm text-gray-600 mb-6">
                Sind Sie sicher, dass Sie "{{ deleteConfirmation.name }}" löschen möchten? Diese Aktion kann nicht rückgängig gemacht werden.
            </p>
            <div class="flex justify-end gap-3">
                <button
                    @click="deleteConfirmation = null"
                    class="px-4 py-2 text-sm text-gray-600 hover:text-black transition-colors cursor-pointer"
                >
                    Abbrechen
                </button>
                <button
                    @click="deleteCategory"
                    class="px-4 py-2 text-sm bg-red-500 text-white hover:bg-red-600 transition-colors cursor-pointer rounded-sm"
                >
                    Löschen
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, inject } from 'vue';
import axios from 'axios';
import { PhPencilSimple, PhTrash, PhPlus } from '@phosphor-icons/vue';

const showToast = inject('showToast');

const categories = ref([]);
const loading = ref(true);
const deleteConfirmation = ref(null);

const confirmDelete = (category) => {
    deleteConfirmation.value = category;
};

const deleteCategory = async () => {
    if (!deleteConfirmation.value) return;

    try {
        await axios.delete(`/api/categories/${deleteConfirmation.value.id}`);
        categories.value = categories.value.filter(c => c.id !== deleteConfirmation.value.id);
        showToast('Kategorie gelöscht', 'success');
    } catch (error) {
        console.error('Error deleting category:', error);
        showToast('Fehler beim Löschen', 'error');
    } finally {
        deleteConfirmation.value = null;
    }
};

onMounted(async () => {
    try {
        const response = await axios.get('/api/categories');
        categories.value = response.data;
    } catch (error) {
        console.error('Error fetching categories:', error);
        showToast('Fehler beim Laden der Kategorien', 'error');
    } finally {
        loading.value = false;
    }
});
</script>

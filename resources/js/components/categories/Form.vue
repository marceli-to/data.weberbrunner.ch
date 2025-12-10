<script setup>
import { ref, computed, onMounted, inject } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import { PhArrowLeft, PhFloppyDisk } from '@phosphor-icons/vue';

const route = useRoute();
const router = useRouter();
const showToast = inject('showToast');

const category = ref({
    name: '',
    slug: ''
});
const loading = ref(false);
const saving = ref(false);

const isEditing = computed(() => !!route.params.id);

const fetchCategory = async () => {
    if (!isEditing.value) return;

    loading.value = true;
    try {
        const response = await axios.get(`/api/categories/${route.params.id}`);
        category.value = response.data;
    } catch (error) {
        console.error('Error fetching category:', error);
        showToast('Fehler beim Laden der Kategorie', 'error');
        router.push({ name: 'categories.index' });
    } finally {
        loading.value = false;
    }
};

const saveCategory = async () => {
    saving.value = true;
    try {
        if (isEditing.value) {
            await axios.put(`/api/categories/${category.value.id}`, {
                name: category.value.name,
                slug: category.value.slug
            });
            showToast('Kategorie gespeichert', 'success');
        } else {
            await axios.post('/api/categories', {
                name: category.value.name,
                slug: category.value.slug || undefined
            });
            showToast('Kategorie erstellt', 'success');
            router.push({ name: 'categories.index' });
        }
    } catch (error) {
        console.error('Error saving category:', error);
        const message = error.response?.data?.message || 'Fehler beim Speichern';
        showToast(message, 'error');
    } finally {
        saving.value = false;
    }
};

const generateSlug = () => {
    category.value.slug = category.value.name
        .toLowerCase()
        .replace(/[äöüß]/g, (match) => {
            const map = { 'ä': 'ae', 'ö': 'oe', 'ü': 'ue', 'ß': 'ss' };
            return map[match];
        })
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-|-$/g, '');
};

onMounted(fetchCategory);
</script>

<template>
    <div class="flex-1 container mx-auto p-6">
        <!-- Header -->
        <div class="mb-8 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <button
                    @click="router.push({ name: 'categories.index' })"
                    class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors"
                >
                    <PhArrowLeft class="h-5 w-5" />
                </button>
                <h1 class="text-2xl font-semibold text-gray-900">
                    {{ isEditing ? 'Kategorie bearbeiten' : 'Neue Kategorie' }}
                </h1>
            </div>
            <button
                @click="saveCategory"
                :disabled="saving || !category.name"
                class="flex items-center gap-2 px-4 py-2 bg-black text-white rounded-lg hover:bg-gray-800 disabled:opacity-50 transition-colors"
            >
                <PhFloppyDisk class="h-5 w-5" />
                {{ saving ? 'Speichern...' : 'Speichern' }}
            </button>
        </div>

        <div v-if="loading" class="text-center py-12 text-gray-500">
            Laden...
        </div>

        <div v-else class="max-w-xl">
            <div class="bg-gray-50 p-4 rounded-sm">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                        <input
                            v-model="category.name"
                            @blur="!category.slug && generateSlug()"
                            type="text"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-1 focus:ring-black focus:border-black"
                            placeholder="z.B. Architektur"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Slug</label>
                        <div class="flex gap-2">
                            <input
                                v-model="category.slug"
                                type="text"
                                class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-1 focus:ring-black focus:border-black"
                                placeholder="z.B. architektur"
                            />
                            <button
                                @click="generateSlug"
                                type="button"
                                class="px-3 py-2 text-sm border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
                            >
                                Generieren
                            </button>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">
                            Wird automatisch aus dem Namen generiert, wenn leer gelassen.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

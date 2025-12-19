<script setup>
import { ref, computed, onMounted, inject } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import draggable from 'vuedraggable';
import {
    PhArrowLeft,
    PhFloppyDisk,
    PhPencil,
    PhTrash,
    PhPlus,
    PhDotsSixVertical
} from '@phosphor-icons/vue';

const route = useRoute();
const router = useRouter();
const showToast = inject('showToast');

const rawData = ref(null);
const loading = ref(true);
const saving = ref(false);

const editingMeta = ref(null);
const editingAttribute = ref(null);

const fetchRawData = async () => {
    loading.value = true;
    try {
        const response = await axios.get(`/api/raw-data/${route.params.id}`);
        rawData.value = response.data;
    } catch (error) {
        console.error('Error fetching raw data:', error);
        showToast('Fehler beim Laden der Rohdaten', 'error');
        router.push({ name: 'rawdata.index' });
    } finally {
        loading.value = false;
    }
};

const saveRawData = async () => {
    saving.value = true;
    try {
        const data = {
            number: rawData.value.number,
            title: rawData.value.title,
        };
        await axios.put(`/api/raw-data/${rawData.value.id}`, data);
        showToast('Rohdaten gespeichert', 'success');
    } catch (error) {
        console.error('Error saving raw data:', error);
        showToast('Fehler beim Speichern', 'error');
    } finally {
        saving.value = false;
    }
};

// Meta
const addMeta = () => {
    editingMeta.value = { id: null, label: '', value: '' };
};

const saveMeta = async (meta) => {
    try {
        if (meta.id) {
            await axios.put(`/api/raw-data/${rawData.value.id}/meta/${meta.id}`, meta);
            const index = rawData.value.meta.findIndex(m => m.id === meta.id);
            if (index > -1) {
                rawData.value.meta[index] = meta;
            }
        } else {
            const response = await axios.post(`/api/raw-data/${rawData.value.id}/meta`, {
                label: meta.label,
                value: meta.value
            });
            rawData.value.meta.push(response.data);
        }
        editingMeta.value = null;
        showToast('Meta gespeichert', 'success');
    } catch (error) {
        console.error('Error saving meta:', error);
        showToast('Fehler beim Speichern', 'error');
    }
};

const deleteMeta = async (meta) => {
    if (!confirm('Meta wirklich löschen?')) return;
    try {
        await axios.delete(`/api/raw-data/${rawData.value.id}/meta/${meta.id}`);
        rawData.value.meta = rawData.value.meta.filter(m => m.id !== meta.id);
        showToast('Meta gelöscht', 'success');
    } catch (error) {
        console.error('Error deleting meta:', error);
        showToast('Fehler beim Löschen', 'error');
    }
};

const onMetaReorder = async () => {
    try {
        const reordered = rawData.value.meta.map((meta, index) => ({
            id: meta.id,
            position: index
        }));
        await axios.put(`/api/raw-data/${rawData.value.id}/meta/reorder`, { meta: reordered });
    } catch (error) {
        console.error('Error reordering meta:', error);
    }
};

// Attributes
const addAttribute = () => {
    editingAttribute.value = { id: null, group_key: '', label: '', value: '' };
};

const saveAttribute = async (attribute) => {
    try {
        if (attribute.id) {
            await axios.put(`/api/raw-data/${rawData.value.id}/attributes/${attribute.id}`, attribute);
            const index = rawData.value.attributes.findIndex(a => a.id === attribute.id);
            if (index > -1) {
                rawData.value.attributes[index] = attribute;
            }
        } else {
            const response = await axios.post(`/api/raw-data/${rawData.value.id}/attributes`, {
                group_key: attribute.group_key || null,
                label: attribute.label,
                value: attribute.value
            });
            rawData.value.attributes.push(response.data);
        }
        editingAttribute.value = null;
        showToast('Attribut gespeichert', 'success');
    } catch (error) {
        console.error('Error saving attribute:', error);
        showToast('Fehler beim Speichern', 'error');
    }
};

const deleteAttribute = async (attribute) => {
    if (!confirm('Attribut wirklich löschen?')) return;
    try {
        await axios.delete(`/api/raw-data/${rawData.value.id}/attributes/${attribute.id}`);
        rawData.value.attributes = rawData.value.attributes.filter(a => a.id !== attribute.id);
        showToast('Attribut gelöscht', 'success');
    } catch (error) {
        console.error('Error deleting attribute:', error);
        showToast('Fehler beim Löschen', 'error');
    }
};

const onAttributesReorder = async () => {
    try {
        const reordered = rawData.value.attributes.map((attr, index) => ({
            id: attr.id,
            position: index
        }));
        await axios.put(`/api/raw-data/${rawData.value.id}/attributes/reorder`, { attributes: reordered });
    } catch (error) {
        console.error('Error reordering attributes:', error);
    }
};

// Group attributes by group_key
const groupedAttributes = computed(() => {
    if (!rawData.value?.attributes) return [];
    
    const groups = {};
    rawData.value.attributes.forEach(attr => {
        const key = attr.group_key || '';
        if (!groups[key]) {
            groups[key] = [];
        }
        groups[key].push(attr);
    });
    
    return Object.entries(groups).map(([key, items]) => ({
        key,
        items
    }));
});

onMounted(() => {
    fetchRawData();
});
</script>

<template>
    <div class="flex-1 container mx-auto p-6">
        <!-- Header -->
        <div class="mb-8 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <button
                    @click="router.push({ name: 'rawdata.index' })"
                    class="cursor-pointer text-gray-400 hover:text-gray-600 transition-colors">
                    <PhArrowLeft class="h-5 w-5" />
                </button>
                <h1 class="text-2xl font-semibold text-gray-900">
                    {{ rawData?.title || 'Rohdaten bearbeiten' }}
                </h1>
            </div>
            <button
                @click="saveRawData"
                :disabled="saving"
                class="flex items-center gap-2 px-4 py-2 bg-black text-white rounded-sm hover:bg-gray-800 disabled:opacity-50 transition-colors"
            >
                {{ saving ? 'Speichern...' : 'Speichern' }}
            </button>
        </div>

        <div v-if="loading" class="text-center py-12 text-gray-500">
            Laden...
        </div>

        <div v-else-if="rawData" class="grid grid-cols-3 gap-12">
            <!-- Main Content -->
            <div class="col-span-2 space-y-12">
                <!-- Basic Info -->
                <div class="bg-gray-50/80 p-4 pt-2 rounded-sm">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">Daten</h2>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nummer</label>
                            <input
                                v-model="rawData.number"
                                type="text"
                                class="w-full px-3 py-2 border border-gray-300 rounded-sm outline-0 focus:ring-1 focus:ring-gray-300 focus:border-gray-300"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Titel</label>
                            <input
                                v-model="rawData.title"
                                type="text"
                                class="w-full px-3 py-2 border border-gray-300 rounded-sm outline-0 focus:ring-1 focus:ring-gray-300 focus:border-gray-300"
                            />
                        </div>
                    </div>
                </div>

                <!-- Meta -->
                <div class="bg-gray-50/80 p-4 pt-2 rounded-sm">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-medium text-gray-900">Meta</h2>
                        <button
                            @click="addMeta"
                            class="flex items-center gap-2 px-3 py-1.5 text-sm border border-gray-300 rounded-sm hover:bg-gray-50 transition-colors"
                        >
                            <PhPlus class="h-4 w-4" />
                            Hinzufügen
                        </button>
                    </div>
                    <div v-if="!rawData.meta || rawData.meta.length === 0" class="text-center py-8 text-gray-500">
                        Keine Meta-Daten vorhanden
                    </div>
                    <draggable
                        v-else
                        v-model="rawData.meta"
                        item-key="id"
                        handle=".drag-handle"
                        class="space-y-2"
                        @end="onMetaReorder"
                    >
                        <template #item="{ element: meta }">
                            <div class="flex items-center gap-3 bg-white p-3 rounded-sm border border-gray-200">
                                <div class="drag-handle cursor-move text-gray-400 hover:text-gray-600">
                                    <PhDotsSixVertical class="h-5 w-5" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="text-sm font-medium text-gray-900">{{ meta.label || '(Kein Label)' }}</div>
                                    <div class="text-sm text-gray-500 truncate">{{ meta.value || '(Kein Wert)' }}</div>
                                </div>
                                <div class="flex items-center gap-1">
                                    <button
                                        @click="editingMeta = { ...meta }"
                                        class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded transition-colors"
                                    >
                                        <PhPencil class="h-4 w-4" />
                                    </button>
                                    <button
                                        @click="deleteMeta(meta)"
                                        class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-gray-100 rounded transition-colors"
                                    >
                                        <PhTrash class="h-4 w-4" />
                                    </button>
                                </div>
                            </div>
                        </template>
                    </draggable>
                </div>

                <!-- Attributes -->
                <div class="bg-gray-50/80 p-4 pt-2 rounded-sm">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-medium text-gray-900">Stammdaten</h2>
                        <button
                            @click="addAttribute"
                            class="flex items-center gap-2 px-3 py-1.5 text-sm border border-gray-300 rounded-sm hover:bg-gray-50 transition-colors"
                        >
                            <PhPlus class="h-4 w-4" />
                            Hinzufügen
                        </button>
                    </div>
                    <div v-if="!rawData.attributes || rawData.attributes.length === 0" class="text-center py-8 text-gray-500">
                        Keine Attribute vorhanden
                    </div>
                    <div v-else class="space-y-4">
                        <div v-for="group in groupedAttributes" :key="group.key" class="space-y-2">
                            <div v-if="group.key" class="text-xs font-medium text-gray-500 uppercase tracking-wider px-1">
                                {{ group.key }}
                            </div>
                            <draggable
                                v-model="group.items"
                                item-key="id"
                                handle=".drag-handle"
                                class="space-y-2"
                                @end="onAttributesReorder"
                            >
                                <template #item="{ element: attribute }">
                                    <div class="flex items-center gap-3 bg-white p-3 rounded-sm border border-gray-200">
                                        <div class="drag-handle cursor-move text-gray-400 hover:text-gray-600">
                                            <PhDotsSixVertical class="h-5 w-5" />
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="text-sm font-medium text-gray-900">{{ attribute.label || '(Kein Label)' }}</div>
                                            <div class="text-sm text-gray-500 truncate">{{ attribute.value || '(Kein Wert)' }}</div>
                                        </div>
                                        <div class="flex items-center gap-1">
                                            <button
                                                @click="editingAttribute = { ...attribute }"
                                                class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded transition-colors"
                                            >
                                                <PhPencil class="h-4 w-4" />
                                            </button>
                                            <button
                                                @click="deleteAttribute(attribute)"
                                                class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-gray-100 rounded transition-colors"
                                            >
                                                <PhTrash class="h-4 w-4" />
                                            </button>
                                        </div>
                                    </div>
                                </template>
                            </draggable>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar (empty for now, but keeps layout consistent) -->
            <div class="space-y-6">
            </div>
        </div>

        <!-- Meta Edit Modal -->
        <div
            v-if="editingMeta"
            class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
            @click.self="editingMeta = null"
        >
            <div class="bg-white rounded-sm p-6 w-full max-w-md">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Meta bearbeiten</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Label</label>
                        <input
                            v-model="editingMeta.label"
                            type="text"
                            class="w-full px-3 py-2 border border-gray-300 rounded-sm outline-0 focus:ring-1 focus:ring-gray-300 focus:border-gray-300"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Wert</label>
                        <textarea
                            v-model="editingMeta.value"
                            rows="3"
                            class="w-full px-3 py-2 border border-gray-300 rounded-sm outline-0 focus:ring-1 focus:ring-gray-300 focus:border-gray-300"
                        ></textarea>
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-6">
                    <button
                        @click="editingMeta = null"
                        class="px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-sm transition-colors"
                    >
                        Abbrechen
                    </button>
                    <button
                        @click="saveMeta(editingMeta)"
                        class="px-4 py-2 text-sm bg-black text-white rounded-sm hover:bg-gray-800 transition-colors"
                    >
                        Speichern
                    </button>
                </div>
            </div>
        </div>

        <!-- Attribute Edit Modal -->
        <div
            v-if="editingAttribute"
            class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
            @click.self="editingAttribute = null"
        >
            <div class="bg-white rounded-sm p-6 w-full max-w-md">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Attribut bearbeiten</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Gruppe</label>
                        <input
                            v-model="editingAttribute.group_key"
                            type="text"
                            placeholder="z.B. Fachplaner"
                            class="w-full px-3 py-2 border border-gray-300 rounded-sm outline-0 focus:ring-1 focus:ring-gray-300 focus:border-gray-300"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Label</label>
                        <input
                            v-model="editingAttribute.label"
                            type="text"
                            class="w-full px-3 py-2 border border-gray-300 rounded-sm outline-0 focus:ring-1 focus:ring-gray-300 focus:border-gray-300"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Wert</label>
                        <textarea
                            v-model="editingAttribute.value"
                            rows="3"
                            class="w-full px-3 py-2 border border-gray-300 rounded-sm outline-0 focus:ring-1 focus:ring-gray-300 focus:border-gray-300"
                        ></textarea>
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-6">
                    <button
                        @click="editingAttribute = null"
                        class="px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-sm transition-colors"
                    >
                        Abbrechen
                    </button>
                    <button
                        @click="saveAttribute(editingAttribute)"
                        class="px-4 py-2 text-sm bg-black text-white rounded-sm hover:bg-gray-800 transition-colors"
                    >
                        Speichern
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

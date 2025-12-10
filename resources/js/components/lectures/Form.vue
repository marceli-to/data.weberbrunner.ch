<script setup>
import { ref, computed, onMounted, inject } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import { PhArrowLeft } from '@phosphor-icons/vue';

const route = useRoute();
const router = useRouter();
const showToast = inject('showToast');

const isNew = computed(() => !route.params.id);
const lecture = ref({
    year: new Date().getFullYear(),
    title: '',
    publish: true,
});
const loading = ref(false);
const saving = ref(false);

const fetchLecture = async () => {
    if (isNew.value) return;

    loading.value = true;
    try {
        const response = await axios.get(`/api/lectures/${route.params.id}`);
        lecture.value = response.data;
    } catch (error) {
        console.error('Error fetching lecture entry:', error);
        showToast('Fehler beim Laden', 'error');
        router.push({ name: 'lectures.index' });
    } finally {
        loading.value = false;
    }
};

const saveLecture = async () => {
    saving.value = true;
    try {
        if (isNew.value) {
            const response = await axios.post('/api/lectures', lecture.value);
            showToast('Vortrag erstellt', 'success');
            router.push({ name: 'lectures.edit', params: { id: response.data.id } });
        } else {
            await axios.put(`/api/lectures/${lecture.value.id}`, lecture.value);
            showToast('Vortrag gespeichert', 'success');
        }
    } catch (error) {
        console.error('Error saving lecture entry:', error);
        if (error.response?.data?.errors) {
            const firstError = Object.values(error.response.data.errors)[0][0];
            showToast(firstError, 'error');
        } else {
            showToast('Fehler beim Speichern', 'error');
        }
    } finally {
        saving.value = false;
    }
};

onMounted(() => {
    fetchLecture();
});
</script>

<template>
    <div class="flex-1 container mx-auto p-6">
        <!-- Header -->
        <div class="mb-8 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <button
                    @click="router.push({ name: 'lectures.index' })"
                    class="cursor-pointer text-gray-400 hover:text-gray-600 transition-colors">
                    <PhArrowLeft class="h-5 w-5" />
                </button>
                <h1 class="text-2xl font-semibold text-gray-900">
                    {{ isNew ? 'Neuer Vortrag' : 'Vortrag bearbeiten' }}
                </h1>
            </div>
            <button
                @click="saveLecture"
                :disabled="saving"
                class="flex items-center gap-2 px-4 py-2 bg-black text-white rounded-sm hover:bg-gray-800 disabled:opacity-50 transition-colors"
            >
                {{ saving ? 'Speichern...' : 'Speichern' }}
            </button>
        </div>

        <div v-if="loading" class="text-center py-12 text-gray-500">
            Laden...
        </div>

        <div v-else class="grid grid-cols-3 gap-12">
            <!-- Main Content -->
            <div class="col-span-2 space-y-12">
                <!-- Basic Info -->
                <div class="bg-gray-50 p-4 pt-2 rounded-sm">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">Daten</h2>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Jahr *</label>
                            <input
                                v-model="lecture.year"
                                type="number"
                                min="1900"
                                max="2100"
                                class="w-full px-3 py-2 border border-gray-300 rounded-sm focus:ring-1 focus:ring-black focus:border-black"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Vortrag *</label>
                            <textarea
                                v-model="lecture.title"
                                rows="4"
                                placeholder="z.B. Vortrag «Zukunftsfähige Baukultur», Holzbau-Fachkongress am Bodensee"
                                class="w-full px-3 py-2 border border-gray-300 rounded-sm focus:ring-1 focus:ring-black focus:border-black"
                            ></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Publish Status -->
                <div class="bg-gray-50 p-4 pt-2 rounded-sm">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">Status</h2>
                    <div class="space-y-2">
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input
                                v-model="lecture.publish"
                                type="radio"
                                :value="true"
                                class="w-4 h-4 text-black focus:ring-black"
                            />
                            <span class="text-sm text-gray-900">Aktiv</span>
                        </label>
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input
                                v-model="lecture.publish"
                                type="radio"
                                :value="false"
                                class="w-4 h-4 text-black focus:ring-black"
                            />
                            <span class="text-sm text-gray-900">Entwurf</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, inject } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import { PhArrowLeft, PhFloppyDisk, PhUser } from '@phosphor-icons/vue';

const route = useRoute();
const router = useRouter();
const showToast = inject('showToast');

const isNew = computed(() => !route.params.id);
const member = ref({
    name: '',
    slug: '',
    title: '',
    email: '',
    since: null,
    role: '',
    profile_url: '',
    image: '',
    publish: true,
});
const loading = ref(false);
const saving = ref(false);

const fetchMember = async () => {
    if (isNew.value) return;

    loading.value = true;
    try {
        const response = await axios.get(`/api/team-members/${route.params.id}`);
        member.value = response.data;
    } catch (error) {
        console.error('Error fetching team member:', error);
        showToast('Fehler beim Laden', 'error');
        router.push({ name: 'team.index' });
    } finally {
        loading.value = false;
    }
};

const saveMember = async () => {
    saving.value = true;
    try {
        if (isNew.value) {
            const response = await axios.post('/api/team-members', member.value);
            showToast('Teammitglied erstellt', 'success');
            router.push({ name: 'team.edit', params: { id: response.data.id } });
        } else {
            await axios.put(`/api/team-members/${member.value.id}`, member.value);
            showToast('Teammitglied gespeichert', 'success');
        }
    } catch (error) {
        console.error('Error saving team member:', error);
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

const generateSlug = () => {
    if (member.value.name && !member.value.slug) {
        member.value.slug = member.value.name
            .toLowerCase()
            .replace(/[äÄ]/g, 'ae')
            .replace(/[öÖ]/g, 'oe')
            .replace(/[üÜ]/g, 'ue')
            .replace(/[ß]/g, 'ss')
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/(^-|-$)/g, '');
    }
};

onMounted(() => {
    fetchMember();
});
</script>

<template>
    <div class="flex-1 container mx-auto p-6">
        <!-- Header -->
        <div class="mb-8 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <button
                    @click="router.push({ name: 'team.index' })"
                    class="cursor-pointer text-gray-400 hover:text-gray-600 transition-colors">
                    <PhArrowLeft class="h-5 w-5" />
                </button>
                <h1 class="text-2xl font-semibold text-gray-900">
                    {{ isNew ? 'Neues Teammitglied' : member.name || 'Teammitglied bearbeiten' }}
                </h1>
            </div>
            <button
                @click="saveMember"
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
                            <label class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
                            <input
                                v-model="member.name"
                                @blur="generateSlug"
                                type="text"
                                class="w-full px-3 py-2 border border-gray-300 rounded-sm focus:ring-1 focus:ring-black focus:border-black"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Slug</label>
                            <input
                                v-model="member.slug"
                                type="text"
                                class="w-full px-3 py-2 border border-gray-300 rounded-sm focus:ring-1 focus:ring-black focus:border-black"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Titel / Ausbildung</label>
                            <input
                                v-model="member.title"
                                type="text"
                                placeholder="z.B. M. Sc. Arch ETH"
                                class="w-full px-3 py-2 border border-gray-300 rounded-sm focus:ring-1 focus:ring-black focus:border-black"
                            />
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">E-Mail</label>
                                <input
                                    v-model="member.email"
                                    type="email"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-sm focus:ring-1 focus:ring-black focus:border-black"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Mitarbeit seit</label>
                                <input
                                    v-model="member.since"
                                    type="number"
                                    min="1900"
                                    max="2100"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-sm focus:ring-1 focus:ring-black focus:border-black"
                                />
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Rolle / Position</label>
                            <input
                                v-model="member.role"
                                type="text"
                                placeholder="z.B. Mitglied der Geschäftsleitung"
                                class="w-full px-3 py-2 border border-gray-300 rounded-sm focus:ring-1 focus:ring-black focus:border-black"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Profil URL</label>
                            <input
                                v-model="member.profile_url"
                                type="url"
                                placeholder="https://..."
                                class="w-full px-3 py-2 border border-gray-300 rounded-sm focus:ring-1 focus:ring-black focus:border-black"
                            />
                        </div>
                    </div>
                </div>

                <!-- Image -->
                <div class="bg-gray-50 p-4 pt-2 rounded-sm">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">Bild</h2>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Bild URL</label>
                            <input
                                v-model="member.image"
                                type="text"
                                placeholder="https://..."
                                class="w-full px-3 py-2 border border-gray-300 rounded-sm focus:ring-1 focus:ring-black focus:border-black"
                            />
                        </div>
                        <div v-if="member.image" class="mt-4">
                            <div class="w-32 h-40 bg-gray-100 rounded-sm overflow-hidden">
                                <img
                                    :src="member.image"
                                    :alt="member.name"
                                    class="w-full h-full object-cover"
                                />
                            </div>
                        </div>
                        <div v-else class="w-32 h-40 bg-gray-100 rounded-sm flex items-center justify-center">
                            <PhUser class="w-12 h-12 text-gray-300" />
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
                                v-model="member.publish"
                                type="radio"
                                :value="true"
                                class="w-4 h-4 text-black focus:ring-black"
                            />
                            <span class="text-sm text-gray-900">Aktiv</span>
                        </label>
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input
                                v-model="member.publish"
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

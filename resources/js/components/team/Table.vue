<script setup>
import { ref, computed, onMounted, onUnmounted, inject } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import draggable from 'vuedraggable';
import { PhPencilSimple, PhTrash, PhPlus, PhMagnifyingGlass, PhCommand, PhDotsSixVertical, PhUser } from '@phosphor-icons/vue';

const router = useRouter();
const showToast = inject('showToast');

const members = ref([]);
const loading = ref(true);
const searchQuery = ref('');
const searchInput = ref(null);
const isSearchFocused = ref(false);

// Pagination
const pagination = ref({
    currentPage: 1,
    lastPage: 1,
    perPage: 50,
    total: 0
});

const fetchMembers = async (page = 1) => {
    loading.value = true;
    try {
        const params = new URLSearchParams();
        params.append('page', page);
        params.append('per_page', pagination.value.perPage);
        if (searchQuery.value) params.append('search', searchQuery.value);

        const response = await axios.get(`/api/team-members?${params.toString()}`);
        members.value = response.data.data;
        pagination.value = {
            currentPage: response.data.current_page,
            lastPage: response.data.last_page,
            perPage: response.data.per_page,
            total: response.data.total
        };
    } catch (error) {
        console.error('Error fetching team members:', error);
        showToast('Fehler beim Laden der Teammitglieder', 'error');
    } finally {
        loading.value = false;
    }
};

const editMember = (member) => {
    router.push({ name: 'team.edit', params: { id: member.id } });
};

const createMember = () => {
    router.push({ name: 'team.create' });
};

const deleteMember = async (member) => {
    if (!confirm(`"${member.name}" wirklich löschen?`)) return;

    try {
        await axios.delete(`/api/team-members/${member.id}`);
        members.value = members.value.filter(m => m.id !== member.id);
        showToast('Teammitglied gelöscht', 'success');
    } catch (error) {
        console.error('Error deleting team member:', error);
        showToast('Fehler beim Löschen', 'error');
    }
};

const onReorder = async () => {
    try {
        const reordered = members.value.map((member, index) => ({
            id: member.id,
            position: index
        }));
        await axios.put('/api/team-members/reorder', { members: reordered });
    } catch (error) {
        console.error('Error reordering members:', error);
        showToast('Fehler beim Sortieren', 'error');
    }
};

// Search
let searchTimeout = null;
const onSearchInput = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        fetchMembers(1);
    }, 300);
};

const handleKeydown = (e) => {
    if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
        e.preventDefault();
        searchInput.value?.focus();
    }
    if (e.key === 'Escape' && isSearchFocused.value) {
        searchQuery.value = '';
        searchInput.value?.blur();
        fetchMembers(1);
    }
};

onMounted(() => {
    fetchMembers();
    window.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleKeydown);
});
</script>

<template>
    <div class="flex-1 container mx-auto p-6">
        <!-- Header -->
        <div class="mb-8 flex justify-between items-start relative">
            <div>
                <h1 class="text-2xl font-semibold text-black mb-1">Team</h1>
                <p class="text-sm text-gray-500">{{ pagination.total }} Mitglieder</p>
            </div>

            <!-- Search -->
            <div class="absolute left-1/2 top-0 -translate-x-1/2">
                <div class="relative">
                    <PhMagnifyingGlass class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                    <input
                        ref="searchInput"
                        v-model="searchQuery"
                        @input="onSearchInput"
                        @focus="isSearchFocused = true"
                        @blur="isSearchFocused = false"
                        type="text"
                        placeholder="Suchen..."
                        class="w-64 h-[38px] pl-9 pr-16 text-sm border border-gray-300 rounded-sm focus:outline-none focus:border-black transition-colors"
                    />
                    <div
                        v-if="!isSearchFocused && !searchQuery"
                        class="absolute right-2 top-1/2 -translate-y-1/2 flex items-center gap-0.5 px-1.5 py-1 bg-gray-100 rounded text-xs text-gray-500"
                    >
                        <PhCommand class="w-3 h-3" />
                        <span>K</span>
                    </div>
                </div>
            </div>

            <button
                @click="createMember"
                class="bg-black text-white text-sm px-3 py-2 hover:bg-gray-800 transition-colors cursor-pointer rounded-sm flex items-center gap-2"
            >
                <PhPlus class="w-4 h-4" />
                Hinzufügen
            </button>
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
                        <th class="py-3 pr-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-10"></th>
                        <th class="py-3 pr-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-16"></th>
                        <th class="py-3 px-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="py-3 px-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Titel</th>
                        <th class="py-3 px-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">E-Mail</th>
                        <th class="py-3 px-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Seit</th>
                        <th class="py-3 px-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="py-3 pl-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider"></th>
                    </tr>
                </thead>
                <draggable
                    v-model="members"
                    tag="tbody"
                    item-key="id"
                    handle=".drag-handle"
                    class="divide-y divide-gray-200"
                    @end="onReorder"
                >
                    <template #item="{ element: member }">
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="py-2 pr-2">
                                <div class="drag-handle cursor-grab text-gray-400 hover:text-gray-600">
                                    <PhDotsSixVertical class="w-5 h-5" />
                                </div>
                            </td>
                            <td class="py-2 pr-2">
                                <div class="w-10 h-10 bg-gray-100 rounded-full overflow-hidden shrink-0">
                                    <img
                                        v-if="member.image"
                                        :src="member.image"
                                        :alt="member.name"
                                        class="w-full h-full object-cover"
                                    />
                                    <div v-else class="w-full h-full flex items-center justify-center">
                                        <PhUser class="w-5 h-5 text-gray-300" />
                                    </div>
                                </div>
                            </td>
                            <td class="py-4 px-2">
                                <div class="text-sm font-medium text-black">
                                    <button
                                        @click="editMember(member)"
                                        class="cursor-pointer hover:text-gray-600 text-left"
                                        title="Bearbeiten">
                                        {{ member.name }}
                                    </button>
                                </div>
                                <div v-if="member.role" class="text-xs text-gray-500 mt-0.5">
                                    {{ member.role }}
                                </div>
                            </td>
                            <td class="py-4 px-2 text-sm text-gray-500">
                                {{ member.title || '—' }}
                            </td>
                            <td class="py-4 px-2 text-sm text-gray-500">
                                <a v-if="member.email" :href="`mailto:${member.email}`" class="hover:text-black">
                                    {{ member.email }}
                                </a>
                                <span v-else>—</span>
                            </td>
                            <td class="py-4 px-2 text-sm text-gray-500">
                                {{ member.since || '—' }}
                            </td>
                            <td class="py-4 px-2">
                                <span
                                    :class="[
                                        'inline-block px-2 py-1 rounded-sm text-xs',
                                        member.publish
                                            ? 'bg-green-100 text-green-700'
                                            : 'bg-gray-100 text-gray-700'
                                    ]"
                                >
                                    {{ member.publish ? 'Aktiv' : 'Entwurf' }}
                                </span>
                            </td>
                            <td class="py-4 pl-2 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button
                                        @click="editMember(member)"
                                        class="text-gray-400 hover:text-black transition-colors cursor-pointer"
                                        title="Bearbeiten"
                                    >
                                        <PhPencilSimple class="w-5 h-5" />
                                    </button>
                                    <button
                                        @click="deleteMember(member)"
                                        class="text-gray-400 hover:text-red-500 transition-colors cursor-pointer"
                                        title="Löschen"
                                    >
                                        <PhTrash class="w-5 h-5" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </draggable>
            </table>
            <div v-if="members.length === 0" class="text-center py-16 text-gray-400">
                Keine Teammitglieder gefunden
            </div>
        </div>
    </div>
</template>

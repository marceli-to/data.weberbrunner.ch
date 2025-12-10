<script setup>
import { ref, computed, onMounted, onUnmounted, inject } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import draggable from 'vuedraggable';
import { PhPencilSimple, PhTrash, PhPlus, PhMagnifyingGlass, PhCommand, PhDotsSixVertical, PhGavel, PhCaretLeft, PhCaretRight } from '@phosphor-icons/vue';

const router = useRouter();
const showToast = inject('showToast');

const juries = ref([]);
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

// Group juries by year for display
const groupedJuries = computed(() => {
    const groups = {};
    juries.value.forEach(jury => {
        if (!groups[jury.year]) {
            groups[jury.year] = [];
        }
        groups[jury.year].push(jury);
    });
    return groups;
});

const fetchJuries = async (page = 1) => {
    loading.value = true;
    try {
        const params = new URLSearchParams();
        params.append('page', page);
        params.append('per_page', pagination.value.perPage);
        if (searchQuery.value) params.append('search', searchQuery.value);

        const response = await axios.get(`/api/jury?${params.toString()}`);
        juries.value = response.data.data;
        pagination.value = {
            currentPage: response.data.current_page,
            lastPage: response.data.last_page,
            perPage: response.data.per_page,
            total: response.data.total
        };
    } catch (error) {
        console.error('Error fetching jury entries:', error);
        showToast('Fehler beim Laden der Jury & Gastkritik', 'error');
    } finally {
        loading.value = false;
    }
};

const editJury = (jury) => {
    router.push({ name: 'jury.edit', params: { id: jury.id } });
};

const createJury = () => {
    router.push({ name: 'jury.create' });
};

const deleteJury = async (jury) => {
    if (!confirm(`"${jury.title}" wirklich löschen?`)) return;

    try {
        await axios.delete(`/api/jury/${jury.id}`);
        juries.value = juries.value.filter(j => j.id !== jury.id);
        showToast('Jurytätigkeit gelöscht', 'success');
    } catch (error) {
        console.error('Error deleting jury entry:', error);
        showToast('Fehler beim Löschen', 'error');
    }
};

const onReorder = async () => {
    try {
        const reordered = juries.value.map((jury, index) => ({
            id: jury.id,
            position: index
        }));
        await axios.put('/api/jury/reorder', { juries: reordered });
    } catch (error) {
        console.error('Error reordering jury entries:', error);
        showToast('Fehler beim Sortieren', 'error');
    }
};

const goToPage = (page) => {
    if (page >= 1 && page <= pagination.value.lastPage) {
        fetchJuries(page);
    }
};

const visiblePages = computed(() => {
    const current = pagination.value.currentPage;
    const last = pagination.value.lastPage;
    const delta = 2;
    const pages = [];

    for (let i = Math.max(1, current - delta); i <= Math.min(last, current + delta); i++) {
        pages.push(i);
    }

    return pages;
});

// Search
let searchTimeout = null;
const onSearchInput = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        fetchJuries(1);
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
        fetchJuries(1);
    }
};

onMounted(() => {
    fetchJuries();
    window.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleKeydown);
});
</script>

<template>
    <div class="flex-1 container mx-auto p-6" :class="{ 'pb-20': pagination.lastPage > 1 }">
        <!-- Header -->
        <div class="mb-8 flex justify-between items-start relative">
            <div>
                <h1 class="text-2xl font-semibold text-black mb-1">Jury & Gastkritik</h1>
                <p class="text-sm text-gray-500">{{ pagination.total }} Einträge</p>
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
                @click="createJury"
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
                        <th class="py-3 px-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-24">Jahr</th>
                        <th class="py-3 px-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jurytätigkeit</th>
                        <th class="py-3 px-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-32">Status</th>
                        <th class="py-3 pl-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider w-32"></th>
                    </tr>
                </thead>
                <draggable
                    v-model="juries"
                    tag="tbody"
                    item-key="id"
                    handle=".drag-handle"
                    class="divide-y divide-gray-200"
                    @end="onReorder"
                >
                    <template #item="{ element: jury }">
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="py-2 pr-2">
                                <div class="drag-handle cursor-grab text-gray-400 hover:text-gray-600">
                                    <PhDotsSixVertical class="w-5 h-5" />
                                </div>
                            </td>
                            <td class="py-4 px-2">
                                <span class="text-sm font-medium text-black">{{ jury.year }}</span>
                            </td>
                            <td class="py-4 px-2">
                                <button
                                    @click="editJury(jury)"
                                    class="text-sm text-black hover:text-gray-600 text-left cursor-pointer"
                                    title="Bearbeiten">
                                    {{ jury.title }}
                                </button>
                            </td>
                            <td class="py-4 px-2">
                                <span
                                    :class="[
                                        'inline-block px-2 py-1 rounded-sm text-xs',
                                        jury.publish
                                            ? 'bg-green-100 text-green-700'
                                            : 'bg-gray-100 text-gray-700'
                                    ]"
                                >
                                    {{ jury.publish ? 'Aktiv' : 'Entwurf' }}
                                </span>
                            </td>
                            <td class="py-4 pl-2 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button
                                        @click="editJury(jury)"
                                        class="text-gray-400 hover:text-black transition-colors cursor-pointer"
                                        title="Bearbeiten"
                                    >
                                        <PhPencilSimple class="w-5 h-5" />
                                    </button>
                                    <button
                                        @click="deleteJury(jury)"
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
            <div v-if="juries.length === 0" class="text-center py-16 text-gray-400">
                Keine Jury & Gastkritik gefunden
            </div>
        </div>

        <!-- Fixed Pagination -->
        <div v-if="pagination.lastPage > 1" class="fixed bottom-0 left-56 right-0 bg-white border-t border-gray-200 px-8 py-3 flex items-center justify-between z-40">
            <div class="text-sm text-gray-500">
                Seite {{ pagination.currentPage }} von {{ pagination.lastPage }}
            </div>
            <div class="flex items-center gap-2">
                <button
                    @click="goToPage(pagination.currentPage - 1)"
                    :disabled="pagination.currentPage === 1"
                    :class="[
                        'p-2 transition-colors cursor-pointer',
                        pagination.currentPage === 1
                            ? 'text-gray-300 cursor-not-allowed'
                            : 'text-gray-600 hover:text-black '
                    ]"
                >
                    <PhCaretLeft class="w-5 h-5" />
                </button>
                <div class="flex items-center gap-1">
                    <button
                        v-for="page in visiblePages"
                        :key="page"
                        @click="goToPage(page)"
                        :class="[
                            'w-8 h-8 border-b-2 border-b-white text-sm transition-colors cursor-pointer',
                            page === pagination.currentPage
                                ? 'border-b-2 !border-b-black'
                                : 'text-gray-600 hover:border-b-2 hover:border-b-black'
                        ]"
                    >
                        {{ page }}
                    </button>
                </div>
                <button
                    @click="goToPage(pagination.currentPage + 1)"
                    :disabled="pagination.currentPage === pagination.lastPage"
                    :class="[
                        'p-2 transition-colors cursor-pointer',
                        pagination.currentPage === pagination.lastPage
                            ? 'text-gray-300 cursor-not-allowed'
                            : 'text-gray-600 hover:text-black '
                    ]"
                >
                    <PhCaretRight class="w-5 h-5" />
                </button>
            </div>
        </div>
    </div>
</template>

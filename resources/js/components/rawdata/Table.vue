<script setup>
import { ref, computed, onMounted, onUnmounted, inject } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import { PhPencilSimple, PhTrash, PhCaretLeft, PhCaretRight, PhMagnifyingGlass, PhCommand, PhCaretUp, PhCaretDown } from '@phosphor-icons/vue';

const router = useRouter();
const showToast = inject('showToast');

const items = ref([]);
const loading = ref(true);
const searchQuery = ref('');
const searchInput = ref(null);
const isSearchFocused = ref(false);

// Sorting
const sort = ref({
    by: 'number',
    direction: 'asc'
});

// Pagination
const pagination = ref({
    currentPage: 1,
    lastPage: 1,
    perPage: 100,
    total: 0
});

const fetchItems = async (page = 1) => {
    loading.value = true;
    try {
        const params = new URLSearchParams();
        params.append('page', page);
        params.append('per_page', pagination.value.perPage);
        if (searchQuery.value) params.append('search', searchQuery.value);
        if (sort.value.by) {
            params.append('sort_by', sort.value.by);
            params.append('sort_direction', sort.value.direction);
        }

        const response = await axios.get(`/api/raw-data?${params.toString()}`);
        items.value = response.data.data;
        pagination.value = {
            currentPage: response.data.current_page,
            lastPage: response.data.last_page,
            perPage: response.data.per_page,
            total: response.data.total
        };
    } catch (error) {
        console.error('Error fetching raw data:', error);
        showToast('Fehler beim Laden der Rohdaten', 'error');
    } finally {
        loading.value = false;
    }
};

const goToPage = (page) => {
    if (page >= 1 && page <= pagination.value.lastPage) {
        fetchItems(page);
    }
};

const editItem = (item) => {
    router.push({ name: 'rawdata.edit', params: { id: item.id } });
};

const deleteItem = async (item) => {
    if (!confirm(`Rohdaten "${item.title}" wirklich löschen?`)) return;

    try {
        await axios.delete(`/api/raw-data/${item.id}`);
        items.value = items.value.filter(i => i.id !== item.id);
        showToast('Rohdaten gelöscht', 'success');
    } catch (error) {
        console.error('Error deleting raw data:', error);
        showToast('Fehler beim Löschen', 'error');
    }
};

const toggleSort = (column) => {
    if (sort.value.by === column) {
        sort.value.direction = sort.value.direction === 'asc' ? 'desc' : 'asc';
    } else {
        sort.value.by = column;
        sort.value.direction = 'asc';
    }
    fetchItems(1);
};

// Search
let searchTimeout = null;
const onSearchInput = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        fetchItems(1);
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
        fetchItems(1);
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

onMounted(() => {
    fetchItems();
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
                <h1 class="text-2xl font-semibold text-black mb-1">Rohdaten</h1>
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
                        class="w-64 h-[38px] pl-9 pr-16 text-sm border border-gray-300 rounded-sm outline-0 focus:ring-1 focus:ring-gray-300 focus:border-gray-300 transition-colors"
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
                        <th class="py-3 px-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-32">
                            <button @click="toggleSort('number')" class="flex items-center gap-1 uppercase hover:text-gray-900 cursor-pointer">
                                Nummer
                                <PhCaretUp v-if="sort.by === 'number' && sort.direction === 'asc'" class="w-3 h-3" />
                                <PhCaretDown v-else-if="sort.by === 'number' && sort.direction === 'desc'" class="w-3 h-3" />
                            </button>
                        </th>
                        <th class="py-3 px-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <button @click="toggleSort('title')" class="flex items-center gap-1 uppercase hover:text-gray-900 cursor-pointer">
                                Titel
                                <PhCaretUp v-if="sort.by === 'title' && sort.direction === 'asc'" class="w-3 h-3" />
                                <PhCaretDown v-else-if="sort.by === 'title' && sort.direction === 'desc'" class="w-3 h-3" />
                            </button>
                        </th>
                        <th class="py-3 pl-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider w-24"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <tr
                        v-for="item in items"
                        :key="item.id"
                        class="hover:bg-gray-50 transition-colors"
                    >
                        <td class="py-4 px-2 text-sm text-gray-500">
                            {{ item.number || '—' }}
                        </td>
                        <td class="py-4 px-2">
                            <div class="text-sm font-medium text-black">
                              <button
                                @click="editItem(item)"
                                class="cursor-pointer hover:text-gray-600 text-left"
                                title="Bearbeiten">
                              {{ item.title }}
                              </button>
                            </div>
                        </td>
                        <td class="py-4 pl-2 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <button
                                    @click="editItem(item)"
                                    class="text-gray-400 hover:text-black transition-colors cursor-pointer"
                                    title="Bearbeiten"
                                >
                                    <PhPencilSimple class="w-5 h-5" />
                                </button>
                                <button
                                    @click="deleteItem(item)"
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
            <div v-if="items.length === 0" class="text-center py-16 text-gray-400">
                Keine Rohdaten gefunden
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
                                ? 'border-b-2 border-b-black!'
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

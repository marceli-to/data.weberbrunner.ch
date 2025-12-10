<script setup>
import { ref, computed, onMounted, onUnmounted, inject } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import { PhPencilSimple, PhTrash, PhFunnelSimple, PhImage, PhCaretLeft, PhCaretRight, PhMagnifyingGlass, PhCommand } from '@phosphor-icons/vue';
import Filters from '../lightbox/Filters.vue';

const router = useRouter();
const showToast = inject('showToast');

const projects = ref([]);
const categories = ref([]);
const loading = ref(true);
const showFilters = ref(false);
const searchQuery = ref('');
const searchInput = ref(null);
const isSearchFocused = ref(false);

// Load filters from localStorage or use defaults
const storedFilters = localStorage.getItem('projectFilters');
const storedPage = localStorage.getItem('projectPage');

const filters = ref(storedFilters ? JSON.parse(storedFilters) : {
    category: '',
    status: '',
    year: '',
    publish_status: ''
});

// Pagination
const pagination = ref({
    currentPage: storedPage ? parseInt(storedPage) : 1,
    lastPage: 1,
    perPage: 25,
    total: 0
});

// Save filters to localStorage
const saveFiltersToStorage = () => {
    localStorage.setItem('projectFilters', JSON.stringify(filters.value));
    localStorage.setItem('projectPage', pagination.value.currentPage.toString());
};

const fetchProjects = async (page = 1) => {
    loading.value = true;
    try {
        const params = new URLSearchParams();
        params.append('page', page);
        params.append('per_page', pagination.value.perPage);
        if (filters.value.category) params.append('category', filters.value.category);
        if (filters.value.status) params.append('status', filters.value.status);
        if (filters.value.year) params.append('year', filters.value.year);
        if (filters.value.publish_status) params.append('publish_status', filters.value.publish_status);
        if (searchQuery.value) params.append('search', searchQuery.value);

        const response = await axios.get(`/api/projects?${params.toString()}`);
        projects.value = response.data.data;
        pagination.value = {
            currentPage: response.data.current_page,
            lastPage: response.data.last_page,
            perPage: response.data.per_page,
            total: response.data.total
        };
        saveFiltersToStorage();
    } catch (error) {
        console.error('Error fetching projects:', error);
        showToast('Fehler beim Laden der Projekte', 'error');
    } finally {
        loading.value = false;
    }
};

const goToPage = (page) => {
    if (page >= 1 && page <= pagination.value.lastPage) {
        fetchProjects(page);
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

// Filter options (all possible values from entire dataset)
const filterOptions = ref({
    statuses: [],
    years: []
});

const fetchFilterOptions = async () => {
    try {
        const response = await axios.get('/api/projects/filter-options');
        filterOptions.value = response.data;
    } catch (error) {
        console.error('Error fetching filter options:', error);
    }
};

const editProject = (project) => {
    router.push({ name: 'projects.edit', params: { id: project.id } });
};

const deleteProject = async (project) => {
    if (!confirm(`Projekt "${project.title}" wirklich löschen?`)) return;

    try {
        await axios.delete(`/api/projects/${project.id}`);
        projects.value = projects.value.filter(p => p.id !== project.id);
        showToast('Projekt gelöscht', 'success');
    } catch (error) {
        console.error('Error deleting project:', error);
        showToast('Fehler beim Löschen', 'error');
    }
};

const applyFilters = (newFilters) => {
    filters.value = newFilters;
    showFilters.value = false;
    fetchProjects(1);
};

const clearFilters = () => {
    filters.value = { category: '', status: '', year: '', publish_status: '' };
    localStorage.removeItem('projectFilters');
    localStorage.removeItem('projectPage');
    fetchProjects(1);
};

const hasActiveFilters = computed(() => {
    return Object.values(filters.value).some(v => v !== '');
});

const activeFilterCount = computed(() => {
    return Object.values(filters.value).filter(v => v !== '').length;
});

// Search
let searchTimeout = null;
const onSearchInput = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        fetchProjects(1);
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
        fetchProjects(1);
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

const getImageUrl = (project) => {
    if (project.featured_image) {
        const sizes = project.featured_image.sizes;
        if (sizes?.thumbnail?.file) {
            return `/storage/uploads/${sizes.thumbnail.file}`;
        }
        return `/storage/uploads/${project.featured_image.filename}`;
    }
    return null;
};

onMounted(() => {
    fetchProjects(pagination.value.currentPage);
    fetchCategories();
    fetchFilterOptions();
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
                <h1 class="text-2xl font-semibold text-black mb-1">Projekte</h1>
                <p class="text-sm text-gray-500">{{ pagination.total }} Projekte</p>
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
                @click="showFilters = true"
                class="bg-white border border-gray-300 text-black text-sm px-3 py-2 hover:bg-gray-100 transition-colors cursor-pointer rounded-sm flex items-center gap-2"
            >
                <PhFunnelSimple class="w-4 h-4" />
                Filter
                <span v-if="activeFilterCount > 0" class="bg-black text-white text-xs p-1 w-5 h-5 leading-none rounded-full flex items-center justify-center">
                    {{ activeFilterCount }}
                </span>
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
                        <th class="py-3 pr-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-16"></th>
                        <th class="py-3 px-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Titel</th>
                        <th class="py-3 px-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jahr</th>
                        <th class="py-3 px-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="py-3 px-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kategorien</th>
                        <th class="py-3 pl-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <tr
                        v-for="project in projects"
                        :key="project.id"
                        class="hover:bg-gray-50 transition-colors"
                    >
                        <td class="py-2 pr-2">
                            <div class="w-10 h-10 bg-gray-100 shrink-0">
                                <img
                                    v-if="getImageUrl(project)"
                                    :src="getImageUrl(project)"
                                    :alt="project.title"
                                    class="w-full h-full object-cover"
                                />
                                <div v-else class="w-full h-full flex items-center justify-center">
                                    <PhImage class="w-5 h-5 text-gray-300" />
                                </div>
                            </div>
                        </td>
                        <td class="py-4 px-2">
                            <div class="text-sm font-medium text-black">
                              <button
                                @click="editProject(project)"
                                class="cursor-pointer hover:text-gray-600 text-left"
                                title="Bearbeiten">
                              {{ project.title }}
                              </button>
                            </div>
                        </td>
                        <td class="py-4 px-2 text-sm text-gray-500">
                            {{ project.year || '—' }}
                        </td>
                        <td class="py-4 px-2">
                            <span
                                v-if="project.status"
                                class="inline-block px-2 py-1 rounded-sm text-xs bg-gray-100 text-gray-700"
                            >
                                {{ project.status }}
                            </span>
                            <span v-else class="text-gray-400">—</span>
                        </td>
                        <td class="py-4 px-2 text-sm text-gray-500">
                            <span v-if="project.categories && project.categories.length > 0">
                                {{ project.categories.map(c => c.name).join(', ') }}
                            </span>
                            <span v-else>—</span>
                        </td>
                        <td class="py-4 pl-2 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <button
                                    @click="editProject(project)"
                                    class="text-gray-400 hover:text-black transition-colors cursor-pointer"
                                    title="Bearbeiten"
                                >
                                    <PhPencilSimple class="w-5 h-5" />
                                </button>
                                <button
                                    @click="deleteProject(project)"
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
            <div v-if="projects.length === 0" class="text-center py-16 text-gray-400">
                Keine Projekte gefunden
            </div>
        </div>

        <!-- Filters Lightbox -->
        <Filters
            v-if="showFilters"
            :categories="categories"
            :statuses="filterOptions.statuses"
            :years="filterOptions.years"
            :current-filters="filters"
            @apply="applyFilters"
            @close="showFilters = false"
        />

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

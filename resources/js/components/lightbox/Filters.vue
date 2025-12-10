<template>
    <div
        @click="emit('close')"
        class="fixed inset-0 bg-black/60 flex items-center justify-center z-50 p-4"
    >
        <div
            @click.stop
            class="bg-white w-full max-w-2xl max-h-[90vh] flex flex-col rounded-md"
        >
            <div class="flex justify-between items-center p-4 border-b border-gray-200 relative">
                <h2 class="text-lg font-semibold text-black">Filter</h2>
                <button
                    @click="emit('close')"
                    class="text-gray-400 w-8 h-8 absolute top-2 right-2 flex items-center justify-center hover:text-black transition-colors cursor-pointer rounded-full"
                >
                    <PhX class="w-5 h-5" />
                </button>
            </div>

            <div class="overflow-y-auto flex-1 p-4 space-y-6">
                <!-- Category Filter -->
                <div>
                    <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">Kategorie</label>
                    <div class="flex flex-wrap gap-2">
                        <button
                            @click="filters.category = ''"
                            :class="[
                                'px-2 py-2 text-xs transition-colors cursor-pointer rounded-sm',
                                filters.category === ''
                                    ? 'bg-black text-white'
                                    : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                            ]"
                        >
                            Alle
                        </button>
                        <button
                            v-for="cat in categories"
                            :key="cat.id"
                            @click="filters.category = filters.category === cat.slug ? '' : cat.slug"
                            :class="[
                                'px-2 py-2 text-xs transition-colors cursor-pointer rounded-sm',
                                filters.category === cat.slug
                                    ? 'bg-black text-white'
                                    : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                            ]"
                        >
                            {{ cat.name }}
                        </button>
                    </div>
                </div>

                <!-- Status Filter -->
                <div v-if="statuses.length > 0">
                    <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">Status</label>
                    <div class="flex flex-wrap gap-2">
                        <button
                            @click="filters.status = ''"
                            :class="[
                                'px-2 py-2 text-xs transition-colors cursor-pointer rounded-sm',
                                filters.status === ''
                                    ? 'bg-black text-white'
                                    : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                            ]"
                        >
                            Alle
                        </button>
                        <button
                            v-for="status in statuses"
                            :key="status"
                            @click="filters.status = filters.status === status ? '' : status"
                            :class="[
                                'px-2 py-2 text-xs transition-colors cursor-pointer rounded-sm',
                                filters.status === status
                                    ? 'bg-black text-white'
                                    : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                            ]"
                        >
                            {{ status }}
                        </button>
                    </div>
                </div>

                <!-- Year Filter -->
                <div v-if="years.length > 0">
                    <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">Jahr</label>
                    <div class="flex flex-wrap gap-2">
                        <button
                            @click="filters.year = ''"
                            :class="[
                                'px-2 py-2 text-xs transition-colors cursor-pointer rounded-sm',
                                filters.year === ''
                                    ? 'bg-black text-white'
                                    : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                            ]"
                        >
                            Alle
                        </button>
                        <button
                            v-for="year in years"
                            :key="year"
                            @click="filters.year = filters.year === year ? '' : year"
                            :class="[
                                'px-2 py-2 text-xs transition-colors cursor-pointer rounded-sm',
                                filters.year === year
                                    ? 'bg-black text-white'
                                    : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                            ]"
                        >
                            {{ year }}
                        </button>
                    </div>
                </div>

                <!-- Publish Status Filter -->
                <div>
                    <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">Veröffentlichung</label>
                    <div class="flex flex-wrap gap-2">
                        <button
                            @click="filters.publish_status = ''"
                            :class="[
                                'px-2 py-2 text-xs transition-colors cursor-pointer rounded-sm',
                                filters.publish_status === ''
                                    ? 'bg-black text-white'
                                    : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                            ]"
                        >
                            Alle
                        </button>
                        <button
                            @click="filters.publish_status = filters.publish_status === 'publish' ? '' : 'publish'"
                            :class="[
                                'px-2 py-2 text-xs transition-colors cursor-pointer rounded-sm',
                                filters.publish_status === 'publish'
                                    ? 'bg-black text-white'
                                    : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                            ]"
                        >
                            Veröffentlicht
                        </button>
                        <button
                            @click="filters.publish_status = filters.publish_status === 'draft' ? '' : 'draft'"
                            :class="[
                                'px-2 py-2 text-xs transition-colors cursor-pointer rounded-sm',
                                filters.publish_status === 'draft'
                                    ? 'bg-black text-white'
                                    : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                            ]"
                        >
                            Entwurf
                        </button>
                    </div>
                </div>
            </div>

            <div class="p-4 border-t border-gray-200 flex gap-3 justify-between">
                <button
                    v-if="hasActiveFilters"
                    @click="reset"
                    class="px-4 py-2 text-sm text-gray-600 hover:text-black transition-colors cursor-pointer rounded-sm"
                >
                    Alle löschen
                </button>
                <div class="flex-1"></div>
                <button
                    @click="apply"
                    class="px-4 py-2 text-sm bg-black text-white hover:bg-gray-800 transition-colors cursor-pointer rounded-sm"
                >
                    Fertig
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { PhX } from '@phosphor-icons/vue';

const props = defineProps({
    categories: {
        type: Array,
        default: () => []
    },
    statuses: {
        type: Array,
        default: () => []
    },
    years: {
        type: Array,
        default: () => []
    },
    currentFilters: {
        type: Object,
        default: () => ({})
    }
});

const emit = defineEmits(['apply', 'close']);

const filters = ref({
    category: '',
    status: '',
    year: '',
    publish_status: ''
});

const hasActiveFilters = computed(() => {
    return filters.value.category || filters.value.status || filters.value.year || filters.value.publish_status;
});

const apply = () => {
    emit('apply', { ...filters.value });
};

const reset = () => {
    filters.value = {
        category: '',
        status: '',
        year: '',
        publish_status: ''
    };
};

onMounted(() => {
    filters.value = { ...props.currentFilters };
});
</script>

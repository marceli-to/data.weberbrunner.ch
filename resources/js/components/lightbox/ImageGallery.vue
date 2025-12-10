<template>
    <div
        class="fixed inset-0 z-50 bg-black"
        @keydown="handleKeydown"
        tabindex="0"
    >
        <!-- Header -->
        <div class="absolute top-0 left-0 right-0 z-10 flex items-center justify-between px-6 py-4 bg-gradient-to-b from-black/50 to-transparent">
            <div class="text-white text-sm">
                {{ currentIndex + 1 }} / {{ images.length }}
            </div>
            <button
                @click="emit('close')"
                class="p-2 text-white/80 hover:text-white hover:bg-white/10 rounded-full transition-colors cursor-pointer"
            >
                <PhX class="w-6 h-6" />
            </button>
        </div>

        <!-- Main Image -->
        <div class="absolute inset-0 flex items-center justify-center p-16">
            <img
                v-if="currentImage()"
                :src="getImageUrl(currentImage())"
                :alt="currentImage().alt || currentImage().filename"
                class="max-w-full max-h-full object-contain"
            />
        </div>

        <!-- Featured Badge -->
        <div
            v-if="currentImage()?.is_featured"
            class="absolute top-20 left-6 flex items-center gap-2 px-3 py-1.5 bg-yellow-400 rounded-sm text-sm font-medium"
        >
            <PhStar class="w-4 h-4" weight="fill" />
            Hauptbild
        </div>

        <!-- Navigation -->
        <button
            @click="prev"
            class="absolute left-4 top-1/2 -translate-y-1/2 p-3 text-white/80 hover:text-white hover:bg-white/10 rounded-full transition-colors cursor-pointer"
        >
            <PhCaretLeft class="w-8 h-8" />
        </button>
        <button
            @click="next"
            class="absolute right-4 top-1/2 -translate-y-1/2 p-3 text-white/80 hover:text-white hover:bg-white/10 rounded-full transition-colors cursor-pointer"
        >
            <PhCaretRight class="w-8 h-8" />
        </button>

        <!-- Thumbnails -->
        <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/50 to-transparent py-4">
            <div class="flex items-center justify-center gap-2 px-4 overflow-x-auto">
                <button
                    v-for="(image, index) in images"
                    :key="image.id"
                    @click="selectImage(index)"
                    :class="[
                        'relative w-16 h-16 overflow-hidden shrink-0 transition-all cursor-pointer',
                        index === currentIndex
                            ? 'ring-2 ring-white ring-offset-2 ring-offset-black'
                            : 'opacity-50 hover:opacity-100'
                    ]"
                >
                    <img
                        :src="getImageUrl(image, 'thumbnail')"
                        :alt="image.alt || image.filename"
                        class="w-full h-full object-cover"
                    />
                    <div
                        v-if="image.is_featured"
                        class="absolute top-1 left-1 p-0.5 bg-yellow-400 rounded-sm"
                    >
                        <PhStar class="w-2.5 h-2.5 text-white" weight="fill" />
                    </div>
                </button>
            </div>
        </div>

        <!-- Image Info -->
        <div
            v-if="currentImage()?.title || currentImage()?.caption"
            class="absolute bottom-24 left-1/2 -translate-x-1/2 max-w-md text-center text-white"
        >
            <p v-if="currentImage().title" class="font-medium">{{ currentImage().title }}</p>
            <p v-if="currentImage().caption" class="text-sm text-white/80 mt-1">{{ currentImage().caption }}</p>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { PhX, PhCaretLeft, PhCaretRight, PhStar } from '@phosphor-icons/vue';

const props = defineProps({
    images: {
        type: Array,
        required: true
    }
});

const emit = defineEmits(['close']);

const currentIndex = ref(0);

const currentImage = () => props.images[currentIndex.value];

const next = () => {
    if (currentIndex.value < props.images.length - 1) {
        currentIndex.value++;
    } else {
        currentIndex.value = 0;
    }
};

const prev = () => {
    if (currentIndex.value > 0) {
        currentIndex.value--;
    } else {
        currentIndex.value = props.images.length - 1;
    }
};

const selectImage = (index) => {
    currentIndex.value = index;
};

const getImageUrl = (image, size = 'large') => {
    if (image.sizes?.[size]?.file) {
        return `/storage/uploads/${image.sizes[size].file}`;
    }
    return `/storage/uploads/${image.filename}`;
};

const handleKeydown = (e) => {
    if (e.key === 'ArrowRight') next();
    if (e.key === 'ArrowLeft') prev();
    if (e.key === 'Escape') emit('close');
};
</script>

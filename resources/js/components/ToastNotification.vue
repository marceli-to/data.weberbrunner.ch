<template>
    <Transition
        enter-active-class="transition ease-out duration-300"
        enter-from-class="opacity-0 translate-y-2"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition ease-in duration-200"
        leave-from-class="opacity-100 translate-y-0"
        leave-to-class="opacity-0 translate-y-2"
    >
        <div
            v-if="show"
            class="fixed bottom-6 right-6 z-50 px-4 py-3 rounded-sm shadow-lg text-sm flex items-center gap-2"
            :class="type === 'success' ? 'bg-black text-white' : 'bg-red-600 text-white'"
        >
            <PhCheck v-if="type === 'success'" class="w-4 h-4" />
            <PhWarningCircle v-else class="w-4 h-4" />
            <span>{{ message }}</span>
        </div>
    </Transition>
</template>

<script setup>
import { PhCheck, PhWarningCircle } from '@phosphor-icons/vue';

defineProps({
    show: {
        type: Boolean,
        default: false
    },
    message: {
        type: String,
        default: ''
    },
    type: {
        type: String,
        default: 'success'
    }
});
</script>

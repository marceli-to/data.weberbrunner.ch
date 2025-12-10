<template>
    <div class="flex min-h-screen">
        <Sidebar />
        <main class="flex-1 bg-white">
            <router-view />
        </main>
        <ToastNotification
            :show="toast.show"
            :message="toast.message"
            :type="toast.type"
            @close="toast.show = false"
        />
    </div>
</template>

<script setup>
import { ref, provide } from 'vue';
import Sidebar from './components/Sidebar.vue';
import ToastNotification from './components/ToastNotification.vue';

const toast = ref({
    show: false,
    message: '',
    type: 'success'
});

const showToast = (message, type = 'success') => {
    toast.value = { show: true, message, type };
    setTimeout(() => {
        toast.value.show = false;
    }, 3000);
};

provide('showToast', showToast);
</script>

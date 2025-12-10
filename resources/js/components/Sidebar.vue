<template>
    <aside class="w-56 bg-gray-50 border-r border-gray-200 h-screen flex flex-col sticky top-0 left-0">
        <!-- Logo / Brand -->
        <div class="p-4">
            <span class="text-lg font-semibold text-black">Weberbrunner</span>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 mt-4">
            <ul>
                <li>
                    <router-link
                        :to="{ name: 'projects.index' }"
                        class="flex items-center gap-3 p-4 text-sm transition-colors"
                        :class="isActive('projects') ? 'bg-gray-100' : 'text-gray-700 hover:bg-gray-200'"
                    >
                        <PhFolders class="w-5 h-5" />
                        Projekte
                    </router-link>
                </li>
                <li>
                    <router-link
                        :to="{ name: 'categories.index' }"
                        class="flex items-center gap-3 p-4 text-sm transition-colors"
                        :class="isActive('categories') ? 'bg-gray-100' : 'text-gray-700 hover:bg-gray-200'"
                    >
                        <PhTag class="w-5 h-5" />
                        Kategorien
                    </router-link>
                </li>
            </ul>
        </nav>

        <!-- Logout -->
        <div class="px-4 py-5 border-t border-gray-200">
            <button
                @click="logout"
                class="flex items-center gap-3 w-full text-sm text-gray-500 hover:text-black transition-colors cursor-pointer"
            >
                <PhSignOut class="w-5 h-5" />
                Abmelden
            </button>
        </div>
    </aside>
</template>

<script setup>
import { useRoute } from 'vue-router';
import { PhFolders, PhTag, PhSignOut } from '@phosphor-icons/vue';

const route = useRoute();

const isActive = (section) => {
    return route.path.startsWith(`/${section}`);
};

const logout = async () => {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/logout';

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    if (csrfToken) {
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = csrfToken;
        form.appendChild(csrfInput);
    }

    document.body.appendChild(form);
    form.submit();
};
</script>

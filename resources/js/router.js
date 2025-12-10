import { createRouter, createWebHistory } from 'vue-router';
import ProjectsTable from './components/projects/Table.vue';
import ProjectsForm from './components/projects/Form.vue';
import CategoriesTable from './components/categories/Table.vue';
import CategoriesForm from './components/categories/Form.vue';

const routes = [
    {
        path: '/',
        redirect: '/projects'
    },
    {
        path: '/projects',
        name: 'projects.index',
        component: ProjectsTable
    },
    {
        path: '/projects/:id/edit',
        name: 'projects.edit',
        component: ProjectsForm,
        props: true
    },
    {
        path: '/categories',
        name: 'categories.index',
        component: CategoriesTable
    },
    {
        path: '/categories/create',
        name: 'categories.create',
        component: CategoriesForm
    },
    {
        path: '/categories/:id/edit',
        name: 'categories.edit',
        component: CategoriesForm,
        props: true
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

export default router;

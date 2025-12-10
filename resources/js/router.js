import { createRouter, createWebHistory } from 'vue-router';
import ProjectsTable from './components/projects/Table.vue';
import ProjectsForm from './components/projects/Form.vue';
import CategoriesTable from './components/categories/Table.vue';
import CategoriesForm from './components/categories/Form.vue';
import TeamTable from './components/team/Table.vue';
import TeamForm from './components/team/Form.vue';
import AwardsTable from './components/awards/Table.vue';
import AwardsForm from './components/awards/Form.vue';
import JuryTable from './components/jury/Table.vue';
import JuryForm from './components/jury/Form.vue';
import LecturesTable from './components/lectures/Table.vue';
import LecturesForm from './components/lectures/Form.vue';

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
    {
        path: '/team',
        name: 'team.index',
        component: TeamTable
    },
    {
        path: '/team/create',
        name: 'team.create',
        component: TeamForm
    },
    {
        path: '/team/:id/edit',
        name: 'team.edit',
        component: TeamForm,
        props: true
    },
    {
        path: '/awards',
        name: 'awards.index',
        component: AwardsTable
    },
    {
        path: '/awards/create',
        name: 'awards.create',
        component: AwardsForm
    },
    {
        path: '/awards/:id/edit',
        name: 'awards.edit',
        component: AwardsForm,
        props: true
    },
    {
        path: '/jury',
        name: 'jury.index',
        component: JuryTable
    },
    {
        path: '/jury/create',
        name: 'jury.create',
        component: JuryForm
    },
    {
        path: '/jury/:id/edit',
        name: 'jury.edit',
        component: JuryForm,
        props: true
    },
    {
        path: '/lectures',
        name: 'lectures.index',
        component: LecturesTable
    },
    {
        path: '/lectures/create',
        name: 'lectures.create',
        component: LecturesForm
    },
    {
        path: '/lectures/:id/edit',
        name: 'lectures.edit',
        component: LecturesForm,
        props: true
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

export default router;

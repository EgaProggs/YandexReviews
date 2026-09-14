import { createRouter, createWebHistory } from 'vue-router';

import api from '../services/api';

import Home from '../views/Home.vue';
import Login from '../views/Login.vue';
import Settings from '../views/Settings.vue';
import Organizations from '../views/Organizations.vue'
import OrganizationsShow from '../views/OrganizationsShow.vue';

const router = createRouter({
    history: createWebHistory(),

    routes: [
        {
            path: '/',
            name: 'home',
            component: Home,
        },
        {
            path: '/login',
            name: 'login',
            component: Login,
        },
        {
            path: '/organizations',
            name: 'organizations',
            component: Organizations,
            meta: {
                requireAuth: true,
                role: "user",
            }
        },
        {
            path: '/organizations/:id',
            name: 'organization',
            component: OrganizationsShow,
            meta: {
                requireAuth: true,
            },
        },
        // {
        //     path: '/settings',
        //     name: 'settings',
        //     component: Settings,
        //     meta: {
        //         requireAuth: true,
        //         role: "user",
        //     }
        // },
    ],
});


// Auth/Role Filter

router.beforeEach(async (to, from) => {
    if (!to.meta.requireAuth) {
        return true;
    }
    try {
        await api.get('/me');
        return true;
    }
    catch {
        return '/login';
    }
});



export default router;
<script setup>
import { onMounted, ref } from "vue";
import { useRouter } from "vue-router";

import OrganizationCard from "../components/OrganizationCard.vue";
import OrganizationForm from "../components/OrganizationForm.vue";
import Pagination from "../components/Pagination.vue";

import {
    getOrganizations,
    createOrganization,
    parseOrganization,
} from "../services/organizations";

const router = useRouter();

const organizations = ref([]);

const pagination = ref({
    current_page: 1,
    last_page: 1,
    per_page: 20,
    total: 0,
});
const loading = ref(false);
const creating = ref(false);
const error = ref("");

const loadOrganizations = async (page = 1) => {
    loading.value = true;

    try {
        const response = await getOrganizations(page);

        organizations.value = response.data.data;
        pagination.value = response.data.meta;
    } catch (error) {
        e.response?.data?.message ?? "Не удалось загрузить организации.";
    } finally {
        loading.value = false;
    }
};

const addOrganization = async (url) => {
    creating.value = true;
    error.value = "";

    try {
        const response = await createOrganization(url);

        const organization = response.data.data;
        await parseOrganization(organization.id);

        await router.push(`/organizations/${organization.id}`);
    } catch (e) {
        error.value =
            e.response?.data?.message ?? "Не удалось добавить организацию.";
    } finally {
        creating.value = false;
    }
};

const openOrganization = (organization) => {
    router.push(`/organizations/${organization.id}`);
};

onMounted(() => {
    loadOrganizations();
});
</script>

<template>
    <div class="min-h-screen bg-gray-50">
        <main class="mx-auto max-w-5xl space-y-6 p-6">
            <div>
                <h1 class="text-2xl font-bold">Организации</h1>

                <p class="mt-1 text-gray-600">
                    Выберите организацию или добавьте новую.
                </p>
            </div>

            <OrganizationForm @created="addOrganization" />

            <p
                v-if="error"
                class="rounded border border-red-200 bg-red-50 p-3 text-red-700"
            >
                {{ error }}
            </p>

            <div v-if="loading" class="py-10 text-center text-gray-500">
                Загрузка организаций...
            </div>

            <div
                v-else-if="organizations.length === 0"
                class="rounded-lg border bg-white p-10 text-center text-gray-500"
            >
                Организаций пока нет.
            </div>

            <div v-else class="space-y-3">
                <OrganizationCard
                    v-for="organization in organizations"
                    :key="organization.id"
                    :organization="organization"
                    @click="openOrganization(organization)"
                />
            </div>

            <Pagination
                :current-page="pagination.current_page"
                :last-page="pagination.last_page"
                @change="loadOrganizations"
            />
        </main>
    </div>
</template>

<script setup>
import { onBeforeUnmount, onMounted, ref } from "vue";
import { useRoute } from "vue-router";

import { getOrganization, parseOrganization } from "../services/organizations";
import { getReviews } from "../services/reviews";

import Pagination from "../components/Pagination.vue";
import ReviewCard from "../components/ReviewCard.vue";

const route = useRoute();

const organization = ref(null);
const reviews = ref([]);

const loading = ref(true);
const reviewsLoading = ref(false);
const parsing = ref(false);

const error = ref("");
const reviewsError = ref("");

const pagination = ref({
    current_page: 1,
    last_page: 1,
    per_page: 50,
    total: 0,
});

let pollingTimer = null;

const organizationId = route.params.id;

const loadOrganization = async () => {
    const response = await getOrganization(organizationId);

    organization.value = response.data.data;
};

const loadReviews = async (page = 1, showLoading = true) => {
    if (showLoading) {
        reviewsLoading.value = true;
    }

    reviewsError.value = "";

    try {
        const response = await getReviews(organizationId, page);

        reviews.value = response.data.data;
        pagination.value = response.data.meta;
    } catch (e) {
        reviewsError.value =
            e.response?.data?.message ?? "Не удалось загрузить отзывы.";
    } finally {
        if (showLoading) {
            reviewsLoading.value = false;
        }
    }
};

const startParsing = async () => {
    if (parsing.value) {
        return;
    }

    parsing.value = true;
    error.value = "";

    try {
        const response = await parseOrganization(organizationId);

        organization.value = response.data.organization;

        startPolling();
    } catch (e) {
        error.value =
            e.response?.data?.message ??
            "Не удалось запустить обновление данных.";

        parsing.value = false;
    }
};

const checkParsingStatus = async () => {
    try {
        await loadOrganization();

        if (organization.value.parsing_status === "in_progress") {
            await loadReviews(pagination.value.current_page, false);
            return;
        }

        stopPolling();
        parsing.value = false;

        if (organization.value.parsing_status === "done") {
            await loadReviews(pagination.value.current_page, false);
        }
    } catch (e) {
        console.error("Failed to check parsing status", e);
    }
};

const startPolling = () => {
    stopPolling();

    pollingTimer = setInterval(() => {
        checkParsingStatus();
    }, 2000);
};

const stopPolling = () => {
    if (pollingTimer) {
        clearInterval(pollingTimer);
        pollingTimer = null;
    }
};

const init = async () => {
    loading.value = true;
    error.value = "";

    try {
        await loadOrganization();
        await loadReviews(1);

        if (organization.value.parsing_status === "in_progress") {
            parsing.value = true;
            startPolling();
        }
    } catch (e) {
        error.value =
            e.response?.data?.message ?? "Не удалось загрузить организацию.";
    } finally {
        loading.value = false;
    }
};

onMounted(init);

onBeforeUnmount(() => {
    stopPolling();
});
</script>

<template>
    <main class="min-h-screen bg-gray-50">
        <div class="max-w-6xl mx-auto px-4 py-8">
            <!-- Loading -->
            <div v-if="loading" class="py-12 text-center text-gray-500">
                Загрузка...
            </div>

            <!-- Error -->
            <div
                v-else-if="error"
                class="rounded-lg border border-red-200 bg-red-50 p-4 text-red-700"
            >
                {{ error }}
            </div>

            <template v-else-if="organization">
                <!-- Organization -->
                <section class="rounded-xl border bg-white p-6 shadow-sm">
                    <div class="flex items-start justify-between gap-6">
                        <div class="flex items-start gap-4">
                            <!-- Logo -->
                            <img
                                v-if="organization.logo?.medium"
                                :src="organization.logo.medium"
                                :alt="organization.name"
                                class="h-20 w-20 rounded-xl object-cover"
                            />

                            <div
                                v-else
                                class="flex h-20 w-20 items-center justify-center rounded-xl bg-gray-100 text-gray-400"
                            >
                                —
                            </div>

                            <!-- Info -->
                            <div>
                                <h1 class="text-2xl font-semibold">
                                    {{ organization.name }}
                                </h1>

                                <p
                                    v-if="organization.address"
                                    class="mt-1 text-gray-600"
                                >
                                    {{ organization.address }}
                                </p>

                                <div class="mt-3 flex flex-wrap gap-4 text-sm">
                                    <span>
                                        ⭐
                                        <strong>
                                            {{ organization.rating ?? "—" }}
                                        </strong>
                                    </span>

                                    <span class="text-gray-600">
                                        Оценок:
                                        {{ organization.ratings_count ?? 0 }}
                                    </span>

                                    <span class="text-gray-600">
                                        Отзывов:
                                        {{ organization.reviews_count ?? 0 }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Refresh -->
                        <button
                            type="button"
                            :disabled="parsing"
                            class="rounded-lg bg-black px-4 py-2 text-sm font-medium text-white hover:bg-gray-800 disabled:cursor-not-allowed disabled:opacity-50"
                            @click="startParsing"
                        >
                            {{ parsing ? "Обновление..." : "Обновить данные" }}
                        </button>
                    </div>

                    <!-- Parsing status -->
                    <div
                        v-if="organization.parsing_status === 'in_progress'"
                        class="mt-5 rounded-lg bg-blue-50 px-4 py-3 text-sm text-blue-700"
                    >
                        Данные организации и отзывы обновляются в фоне.
                    </div>

                    <div
                        v-else-if="organization.parsing_status === 'failed'"
                        class="mt-5 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700"
                    >
                        При обновлении данных произошла ошибка.
                    </div>

                    <div
                        v-if="organization.last_parsed_at"
                        class="mt-4 text-xs text-gray-500"
                    >
                        Последнее обновление:
                        {{ organization.last_parsed_at }}
                    </div>
                </section>

                <!-- Reviews -->
                <section class="mt-8">
                    <div class="mb-4 flex items-center justify-between">
                        <h2 class="text-xl font-semibold">Отзывы</h2>

                        <span class="text-sm text-gray-500">
                            доступно
                            {{ pagination.total }} из
                            {{ organization.reviews_count }} отзывов
                        </span>
                    </div>

                    <!-- Reviews error -->
                    <div
                        v-if="reviewsError"
                        class="mb-4 rounded-lg border border-red-200 bg-red-50 p-4 text-red-700"
                    >
                        {{ reviewsError }}
                    </div>

                    <!-- Reviews loading -->
                    <div
                        v-if="reviewsLoading"
                        class="py-10 text-center text-gray-500"
                    >
                        Загрузка отзывов...
                    </div>

                    <!-- Empty -->
                    <div
                        v-else-if="reviews.length === 0"
                        class="rounded-xl border bg-white p-8 text-center text-gray-500"
                    >
                        Отзывов пока нет.
                    </div>

                    <!-- Reviews -->
                    <div v-else class="space-y-4">
                        <ReviewCard
                            v-for="review in reviews"
                            :key="review.id"
                            :review="review"
                        />
                    </div>

                    <!-- Pagination -->
                    <div class="mt-6">
                        <Pagination
                            :current-page="pagination.current_page"
                            :last-page="pagination.last_page"
                            @change="loadReviews"
                        />
                    </div>
                </section>
            </template>
        </div>
    </main>
</template>

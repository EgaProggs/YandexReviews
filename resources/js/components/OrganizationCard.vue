<script setup>
defineProps({
    organization: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(["click"]);
</script>

<template>
    <article
        class="flex cursor-pointer gap-4 rounded-lg border bg-white p-4 transition hover:shadow-md"
        @click="emit('click')"
    >
        <div class="shrink-0">
            <img
                v-if="organization.logo?.medium"
                :src="organization.logo.medium"
                :alt="organization.name"
                class="h-20 w-20 rounded-lg object-cover"
            />

            <div
                v-else
                class="flex h-20 w-20 items-center justify-center rounded-lg bg-gray-100 text-gray-400"
            >
                —
            </div>
        </div>

        <div class="min-w-0 flex-1">
            <h2 class="text-lg font-semibold">
                {{ organization.name || "Без названия" }}
            </h2>

            <p v-if="organization.address" class="mt-1 text-sm text-gray-600">
                {{ organization.address }}
            </p>

            <div class="mt-3 flex gap-4 text-sm text-gray-600">
                <span v-if="organization.rating">
                    ★ {{ organization.rating }}
                </span>

                <span> {{ organization.reviews_count ?? 0 }} отзывов </span>
            </div>
        </div>
    </article>
</template>

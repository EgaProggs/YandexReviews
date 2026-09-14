<script setup>
import { computed } from "vue";

const props = defineProps({
    currentPage: {
        type: Number,
        required: true,
    },
    lastPage: {
        type: Number,
        required: true,
    },
});

const emit = defineEmits(["change"]);

const pages = computed(() => {
    const current = props.currentPage;
    const last = props.lastPage;

    if (last <= 7) {
        return Array.from({ length: last }, (_, i) => i + 1);
    }

    const result = [1];

    if (current > 4) {
        result.push("...");
    }

    const start = Math.max(2, current - 1);
    const end = Math.min(last - 1, current + 1);

    for (let page = start; page <= end; page++) {
        result.push(page);
    }

    if (current < last - 3) {
        result.push("...");
    }

    result.push(last);

    return result;
});

const goTo = (page) => {
    if (
        page === "..." ||
        page < 1 ||
        page > props.lastPage ||
        page === props.currentPage
    ) {
        return;
    }

    emit("change", page);
};
</script>

<template>
    <nav
        v-if="lastPage > 1"
        class="flex items-center justify-center gap-1"
        aria-label="Pagination"
    >
        <!-- Назад -->
        <button
            type="button"
            :disabled="currentPage === 1"
            class="px-3 py-2 rounded-lg border text-sm disabled:opacity-40 disabled:cursor-not-allowed hover:bg-gray-50"
            @click="goTo(currentPage - 1)"
        >
            ←
        </button>

        <!-- Страницы -->
        <template v-for="(page, index) in pages" :key="`${page}-${index}`">
            <span v-if="page === '...'" class="px-2 py-2 text-gray-500">
                …
            </span>

            <button
                v-else
                type="button"
                class="min-w-10 px-3 py-2 rounded-lg border text-sm"
                :class="
                    page === currentPage
                        ? 'bg-gray-900 text-white border-gray-900'
                        : 'hover:bg-gray-50'
                "
                @click="goTo(page)"
            >
                {{ page }}
            </button>
        </template>

        <!-- Вперёд -->
        <button
            type="button"
            :disabled="currentPage === lastPage"
            class="px-3 py-2 rounded-lg border text-sm disabled:opacity-40 disabled:cursor-not-allowed hover:bg-gray-50"
            @click="goTo(currentPage + 1)"
        >
            →
        </button>
    </nav>
</template>

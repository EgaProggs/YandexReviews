<script setup>
import { ref } from "vue";

const emit = defineEmits(["created"]);

const yandexUrl = ref("");
const loading = ref(false);
const error = ref("");

const submit = async () => {
    error.value = "";

    if (!yandexUrl.value.trim()) {
        error.value = "Введите ссылку на организацию.";
        return;
    }

    loading.value = true;

    try {
        emit("created", yandexUrl.value.trim());
        yandexUrl.value = "";
    } finally {
        loading.value = false;
    }
};
</script>

<template>
    <form class="rounded-lg border bg-white p-4" @submit.prevent="submit">
        <h2 class="mb-4 text-lg font-semibold">Добавить организацию</h2>

        <div class="flex gap-3">
            <input
                v-model="yandexUrl"
                type="url"
                placeholder="https://yandex.ru/maps/org/..."
                class="min-w-0 flex-1 rounded border px-3 py-2"
                :disabled="loading"
            />

            <button
                type="submit"
                class="rounded bg-black px-4 py-2 text-white disabled:opacity-50"
                :disabled="loading"
            >
                {{ loading ? "Добавление..." : "Добавить" }}
            </button>
        </div>

        <p v-if="error" class="mt-2 text-sm text-red-600">
            {{ error }}
        </p>
    </form>
</template>

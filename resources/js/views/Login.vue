<script setup>
import { ref } from "vue";
import { useRouter } from "vue-router";
import api, { initCsrf } from "../services/api";

const router = useRouter();
const email = ref("admin@admin.com");
const password = ref("admin");
const error = ref("");
const loading = ref(false);

const login = async () => {
    error.value = "";
    loading.value = true;

    try {
        await initCsrf();

        await api.post("/login", {
            email: email.value,
            password: password.value,
        });

        await router.push("/organizations");
    } catch (e) {
        error.value = e.response?.data?.message ?? "Не удалось войти.";
    } finally {
        loading.value = false;
    }
};
</script>

<template>
    <div class="min-h-screen flex items-center justify-center">
        <form class="w-full max-w-sm space-y-4" @submit.prevent="login">
            <h1 class="text-2xl font-bold">Login</h1>

            <input
                v-model="email"
                type="email"
                placeholder="Email"
                class="w-full rounded border px-3 py-2"
            />

            <input
                v-model="password"
                type="password"
                placeholder="Password"
                class="w-full rounded border px-3 py-2"
            />

            <p v-if="error" class="text-red-600">
                {{ error }}
            </p>

            <button
                type="submit"
                :disabled="loading"
                class="w-full rounded bg-black px-4 py-2 text-white disabled:opacity-50"
            >
                {{ loading ? "Загрузка..." : "Войти" }}
            </button>
        </form>
    </div>
</template>

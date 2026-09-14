<script setup>
defineProps({
    review: {
        type: Object,
        required: true,
    },
});

const avatarUrl = (url) => {
    if (!url) {
        return null;
    }

    return url.replace("{size}", "200x200");
};

const mediaUrl = (url) => {
    if (!url) {
        return null;
    }

    return url.replace("{size}", "XXXL");
};

const authorInitial = (name) => {
    return name?.charAt(0)?.toUpperCase() ?? "?";
};
</script>

<template>
    <article class="rounded-xl border bg-white p-5 shadow-sm">
        <!-- Author -->
        <div class="flex items-start gap-3">
            <img
                v-if="review.author?.avatar_url"
                :src="avatarUrl(review.author.avatar_url)"
                :alt="review.author.name"
                class="h-10 w-10 shrink-0 rounded-full object-cover"
            />

            <div
                v-else
                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gray-100 text-sm text-gray-500"
            >
                {{ authorInitial(review.author?.name) }}
            </div>

            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="font-medium">
                        {{ review.author?.name ?? "Аноним" }}
                    </span>

                    <span
                        v-if="review.rating"
                        class="flex items-center text-sm"
                        :aria-label="`Оценка ${review.rating} из 5`"
                    >
                        <span class="text-yellow-500">
                            {{ "★".repeat(review.rating) }}
                        </span>

                        <span class="text-gray-300">
                            {{ "★".repeat(5 - review.rating) }}
                        </span>
                    </span>
                </div>

                <div
                    v-if="review.external_updated_at"
                    class="mt-1 text-xs text-gray-400"
                >
                    {{ review.external_updated_at }}
                </div>
            </div>
        </div>

        <!-- Review text -->
        <p v-if="review.text" class="mt-4 whitespace-pre-line text-gray-700">
            {{ review.text }}
        </p>

        <!-- Business reply -->
        <div
            v-if="review.business_reply"
            class="mt-4 rounded-lg bg-gray-50 p-4"
        >
            <div class="mb-1 text-sm font-medium">Ответ организации</div>

            <p class="whitespace-pre-line text-sm text-gray-700">
                {{ review.business_reply }}
            </p>

            <div
                v-if="review.business_reply_updated_at"
                class="mt-2 text-xs text-gray-400"
            >
                {{ review.business_reply_updated_at }}
            </div>
        </div>

        <!-- Media -->
        <div v-if="review.media?.length" class="mt-4 flex flex-wrap gap-2">
            <template v-for="media in review.media" :key="media.id">
                <img
                    v-if="media.type === 'photo' && media.url_template"
                    :src="mediaUrl(media.url_template)"
                    alt=""
                    class="h-24 w-24 rounded-lg object-cover"
                />

                <a
                    v-else-if="media.type === 'video' && media.url_template"
                    :href="mediaUrl(media.url_template)"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="flex h-24 w-24 items-center justify-center rounded-lg bg-gray-100 text-sm text-gray-600"
                >
                    Видео
                </a>
            </template>
        </div>
    </article>
</template>

<script setup>
import { Link } from "@inertiajs/vue3";

defineProps({
    item: Object,
});

const itemUrl = (item) =>
    item.media_type === "movie"
        ? `/explore/movies/${item.id}`
        : `/explore/tv/${item.id}`;
</script>

<template>
    <div class="group bg-gray-300 dark:bg-gray-800 shadow-lg rounded p-1">
        <div class="relative">
            <Link :href="itemUrl(item)">
                <div class="aspect-w-2 aspect-h-3">
                    <img
                        v-if="item.poster_url"
                        class="object-cover"
                        :src="item.poster_url"
                        :alt="item.title"
                        loading="lazy"
                    />
                    <div
                        v-else
                        class="flex h-full w-full items-center justify-center rounded bg-gray-500 text-xs text-white"
                    >
                        No poster
                    </div>
                </div>
                <div
                    class="absolute left-2 top-2 h-6 rounded bg-gray-800 px-2 text-center text-xs font-bold leading-6 text-blue-400"
                >
                    {{ item.media_type === "movie" ? "Movie" : "TV" }}
                </div>
                <div
                    v-if="item.vote_average"
                    class="absolute right-2 top-2 rounded bg-yellow-500 px-2 text-xs font-bold leading-6 text-gray-900"
                >
                    {{ Number(item.vote_average).toFixed(1) }}
                </div>
                <div
                    class="absolute inset-0 z-10 bg-gradient-to-t from-black to-transparent"
                ></div>
            </Link>
        </div>
        <div class="p-2">
            <Link
                :href="itemUrl(item)"
                class="block truncate font-bold dark:text-white group-hover:text-blue-400"
            >
                {{ item.title }}
            </Link>
            <div class="mt-1 text-xs text-gray-600 dark:text-gray-400">
                {{ item.release_date || "—" }}
            </div>
        </div>
    </div>
</template>

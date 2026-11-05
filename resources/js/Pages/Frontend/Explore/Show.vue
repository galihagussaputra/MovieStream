<script setup>
import { computed } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import FrontLayout from "@/Layouts/FrontLayout.vue";
import TmdbCard from "@/Matrials/TmdbCard.vue";

const props = defineProps({
    mediaType: String,
    item: Object,
    cast: Array,
    trailers: Array,
    recommendations: Array,
    seasons: Array,
});

const trailer = computed(() => props.trailers[0] || null);
const backUrl = computed(() => "/explore");
</script>

<template>
    <Head :title="item.title" />
    <FrontLayout>
        <main class="min-h-screen">
            <section class="relative">
                <img
                    v-if="item.backdrop_url"
                    :src="item.backdrop_url"
                    :alt="item.title"
                    class="h-72 w-full object-cover opacity-40"
                />
                <div class="absolute inset-0 bg-gradient-to-t from-gray-900 to-transparent"></div>
            </section>

            <section class="mx-auto -mt-40 max-w-6xl px-4">
                <div class="relative flex flex-col gap-6 md:flex-row">
                    <div class="w-full md:w-3/12">
                        <img
                            v-if="item.poster_url"
                            :src="item.poster_url"
                            :alt="item.title"
                            class="w-full rounded shadow-lg"
                        />
                    </div>
                    <div class="w-full text-white md:w-9/12">
                        <div class="mb-2 flex flex-wrap items-center gap-2 text-sm">
                            <span class="rounded bg-blue-700 px-2 py-1 font-bold uppercase">
                                {{ mediaType }}
                            </span>
                            <span v-if="item.release_date">{{ item.release_date }}</span>
                            <span v-if="item.vote_average" class="font-bold text-yellow-400">
                                ★ {{ Number(item.vote_average).toFixed(1) }}
                            </span>
                            <span v-if="item.runtime">{{ item.runtime }} menit</span>
                            <span v-if="item.number_of_seasons">
                                {{ item.number_of_seasons }} season
                            </span>
                            <span v-if="item.number_of_episodes">
                                {{ item.number_of_episodes }} episode
                            </span>
                        </div>
                        <h1 class="text-4xl font-bold">{{ item.title }}</h1>
                        <p v-if="item.tagline" class="mt-2 italic text-gray-300">
                            {{ item.tagline }}
                        </p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <span
                                v-for="genre in item.genres"
                                :key="genre"
                                class="rounded bg-white/10 px-2 py-1 text-sm"
                            >
                                {{ genre }}
                            </span>
                        </div>
                        <p class="mt-4 leading-relaxed text-gray-200">
                            {{ item.overview || "Belum ada sinopsis." }}
                        </p>

                        <div class="mt-6 flex flex-wrap gap-3">
                            <a
                                v-if="trailer"
                                :href="`https://www.youtube.com/watch?v=${trailer.key}`"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="rounded bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700"
                            >
                                Tonton Trailer
                            </a>
                            <Link
                                :href="backUrl"
                                class="rounded bg-white/10 px-4 py-2 text-sm font-semibold text-white hover:bg-white/20"
                            >
                                Kembali
                            </Link>
                        </div>
                    </div>
                </div>
            </section>

            <section
                v-if="trailer"
                class="mx-auto mt-8 max-w-6xl px-4"
            >
                <h2 class="mb-3 text-xl font-bold text-indigo-600 dark:text-indigo-300">
                    Trailer
                </h2>
                <div class="aspect-w-16 aspect-h-9 overflow-hidden rounded-lg">
                    <iframe
                        class="h-full w-full"
                        :src="`https://www.youtube.com/embed/${trailer.key}`"
                        :title="trailer.name"
                        frameborder="0"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                        allowfullscreen
                    ></iframe>
                </div>
            </section>

            <section v-if="cast.length" class="mx-auto mt-8 max-w-6xl px-4">
                <h2 class="mb-3 text-xl font-bold text-indigo-600 dark:text-indigo-300">
                    Pemeran
                </h2>
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-6">
                    <div
                        v-for="person in cast"
                        :key="person.id"
                        class="rounded bg-gray-200 p-2 dark:bg-gray-800"
                    >
                        <img
                            v-if="person.profile_url"
                            :src="person.profile_url"
                            :alt="person.name"
                            class="aspect-w-2 aspect-h-3 w-full rounded object-cover"
                            loading="lazy"
                        />
                        <div
                            v-else
                            class="flex aspect-w-2 aspect-h-3 items-center justify-center rounded bg-gray-500 text-xs text-white"
                        >
                            No photo
                        </div>
                        <div class="mt-2 text-sm font-bold dark:text-white">
                            {{ person.name }}
                        </div>
                        <div v-if="person.character" class="text-xs text-gray-500">
                            {{ person.character }}
                        </div>
                    </div>
                </div>
            </section>

            <section v-if="seasons.length" class="mx-auto mt-8 max-w-6xl px-4">
                <h2 class="mb-3 text-xl font-bold text-indigo-600 dark:text-indigo-300">
                    Season
                </h2>
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4">
                    <div
                        v-for="season in seasons"
                        :key="season.season_number"
                        class="rounded bg-gray-200 p-3 dark:bg-gray-800"
                    >
                        <div class="font-bold dark:text-white">{{ season.name }}</div>
                        <div class="text-sm text-gray-500">
                            {{ season.episode_count }} episode
                            <span v-if="season.air_date"> • {{ season.air_date }}</span>
                        </div>
                    </div>
                </div>
            </section>

            <section v-if="recommendations.length" class="mx-auto mt-8 max-w-6xl px-4 pb-10">
                <h2 class="mb-3 text-xl font-bold text-indigo-600 dark:text-indigo-300">
                    Rekomendasi
                </h2>
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6">
                    <TmdbCard
                        v-for="recommendation in recommendations"
                        :key="`${recommendation.media_type}-${recommendation.id}`"
                        :item="recommendation"
                    />
                </div>
            </section>
        </main>
    </FrontLayout>
</template>

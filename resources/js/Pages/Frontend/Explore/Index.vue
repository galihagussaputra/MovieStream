<script setup>
import { ref } from "vue";
import { Head, router } from "@inertiajs/vue3";
import FrontLayout from "@/Layouts/FrontLayout.vue";
import TmdbCard from "@/Matrials/TmdbCard.vue";

const props = defineProps({
    configured: Boolean,
    query: String,
    page: Number,
    totalPages: Number,
    results: Array,
    trending: Array,
    popularMovies: Array,
    popularTv: Array,
});

const search = ref(props.query || "");

function submit() {
    router.get(
        "/explore",
        search.value ? { q: search.value } : {},
        { preserveState: true }
    );
}

function goToPage(page) {
    router.get(
        "/explore",
        {
            ...(search.value ? { q: search.value } : {}),
            page,
        },
        { preserveState: true }
    );
}
</script>

<template>
    <Head title="Explore TMDB" />
    <FrontLayout>
        <main class="mx-auto mt-6 min-h-screen max-w-6xl px-2">
            <section
                class="mt-4 rounded bg-gray-200 p-4 dark:bg-gray-900 dark:text-white"
            >
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-indigo-600 dark:text-indigo-300">
                            Explore TMDB
                        </h1>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Data diambil langsung dari TMDB API secara realtime dan
                            di-cache agar tetap cepat.
                        </p>
                    </div>
                    <form class="flex w-full gap-2 sm:w-auto" @submit.prevent="submit">
                        <input
                            v-model="search"
                            type="search"
                            placeholder="Cari film atau serial TV..."
                            class="w-full rounded-lg border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white sm:w-72"
                        />
                        <button
                            type="submit"
                            class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700"
                        >
                            Cari
                        </button>
                    </form>
                </div>

                <div
                    v-if="!configured"
                    class="mt-6 rounded-lg border border-yellow-400 bg-yellow-100 p-4 text-sm text-yellow-800"
                >
                    TMDB belum dikonfigurasi. Isi <code>TMDB_TOKEN</code> (disarankan)
                    atau <code>TMDB_SECRET</code> pada <code>.env</code>, lalu
                    restart container aplikasi.
                </div>
            </section>

            <template v-if="configured">
                <section v-if="query" class="mt-4">
                    <div class="mb-3 flex items-center justify-between">
                        <h2 class="text-xl font-bold text-indigo-600 dark:text-indigo-300">
                            Hasil untuk "{{ query }}"
                        </h2>
                        <span class="text-sm text-gray-500 dark:text-gray-400">
                            Halaman {{ page }} dari {{ totalPages || 1 }}
                        </span>
                    </div>
                    <div
                        v-if="results.length"
                        class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6"
                    >
                        <TmdbCard v-for="item in results" :key="`${item.media_type}-${item.id}`" :item="item" />
                    </div>
                    <p v-else class="rounded bg-gray-200 p-8 text-center text-gray-500 dark:bg-gray-900">
                        Tidak ada hasil.
                    </p>
                    <div class="mt-4 flex items-center justify-center gap-3">
                        <button
                            class="rounded bg-gray-700 px-4 py-2 text-sm text-white disabled:opacity-40"
                            :disabled="page <= 1"
                            @click="goToPage(page - 1)"
                        >
                            Sebelumnya
                        </button>
                        <button
                            class="rounded bg-gray-700 px-4 py-2 text-sm text-white disabled:opacity-40"
                            :disabled="page >= totalPages"
                            @click="goToPage(page + 1)"
                        >
                            Berikutnya
                        </button>
                    </div>
                </section>

                <template v-else>
                    <section v-if="trending.length" class="mt-4">
                        <h2 class="mb-3 text-xl font-bold text-indigo-600 dark:text-indigo-300">
                            Trending Minggu Ini
                        </h2>
                        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6">
                            <TmdbCard v-for="item in trending" :key="`${item.media_type}-${item.id}`" :item="item" />
                        </div>
                    </section>

                    <section v-if="popularMovies.length" class="mt-6">
                        <h2 class="mb-3 text-xl font-bold text-indigo-600 dark:text-indigo-300">
                            Film Populer
                        </h2>
                        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6">
                            <TmdbCard v-for="item in popularMovies" :key="`movie-${item.id}`" :item="item" />
                        </div>
                    </section>

                    <section v-if="popularTv.length" class="mt-6">
                        <h2 class="mb-3 text-xl font-bold text-indigo-600 dark:text-indigo-300">
                            Serial TV Populer
                        </h2>
                        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6">
                            <TmdbCard v-for="item in popularTv" :key="`tv-${item.id}`" :item="item" />
                        </div>
                    </section>

                    <div class="mt-6 flex items-center justify-center gap-3">
                        <button
                            class="rounded bg-gray-700 px-4 py-2 text-sm text-white disabled:opacity-40"
                            :disabled="page <= 1"
                            @click="goToPage(page - 1)"
                        >
                            Sebelumnya
                        </button>
                        <span class="text-sm text-gray-500 dark:text-gray-400">
                            Halaman {{ page }} dari {{ totalPages || 1 }}
                        </span>
                        <button
                            class="rounded bg-gray-700 px-4 py-2 text-sm text-white disabled:opacity-40"
                            :disabled="page >= totalPages"
                            @click="goToPage(page + 1)"
                        >
                            Berikutnya
                        </button>
                    </div>
                </template>
            </template>
        </main>
    </FrontLayout>
</template>

<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Services\TmdbService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ExploreController extends Controller
{
    public function index(Request $request, TmdbService $tmdb)
    {
        $query = trim((string) $request->input('q', ''));
        $page = max(1, (int) $request->input('page', 1));

        $data = [
            'configured' => $tmdb->isConfigured(),
            'query' => $query,
            'page' => $page,
            'totalPages' => 1,
            'results' => [],
            'trending' => [],
            'popularMovies' => [],
            'popularTv' => [],
        ];

        if (! $tmdb->isConfigured()) {
            return Inertia::render('Frontend/Explore/Index', $data);
        }

        if ($query !== '') {
            $response = $tmdb->search($query, $page);

            $data['results'] = $this->normalizeMany($tmdb, $response['results'] ?? []);
            $data['totalPages'] = (int) ($response['total_pages'] ?? 1);

            return Inertia::render('Frontend/Explore/Index', $data);
        }

        $moviesResponse = $tmdb->popularMovies($page);
        $tvResponse = $tmdb->popularTv($page);

        $data['trending'] = $this->normalizeMany($tmdb, $tmdb->trending('all', 'week'));
        $data['popularMovies'] = $this->normalizeMany($tmdb, $moviesResponse['results'] ?? [], 'movie');
        $data['popularTv'] = $this->normalizeMany($tmdb, $tvResponse['results'] ?? [], 'tv');
        $data['totalPages'] = max(
            (int) ($moviesResponse['total_pages'] ?? 1),
            (int) ($tvResponse['total_pages'] ?? 1)
        );

        return Inertia::render('Frontend/Explore/Index', $data);
    }

    public function movie(int $id, TmdbService $tmdb)
    {
        $movie = $tmdb->movie($id, ['append_to_response' => 'credits,videos,recommendations']);

        abort_if(! $movie, 404);

        return Inertia::render('Frontend/Explore/Show', [
            'mediaType' => 'movie',
            'item' => $this->normalizeDetail($tmdb, $movie, 'movie'),
            'cast' => $this->normalizeCast($tmdb, $movie),
            'trailers' => $this->normalizeTrailers($movie),
            'recommendations' => $this->normalizeMany(
                $tmdb,
                $movie['recommendations']['results'] ?? [],
                'movie'
            ),
            'seasons' => [],
        ]);
    }

    public function tv(int $id, TmdbService $tmdb)
    {
        $tv = $tmdb->tv($id, ['append_to_response' => 'credits,videos,recommendations']);

        abort_if(! $tv, 404);

        return Inertia::render('Frontend/Explore/Show', [
            'mediaType' => 'tv',
            'item' => $this->normalizeDetail($tmdb, $tv, 'tv'),
            'cast' => $this->normalizeCast($tmdb, $tv),
            'trailers' => $this->normalizeTrailers($tv),
            'recommendations' => $this->normalizeMany(
                $tmdb,
                $tv['recommendations']['results'] ?? [],
                'tv'
            ),
            'seasons' => collect($tv['seasons'] ?? [])->map(fn (array $season) => [
                'season_number' => $season['season_number'] ?? null,
                'name' => $season['name'] ?? '',
                'episode_count' => $season['episode_count'] ?? 0,
                'air_date' => $season['air_date'] ?? null,
                'poster_url' => $tmdb->image($season['poster_path'] ?? null, 'w342'),
            ])->values()->all(),
        ]);
    }

    private function normalizeMany(TmdbService $tmdb, array $items, ?string $mediaType = null): array
    {
        return collect($items)
            ->filter(function (array $item) use ($mediaType) {
                $type = $mediaType ?? $item['media_type'] ?? (isset($item['title']) ? 'movie' : 'tv');

                return in_array($type, ['movie', 'tv'], true);
            })
            ->map(fn (array $item) => $tmdb->normalize($item, $mediaType))
            ->values()
            ->all();
    }

    private function normalizeDetail(TmdbService $tmdb, array $item, string $mediaType): array
    {
        $normalized = $tmdb->normalize($item, $mediaType);

        $normalized['tagline'] = $item['tagline'] ?? null;
        $normalized['status'] = $item['status'] ?? null;
        $normalized['genres'] = collect($item['genres'] ?? [])->pluck('name')->values()->all();
        $normalized['runtime'] = $mediaType === 'movie'
            ? ($item['runtime'] ?? null)
            : ($item['episode_run_time'][0] ?? null);
        $normalized['number_of_seasons'] = $item['number_of_seasons'] ?? null;
        $normalized['number_of_episodes'] = $item['number_of_episodes'] ?? null;

        return $normalized;
    }

    private function normalizeCast(TmdbService $tmdb, array $item): array
    {
        return collect($item['credits']['cast'] ?? [])
            ->take(12)
            ->map(fn (array $cast) => [
                'id' => $cast['id'] ?? null,
                'name' => $cast['name'] ?? '',
                'character' => $cast['character'] ?? null,
                'profile_url' => $tmdb->image($cast['profile_path'] ?? null, 'w185'),
            ])
            ->values()
            ->all();
    }

    private function normalizeTrailers(array $item): array
    {
        return collect($item['videos']['results'] ?? [])
            ->filter(fn (array $video) => ($video['site'] ?? null) === 'YouTube')
            ->take(6)
            ->map(fn (array $video) => [
                'key' => $video['key'] ?? null,
                'name' => $video['name'] ?? '',
                'type' => $video['type'] ?? null,
            ])
            ->values()
            ->all();
    }
}

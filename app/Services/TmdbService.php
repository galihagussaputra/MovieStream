<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TmdbService
{
    public function isConfigured(): bool
    {
        return filled(config('services.tmdb.token')) || filled(config('services.tmdb.secret'));
    }

    public function usesToken(): bool
    {
        return filled(config('services.tmdb.token'));
    }

    public function get(string $path, array $query = []): ?array
    {
        if (! $this->isConfigured()) {
            return null;
        }

        $endpoint = rtrim(config('services.tmdb.endpoint'), '/');
        $path = ltrim($path, '/');

        $query = array_filter(array_merge([
            'language' => config('services.tmdb.language', 'en-US'),
        ], $query), fn ($value) => $value !== null && $value !== '');

        $cacheKey = 'tmdb:'.sha1($endpoint.'/'.$path.'?'.http_build_query($query).'|'.$this->authMode());
        $ttl = (int) config('services.tmdb.cache_ttl', 600);

        $callback = function () use ($endpoint, $path, $query) {
            $request = Http::acceptJson()->timeout(15);

            if ($this->usesToken()) {
                $request = $request->withToken(config('services.tmdb.token'));
            } else {
                $query['api_key'] = config('services.tmdb.secret');
            }

            $response = $request->get($endpoint.'/'.$path, $query);

            if (! $response->successful()) {
                Log::warning('TMDB request failed', [
                    'path' => $path,
                    'status' => $response->status(),
                ]);

                return null;
            }

            return $response->json();
        };

        if ($ttl <= 0) {
            return $callback();
        }

        $result = Cache::remember($cacheKey, $ttl, $callback);

        if ($result === null) {
            Cache::forget($cacheKey);
        }

        return $result;
    }

    public function movie(int $id, array $query = []): ?array
    {
        return $this->get("movie/{$id}", $query);
    }

    public function tv(int $id, array $query = []): ?array
    {
        return $this->get("tv/{$id}", $query);
    }

    public function person(int $id, array $query = []): ?array
    {
        return $this->get("person/{$id}", $query);
    }

    public function season(int $tvId, int $seasonNumber, array $query = []): ?array
    {
        return $this->get("tv/{$tvId}/season/{$seasonNumber}", $query);
    }

    public function episode(int $tvId, int $seasonNumber, int $episodeNumber, array $query = []): ?array
    {
        return $this->get("tv/{$tvId}/season/{$seasonNumber}/episode/{$episodeNumber}", $query);
    }

    public function genres(string $mediaType = 'movie'): array
    {
        return $this->get("genre/{$mediaType}/list")['genres'] ?? [];
    }

    public function trending(string $mediaType = 'all', string $window = 'week'): array
    {
        return $this->get("trending/{$mediaType}/{$window}")['results'] ?? [];
    }

    public function popularMovies(int $page = 1): array
    {
        return $this->get('movie/popular', ['page' => $page]) ?? $this->emptyPage($page);
    }

    public function popularTv(int $page = 1): array
    {
        return $this->get('tv/popular', ['page' => $page]) ?? $this->emptyPage($page);
    }

    public function nowPlaying(int $page = 1): array
    {
        return $this->get('movie/now_playing', ['page' => $page]) ?? $this->emptyPage($page);
    }

    public function search(string $query, int $page = 1): array
    {
        return $this->get('search/multi', [
            'query' => $query,
            'page' => $page,
            'include_adult' => 'false',
        ]) ?? $this->emptyPage($page);
    }

    public function image(?string $path, string $size = 'w500'): ?string
    {
        if (blank($path)) {
            return null;
        }

        return rtrim(config('services.tmdb.image_url', 'https://image.tmdb.org/t/p/'), '/')
            .'/'.trim($size, '/').'/'.ltrim($path, '/');
    }

    public function normalize(array $item, ?string $mediaType = null): array
    {
        $type = $mediaType ?? $item['media_type'] ?? (isset($item['title']) ? 'movie' : 'tv');

        return [
            'id' => $item['id'] ?? null,
            'media_type' => $type,
            'title' => $item['title'] ?? $item['name'] ?? '',
            'overview' => $item['overview'] ?? '',
            'release_date' => $item['release_date'] ?? $item['first_air_date'] ?? null,
            'vote_average' => $item['vote_average'] ?? null,
            'poster_path' => $item['poster_path'] ?? null,
            'backdrop_path' => $item['backdrop_path'] ?? null,
            'poster_url' => $this->image($item['poster_path'] ?? null),
            'backdrop_url' => $this->image($item['backdrop_path'] ?? null, 'w1280'),
        ];
    }

    private function authMode(): string
    {
        return $this->usesToken() ? 'token' : 'key';
    }

    private function emptyPage(int $page): array
    {
        return [
            'page' => $page,
            'results' => [],
            'total_pages' => 1,
            'total_results' => 0,
        ];
    }
}

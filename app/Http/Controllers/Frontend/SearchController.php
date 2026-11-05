<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Episode;
use App\Models\Movie;
use App\Models\TvShow;
use App\Services\TmdbService;
use Illuminate\Http\Request;
use Spatie\Searchable\Search;

class SearchController extends Controller
{
    public function search(Request $request, TmdbService $tmdb)
    {
        $term = trim((string) $request->input('search', ''));

        if ($term === '') {
            return response()->json([]);
        }

        $databaseResults = collect(
            (new Search())
                ->registerModel(Movie::class, 'title')
                ->registerModel(TvShow::class, 'name')
                ->registerModel(Episode::class, 'name')
                ->search($term)
        )->map(fn ($result) => [
            'title' => $result->title,
            'type' => $result->type,
            'url' => $result->url,
        ]);

        $liveResults = collect($tmdb->search($term)['results'] ?? [])
            ->filter(fn (array $item) => in_array($item['media_type'] ?? null, ['movie', 'tv'], true))
            ->take(5)
            ->map(fn (array $item) => [
                'title' => $item['title'] ?? $item['name'] ?? '',
                'type' => $item['media_type'] === 'movie' ? 'Movie (TMDB)' : 'TV (TMDB)',
                'url' => route(
                    $item['media_type'] === 'movie' ? 'explore.movie' : 'explore.tv',
                    $item['id']
                ),
            ]);

        return response()->json($databaseResults->concat($liveResults)->values());
    }
}

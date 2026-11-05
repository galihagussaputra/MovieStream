<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Episode;
use App\Models\Movie;
use App\Models\TvShow;
use App\Services\TmdbService;
use Inertia\Inertia;

class WelcomeController extends Controller
{
    public function index(TmdbService $tmdb)
    {
        $movies = Movie::orderBy('updated_at', 'desc')
            ->with('genres')
            ->take(12)
            ->get();
        $tvShows = TvShow::withCount('seasons')
            ->orderBy('created_at', 'desc')
            ->take(12)
            ->get();
        $episodes = Episode::orderBy('created_at', 'desc')
            ->with('season')
            ->take(12)
            ->get();

        $trending = collect($tmdb->trending('all', 'week'))
            ->filter(fn (array $item) => in_array(
                $item['media_type'] ?? (isset($item['title']) ? 'movie' : 'tv'),
                ['movie', 'tv'],
                true
            ))
            ->take(12)
            ->map(fn (array $item) => $tmdb->normalize($item))
            ->values()
            ->all();

        return Inertia::render('Welcome', [
            'movies' => $movies,
            'episodes' => $episodes,
            'tvShows' => $tvShows,
            'trending' => $trending,
            'tmdbConfigured' => $tmdb->isConfigured(),
        ]);
    }
}

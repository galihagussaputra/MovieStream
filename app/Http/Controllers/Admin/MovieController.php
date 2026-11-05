<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\HandlesAdminIndex;
use App\Http\Controllers\Controller;
use App\Models\Genre;
use App\Models\Movie;
use App\Services\TmdbService;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Inertia\Inertia;

class MovieController extends Controller
{
    use HandlesAdminIndex;

    public function index()
    {
        $perPage = $this->perPage();
        $column = $this->sortColumn(['title', 'rating', 'visits']);
        $direction = $this->sortDirection();

        return Inertia::render('Movies/Index', [
            'movies' => Movie::query()
                ->when(Request::input('search'), function ($query, $search) {
                    $query->where('title', 'like', "%{$search}%");
                })->when($column, function ($query) use ($column, $direction) {
                    $query->orderBy($column, $direction);
                })
                ->paginate($perPage),
            // ->withQueryString(),
            'filters' => Request::only(['search', 'perPage', 'column', 'direction']),
        ]);
    }

    public function store()
    {
        $validated = Request::validate([
            'movieTMDBId' => ['required', 'integer', 'min:1', 'max:2147483647'],
        ]);
        $movieTMDBId = $validated['movieTMDBId'];

        $movie = Movie::where('tmdb_id', $movieTMDBId)->exists();
        if ($movie) {
            return Redirect::back()->with('flash.banner', 'Movie Exists.');
        }

        $apiMovie = app(TmdbService::class)->movie($movieTMDBId);

        if ($apiMovie) {

            $created_movie = Movie::create([
                'tmdb_id' => $apiMovie['id'],
                'title' => $apiMovie['title'],
                'runtime' => $apiMovie['runtime'] ?? 0,
                'rating' => $apiMovie['vote_average'] ?? 0,
                'release_date' => $apiMovie['release_date'] ?? null,
                'lang' => $apiMovie['original_language'] ?? null,
                'video_format' => 'HD',
                'is_public' => false,
                'overview' => $apiMovie['overview'] ?? '',
                'poster_path' => $apiMovie['poster_path'] ?? null,
                'backdrop_path' => $apiMovie['backdrop_path'] ?? null,
            ]);

            $genres = collect($apiMovie['genres'] ?? [])->map(function (array $tmdbGenre) {
                return Genre::firstOrCreate(
                    ['tmdb_id' => $tmdbGenre['id']],
                    ['title' => $tmdbGenre['name']]
                );
            });

            $created_movie->genres()->attach($genres->pluck('id'));

            return Redirect::back()->with('flash.banner', 'Movie create.');

        } else {
            return Redirect::back()->with('flash.banner', 'Api Error.');

        }
    }

    public function edit(Movie $movie)
    {
        return Inertia::render('Movies/Edit', ['movie' => $movie]);
    }

    public function update(Movie $movie)
    {
        $validated = Request::validate([
            'title' => ['required', 'string', 'max:255'],
            'poster_path' => ['required', 'string', 'max:2048'],
            'runtime' => ['required', 'integer', 'min:1', 'max:1000'],
            'lang' => ['required', 'string', 'max:16'],
            'video_format' => ['required', 'string', 'max:50'],
            'rating' => ['required', 'numeric', 'between:0,10'],
            'backdrop_path' => ['required', 'string', 'max:2048'],
            'overview' => ['required', 'string', 'max:10000'],
            'is_public' => ['required', 'boolean'],
        ]);

        $movie->update($validated);

        return Redirect::route('admin.movies.index')->with('flash.banner', 'Movie Updated.');
    }

    public function destroy(Movie $movie)
    {
        $movie->delete();

        return Redirect::route('admin.movies.index')->with('flash.banner', 'Movie Deleted.')->with('flash.bannerStyle', 'danger');
    }
}

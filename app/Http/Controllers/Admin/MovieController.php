<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\HandlesAdminIndex;
use App\Http\Controllers\Controller;
use App\Models\Genre;
use App\Models\Movie;
use Illuminate\Support\Facades\Http;
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
            'filters' => Request::only(['search', 'perPage', 'column', 'direction'])
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

        $apiMovie = Http::asJson()->get(config('services.tmdb.endpoint').'movie/'. $movieTMDBId . '?api_key=' . config('services.tmdb.secret') . '&language=en-US');

        if ($apiMovie->successful()) {

            $created_movie = Movie::create([
                'tmdb_id' => $apiMovie['id'],
                'title' => $apiMovie['title'],
                'runtime' => $apiMovie['runtime'],
                'rating' => $apiMovie['vote_average'],
                'release_date' => $apiMovie['release_date'],
                'lang' => $apiMovie['original_language'],
                'video_format' => 'HD',
                'is_public' => false,
                'overview' => $apiMovie['overview'],
                'poster_path' => $apiMovie['poster_path'],
                'backdrop_path' => $apiMovie['backdrop_path']
            ]);
            $tmdb_genres = $apiMovie['genres'];
            $tmdb_genres_ids = collect($tmdb_genres)->pluck('id');
            $genres = Genre::whereIn('tmdb_id', $tmdb_genres_ids)->get();
            $created_movie->genres()->attach($genres);
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

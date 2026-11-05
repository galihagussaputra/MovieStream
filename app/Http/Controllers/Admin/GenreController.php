<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\HandlesAdminIndex;
use App\Http\Controllers\Controller;
use App\Models\Genre;
use App\Services\TmdbService;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class GenreController extends Controller
{
    use HandlesAdminIndex;

    public function index()
    {
        $perPage = $this->perPage();

        return Inertia::render('Genres/Index', [
            'genres' => Genre::query()
                ->when(Request::input('search'), function ($query, $search) {
                    $query->where('title', 'like', "%{$search}%");
                })
                ->paginate($perPage),
            // ->withQueryString(),
            'filters' => Request::only(['search', 'perPage']),
        ]);
    }

    public function store()
    {
        $tmdb = app(TmdbService::class);
        $genres = collect($tmdb->genres('movie'))
            ->merge($tmdb->genres('tv'))
            ->unique('id');

        if ($genres->isEmpty()) {
            return Redirect::back()->with('flash.banner', 'Api Error.');
        }

        foreach ($genres as $tmdbGenre) {
            Genre::firstOrCreate(
                ['tmdb_id' => $tmdbGenre['id']],
                ['title' => $tmdbGenre['name']]
            );
        }

        return Redirect::back()->with('flash.banner', 'genre created.');
    }

    public function edit(Genre $genre)
    {
        return Inertia::render('Genres/Edit', ['genre' => $genre]);
    }

    public function update(Genre $genre)
    {
        $genre->update(Request::validate([
            'title' => [
                'required',
                'string',
                'max:255',
                Rule::unique('genres', 'title')->ignore($genre),
            ],
        ]));

        return Redirect::route('admin.genres.index')->with('flash.banner', 'Genre updated.');
    }

    public function destroy(Genre $genre)
    {
        $genre->delete();

        return Redirect::route('admin.genres.index')->with('flash.banner', 'Genre deleted.')->with('flash.bannerStyle', 'danger');
    }
}

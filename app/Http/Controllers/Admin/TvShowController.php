<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\HandlesAdminIndex;
use App\Http\Controllers\Controller;
use App\Models\TvShow;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Inertia\Inertia;

class TvShowController extends Controller
{
    use HandlesAdminIndex;

    public function index()
    {
        $perPage = $this->perPage();

        return Inertia::render('TvShows/Index', [
            'tvShows' => TvShow::query()
                ->when(Request::input('search'), function ($query, $search) {
                    $query->where('name', 'like', "%{$search}%");
                })
                ->paginate($perPage),
                // ->withQueryString(),
            'filters' => Request::only(['search', 'perPage'])
        ]);
    }

    public function store()
    {
        $validated = Request::validate([
            'tvShowTMDBId' => ['required', 'integer', 'min:1', 'max:2147483647'],
        ]);
        $tvShowTMDBId = $validated['tvShowTMDBId'];

        $tvShow = TvShow::where('tmdb_id', $tvShowTMDBId)->first();
        if ($tvShow) {
            return Redirect::back()->with('flash.banner', 'Tv Show Exists.');
        }

        $tmdb_tv = Http::asJson()->get(config('services.tmdb.endpoint') . 'tv/' . $tvShowTMDBId . '?api_key=' . config('services.tmdb.secret') . '&language=en-US');
        if ($tmdb_tv->successful()) {
            TvShow::create([
                'tmdb_id' => $tmdb_tv['id'],
                'name'    => $tmdb_tv['name'],
                'poster_path' => $tmdb_tv['poster_path'],
                'created_year' => $tmdb_tv['first_air_date']
            ]);
            return Redirect::back()->with('flash.banner', 'Tv Show created.');
        } else {
            return Redirect::back()->with('flash.banner', 'Api error.');
        }      
    }

    public function edit(TvShow $tvShow)
    {
        return Inertia::render('TvShows/Edit', ['tvShow' => $tvShow]);
    }

    public function update(TvShow $tvShow)
    {
        $validated = Request::validate([
            'name' => ['required', 'string', 'max:255'],
            'poster_path' => ['required', 'string', 'max:2048'],
        ]);
        $tvShow->update($validated);
        return Redirect::route('admin.tv-shows.index')->with('flash.banner', 'Tv Show updated.');
    }

    public function destroy(TvShow $tvShow)
    {
        $tvShow->delete();
        return Redirect::route('admin.tv-shows.index')->with('flash.banner', 'Tv Show deleted.')->with('flash.bannerStyle', 'danger');

    }
}

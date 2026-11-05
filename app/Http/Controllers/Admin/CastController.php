<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\HandlesAdminIndex;
use App\Http\Controllers\Controller;
use App\Models\Cast;
use App\Services\TmdbService;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class CastController extends Controller
{
    use HandlesAdminIndex;

    public function index()
    {
        $perPage = $this->perPage();

        return Inertia::render('Casts/Index', [
            'casts' => Cast::query()
                ->when(Request::input('search'), function ($query, $search) {
                    $query->where('name', 'like', "%{$search}%");
                })
                ->paginate($perPage),
            // ->withQueryString(),
            'filters' => Request::only(['search', 'perPage']),
        ]);
    }

    public function store()
    {
        $validated = Request::validate([
            'castTMDBId' => ['required', 'integer', 'min:1', 'max:2147483647'],
        ]);
        $castTMDBId = $validated['castTMDBId'];

        $cast = Cast::where('tmdb_id', $castTMDBId)->first();
        if ($cast) {
            return Redirect::back()->with('flash.banner', 'Cast Exists.');
        }

        $tmdb_cast = app(TmdbService::class)->person($castTMDBId);

        if ($tmdb_cast) {
            Cast::create([
                'tmdb_id' => $tmdb_cast['id'],
                'name' => $tmdb_cast['name'],
                'slug' => Str::slug($tmdb_cast['name']),
                'poster_path' => $tmdb_cast['profile_path'],
            ]);

            return Redirect::back()->with('flash.banner', 'Cast created.');
        } else {
            return Redirect::back()->with('flash.banner', 'Api error.');
        }
    }

    public function edit(Cast $cast)
    {
        return Inertia::render('Casts/Edit', ['cast' => $cast]);
    }

    public function update(Cast $cast)
    {
        $validated = Request::validate([
            'name' => ['required', 'string', 'max:255'],
            'poster_path' => ['required', 'string', 'max:2048'],
        ]);
        $cast->update($validated);

        return Redirect::route('admin.casts.index')->with('flash.banner', 'Cast Updated.');
    }

    public function destroy(Cast $cast)
    {
        $cast->delete();

        return Redirect::back()->with('flash.banner', 'Cast Deleted.');
    }
}

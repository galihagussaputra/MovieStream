<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Download;
use App\Models\Movie;
use App\Models\Tag;
use App\Models\TrailerUrl;
use App\Models\Cast;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Inertia\Inertia;


class MovieAttachController extends Controller
{
    public function index(Movie $movie)
    {
        return Inertia::render('Movies/Attach', [
            'movie' => $movie,
            'trailers' => $movie->trailers,
            'downloads' => $movie->downloads,
            'casts' => Cast::all('id', 'name'),
            'tags' => Tag::all('id', 'tag_name'),
            'movieCasts' => $movie->casts,
            'movieTags' => $movie->tags
        ]);
    }

    public function addTrailer(Movie $movie)
    {
        $movie->trailers()->create(Request::validate([
            'name' => ['required', 'string', 'max:255'],
            'embed_html' => ['required', 'string', 'max:10000', 'not_regex:/<\s*script\b/i'],
        ]));
        return Redirect::back()->with('flash.banner', 'Trailer Added.');
    }

    public function addDownload(Movie $movie)
    {
        $movie->downloads()->create(Request::validate([
            'name' => ['required', 'string', 'max:255'],
            'web_url' => ['required', 'url:http,https', 'max:2048'],
        ]));
        return Redirect::back()->with('flash.banner', 'Download Added.');
    }

    public function destroyTrailer(TrailerUrl $trailerUrl)
    {
        $trailerUrl->delete();
        return Redirect::back()->with('flash.banner', 'Trailer deleted.');
    }

    public function destroyDownload(Download $download)
    {
        $download->delete();
        return Redirect::back()->with('flash.banner', 'Download deleted.');
    }

    public function addCast(Movie $movie)
    {
        $validated = Request::validate([
            'casts' => ['present', 'array'],
            'casts.*.id' => ['required', 'integer', 'exists:casts,id'],
        ]);
        $movie->casts()->sync(collect($validated['casts'])->pluck('id'));
        return Redirect::back()->with('flash.banner', 'Casts attached.');
    }
    public function addTag(Movie $movie)
    {
        $validated = Request::validate([
            'tags' => ['present', 'array'],
            'tags.*.id' => ['required', 'integer', 'exists:tags,id'],
        ]);
        $movie->tags()->sync(collect($validated['tags'])->pluck('id'));
        return Redirect::back()->with('flash.banner', 'Tags attached.');
    }
}

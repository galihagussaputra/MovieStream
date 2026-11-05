<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\HandlesAdminIndex;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use App\Models\Tag;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TagController extends Controller
{
    use HandlesAdminIndex;

    public function index(){
        $perPage = $this->perPage();
        return Inertia::render('Tags/Index', [
            'tags' => Tag::query()
                           ->when(Request::input('search'), function($query, $search) {
                               $query->where('tag_name', 'like', "%{$search}%");
                           })
                           ->paginate($perPage)
                           ->withQueryString(),
            'filters' => Request::only(['search', 'perPage'])               
        ]);
    }

    public function create()
    {
        return Inertia::render('Tags/Create');
    }
    public function store()
    {
        $validated = Request::validate([
            'tagName' => ['required', 'string', 'max:255', Rule::unique('tags', 'tag_name')],
        ]);

        Tag::create([
            'tag_name' => $validated['tagName'],
            'slug' => Str::slug($validated['tagName']),
        ]);

        return Redirect::route('admin.tags.index')->with('flash.banner', 'Tag Created.');

    }

    public function edit(Tag $tag)
    {
        return Inertia::render('Tags/Edit', [
            'tag' => $tag
        ]);
    }

    public function update(Tag $tag)
    {
        $validated = Request::validate([
            'tagName' => [
                'required',
                'string',
                'max:255',
                Rule::unique('tags', 'tag_name')->ignore($tag),
            ],
        ]);

        $tag->update([
            'tag_name' => $validated['tagName'],
            'slug' => Str::slug($validated['tagName']),
        ]);

        return Redirect::route('admin.tags.index')->with('flash.banner', 'Tag updated.');
    }

    public function destroy(Tag $tag)
    {
        $tag->delete();
        return Redirect::route('admin.tags.index')->with('flash.banner', 'Tag deleted.')->with('flash.bannerStyle', 'danger');
    }
}

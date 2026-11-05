<?php

namespace Tests\Feature;

use App\Models\TvShow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TvShowSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_tv_show_search_results_link_to_the_tv_show_page(): void
    {
        $tvShow = TvShow::create([
            'tmdb_id' => 1,
            'name' => 'Show A',
            'poster_path' => '/poster.jpg',
        ]);

        $response = $this->get('/api/search?search=Show%20A');

        $response->assertOk();
        $response->assertJsonFragment([
            'title' => 'Show A',
            'url' => route('tvShows.show', $tvShow->slug),
        ]);
    }
}

<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Movie;
use App\Models\Season;
use App\Models\TvShow;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    public function test_movie_store_requires_a_valid_tmdb_id(): void
    {
        $response = $this->actingAs($this->admin())
            ->post(route('admin.movies.store'), ['movieTMDBId' => 'not-an-id']);

        $response->assertSessionHasErrors('movieTMDBId');
        $this->assertDatabaseCount('movies', 0);
    }

    public function test_tv_show_store_requires_a_valid_tmdb_id(): void
    {
        $response = $this->actingAs($this->admin())
            ->post(route('admin.tv-shows.store'), ['tvShowTMDBId' => 'not-an-id']);

        $response->assertSessionHasErrors('tvShowTMDBId');
        $this->assertDatabaseCount('tv_shows', 0);
    }

    public function test_season_and_episode_store_require_valid_numbers(): void
    {
        $admin = $this->admin();
        $tvShow = $this->tvShow();
        $season = $this->season($tvShow);

        $seasonResponse = $this->actingAs($admin)->post(
            route('admin.seasons.store', $tvShow),
            ['seasonNumber' => 'not-a-number']
        );
        $seasonResponse->assertSessionHasErrors('seasonNumber');

        $episodeResponse = $this->actingAs($admin)->post(
            route('admin.episodes.store', [$tvShow, $season]),
            ['episodeNumber' => 0]
        );
        $episodeResponse->assertSessionHasErrors('episodeNumber');
    }

    public function test_cast_store_requires_a_valid_tmdb_id(): void
    {
        $response = $this->actingAs($this->admin())
            ->post(route('admin.casts.store'), ['castTMDBId' => 'not-an-id']);

        $response->assertSessionHasErrors('castTMDBId');
        $this->assertDatabaseCount('casts', 0);
    }

    public function test_movie_update_rejects_invalid_runtime_and_rating(): void
    {
        $movie = $this->movie();

        $response = $this->actingAs($this->admin())->put(route('admin.movies.update', $movie), [
            'title' => $movie->title,
            'poster_path' => $movie->poster_path,
            'runtime' => 'not-a-number',
            'lang' => $movie->lang,
            'video_format' => $movie->video_format,
            'rating' => 11,
            'backdrop_path' => $movie->backdrop_path,
            'overview' => $movie->overview,
            'is_public' => true,
        ]);

        $response->assertSessionHasErrors(['runtime', 'rating']);
    }

    public function test_movie_attachments_validate_urls_and_embeds(): void
    {
        $admin = $this->admin();
        $movie = $this->movie();

        $downloadResponse = $this->actingAs($admin)->post(
            route('admin.movies.add.download', $movie),
            ['name' => '1080p', 'web_url' => 'not-a-url']
        );
        $downloadResponse->assertSessionHasErrors('web_url');

        $trailerResponse = $this->actingAs($admin)->post(
            route('admin.movies.add.trailer', $movie),
            ['name' => 'Trailer', 'embed_html' => '<script>alert(1)</script>']
        );
        $trailerResponse->assertSessionHasErrors('embed_html');
    }

    public function test_movie_attachments_require_existing_cast_and_tag_ids(): void
    {
        $admin = $this->admin();
        $movie = $this->movie();

        $castResponse = $this->actingAs($admin)->post(
            route('admin.movies.add.casts', $movie),
            ['casts' => [['id' => 999]]]
        );
        $castResponse->assertSessionHasErrors('casts.0.id');

        $tagResponse = $this->actingAs($admin)->post(
            route('admin.movies.add.tags', $movie),
            ['tags' => [['id' => 999]]]
        );
        $tagResponse->assertSessionHasErrors('tags.0.id');
    }

    public function test_movie_index_clamps_per_page_and_ignores_invalid_sorting(): void
    {
        $admin = $this->admin();
        $this->movie();

        $response = $this->actingAs($admin)->get(route('admin.movies.index', [
            'perPage' => 999,
            'column' => 'not-a-column',
            'direction' => 'not-a-direction',
        ]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Movies/Index')
            ->where('movies.per_page', 100));
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('admin'));

        return $user;
    }

    private function movie(): Movie
    {
        return Movie::create([
            'tmdb_id' => '1',
            'title' => 'Movie A',
            'release_date' => '2024-01-01',
            'runtime' => '120',
            'lang' => 'en',
            'video_format' => 'HD',
            'is_public' => true,
            'rating' => '8.0',
            'poster_path' => '/poster.jpg',
            'backdrop_path' => '/backdrop.jpg',
            'overview' => 'Movie overview.',
        ]);
    }

    private function tvShow(): TvShow
    {
        return TvShow::create([
            'tmdb_id' => 1,
            'name' => 'Show A',
            'poster_path' => '/poster.jpg',
        ]);
    }

    private function season(TvShow $tvShow): Season
    {
        return Season::create([
            'tmdb_id' => 1,
            'tv_show_id' => $tvShow->id,
            'name' => 'Season 1',
            'season_number' => 1,
            'poster_path' => '/poster.jpg',
        ]);
    }
}

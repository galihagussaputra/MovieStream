<?php

namespace Tests\Feature;

use App\Models\Episode;
use App\Models\Season;
use App\Models\TvShow;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminTvHierarchyTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_season_must_belong_to_the_tv_show_in_the_route(): void
    {
        $admin = $this->admin();
        $tvShow = $this->tvShow(1, 'Show A');
        $otherTvShow = $this->tvShow(2, 'Show B');
        $season = $this->season(1, $otherTvShow, 1);

        $response = $this->actingAs($admin)->get(route('admin.seasons.edit', [
            'tv_show' => $tvShow->id,
            'season' => $season->id,
        ]));

        $response->assertNotFound();
    }

    public function test_a_season_belonging_to_the_tv_show_can_be_resolved(): void
    {
        $admin = $this->admin();
        $tvShow = $this->tvShow(1, 'Show A');
        $season = $this->season(1, $tvShow, 1);

        $response = $this->actingAs($admin)->get(route('admin.seasons.edit', [
            'tv_show' => $tvShow->id,
            'season' => $season->id,
        ]));

        $response->assertOk();
    }

    public function test_an_episode_must_belong_to_the_season_in_the_route(): void
    {
        $admin = $this->admin();
        $tvShow = $this->tvShow(1, 'Show A');
        $season = $this->season(1, $tvShow, 1);
        $otherSeason = $this->season(2, $tvShow, 2);
        $episode = $this->episode(1, $otherSeason, 1);

        $response = $this->actingAs($admin)->get(route('admin.episodes.edit', [
            'tv_show' => $tvShow->id,
            'season' => $season->id,
            'episode' => $episode->id,
        ]));

        $response->assertNotFound();
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('admin'));

        return $user;
    }

    private function tvShow(int $tmdbId, string $name): TvShow
    {
        return TvShow::create([
            'tmdb_id' => $tmdbId,
            'name' => $name,
            'poster_path' => '/poster.jpg',
        ]);
    }

    private function season(int $tmdbId, TvShow $tvShow, int $seasonNumber): Season
    {
        return Season::create([
            'tmdb_id' => $tmdbId,
            'tv_show_id' => $tvShow->id,
            'name' => "Season {$seasonNumber}",
            'season_number' => $seasonNumber,
            'poster_path' => '/poster.jpg',
        ]);
    }

    private function episode(int $tmdbId, Season $season, int $episodeNumber): Episode
    {
        return Episode::create([
            'tmdb_id' => $tmdbId,
            'season_id' => $season->id,
            'name' => "Episode {$episodeNumber}",
            'episode_number' => $episodeNumber,
            'overview' => 'Episode overview.',
        ]);
    }
}

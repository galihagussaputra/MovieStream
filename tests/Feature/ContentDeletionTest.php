<?php

namespace Tests\Feature;

use App\Models\Cast;
use App\Models\Episode;
use App\Models\Genre;
use App\Models\Movie;
use App\Models\Season;
use App\Models\Tag;
use App\Models\TvShow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_deleting_a_tv_show_deletes_its_seasons_and_episodes(): void
    {
        $tvShow = $this->tvShow();
        $season = $this->season($tvShow);
        $episode = $this->episode($season);

        $tvShow->delete();

        $this->assertDatabaseMissing('tv_shows', ['id' => $tvShow->id]);
        $this->assertDatabaseMissing('seasons', ['id' => $season->id]);
        $this->assertDatabaseMissing('episodes', ['id' => $episode->id]);
    }

    public function test_deleting_a_season_deletes_its_episodes(): void
    {
        $tvShow = $this->tvShow();
        $season = $this->season($tvShow);
        $episode = $this->episode($season);

        $season->delete();

        $this->assertDatabaseHas('tv_shows', ['id' => $tvShow->id]);
        $this->assertDatabaseMissing('seasons', ['id' => $season->id]);
        $this->assertDatabaseMissing('episodes', ['id' => $episode->id]);
    }

    public function test_deleting_a_movie_deletes_its_attachments_and_pivots(): void
    {
        $movie = $this->movie();
        $genre = $this->genre();
        $cast = $this->cast();
        $tag = $this->tag();
        $trailer = $movie->trailers()->create([
            'name' => 'Trailer',
            'embed_html' => '<iframe></iframe>',
        ]);
        $download = $movie->downloads()->create([
            'name' => '1080p',
            'web_url' => 'https://example.com/movie',
        ]);

        $movie->genres()->attach($genre);
        $movie->casts()->attach($cast);
        $movie->tags()->attach($tag);

        $movie->delete();

        $this->assertDatabaseMissing('movies', ['id' => $movie->id]);
        $this->assertDatabaseMissing('trailer_urls', ['id' => $trailer->id]);
        $this->assertDatabaseMissing('downloads', ['id' => $download->id]);
        $this->assertDatabaseMissing('genre_movie', ['movie_id' => $movie->id]);
        $this->assertDatabaseMissing('cast_movie', ['movie_id' => $movie->id]);
        $this->assertDatabaseMissing('taggables', ['taggable_id' => $movie->id]);
    }

    public function test_deleting_a_genre_removes_its_movie_pivots(): void
    {
        $movie = $this->movie();
        $genre = $this->genre();
        $movie->genres()->attach($genre);

        $genre->delete();

        $this->assertDatabaseHas('movies', ['id' => $movie->id]);
        $this->assertDatabaseMissing('genre_movie', ['genre_id' => $genre->id]);
    }

    public function test_deleting_a_cast_removes_its_movie_pivots(): void
    {
        $movie = $this->movie();
        $cast = $this->cast();
        $movie->casts()->attach($cast);

        $cast->delete();

        $this->assertDatabaseHas('movies', ['id' => $movie->id]);
        $this->assertDatabaseMissing('cast_movie', ['cast_id' => $cast->id]);
    }

    public function test_deleting_a_tag_removes_its_taggable_pivots(): void
    {
        $movie = $this->movie();
        $tag = $this->tag();
        $movie->tags()->attach($tag);

        $tag->delete();

        $this->assertDatabaseHas('movies', ['id' => $movie->id]);
        $this->assertDatabaseMissing('taggables', ['tag_id' => $tag->id]);
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

    private function episode(Season $season): Episode
    {
        return Episode::create([
            'tmdb_id' => 1,
            'season_id' => $season->id,
            'name' => 'Episode 1',
            'episode_number' => 1,
            'overview' => 'Episode overview.',
        ]);
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

    private function genre(): Genre
    {
        return Genre::create([
            'tmdb_id' => 1,
            'title' => 'Action',
        ]);
    }

    private function cast(): Cast
    {
        return Cast::create([
            'tmdb_id' => 1,
            'name' => 'Actor',
            'poster_path' => '/actor.jpg',
        ]);
    }

    private function tag(): Tag
    {
        return Tag::create([
            'tag_name' => 'Featured',
            'slug' => 'featured',
        ]);
    }
}

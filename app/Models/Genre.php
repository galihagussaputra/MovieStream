<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;


class Genre extends Model
{
    use HasFactory;

    protected $fillable = ['tmdb_id', 'title', 'slug'];

    protected static function booted(): void
    {
        static::deleting(function (Genre $genre) {
            $genre->movies()->detach();
        });
    }

    public function setTitleAttribute($value)
    {
        $this->attributes['title'] = $value;
        $this->attributes['slug'] = Str::slug($value);
    }

    public function movies()
    {
        return $this->belongsToMany(Movie::class);

    }
}

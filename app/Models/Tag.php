<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    use HasFactory;

    protected $fillable = ['tag_name', 'slug'];

    protected static function booted(): void
    {
        static::deleting(function (Tag $tag) {
            $tag->movies()->detach();
        });
    }

    public function movies()
    {
        return $this->morphedByMany(Movie::class, 'taggable');
    }
}

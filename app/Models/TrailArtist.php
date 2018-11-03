<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrailArtist extends Model
{
    protected $table = 'trail_artist';

    protected $fillable = [
        'trail_id',
        'artist_id',
    ];
}

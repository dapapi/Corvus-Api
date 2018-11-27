<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BloggerProducer extends Model
{
    protected  $table = 'blogger_producer';
    protected $fillable = [
        'name',
    ];
}

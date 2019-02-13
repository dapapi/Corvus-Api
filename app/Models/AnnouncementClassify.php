<?php

namespace App\Models;

use App\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;


class AnnouncementClassify extends Model
{
    use SoftDeletes;
    protected $table =  'announcement_classify';
    protected $fillable = [
        'name',
        'desc'
    ];

    public function scopeCreateDesc($query)
    {

        return $query->orderBy('id');

    }
    public  function sum()
    {
        return $this->hasOne(Announcement::class, 'classify', 'id')->select(DB::raw('count(*) as sum'));
    }
}

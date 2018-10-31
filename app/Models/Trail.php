<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Trail extends Model
{
    use SoftDeletes {
        restore as private restoreSoftDeletes;
    }

    // 线索来源类型
    const PERSONAL = 1;
    const MAIL = 2;
    const SENIOR = 3;

    protected $fillable = [
        'title',
        'principal_id',
        'client_id',
        'artist_id',
        'contact_id',
        'progress_status',
        'source_type',
        'desc',
        'type',
        'status',
    ];

    protected $dates = ['deleted_at'];
}

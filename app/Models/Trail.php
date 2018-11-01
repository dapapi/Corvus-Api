<?php

namespace App\Models;

use App\User;
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
        'brand',
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

    public function principal()
    {
        return $this->belongsTo(User::class, 'principal_id', 'id');
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class, 'contact_id', 'id');
    }

    public function client()
    {
        return $this->belongsTo(Client::class, 'contact_id', 'id');
    }

//    public function artist()
//    {
//        return $this->belongsTo(Artist::class, 'principal_id', 'id');
//    }
}

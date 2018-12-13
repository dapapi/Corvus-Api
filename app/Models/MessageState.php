<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MessageState extends Model
{
    const UN_READ = 1;//未读
    const HAS_READ = 2;//已读
//    use SoftDeletes;
    public function data()
    {
        return $this->hasMany(MessageData::class,"message_id",'message_id');
    }
}

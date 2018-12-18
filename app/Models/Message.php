<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Message extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'module',
        'title',
        'link'
    ];
    const STAR = 207;
    const BLOGGER = 208;
    const TRAILS = 209;
    const CLIENT = 210;
    const PROJECT = 211;
    const TASK = 212;
    const CONTRACT = 213;
    const CALENDAR = 214;
    const ATTENDANCE = 215;
    const APPROVAL = 216;
    const REPOSITORY = 217;
    const ANNOUNCENMENT = 218;
    const REPORT = 219;
    const PERSONNELMANAGE = 220;
    public function data()
    {
        return $this->hasMany(MessageData::class,"message_id",'id');
    }
    public function recive()
    {
        return $this->hasMany(MessageState::class,'message_id','id');
    }
}

<?php

namespace App\Models;

use App\User;
use App\Models\ReviewAnswer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
class ReviewQuestionnaire extends Model
{
    use SoftDeletes;
    protected $fillable = ['name','creator_id', 'deadline', 'reviewable_id', 'reviewable_type', 'auth_type'];


    public function scopeCreateDesc($query)
    {
        return $query->orderBy('created_at', 'desc');
    }
    public function questions() {
        return $this->hasMany(ReviewQuestion::class,'review_id','id')->orderBy('sort', 'asc');
    }
    public function sum() {

     $data =  $this->hasMany(ReviewAnswer::class, 'review_id', 'id')->select('*',DB::raw('sum(content) as counts'))->groupby('user_id');
        return $data;
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id', 'id');
    }

    public function operateLogs()
    {
        return $this->morphMany(OperateLog::class, 'logable');
    }

    public function tasks()
    {
        return $this->morphToMany(Task::class, 'resourceable', 'task_resources');
    }


}

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

         // 总分
     $sums =  $this->hasMany(ReviewAnswer::class, 'review_id', 'id')->select('review_id',DB::raw('sum(content) as sums'))->groupby('review_id')->get();

        // 参与人数
     $count =  count($this->hasMany(ReviewAnswer::class, 'review_id', 'id')->select('user_id',DB::raw('count(user_id) as counts'))->groupby('user_id')->get()->toArray());

        $data =  $this->hasMany(ReviewAnswer::class, 'review_id', 'id')->select('*',DB::raw('TRUNCATE('.$sums[0]->sums.'/'.$count.',2) as TRUNCATE'))->groupby('review_id');
     //->select('*',DB::raw('sum(content) as counts'))->groupby('review_id'),
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
    public function production()
    {
        return $this->belongsTo(Production::class, 'reviewable_id', 'id');
    }

}

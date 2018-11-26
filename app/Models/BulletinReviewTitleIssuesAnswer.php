<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BulletinReviewTitleIssuesAnswer extends Model
{
    protected  $table = 'bulletion_review_title_issues_answer';
    protected $fillable = [
        'bulletin_review_title_id', // 简报类型
        'issues',  //成员
        'answer',     //简报周期




    ];

    public function scopeCreateDesc($query)
    {

        return $query->orderBy('updated_at');

    }

    public function creator()
    {

      //  return $this->belongsTo(User::class, 'creator_id', 'id');
    }

    public function affixes()
    {

        return $this->morphMany(Affix::class, 'affixable');
    }

    public function operateLogs()
    {
        return $this->morphMany(OperateLog::class, 'logable');
    }

    public function tasks()
    {

        return $this->morphToMany(Task::class, 'resourceable','task_resources');
    }

    public function broker()
    {
       // return $this->belongsTo(User::class, 'broker_id', 'id');

    }

}

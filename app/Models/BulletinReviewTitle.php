<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BulletinReviewTitle extends Model
{
    protected  $table = 'bulletin_review_title';
    protected $fillable = [
        'bulletin_review_id', // 简报类型
        'creator_id',  //创建人
        'reviewer_id',     //评论
        'comment_id', //问题
        'title',     //标题
        'status',    // 默认0      1  待审批   2 已审批



    ];

    public function scopeCreateDesc($query)
    {

        return $query->orderBy('updated_at');

    }

    public function creator()
    {

        return $this->belongsTo(User::class, 'creator_id', 'id');
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
        return $this->belongsTo(User::class, 'broker_id', 'id');

    }

     public function issues()
    {
        return $this->hasMany(BulletinReviewTitleIssuesAnswer::class, 'bulletin_review_title_id');
        //return $this->belongsTo(BulletinReviewTitleIssuesAnswer::class, 'id', 'bulletin_review_title_id');
      //  return $this->morphToMany(BulletinReviewTitleIssuesAnswer::class, 'resourceable','bulletion_review_title');
    }
}

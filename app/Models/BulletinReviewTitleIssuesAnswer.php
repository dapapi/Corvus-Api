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


}

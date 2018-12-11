<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Draft extends Model
{
    use SoftDeletes;
    protected $table = 'draft';
    protected $fillable = [
        'template_id',
        'member',
        'reviewer_id'
    ];
//隐藏字段
//'contract_type',//合同类型
//'divide_into_proportion',//分成比例

    public function scopeCreateDesc($query)
    {
        return $query->orderBy('created_at');
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
        return $this->morphToMany(Task::class, 'resourceable', 'task_resources');
    }
    public function project()
    {
        return $this->morphToMany(Project::class, 'resourceable', 'project_resources');
    }
    public function producer()
    {
        return $this->belongsTo(User::class, 'producer_id', 'id');
    }
    public function issues()
    {

        return $this->belongsTo(DraftIssuesAnswer::class, 'id', 'draft_id');
        //  return $this->morphToMany(BulletinReviewTitleIssuesAnswer::class, 'resourceable','bulletion_review_title');
    }
    public function getAnswerAttribute()
    {
        return $this->issues()->get(['issues_id','answer']);
    }
    public function bulletinReview()
    {
        return $this->hasMany(Draft::class,'template_id','id' );
//        return $this->belongsTo(BulletinReview::class, 'template_id', 'id');
    }

    public function getStatusAttribute()
    {

        //
        $user = Auth::guard('api')->user();
        $query = $this->BulletinReview()->where('member', $user->id);

        switch ($this->frequency) {
            case 1:
                break;
            case 2:
                break;
            case 3:
                break;
            case 4:
                break;
            default:
                $query->where('created_at', '>=', Carbon::today()->toDateTimeString())->where('created_at', '<=',Carbon::tomorrow()->toDateTimeString());
                break;
        }

        $review = $query->first();

        if ($review)
            return $review->status;
        else
            return null;
    }

}

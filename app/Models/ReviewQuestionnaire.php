<?php

namespace App\Models;

use App\User;
use App\Models\ReviewAnswer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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

//        ReviewAnswer::select(DB::raw('sum(content) as counts'))->where('review_id',$reviewquestionnaire->id)->groupBy('user_id')->get()
        return $this->hasMany(ReviewAnswer::class, 'review_id', 'id');
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
//    public function bulletincotent()
//    {
//        return $this->hasMany(ReviewAnswer::class,'template_id','id' );
////        return $this->belongsTo(BulletinReview::class, 'template_id', 'id');
//    }
//
//    public function getStatusAttribute()
//    {
//        //
//        $user = Auth::guard('api')->user();
//        $query = $this->BulletinReview()->where('member', $user->id);
//
//        switch ($this->frequency) {
//            case 1:
//                break;
//            case 2:
//                break;
//            case 3:
//                break;
//            case 4:
//                break;
//            default:
//                $query->where('created_at', '>=', Carbon::today()->toDateTimeString())->where('created_at', '<=',Carbon::tomorrow()->toDateTimeString());
//                break;
//        }
//
//        $review = $query->first();
//
//        if ($review){
//            $re['id'] = hashid_encode($review->id);
//            $re['status'] = $review->status;
//
//            return  $re;
//        }else{
//            return null;
//        }
//    }

}

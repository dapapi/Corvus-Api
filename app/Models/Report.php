<?php

namespace App\Models;

use App\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Report extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'template_name', // 模板名称
        'colour',
        'frequency',  //频率
        'department_id', //模板对象id
        'member', //成员id
        'creator_id',  // 创建人  id


    ];
    protected $dates = ['deleted_at'];

    public function scopeCreateDesc($query)
    {

        return $query->orderBy('id');

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
//    public function template()
//    {
//
//        return $this->belongsTo(Report::class, 'template_id','id');
//    }
// 一对多 - Post::comments()
    public function bulletinReview()
    {
        return $this->hasMany(BulletinReview::class,'template_id','id' );
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

        if ($review){
            $re['id'] = hashid_encode($review->id);
            $re['status'] = $review->status;

            return  $re;
    }else{
            return null;
        }
    }
    public function setStateAttribute($value)
    {
        $this->attributes['state'] = strtolower($value);
    }

}

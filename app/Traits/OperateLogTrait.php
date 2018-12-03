<?php
/**
 * Created by PhpStorm.
 * User: xiao
 * Date: 2018/12/3
 * Time: 上午11:18
 */
namespace App\Traits;

use App\Models\OperateLog;
use App\OperateLogMethod;

trait OperateLogTrait
{
    public function operateLogs()
    {
        return $this->morphMany(OperateLog::class, 'logable');
    }

    public function getLastFollowUpAtAttribute()
    {
        $lastFollowUp = $this->operateLogs()->where('method', OperateLogMethod::FOLLOW_UP)->orderBy('created_at', 'desc')->first();

        if ($lastFollowUp)
            return $lastFollowUp->created_at->toDateTimeString();
        else
            return null;
    }

    public function getLastUpdatedUserAttribute()
    {
        $lastFollowUp = $this->operateLogs()->where('method', OperateLogMethod::UPDATE)->orderBy('created_at', 'desc')->first();

        if ($lastFollowUp)
            return $lastFollowUp->user()->name;
        else
            return null;
    }

    public function getLastUpdatedAtAttribute()
    {
        $lastFollowUp = $this->operateLogs()->where('method', OperateLogMethod::UPDATE)->orderBy('created_at', 'desc')->first();

        if ($lastFollowUp)
            return $lastFollowUp->created_at->toDateTimeString();
        else
            return null;
    }

    public function getRefusedAtAttribute()
    {
        $lastFollowUp = $this->operateLogs()->where('method', OperateLogMethod::REFUSE)->orderBy('created_at', 'desc')->first();

        if ($lastFollowUp)
            return $lastFollowUp->created_at->toDateTimeString();
        else
            return null;
    }

    public function getRefusedUserAttribute()
    {
        $lastFollowUp = $this->operateLogs()->where('method', OperateLogMethod::REFUSE)->orderBy('created_at', 'desc')->first();

        if ($lastFollowUp)
            return $lastFollowUp->user()->name;
        else
            return null;
    }
}
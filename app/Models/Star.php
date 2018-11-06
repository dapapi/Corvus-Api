<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Star extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'desc',
        'broker_id',
        'avatar',
        'gender',
        'birthday',
        'phone',
        'wechat',
        'email',
        'source',//艺人来源
        'communication_status',//沟通状态
        'intention',//与我司签约意向
        'intention_desc',//不与我司签约原因
        'sign_contract_other',//是否签约其他公司
        'sign_contract_other_name',//签约公司名称
        'sign_contract_at',//签约日期
        'sign_contract_status',//签约状态
        'contract_type',//合同类型
        'divide_into_proportion',//分成比例
        'terminate_agreement_at',//解约日期
        'creator_id',//录入人
        'status',
        'type',
    ];

    public function scopeCreateDesc($query)
    {
        return $query->orderBy('created_at', 'desc');
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

}

<?php

namespace App\Models;
use App\ModuleUserType;
use App\User;
use App\Traits\OperateLogTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContractQinyang extends Model
{
    use SoftDeletes;
    use OperateLogTrait;
    protected $fillable = [
        'nickname',
        'contract_no',//合同编号
        'type_id',//合同类型
        'contract_company',//合同公司
        'nickname',//昵称
        'name',//姓名
        'contract_name',//合同名称
        'treaty_particulars',//合同摘要
        'business_id',//业务类型
        'contract_start_date',//合约起始日
        'contract_end_date',//合约终止日
        'earnings',//收益分配比例
        'certificate_id',//证件类别
        'certificate_number',//certificate_number
        'certificate_affix_id',//certificate_affix_id
        'scanning_affix_id',//scanning_affix_id
        'scanning',//份数
        'contract_affix_id',//附件类别

    ];
//隐藏字段
//'contract_type',//合同类型
//'divide_into_proportion',//分成比例

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

    public function tasks()
    {
        return $this->morphToMany(Task::class, 'resourceable', 'task_resources')->orderBy('priority', 'status')->limit(5);
    }

    public function producer()
    {
        return $this->belongsTo(User::class, 'producer_id', 'id');
    }

    public function type()
    {
        return $this->belongsTo(BloggerType::class, 'type_id', 'id');
    }

    public function trail()
    {
        return $this->morphToMany(Trail::class, 'starable', 'trail_star')->wherePivot('type', TrailStar::EXPECTATION);
    }
    public function publicity()
    {
        return $this->belongsToMany(User::class,"module_users","moduleable_id")->where('type',ModuleUserType::PRODUCER);
    }

}

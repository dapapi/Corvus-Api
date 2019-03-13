<?php

namespace App\Models;

use App\Traits\OperateLogTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use App\Repositories\ScopeRepository;
use App\Scopes\SearchDataScope;


class Contract extends Model
{
    private $model_dic_id = DataDictionarie::CONTRACTS;//数据字典中模块id
    protected $fillable = [
        'contract_number',
        'title',
        'form_instance_number',
        'contract_start_date',
        'contract_end_date',
        'contract_money',
        'contract_sharing_ratio',
        'creator_id',
        'creator_name',
        'project_id',
        'client_id',
        'type',
        'stars',
        'star_type',
        'updater_id',
        'updater_name',
    ];
    use OperateLogTrait;


    public function scopeSearchData($query)
    {
        $user = Auth::guard("api")->user();
        $userid = $user->id;

        $rules = (new ScopeRepository())->getDataViewUsers($this->model_dic_id);

        return  (new SearchDataScope())->getCondition($query,$rules,$userid)->orWhereRaw("{$userid} in (
            select usr.id from contracts as c 
            left join approval_form_participants as afps on afps.notice_id = c.creator_id
             left join users as usr on usr.id = afps.notice_id  where usr.id = c.creator_id
        )");

    }



    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'id');
    }

    public function stars()
    {
        if ($this->star_type == 'stars')
            return $this->belongsTo(Star::class, 'project_id', 'id');
        else
            return $this->belongsTo(Project::class, 'project_id', 'id');
    }

    public function operateLogs()
    {
        return $this->morphMany(OperateLog::class, 'logable');
    }
}

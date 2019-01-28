<?php

namespace App\Models\ApprovalForm;

use App\Interfaces\ApprovalInstanceInterface;
use App\Models\DataDictionarie;
use App\Models\DataDictionary;
use App\Models\OperateLog;
use App\Repositories\ScopeRepository;
use App\Scopes\SearchDataScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;


class Business extends Model implements ApprovalInstanceInterface
{
    protected $table = 'approval_form_business';
    private $model_dic_id = DataDictionarie::CONTRACTS;//数据字典中模块id
    public $timestamps = false;
    protected $fillable = [
        'form_id',
        'form_instance_number',
        'form_status',
        'business_type',
    ];
    //合同查询作用域，用于权限
    public function scopeContractSearchData($query)
    {
        $user = Auth::guard("api")->user();
        $userid = $user->id;
        $rules = (new ScopeRepository())->getDataViewUsers($this->model_dic_id);
        return (new SearchDataScope())->getCondition($query,$rules,$userid)->orWhereRaw("{$userid} in (
            select u.id from contracts as c 
            left join approval_form_participants as afps on afps.notice_id = c.creator_id 
  
             left join users as u on u.id = afps.notice_id where c.id = cs.id
        )");
    }

    public function form()
    {
        return $this->belongsTo(ApprovalForm::class, 'form_id', 'form_id');
    }

    public function status()
    {
        return $this->belongsTo(DataDictionary::class, 'form_status', 'id');
    }

    public function fields()
    {
        return $this->hasMany(InstanceValue::class, 'form_instance_number', 'form_instance_number');
    }
    public function operateLogs()
    {
        return $this->morphMany(OperateLog::class, 'logable');
    }
}

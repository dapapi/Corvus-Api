<?php

namespace App\Models\ApprovalForm;

use App\Interfaces\ApprovalInstanceInterface;
use App\Models\DataDictionarie;
use App\Models\DataDictionary;
use App\Models\OperateLog;
use Illuminate\Database\Eloquent\Model;

class Business extends Model implements ApprovalInstanceInterface
{
    protected $table = 'approval_form_business';

    public $timestamps = false;
    private  $model_dic_id = DataDictionarie::APPROVAL;//数据字典中模块id
    protected $fillable = [
        'form_id',
        'form_instance_number',
        'form_status',
        'business_type',
    ];

    public function scopeSearchData($query)
    {
        $user = Auth::guard("api")->user();
        $userid = $user->id;
        $rules = (new Business())->getDataViewUsers($this->model_dic_id);
        return (new Business())->getCondition($query,$rules,$userid)->orWhereRaw("{$userid} in (
            select u.id from stars as s 
            left join module_users as mu on mu.moduleable_id = s.id and 
            mu.moduleable_type='".ModuleableType::App.
            "' left join users as u on u.id = mu.user_id where s.id = stars.id
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

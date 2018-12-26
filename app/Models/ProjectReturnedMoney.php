<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
class ProjectReturnedMoney extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'contract_id',
        'project_id',
        'p_id',
        'creator_id',
        'principal_id',
        'issue_name',
        'plan_returned_money',
        'plan_returned_time',
        'project_returned_money_type_id',
        'desc',

    ];

    public function scopeCreateDesc($query)
    {
        return $query->orderBy('created_at', 'asc');
    }
    public function money()
    {
        return $this->belongsTo(ProjectReturnedMoney::class, 'id', 'p_id');
    }
    public function type()
    {
        return $this->belongsTo(ProjectReturnedMoneyType::class, 'project_returned_money_type_id', 'id');
    }
    public function practicalSum() {

        // 总分
        $type_id =ProjectReturnedMoneyType::where('type',1)->get(['id']);
        $practicalsums =  $this->hasMany(ProjectReturnedMoney::class, 'p_id', 'id')->wherein('project_returned_money_type_id',$type_id)->select('*',DB::raw('sum(plan_returned_money) as practicalsums'));
        return $practicalsums;
    }
    public function invoiceSum() {

        // 总分
        $type_id =ProjectReturnedMoneyType::where('type',2)->get(['id']);
        $practicalsums =  $this->hasMany(ProjectReturnedMoney::class, 'p_id', 'id')->wherein('project_returned_money_type_id',$type_id)->select('*',DB::raw('sum(plan_returned_money) as practicalsums'));
        return $practicalsums;
    }
}

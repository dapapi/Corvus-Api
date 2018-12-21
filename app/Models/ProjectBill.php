<?php

namespace App\Models;


use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ProjectBill extends Model
{
   protected $table = 'project_bill';

    protected $fillable = [
        'account_year',
        'account_period',
        'voucher_number',
        'project_kd_code',
        'project_kd_name',
        'bill_number',
        'expense_name',
        'artist_name',
        'money',
        'pay_rec_time',
        'action_user',
        'expense_type',
        'apply_by',
        'apply_at',
        'audit_by'
    ];

    public function scopeCreateDesc($query)
    {
        return $query->orderBy('pay_rec_time', 'desc');
    }
    public function expendituresum() {

        // 总分
        $sums =  $this->hasMany(project::class, 'title', 'project_kd_name')->select(DB::raw('sum(content) as sums'))->groupby('review_id')->get();
dd($sums);


    }
}

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
        //支出总分
        $sums =  $this->hasMany(ProjectBill::class, 'title', 'project_kd_name')->where('expense_type',2)->select(DB::raw('sum(money) as sums'))->groupby('expense_type')->get();
dd($sums);
//        // 总分
//        $sums =  $this->hasMany(ReviewAnswer::class, 'review_id', 'id')->select('review_id',DB::raw('sum(content) as sums'))->groupby('review_id')->get();
//
//        // 参与人数
//        $count =  count($this->hasMany(ReviewAnswer::class, 'review_id', 'id')->select('user_id',DB::raw('count(user_id) as counts'))->groupby('user_id')->get()->toArray());
//
//        $data =  $this->hasMany(ReviewAnswer::class, 'review_id', 'id')->select('*',DB::raw('TRUNCATE('.$sums[0]->sums.'/'.$count.',2) as TRUNCATE'))->groupby('review_id');
        //->select('*',DB::raw('sum(content) as counts'))->groupby('review_id'),
        return $sums;
    }
}

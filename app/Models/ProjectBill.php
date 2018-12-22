<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ProjectBill extends Model
{

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
    public function scopeExpendItureSum($query)
    {
        return $query->select(DB::raw('sum(money) as expendituresum'))->groupby('expense_type');

    }
    public function getStatusAttribute($value)
    {
        $this->attributes['expendituresum'] = strtolower($value);
    }
    public function setStateAttribute($value)
    {
        $this->attributes['expendituresum'] = strtolower($value);
    }
}

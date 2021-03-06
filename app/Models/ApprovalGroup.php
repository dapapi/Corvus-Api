<?php

namespace App\Models;

use App\Models\ApprovalForm\ApprovalForm;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApprovalGroup extends Model
{
    protected $table = 'approval_form_groups';
    use SoftDeletes;

    protected $fillable = [
        'name',
        'icon',
        'sort_number',
        'description',
        'created_by',
        'created_at',
        'updated_by',
        'updated_at',
        'order_by',
    ];

    public function forms()
    {
        return $this->hasMany(ApprovalForm::class, 'group_id', 'id');
    }
}

<?php

namespace App\Models\ApprovalForm;

use App\Models\ApprovalFlow\Condition;
use App\Models\DataDictionary;
use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApprovalForm extends Model
{
    use SoftDeletes;
    protected $table = 'approval_forms';

    protected $fillable = [
        'form_id',
        'name',
        'group_id',
        'modified',
        'description',
        'icon',
        'change_type',
        'sort_number',
        'created_by',
        'created_at',
        'updated_by',
        'updated_at',
        'order_by',
    ];

    public function application()
    {
        return $this->belongsTo(User::class, 'apply_id', 'id');
    }

    public function changeTypeDetail()
    {
        return $this->belongsTo(DataDictionary::class, 'change_type', 'id');
    }

    public function modifiedDetail()
    {
        return $this->belongsTo(DataDictionary::class, 'modified', 'id');
    }

    public function controls()
    {
        return $this->hasMany(Control::class, 'form_id', 'form_id')->orderBy('sort_number');
    }

    public function getConditionControlAttribute()
    {
        $controlIds = Condition::where('form_id', $this->form_id)->value('form_control_id');
        if (is_null($controlIds))
            return null;

        $controlArr = explode(',', $controlIds);
        foreach ($controlArr as &$control) {
            $control = hashid_encode($control);
        }
        unset($control);
        return $controlArr;
    }
}

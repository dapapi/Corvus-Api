<?php

namespace App\Models\ApprovalForm;

use Illuminate\Database\Eloquent\Model;

class ControlEnums extends Model
{
    protected $table = 'approval_form_control_enums';

    protected $fillable = [
        'form_control_id',
        'enum_value',
        'sort_number'
    ];

    public function getEnumValueAttribute($value)
    {
        if (strpos($value, ':') === false) {
            return $value;
        } else {
            $enum = explode(':', $value);
            $result = [
                'column' => $enum[0],
                'list' => explode('|', $enum[1]),
            ];
            return $result;
        }
    }
}

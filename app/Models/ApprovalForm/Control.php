<?php

namespace App\Models\ApprovalForm;

use App\Models\DataDictionary;
use Illuminate\Database\Eloquent\Model;

class Control extends Model
{
    protected $table = 'approval_form_controls';

    protected $fillable = [
        'form_control_id',
        'form_id',
        'control_id',
        'pid',
        'sort_number',
        'required',
        'created_by',
        'created_at',
        'order_by',
    ];

    public function properties()
    {
        return $this->hasMany(ControlProperty::class, 'form_control_id', 'form_control_id');
    }

    public function value($num = null)
    {
         $value = $this->hasMany(InstanceValue::class, 'form_control_id', 'form_control_id')->where('form_instance_number', $num)->first();
         if ($value)
             return $value->form_control_value;
         return null;
    }

    public function dictionary()
    {
        return $this->belongsTo(DataDictionary::class, 'control_id', 'id');
    }

    public function getTitleAttribute()
    {
        $property_value = $this->properties()->where('property_id', 67)->select('property_value')->first();
        if ($property_value)
            return $property_value->property_value;
        return null;
    }

    public function getPlaceholderAttribute()
    {
        $property_value = $this->properties()->where('property_id', 68)->select('property_value')->first();
        if ($property_value)
            return $property_value->property_value;
        return null;
    }

    public function getFormatAttribute()
    {
        $property_value = $this->properties()->where('property_id', 69)->select('property_value')->first();
        if ($property_value)
            return $property_value->property_value;
        return null;
    }

    public function getSourceAttribute()
    {
        $property_value = $this->properties()->where('property_id', 389)->select('property_value')->first();
        if ($property_value)
            return json_decode($property_value->property_value);
        return null;
    }

    public function enum()
    {
        return $this->hasMany(ControlEnums::class, 'form_control_id', 'form_control_id')->orderBy('sort_number')->select('enum_value');
    }
}

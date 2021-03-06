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

         return $value;
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

    public function getSubTitleAttribute()
    {
        $property_value = $this->properties()->where('property_id', 72)->select('property_value')->first();
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

    public function getSubPlaceholderAttribute()
    {
        $property_value = $this->properties()->where('property_id', 73)->select('property_value')->first();
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

    public function getRelateAttribute()
    {
        $property_value = $this->properties()->where('property_id', 390)->select('property_value')->first();
        if ($property_value)
            return $property_value->property_value;
        return null;
    }

    public function getDisabledAttribute()
    {
        $property_value = $this->properties()->where('property_id', 410)->select('property_value')->first();
        if ($property_value)
            return 1;
        return 0;
    }

    public function getIndefiniteShowAttribute()
    {
        $property_value = $this->properties()->where('property_id', 503)->select('property_value')->first();
        if ($property_value)
            return 1;
        return null;
    }

    public function enum()
    {
        return $this->hasMany(ControlEnums::class, 'form_control_id', 'form_control_id')->orderBy('sort_number')->select('enum_value');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FieldValue extends Model
{
    protected $table = 'template_field_values';

    protected $fillable = [
        'field_id',
        'project_id',
        'value',
    ];

    public function field()
    {
        return $this->belongsTo(TemplateField::class, 'field_id', 'id');
    }
}

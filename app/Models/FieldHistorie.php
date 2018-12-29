<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FieldHistorie extends Model
{
    protected $table = 'template_field_value_histories';

    protected $fillable = [
        'field_id',
        'project_id',
        'value',
    ];

    public function field()
    {
        return $this->belongsTo(FieldHistorie::class, 'field_id', 'id');
    }
}

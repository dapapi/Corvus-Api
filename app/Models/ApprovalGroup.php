<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApprovalGroup extends Model
{
    protected $fillable = [
        'name',
        'sort',
        'desc',
    ];
}

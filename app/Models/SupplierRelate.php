<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class SupplierRelate extends Model
{
    protected $table = 'supplier_relates';

    protected $fillable = [
        'id',
        'type',
        'key',
        'value',
        'currency',
        'supplier_id',

    ];





}

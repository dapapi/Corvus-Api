<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DashboardRelate extends Model
{
    protected $table = 'dashboard_relates';

    protected $fillable = [
        'dashboard_id',
        'includes'
    ];
}

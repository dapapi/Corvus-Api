<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Dashboard extends Model
{
    use SoftDeletes {
        restore as private restoreSoftDeletes;
    }
    //
    protected $table = 'department_dashboards';

    protected $fillable = [
        'name',
        'includes',
        'creator_id',
        'department_id',
        'desc'
    ];

    public function department()
    {
        return $this->belongsTo(Department:: class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class);
    }

    public function relate()
    {
        return $this->hasOne(DashboardRelate::class, 'dashboard_id', 'id');
    }
}

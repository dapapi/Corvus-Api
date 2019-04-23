<?php

namespace App\Models;

use App\Repositories\ScopeRepository;
use App\Scopes\SearchDataScope;
use App\Traits\OperateLogTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;






class Supplier extends Model
{
    use OperateLogTrait;
    protected $fillable = [
        'name',
        'code',
        'create_id',
        'address',
        'level',
    ];




    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'id');
    }

    public function stars()
    {
        if ($this->star_type == 'stars')
            return $this->belongsTo(Star::class, 'project_id', 'id');
        else
            return $this->belongsTo(Project::class, 'project_id', 'id');
    }

    public function operateLogs()
    {
        return $this->morphMany(OperateLog::class, 'logable');
    }

    public function archives()
    {
        return $this->hasMany(ContractArchive::class);
    }
}

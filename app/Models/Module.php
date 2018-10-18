<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// php artisan make:model Models/Dummy
class Module extends Model
{
    public function actions()
    {
        return $this->hasMany(Action::class);
    }
}

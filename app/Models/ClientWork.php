<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\User;


class ClientWork extends Model
{

    protected $table = 'client_work';
    protected $fillable = [
        'client_id',
        'works',
    ];


}

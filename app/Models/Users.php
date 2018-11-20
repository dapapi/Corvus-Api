<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Users extends Model
{
    protected $table = 'users';


    protected $fillable = [
        'name',
        'password',
        'email',
        'remember_token',
        'en_name',
        'gender',
        'id_number',
        'phone',
        'political',
        'marriage',
        'cadastral_address',
        'national',
        'current_address',
        'gender',
        'id_number',
        'birth_time',
        'entry_time',
        'blood_type',
        'status',
    ];



    const USER_STATUS_ONE = 1; //
    const USER_STATUS_TOW = 2; //
    const USER_STATUS_THREE = 3; //
    const USER_STATUS_FOUR = 4; //

    const USER_PSWORD = '$2y$10$8D4nCQeQDaCVlPfCveE.2eT4aJyvzxRIQpvpunptdYzGmsQ9hWLJy';
    const SIZE_LISTED = 2;
    const SIZE_TOP500 = 3;

    const STATUS_NORMAL = 1;
    const STATUS_FROZEN = 2;


    public function affixes()
    {
        return $this->morphMany(Users::class, 'users');
    }

}

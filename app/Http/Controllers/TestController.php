<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;

class TestController extends Controller
{
    const NAME = 'name';

    public function hello()
    {
        return $this->response->array([
            'success' => true,
            'message' => 'hello Corvus CRM'
        ]);
    }

    public function signin()
    {
        $user = User::where(self::NAME, 'wyjson')->first();
        $token = $user->createToken('web api')->accessToken;

        return $this->response->array(['token_type' => 'Bearer', 'access_token' => $token]);
    }

}

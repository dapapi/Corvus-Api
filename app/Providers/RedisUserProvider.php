<?php

namespace App\Providers;

use Illuminate\Auth\EloquentUserProvider;

class RedisUserProvider extends EloquentUserProvider
{
    /**
     * Retrieve a user by the given credentials.
     *
     * @param  array  $credentials
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByCredentials(array $credentials)
    {
        if (!isset($credentials['api_token'])) {
            return;
        }

        $userId = Redis::get($credentials['api_token']);

        return $this->retrieveById($userId);
    }
}

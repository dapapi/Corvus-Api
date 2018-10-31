<?php

namespace App\Policies;

use App\Models\Action;
use App\Models\Module;
use App\User;
use App\Models\Client;
use Illuminate\Auth\Access\HandlesAuthorization;

class ClientPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the client.
     *
     * @param  \App\User  $user
     * @param  \App\Models\Client  $client
     * @return mixed
     */
    public function view(User $user, Client $client)
    {
        //
    }

    /**
     * Determine whether the user can create clients.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        $action = Module::where('code', 'clients')->first()->actions()->where('code', 'create')->first();
        $actionId = $action->id;

        $userRoles = $user->roles;

        foreach ($userRoles as $userRole) {
            $action = $userRole->actions()->find($actionId);
            if ($action)
                return true;
        }
        return abort(403, '没有权限操作');
    }

    /**
     * Determine whether the user can update the client.
     *
     * @param  \App\User  $user
     * @param  \App\Models\Client  $client
     * @return mixed
     */
    public function update(User $user, Client $client)
    {
        //
    }

    /**
     * Determine whether the user can delete the client.
     *
     * @param  \App\User  $user
     * @param  \App\Models\Client  $client
     * @return mixed
     */
    public function delete(User $user, Client $client)
    {
        //
    }

    /**
     * Determine whether the user can restore the client.
     *
     * @param  \App\User  $user
     * @param  \App\Models\Client  $client
     * @return mixed
     */
    public function restore(User $user, Client $client)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the client.
     *
     * @param  \App\User  $user
     * @param  \App\Models\Client  $client
     * @return mixed
     */
    public function forceDelete(User $user, Client $client)
    {
        //
    }
}

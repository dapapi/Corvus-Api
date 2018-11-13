<?php

namespace App\Policies;

use App\Models\Blogger;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BloggerPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the blogger.
     *
     * @param  \App\User  $user
     * @param  \App\Blogger  $blogger
     * @return mixed
     */
    public function view(User $user, Blogger $blogger)
    {
        //
    }

    /**
     * Determine whether the user can create bloggers.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        //
    }

    /**
     * Determine whether the user can update the blogger.
     *
     * @param  \App\User  $user
     * @param  \App\Blogger  $blogger
     * @return mixed
     */
    public function update(User $user, Blogger $blogger)
    {
        //
    }

    /**
     * Determine whether the user can delete the blogger.
     *
     * @param  \App\User  $user
     * @param  \App\Blogger  $blogger
     * @return mixed
     */
    public function delete(User $user, Blogger $blogger)
    {
        //
    }

    /**
     * Determine whether the user can restore the blogger.
     *
     * @param  \App\User  $user
     * @param  \App\Blogger  $blogger
     * @return mixed
     */
    public function restore(User $user, Blogger $blogger)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the blogger.
     *
     * @param  \App\User  $user
     * @param  \App\Blogger  $blogger
     * @return mixed
     */
    public function forceDelete(User $user, Blogger $blogger)
    {
        //
    }
}

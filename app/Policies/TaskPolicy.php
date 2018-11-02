<?php

namespace App\Policies;

use App\Models\Module;
use App\Models\Task;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TaskPolicy
{
    use HandlesAuthorization;


    /**
     * Determine whether the user can view the task.
     *
     * @param  \App\User $user
     * @param  \App\Models\Task $task
     * @return mixed
     */
    public function view(User $user, Task $task)
    {
        //
    }

    /**
     * Determine whether the user can create tasks.
     *
     * @param  \App\User $user
     * @return mixed
     */
    public function create(User $user)
    {
        //
    }

    /**
     * Determine whether the user can update the task.
     *
     * @param  \App\User $user
     * @param  \App\Models\Task $task
     * @return mixed
     */
    public function update(User $user, Task $task)
    {
        //
    }

    /**
     * Determine whether the user can delete the task.
     *
     * @param  \App\User $user
     * @param  \App\Models\Task $task
     * @return mixed
     */
    public function delete(User $user, Task $task)
    {
        $action = Module::where('code', 'tasks')->first()->actions()->where('code', 'delete')->first();
        $actionId = $action->id;

        $userRoles = $user->roles;

        foreach ($userRoles as $userRole) {
            $action = $userRole->actions()->find($actionId);
            if ($action) {
                //创建人,负责人
                $userId = $user->id;
                if ($userId == $task->creator_id || $userId == $task->principal_id) {
                    return true;
                }
                //参与人
                $participantUsers = $task->participants()->get();
                foreach ($participantUsers as $participantUser) {
                    if ($userId == $participantUser->id) {
                        return true;
                    }
                }
            }
        }
        return abort(403, '没有操作权限');
    }

    /**
     * Determine whether the user can restore the task.
     *
     * @param  \App\User $user
     * @param  \App\Models\Task $task
     * @return mixed
     */
    public function restore(User $user, Task $task)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the task.
     *
     * @param  \App\User $user
     * @param  \App\Models\Task $task
     * @return mixed
     */
    public function forceDelete(User $user, Task $task)
    {
        //
    }
}

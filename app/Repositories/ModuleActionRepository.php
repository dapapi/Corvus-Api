<?php

namespace App\Repositories;

use App\Models\Module;

class ModuleActionRepository
{
    public function findUserPermission($user, $moduleCode, $actionCode)
    {
        $action = Module::where('code', $moduleCode)->first()->actions()->where('code', $actionCode)->first();
        $actionId = $action->id;

        $userRoles = $user->roles;

        foreach ($userRoles as $userRole) {
            $action = $userRole->actions()->find($actionId);
            if ($action)
                return true;
        }
        return false;
    }
}

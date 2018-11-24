<?php

namespace App\Http\Controllers;

use App\Http\Transformers\UserTransformer;
use App\Models\Department;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::orderBy('name')->get();

        return $this->response->collection($users, new UserTransformer());
    }

    public function my(Request $request)
    {
        $user = Auth::guard('api')->user();

        return $this->response->item($user, new UserTransformer());
    }

    private function department(Department $department)
    {
        $department = $department->pDepartment;
        if ($department->department_pid == 0) {
            return $department;
        } else {
            $this->department($department);
        }
    }
}

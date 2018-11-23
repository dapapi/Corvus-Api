<?php

namespace App\Http\Controllers;

use App\Http\Transformers\DepartmentTransformer;
use App\Models\Department;
use App\Models\DepartmentUser;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index(Request $request)
    {
        $depatments = Department::where('department_pid', 0)->get();
        return $this->response->collection($depatments, new DepartmentTransformer());
    }
}

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
        $depatments = Department::all();
        return $this->response->collection($depatments, new DepartmentTransformer());
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Transformers\ModuleTransformer;
use App\Models\Module;
use Illuminate\Http\Request;

class RoleActionController extends Controller
{
    public function index(Request $request)
    {
        $modules = Module::all();
        return $this->response->collection($modules, new ModuleTransformer());
    }
}

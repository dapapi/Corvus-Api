<?php

namespace App\Http\Controllers;

use App\Http\Transformers\FilterFieldTransformer;
use App\Models\FilterField;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class FilterFieldController extends Controller
{
    public function index(Request $request)
    {
        // 应该对应的表
        $path = $request->path();
        $splitter = strpos($path, '/');
        $table = substr($path,0,$splitter);

        //
        $user = Auth::guard('api')->user();
        $departmentId = $user->department()->first()->id;

        $fields = FilterField::where('table_name', $table)->where('department_id', $departmentId)->get();

        return $this->response->collection($fields, new FilterFieldTransformer());
    }

}

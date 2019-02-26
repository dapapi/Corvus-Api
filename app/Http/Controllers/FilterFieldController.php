<?php

namespace App\Http\Controllers;

use App\Http\Transformers\FilterFieldTransformer;
use App\Models\FilterField;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FilterFieldController extends Controller
{
    public function index(Request $request)
    {
        // 应该对应的表
        $path = $request->path();
        $splitter = strpos($path, '/');
        $table = substr($path,0,$splitter);

        $fields = FilterField::where('table_name', $table)->get();

        return $this->response->collection($fields, new FilterFieldTransformer());
    }


}

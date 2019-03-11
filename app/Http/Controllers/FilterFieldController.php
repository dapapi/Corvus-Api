<?php

namespace App\Http\Controllers;

use App\Http\Transformers\FilterFieldTransformer;
use App\Models\FilterField;
use App\Models\DataDictionary;
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
        //   分papi 和 泰洋    暂时不用
//        $user = Auth::guard('api')->user();
//        if($table != 'bloggers' || $table != 'stars'){
//
//            $company_id = $user->department()->get(['company_id']);
//            $name = DataDictionary::where('id',$company_id)->select('name')->get();
//            if($name == '泰洋川禾'){
//                $fields = FilterField::where('table_name', $table)->where('organization',1)->get();
//                return $this->response->collection($fields, new FilterFieldTransformer());
//            }else if($name == '短视频组'){
//                $fields = FilterField::where('table_name', $table)->where('organization',2)->get();
//                return $this->response->collection($fields, new FilterFieldTransformer());
//            }else{

//            }
//        }
        $fields = FilterField::where('table_name', $table)->get();
        return $this->response->collection($fields, new FilterFieldTransformer());
    }


}

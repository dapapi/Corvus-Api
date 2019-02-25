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


//    public function getTableNameAndCondition(Request $request,$model)
//    {
//      $payload = $request->all();
//      //$query =$model->query();
//        $array = [];//查询条件
//      if(empty($payload['conditions'])){
//         return null;
//      }
//      foreach($payload['conditions'] as $k => $v){
//
//          switch ($v['operator']) {
//              case 'LIKE':
//                  $field = $v['field'];
//                  $value = $v['type'];
//               //   $query->where($field,'like','%'.$value.'%');
//                  $array[]  = [$field,'like','%'.$value.'%'];
//                  break;
//              case 'IN':
//                  $field = $v['field'];
//                  $value = $v['value'];
//                //  $query->whereIn($field,'In',$value);
//                  $array[]  = [$field,'In',$value];
//                  break;
//              case '>':
//                  $value = $v['value'];
//                  $field = $v['field'];
//                //  $query->whereIn($field,'>',$value);
//                  $array[]  = [$field,'>',$value];
//                  break;
//              case '>=':
//                  $value = $v['value'];
//                  $field = $v['field'];
//                //  $query->whereIn($field,'>=',$value);
//                  $array[]  = [$field,'>=',$value];
//                  break;
//              case '<':
//                  $value = $v['value'];
//                  $field = $v['field'];
//                //  $query->whereIn($field,'<',$value);
//                  $array[]  = [$field,'<',$value];
//                  break;
//              case '<=':
//                  $value = $v['value'];
//                  $field = $v['field'];
//                 // $query->whereIn($field,'<=',$value);
//                  $array[]  = [$field,'<=',$value];
//                  break;
//
//              default:
//                  break;
//          }
//
//        }
//       // dd($query);
//      //  return $query;
//      return $array;
//    }
//
//    public function getTableNameInCondition(Request $request,$model)
//    {
//        $payload = $request->all();
//        $array = [];//查询条件
//        $query =$model->query();
//        if(empty($payload['conditions'])){
//            return null;
//        }
//        foreach($payload['conditions'] as $k => $v){
//
//            switch ($v['operator']) {
//                case 'IN':
//                    $field = $v['field'];
////                    $value = implode(',',$v['value']);
//                    $value = $v['value'];
//                    $array = [$field,$value];
//                    //$query->whereIn($field,$value);
//                    break;
//
//                default:
//                    break;
//            }
//
//        }
//        return $array;
//    }

}

<?php
/**
 * Created by PhpStorm.
 * User: apple
 * Date: 2018/12/18
 * Time: 3:12 PM
 */

namespace App\Http\Controllers;


use App\Models\DataDictionarie;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DataDictionaryController extends Controller
{
    /**
     * 数据字典列表
     */
    public function index(Request $request)
    {
        $parent_id = $request->get('parent_id',0);
        return DataDictionarie::where('parent_id',$parent_id)->select('id','code','val','icon','name','description','created_by','created_at')->get();
    }

    /**
     * 存储数据字典
     * @param Request $request
     * @return \Dingo\Api\Http\Response|void
     */
    public function store(Request $request)
    {
        $payload = $request->all();
        $user = Auth::guard("api")->user();
        $payload['created_by']  =   $user->name;
        DB::beginTransaction();
        try{
            DataDictionarie::create($payload);
            DB::commit();
            return $this->response->noContent();
        }catch (\Exception $e){
            DB::rollBack();
            return $this->response->errorInternal("数据字典添加失败");
        }
    }
}
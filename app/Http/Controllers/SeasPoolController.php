<?php

namespace App\Http\Controllers;

use App\Events\OperateLogEvent;
use App\Http\Requests\Filter\FilterRequest;
use App\Http\Transformers\TrailTransformer;
use App\Models\Department;
use App\Models\Message;
use App\Models\OperateEntity;
use App\Models\Trail;
use App\OperateLogMethod;
use App\Repositories\MessageRepository;
use App\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SeasPoolController extends Controller
{
    public function index(Request $request)
    {
        $payload = $request->all();
        $user = Auth::guard('api')->user();
        $department_id = Department::where('name', '商业管理部')->first();
        $pageSize = $request->get('page_size', config('app.page_size'));

        $takeType = isset($payload['take_type']) ? $payload['take_type'] : 0;

        $receive = isset($payload['receive_type']) ? $payload['receive_type'] : 0;


        $trails = Trail::where(function ($query) use ($request, $payload, $takeType,$receive) {
            if ($request->has('keyword') && $payload['keyword'])
                $query->where('title', 'LIKE', '%' . $payload['keyword'] . '%');
            if ($takeType ==1){

                $query->where('take_type', 1);
            }else{
                $query->whereIn('pool_type', [1,2,3]);
            }
            if ($receive ==1){
                $query->where('take_type', $receive);
            }elseif($receive ==2){
                $query->where('take_type',2);
            }

            if ($request->has('pool_type') && !is_null($payload['pool_type']))
                $query->where('pool_type', $payload['pool_type']);


            //->searchData()
        })->orderBy('created_at', 'desc')->paginate($pageSize);

        return $this->response->paginator($trails, new TrailTransformer());
    }

    public function receive(Request $request)
    {
        $payload = $request->all();

        $user = Auth::guard('api')->user();
        $userName = $user->name;
        DB::beginTransaction();
        try {
            if(!empty($payload['id'])){
               foreach ($payload['id'] as $valId){
                    //修改领取销售线索状态
                   $array = [
                       'principal_id' => $user->id,
                       'take_type' => 2
                   ];
                    //判断是否领取
                   $principal = DB::table('trails')->select('principal_id')->where('id',hashid_decode($valId))->get()->toArray();
                   if($principal[0]->principal_id !==0){

                       return $this->response->errorInternal('该销售线索有负责人');

                   }

                   $num = DB::table('trails')->where('id',hashid_decode($valId))->update($array);

                   $trail = Trail::where('id',hashid_decode($valId))->first();

                   // 操作日志
                   $operate = new OperateEntity([
                       'obj' => $trail,
                       'title' => null,
                       'start' => null,
                       'end' => null,
                       'method' => OperateLogMethod::RECEIVE,
                   ]);

                   event(new OperateLogEvent([
                       $operate,
                   ]));
               }

            }

        } catch (Exception $e) {
            Log::error($e);
            return $this->response->errorInternal('跟进失败');
        }
        DB::commit();

    }

    public function allot(Request $request)
    {
        $payload = $request->all();

        $user = Auth::guard('api')->user();
        $userName = $user->name;
        if (!isset($payload['user_id'])) {

            return $this->response->errorInternal('请选择被分配人');

        }
        $userId = hashid_decode($payload['user_id']);
        $userInfo = DB::table('users')->where('users.id', $userId)->select('users.name')->first();
        DB::beginTransaction();
        try {


            if(!empty($payload['id'])){
                foreach ($payload['id'] as $valId){
                    //修改领取销售线索状态

                    //修改分配销售线索状态
                    $array = [
                        'principal_id' => $userId,
                        'take_type' => 2
                    ];
                    //判断是否领取
                    $principal = DB::table('trails')->select('principal_id')->where('id',hashid_decode($valId))->get()->toArray();

                    if($principal[0]->principal_id !==0){

                        return $this->response->errorInternal('该销售线索有负责人');

                    }

                    $num = DB::table('trails')->where('id',hashid_decode($valId))->update($array);

                    $trail = Trail::where('id',hashid_decode($valId))->first();

                    // 操作日志
                    $operate = new OperateEntity([
                        'obj' => $trail,
                        'title' => $userInfo->name,
                        'start' => null,
                        'end' => null,
                        'method' => OperateLogMethod::ALLOT,
                    ]);

                    event(new OperateLogEvent([
                        $operate,
                    ]));
                }

            }


        } catch (Exception $e) {
            Log::error($e);
            return $this->response->errorInternal('分配失败');
        }
        DB::commit();

        //分配销售线索发消息
        DB::beginTransaction();
        try {

            $user = Auth::guard('api')->user();
            $subheading = $title = $user->name . "将销售线索".$trail->title."分配给了您";  //通知消息的标题
            $module = Message::TRAILS;
            $data = [];
            $data[] = [
                "title" => '线索名称', //通知消息中的消息内容标题
                'value' => $trail->title,  //通知消息内容对应的值
            ];
            $principal = User::findOrFail($trail->principal_id);
            $data[] = [
                'title' => '负责人',
                'value' => $principal->name
            ];
            $data[] = [
                'title' =>  '预计订单费用',
                "value" =>  $trail->fee
            ];

            $recives[] = $trail->principal_id;//负责人
            $authorization = $request->header()['authorization'][0];
            (new MessageRepository())->addMessage($user, $authorization, $title, $subheading, $module, null, $data, $recives,$trail->id);

            DB::commit();
        }catch (Exception $e){
            DB::rollBack();
            Log::error($e);
        }

    }

    public function refund(Request $request, Trail $trail)
    {
        $payload = $request->all();
        $trailId = $trail->id;

        $user = Auth::guard('api')->user();
        $userName = $user->name;

        $content = addslashes($payload['content']);

        DB::beginTransaction();
        try {

            //修改分配销售线索状态
            $array = [
                'principal_id' => '',
                'take_type' => 1
            ];
            $principal = DB::table('trails')->select('principal_id')->where('id',$trailId)->where('principal_id',$user->id)->count();

            if($principal==0){
                return $this->response->errorInternal('该销售线索没有退回权限');

            }

            $trail->update($array);
            // 操作日志
            $operate = new OperateEntity([
                'obj' => $trail,
                'title' => $content,
                'start' => null,
                'end' => null,
                'method' => OperateLogMethod::REFUND_TRAIL,
            ]);
            event(new OperateLogEvent([
                $operate,
            ]));
        } catch (Exception $e) {
            Log::error($e);
            return $this->response->errorInternal('销售线索退回失败');
        }
        DB::commit();
    }

    /**
     *  todo
     *  1. 定返回格式
     *  2. 根据返回拼sql
     *  3. sql返回带分页带eloquent模型
     * @param $request
     */
    public function getFilter(FilterRequest $request)
    {
        $pageSize = $request->get('page_size', config('app.page_size'));

        $user = Auth::guard('api')->user();
//        $company = $user->company->name;

//        $joinSql = FilterJoin::where('company', $company)->where('table_name', 'trails')->first()->join_sql;

//        $query = DB::table('trails')->selectRaw('DISTINCT(trails.id) as ids')->from(DB::raw($joinSql));

        $query = Trail::query();
        $conditions = $request->get('conditions');
        foreach ($conditions as $condition) {
            $field = $condition['field'];
            $operator = $condition['operator'];
            $type = $condition['type'];
            if ($operator == 'LIKE') {
                $value = '%' . $condition['value'] . '%';
                $query->whereRaw("$field $operator ?", [$value]);
            } else if ($operator == 'in') {
                $value = $condition['value'];
                if ($type >= 5)
                    foreach ($value as &$v) {
                        $v = hashid_decode($v);
                    }
                unset($v);
                $query->whereIn($field, $value);
            } else {
                $value = $condition['value'];
                $query->whereRaw("$field $operator ?", [$value]);
            }

        }

        // 这句用来检查绑定的参数
        //       $sql_with_bindings = str_replace_array('?', $query->getBindings(), $query->toSql());
//        dd($sql_with_bindings);
        //       $result = $query->pluck('ids')->toArray();

//        $trails = Trail::whereIn('id', $result)->orderBy('created_at', 'desc')->paginate($pageSize);
        $trails = $query->whereNotNull('take_type')->searchData()->orderBy('created_at', 'desc')->paginate($pageSize);

        return $this->response->paginator($trails, new TrailTransformer());
    }
//allocation

}
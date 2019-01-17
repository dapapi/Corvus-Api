<?php

namespace App\Http\Controllers;

use App\Events\OperateLogEvent;
use App\Exports\TrailsExport;
use App\Http\Requests\Filter\TrailFilterRequest;
use App\Http\Requests\Trail\EditTrailRequest;
use App\Http\Requests\Trail\FilterTrailRequest;
use App\Http\Requests\Trail\RefuseTrailReuqest;
use App\Http\Requests\Trail\SearchTrailRequest;
use App\Http\Requests\Trail\StoreTrailRequest;
use App\Http\Requests\Trail\TypeTrailReuqest;
use App\Http\Transformers\TrailTransformer;
use App\Models\Blogger;
use App\Models\DataDictionarie;
use App\Models\DataDictionary;
use App\Models\Department;
use App\Models\DepartmentUser;
use App\Models\FilterJoin;
use App\Models\Industry;
use App\Models\Message;
use App\Models\OperateEntity;
use App\Models\Star;
use App\Models\Client;
use App\Models\Contact;
use App\Models\Trail;
use App\Models\TrailStar;
use App\ModuleableType;
use App\OperateLogMethod;
use App\Repositories\ScopeRepository;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Maatwebsite\Excel\Excel;

class SeasPoolController extends Controller
{
    public function index(FilterTrailRequest $request)
    {
        $payload = $request->all();
        $user = Auth::guard('api')->user();
        $department_id = Department::where('name', '商业管理部')->first();
        $pageSize = $request->get('page_size', config('app.page_size'));

        $takeType = isset($payload['take_type']) ? $payload['take_type'] : 0;

        $receive = isset($payload['receive_type']) ? $payload['receive_type'] : 1;


        $trails = Trail::where(function ($query) use ($request, $payload, $takeType,$receive) {
            if ($request->has('keyword') && $payload['keyword'])
                $query->where('title', 'LIKE', '%' . $payload['keyword'] . '%');
            if ($takeType ==1){
                $query->where('take_type', $takeType);
            }else{
                $query->whereIn('take_type', [1,2]);
            }
            if ($receive ==1){
                $query->where('take_type', $receive);
            }else{
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
        $content = $userName . '领取销售线索';
        DB::beginTransaction();
        try {
            if(!empty($payload['id'])){
               foreach ($payload['id'] as $valId){
                    //修改领取销售线索状态
                   $array = [
                       'principal_id' => $user->id,
                       'take_type' => 2
                   ];

                   $num = DB::table('trails')->where('id',hashid_decode($valId))->update($array);

                   $trail = Trail::where('id',hashid_decode($valId))->first();

                   // 操作日志
                   $operate = new OperateEntity([
                       'obj' => $trail,
                       'title' => null,
                       'start' => $content,
                       'end' => null,
                       'method' => OperateLogMethod::FOLLOW_UP,
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
        $content = $userName . '将该销售线索分配给' . $userInfo->name;
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

                    $num = DB::table('trails')->where('id',hashid_decode($valId))->update($array);

                    $trail = Trail::where('id',hashid_decode($valId))->first();

                    // 操作日志
                    $operate = new OperateEntity([
                        'obj' => $trail,
                        'title' => null,
                        'start' => $content,
                        'end' => null,
                        'method' => OperateLogMethod::FOLLOW_UP,
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

    public function refund(Request $request, Trail $trail)
    {
        $payload = $request->all();

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
            $trail->update($array);
            // 操作日志
            $operate = new OperateEntity([
                'obj' => $trail,
                'title' => null,
                'start' => $content,
                'end' => null,
                'method' => OperateLogMethod::CANCEL,
            ]);
            event(new OperateLogEvent([
                $operate,
            ]));
        } catch (Exception $e) {
            Log::error($e);
            return $this->response->errorInternal('跟进失败');
        }
        DB::commit();
    }
//allocation

}
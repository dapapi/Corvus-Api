<?php

namespace App\Http\Controllers;


use App\Http\Transformers\ProjectBillTransformer;
use App\Models\Star;
use App\Models\Blogger;
use App\Models\Project;
use App\Models\ProjectBill;
use App\Models\ProjectBillsResource;
use App\Models\ProjectBillsResourceUser;
use App\Models\OperateEntity;
use App\OperateLogMethod;
use App\Events\OperateLogEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class ProjectBillController extends Controller
{
    public function index(Request $request, Blogger $blogger, Star $star, Project $project)
    {
        $payload = $request->all();

        $pageSize = $request->get('page_size', config('app.page_size'));
        if ($request->has('expense_type')) {
            if ($payload['expense_type'] == 1) {
                $array['expense_type'] = '收入';
            } else if ($payload['expense_type'] == 2) {
                $array['expense_type'] = '支出';
            } else {
                $array['expense_type'] = '';
            }
        } else {
            $array['expense_type'] = '';
        }
        if ($blogger) {
            $array['action_user'] = $blogger->nickname;

        }

        if ($project) {
            $approval = (new ApprovalContractController())->projectList($request, $project);
            $dataOne = array();
            $data = [];
            foreach ($approval['data'] as $key => $contract) {
                foreach ($contract['stars_name'] as $key1 => $talent) {

                    if ($contract['star_type'] == 'stars')
                        $data[$key][$key1] = $talent->name;
                    if ($contract['star_type'] == 'bloggers')
                        $data[$key][$key1] = $talent->nickname;
                    // $dataOne[] = $value1->name;
                    //    $dataOne = array_unique($dataOne);
                }
                $dataOne = $data[$key];
            //    $dataOne[] = implode('/', $data[$key]);

            }
            $array['project_kd_name'] = $project->title;
            $projectbillresource = ProjectBillsResource::where(['resourceable_id' => $project->id, 'resourceable_title' => $project->title])->first(['id', 'expenses', 'papi_divide', 'bigger_divide', 'my_divide']);
            if ($projectbillresource) {
                $divide = ProjectBillsResourceUser::where(['moduleable_id' => $projectbillresource->id])->get(['money', 'moduleable_title'])->toArray();
            } else {
                $divide = null;
            }

        }

        if ($star) {
            $array['artist_name'] = $star->name;
        }

        if ($array['expense_type'] == '支出') {

            $expendituresum = ProjectBill::where($array)->select(DB::raw('sum(money) as expendituresum'))->groupby('expense_type')->first();

            $array['expense_type'] = '收入';
            $incomesum = ProjectBill::where($array)->select(DB::raw('sum(money) as incomesum'))->groupby('expense_type')->first();
            $array['expense_type'] = '支出';

        } else if ($array['expense_type'] == '收入') {
            $incomesum = ProjectBill::where($array)->select(DB::raw('sum(money) as incomesum'))->groupby('expense_type')->first();
            $array['expense_type'] = '支出';
            $expendituresum = ProjectBill::where($array)->select(DB::raw('sum(money) as expendituresum'))->groupby('expense_type')->first();
            $array['expense_type'] = '收入';
        } else {
            $array['expense_type'] = '收入';
            $incomesum = ProjectBill::where($array)->select(DB::raw('sum(money) as incomesum'))->groupby('expense_type')->first();
            $array['expense_type'] = '支出';
            // dd(ProjectBill::where($array)->select(DB::raw('sum(money) as expendituresum')));
            $expendituresum = ProjectBill::where($array)->select(DB::raw('sum(money) as expendituresum'))->groupby('expense_type')->first();

            unset($array['expense_type']);
        }
        $projectbill = ProjectBill::where($array)->createDesc()->paginate($pageSize);
        $result = $this->response->paginator($projectbill, new ProjectBillTransformer());
        if ($project && $project->id) {
            $result->addMeta('appoval', $approval);
            $result->addMeta('datatitle', $dataOne);
        }


        if (!empty($expendituresum) && isset($expendituresum))
            $result->addMeta('expendituresum', $expendituresum->expendituresum);
        else
            $result->addMeta('expendituresum', 0);

        if (!empty($incomesum) && isset($incomesum))
            $result->addMeta('incomesum', $incomesum->incomesum);
        else
            $result->addMeta('incomesum', 0);

        if (isset($projectbillresource)) {
            $result->addMeta('expenses', $projectbillresource->expenses);
            $result->addMeta('divide', $divide);
            $result->addMeta('my_divide', $projectbillresource->my_divide);
        }
        return $result;
        //}else{
        //return $result;
        // }

    }

    public function store(Request $request, Blogger $Blogger, Star $star, Project $project)
    {

        $payload = $request->all();
        $user = Auth::guard('api')->user();
        unset($payload['status']);
        $payload['creator_id'] = $user->id;
        $array = $payload;
        if ($Blogger && $Blogger->id) {
            $array['resourceable_id'] = $project->id;
            $array['resourceable_title'] = $Blogger->nickname;
            $array['resourceable_type'] = 'blogger';

        } else if ($project && $project->id) {
            $array['resourceable_id'] = $project->id;
            $array['resourceable_title'] = $project->title;
            $array['resourceable_type'] = 'project';

        } else if ($star && $star->id) {
            $array['resourceable_id'] = $project->id;
            $array['resourceable_title'] = $star->name;
            $array['resourceable_type'] = 'star';

        }
        $is_exist = ProjectBillsResource::where(['resourceable_id' => $array['resourceable_id'], 'resourceable_title' => $array['resourceable_title'], 'resourceable_type' => $array['resourceable_type']])->first();

        if (isset($is_exist)) {
            return $this->response->errorNotFound('已存在');
        }
        DB::beginTransaction();
        try {


            $bill = ProjectBillsResource::create($array);
            if ($request->has(['star'])) {
                foreach ($payload['star'] as $key => $value) {
                    $date = array();
                    $date['moduleable_id'] = $bill->id;
                    $date['money'] = $payload['star'][$key]['money'];
                    $date['moduleable_title'] = $payload['star'][$key]['moduleable_title'];
                    $billUser = ProjectBillsResourceUser::create($date);
                }
            }

            // 操作日志
            $operate = new OperateEntity([
                'obj' => $bill,
                'title' => null,
                'start' => null,
                'end' => null,
                'method' => OperateLogMethod::CREATE,
            ]);
            event(new OperateLogEvent([
                $operate,
            ]));
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            return $this->response->errorInternal('创建失败');
        }
        DB::commit();


    }

    public function edit(Request $request, Blogger $Blogger, Star $star, Project $project)
    {

        $payload = $request->all();
        $user = Auth::guard('api')->user();
        unset($payload['status']);
        $payload['creator_id'] = $user->id;
        $array = $payload;
        if ($Blogger && $Blogger->id) {
            $array['resourceable_id'] = $project->id;
            $array['resourceable_title'] = $Blogger->nickname;
            $array['resourceable_type'] = 'blogger';

        } else if ($project && $project->id) {
            $array['resourceable_id'] = $project->id;
            $array['resourceable_title'] = $project->title;
            $array['resourceable_type'] = 'project';

        } else if ($star && $star->id) {
            $array['resourceable_id'] = $project->id;
            $array['resourceable_title'] = $star->name;
            $array['resourceable_type'] = 'star';

        }
        $is_exist = ProjectBillsResource::where(['resourceable_id' => $array['resourceable_id'], 'resourceable_title' => $array['resourceable_title'], 'resourceable_type' => $array['resourceable_type']])->first();
        if (!isset($is_exist)) {
            return $this->response->errorNotFound('请先添加结算单');
        }
        DB::beginTransaction();
        try {
            $data = $array['star'];
            unset($array['star']);
            $bill = ProjectBillsResource::where('id', $is_exist->id)->first()->update($array);

            if ($data) {
                foreach ($payload['star'] as $key => $value) {
                    $date = array();
                    $dateid = array();
                    $dateid['moduleable_id'] = $is_exist->id;
                    $date['money'] = $payload['star'][$key]['money'];
                    $date['moduleable_title'] = $payload['star'][$key]['moduleable_title'];
                   // ProjectBillsResourceUser::updateOrCreate($dateid, $date);
                    $is_star  = ProjectBillsResourceUser::where('moduleable_id',$dateid)->where('moduleable_title',$date['moduleable_title'])->first();
                    if($is_star){
                        $date['moduleable_id'] = $is_exist->id;
                            ProjectBillsResourceUser::where('id',$is_star->id)->update($date);
                    }else{
                        $date['moduleable_id'] = $is_exist->id;
                        ProjectBillsResourceUser::create( $date);
                    }
                }
            }
//
//            // 操作日志
//            $operate = new OperateEntity([
//                'obj' => $bill,
//                'title' => null,
//                'start' => null,
//                'end' => null,
//                'method' => OperateLogMethod::UPDATE,
//            ]);
//            event(new OperateLogEvent([
//                $operate,
//            ]));
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            return $this->response->errorInternal('修改失败');
        }
        DB::commit();


    }

}

<?php
namespace App\Http\Controllers;

/**
 * Created by PhpStorm.
 * User: wy
 * Date: 2018/11/19
 * Time: 下午2:14
 */

use App\Http\Requests\RepositoryRequest;
use App\Http\Requests\RepositoryUpdateRequest;
use App\Http\Transformers\RepositoryTransformer;
use App\Http\Transformers\RepositoryShowTransformer;
use App\Events\OperateLogEvent;
use App\Models\OperateEntity;
use App\OperateLogMethod;
use App\Models\Department;
use App\Models\DepartmentUser;
use App\Models\Repository;
use App\Repositories\AffixRepository;
use Illuminate\Http\Request;
use App\RepositoryScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RepositoryController extends Controller
{
    protected $affixRepository;
    public function __construct(AffixRepository $affixRepository)
    {
        $this->affixRepository = $affixRepository;
    }

    public function index(RepositoryRequest $request)
    {

        $payload = $request->all();
        $user = Auth::guard('api')->user();
        $userId = $user->id;
        $pageSize = $request->get('page_size', config('app.page_size'));
        if($request->has('department')){
            $department = hashid_decode($payload['department']);
            $users = DepartmentUser::where('department_id',$department)->get(['user_id']);
            $department = DepartmentUser::where('department_id',$department)->get(['department_id'])->toArray();
            if(!empty($department)){
                    $department_name  = !empty(Department::where('id',$department[0]['department_id'])->get()->toArray())?Department::where('id',$department[0]['department_id'])->get()->toArray():'';
                    $scope = RepositoryScope::getStr($department_name[0]['name']);
                    if($scope == 0){
                        $stars = Repository::wherein('creator_id',$users)->orwhere('scope',1)->createDesc()->paginate($pageSize);
                    }else if($scope == 1){
                        $stars = Repository::wherein('creator_id',$users)->orwhere('scope',1)->createDesc()->paginate($pageSize);
                    }else if($scope == 2){

                        $stars = Repository::wherein('creator_id',$users)->orwhere('scope',2)->createDesc()->paginate($pageSize);
                    }else if($scope == 3){

                        $stars = Repository::wherein('creator_id',$users)->orwhere('scope',3)->createDesc()->paginate($pageSize);
                    }else if($scope == 4){

                        $stars = Repository::wherein('creator_id',$users)->orwhere('scope',4)->createDesc()->paginate($pageSize);
                    }
              }else{

                    $department = DepartmentUser::where('user_id',$userId)->get(['department_id'])->toArray();
                    $users = DepartmentUser::where('department_id',$department)->get(['user_id']);
                    $department = DepartmentUser::where('department_id',$department)->get(['department_id'])->toArray();
                    $department_name  = !empty(Department::where('id',$department[0]['department_id'])->get()->toArray())?Department::where('id',$department[0]['department_id'])->get()->toArray():'';
                    $scope = RepositoryScope::getStr($department_name[0]['name']);
                    if($scope == 0){
                        $stars = Repository::wherein('creator_id',$users)->orwhere('scope',1)->createDesc()->paginate($pageSize);
                    }else if($scope == 1){
                        $stars = Repository::wherein('creator_id',$users)->orwhere('scope',1)->createDesc()->paginate($pageSize);
                    }else if($scope == 2){

                        $stars = Repository::wherein('creator_id',$users)->orwhere('scope',2)->createDesc()->paginate($pageSize);
                    }else if($scope == 3){

                        $stars = Repository::wherein('creator_id',$users)->orwhere('scope',3)->createDesc()->paginate($pageSize);
                    }else if($scope == 4){

                        $stars = Repository::wherein('creator_id',$users)->orwhere('scope',4)->createDesc()->paginate($pageSize);
                    }


            }

        }else{


            $department = DepartmentUser::where('user_id',$userId)->get(['department_id'])->toArray();
            $users = DepartmentUser::where('department_id',$department)->get(['user_id']);
            $department = DepartmentUser::where('department_id',$department)->get(['department_id'])->toArray();
            $department_name  = !empty(Department::where('id',$department[0]['department_id'])->get()->toArray())?Department::where('id',$department[0]['department_id'])->get()->toArray():'';
            $scope = RepositoryScope::getStr($department_name[0]['name']);
            if($scope == 0){
                $stars = Repository::wherein('creator_id',$users)->orwhere('scope',1)->createDesc()->paginate($pageSize);
            }else if($scope == 1){
                $stars = Repository::wherein('creator_id',$users)->orwhere('scope',1)->createDesc()->paginate($pageSize);
            }else if($scope == 2){

                $stars = Repository::wherein('creator_id',$users)->orwhere('scope',2)->createDesc()->paginate($pageSize);
            }else if($scope == 3){

                $stars = Repository::wherein('creator_id',$users)->orwhere('scope',3)->createDesc()->paginate($pageSize);
            }else if($scope == 4){

                $stars = Repository::wherein('creator_id',$users)->orwhere('scope',4)->createDesc()->paginate($pageSize);
            }

        }
        return $this->response->paginator($stars, new RepositoryTransformer());
    }
    public function show(Request $request,Repository $repository)
    {


        return $this->response->item($repository, new RepositoryShowTransformer());

    }
    public function store(RepositoryRequest $repositoryrequest,Repository $repository)
    {
        $payload = $repositoryrequest->all();
        $user = Auth::guard('api')->user();
        unset($payload['status']);
        unset($payload['type']);
        $payload['creator_id'] = $user->id;//发布人

        if($repositoryrequest->has('scope')){
            $payload['scope'] = hashid_decode($payload['scope']);
        }
        if ($payload['creator_id']) {

            DB::beginTransaction();
            try {
                $star = Repository::create($payload);


            }catch (\Exception $e) {
                DB::rollBack();
                Log::error($e);
                return $this->response->errorInternal('创建失败');
            }
            DB::commit();
        }else{
            return $this->response->errorInternal('创建失败');
        }

    }
    public function delete(Repository $repository)
    {
        DB::beginTransaction();
        try {
            $repository->delete();
            // 操作日志
            $operate = new OperateEntity([
                'obj' => $repository,
                'title' => null,
                'start' => null,
                'end' => null,
                'method' => OperateLogMethod::DELETE,
            ]);
            event(new OperateLogEvent([
                $operate,
            ]));
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            return $this->response->errorInternal('删除失败');
        }
        DB::commit();
    }
    public function edit(RepositoryUpdateRequest $request, Repository $repository)
    {
        $payload = $request->all();
        $array = [];
        $arrayOperateLog = [];
        if ($request->has('title')) {
            $array['title'] = $payload['title'];
            if ($array['title'] != $repository->title) {
                $operateNickname = new OperateEntity([
                    'obj' => $repository,
                    'title' => '标题',
                    'start' => $repository->title,
                    'end' => $array['title'],
                    'method' => OperateLogMethod::UPDATE,
                ]);
                $arrayOperateLog[] = $operateNickname;
            } else {
                unset($array['title']);
            }
        }
        if ($request->has('scope')) {
            $array['scope'] = hashid_decode($payload['scope']);
            if ($array['scope'] != $repository->scope) {
                $operateNickname = new OperateEntity([
                    'obj' => $repository,
                    'title' => '对象id',
                    'start' => $repository->scope,
                    'end' => $array['scope'],
                    'method' => OperateLogMethod::UPDATE,
                ]);
                $arrayOperateLog[] = $operateNickname;
            } else {
                unset($array['scope']);
            }
        }

        if ($request->has('desc')) {
            $array['desc'] = $payload['desc'];
            if ($array['desc'] != $repository->desc) {
                $operateNickname = new OperateEntity([
                    'obj' => $repository,
                    'title' => '知识库内容',
                    'start' => $repository->desc,
                    'end' => $array['desc'],
                    'method' => OperateLogMethod::UPDATE,
                ]);
                $arrayOperateLog[] = $operateNickname;
            } else {
                unset($array['desc']);
            }
        }
        DB::beginTransaction();
        try {
            if (count($array) == 0)
                return $this->response->noContent();
            $repository->update($array);
            // 操作日志
            event(new OperateLogEvent($arrayOperateLog));
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            return $this->response->errorInternal('修改失败');
        }
        DB::commit();

        return $this->response->accepted();

    }

}
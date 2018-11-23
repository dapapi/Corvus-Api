<?php

namespace App\Http\Controllers;

use App\Http\Requests\Approval\EditApprovalGroupRequest;
use App\Http\Requests\Approval\StoreApprovalGroupRequest;
use App\Http\Transformers\ApprovalGroupTransformer;
use App\Models\ApprovalGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ApprovalGroupController extends Controller
{
    public function index(Request $request)
    {
        $pageSize = $request->get('page_size', config('app.page_size'));
        $groups = ApprovalGroup::orderBy('sort', 'asc')->paginate($pageSize);
        return $this->response->collection($groups, new ApprovalGroupTransformer());
    }

    public function all(Request $request)
    {
        $groups = ApprovalGroup::all();
        return $this->response->collection($groups, new ApprovalGroupTransformer());
    }

    public function store(StoreApprovalGroupRequest $request)
    {
        $payload = $request->all();

        $num = ApprovalGroup::count();
        $payload['sort'] = $num + 1;

        try {
            $group = ApprovalGroup::create($payload);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->response->errorInternal('创建审批组失败');
        }

        return $this->response->item($group, new ApprovalGroupTransformer());
    }

    public function edit(EditApprovalGroupRequest $request, ApprovalGroup $group)
    {
        $payload = $request->all();
        try {
            $group->update($payload);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->response->errorInternal('更新审批组失败');
        }

        return $this->response->accepted();
    }

    public function detail(Request $request, ApprovalGroup $group)
    {

    }

    public function delete(Request $request)
    {

    }

    public function changeSort(Request $request)
    {

    }
}

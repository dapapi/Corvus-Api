<?php

namespace App\Http\Controllers;

use App\Http\Requests\Material\MaterialEditRequest;
use App\Http\Requests\Material\MaterialStoreRequest;
use App\Http\Transformers\MaterialTransformer;
use App\Models\Material;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class MaterialController extends Controller
{
    // todo 跟前端确定是用一次返回还是用多次返回的方式
    public function all(Request $request)
    {
//        $type = $request->get('type', 1);
//        $materials = Material::where('type', $type)->get();
        $materials = Material::get();
        return $this->response->collection($materials, new MaterialTransformer());
    }

    public function store(MaterialStoreRequest $request)
    {
        $payload = $request->all();

        $user = Auth::guard('api')->user();

        $payload['creator_id'] = $user->id;

        try {
            $material = Material::create($payload);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->response->errorInternal('创建失败');
        }

        return $this->response->item($material, new MaterialTransformer());
    }

    public function edit(MaterialEditRequest $request, Material $material)
    {
        $payload = $request->all();

        try {
            $material->update($payload);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->response->errorInternal('修改失败');
        }

        return $this->response->accepted();
    }

    public function detail(Request $request, Material $material)
    {
        return $this->response->item($material, new MaterialTransformer());
    }

    // todo 后台管理系统，暂不着急
    public function delete(Request $request, Material $material)
    {
        $material->delete();

        return $this->response->noContent();
    }
}

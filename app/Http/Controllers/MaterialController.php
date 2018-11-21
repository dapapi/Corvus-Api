<?php

namespace App\Http\Controllers;

use App\Http\Transformers\MaterialTransformer;
use App\Models\Material;
use Illuminate\Http\Request;

class MaterialController extends Controller
{
    public function all(Request $request)
    {
        $materials = Material::all();

        return $this->response->collection($materials, new MaterialTransformer());
    }

    // todo 后台管理系统，暂不着急
    public function store()
    {

    }

    public function edit()
    {

    }

    public function detail()
    {

    }

    public function delete()
    {

    }

    public function recover()
    {

    }

}

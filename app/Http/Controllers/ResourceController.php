<?php

namespace App\Http\Controllers;

use App\Http\Transformers\ResourceTransformer;
use App\Models\Blogger;
use App\Models\Client;
use App\Models\Project;
use App\Models\Resource;
use App\Models\Star;
use App\Models\Trail;
use Illuminate\Http\Request;

class ResourceController extends Controller
{
    public function index(Request $request)
    {
//        $payload = $request->all();
        $resources = Resource::all();
        //要求关联模块里直接返回所有模块数据
        $metaArray = [
            'stars' => Star::all(),
            'projects' => Project::all(),
            'trails' => Trail::all(),
            'bloggers' => Blogger::all(),
            'clients' => Client::all(),
            // TODO
        ];
        return $this->response->collection($resources, new ResourceTransformer())->setMeta($metaArray);
    }
}

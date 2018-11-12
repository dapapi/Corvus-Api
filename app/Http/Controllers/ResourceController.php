<?php

namespace App\Http\Controllers;

use App\Http\Transformers\ResourceTransformer;
use App\Http\Transformers\StarTransformer;
use App\Models\Project;
use App\Models\Resource;
use App\Models\Star;
use Illuminate\Http\Request;

class ResourceController extends Controller
{
    public function index(Request $request)
    {
//        $payload = $request->all();
        $resources = Resource::all();

        $metaArray = [
            'stars' => Star::all(),
            'projects' => Project::all(),
        ];
        return $this->response->collection($resources, new ResourceTransformer())->setMeta($metaArray);
    }
}

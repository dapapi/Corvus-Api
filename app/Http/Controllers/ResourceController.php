<?php

namespace App\Http\Controllers;

use App\Http\Transformers\ResourceTransformer;
use App\Models\Resource;
use Illuminate\Http\Request;

class ResourceController extends Controller
{
    public function index(Request $request)
    {
//        $payload = $request->all();
        $resources = Resource::all();
        return $this->response->collection($resources, new ResourceTransformer());
    }
}

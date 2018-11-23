<?php

namespace App\Http\Controllers;

use App\Http\Transformers\PlatformTransformer;
use App\Models\Platform;
use Illuminate\Http\Request;

class PlatformController extends Controller
{
    public function index(Request $request)
    {
        $platform = Platform::all();
        return $this->response->collection($platform, new PlatformTransformer());
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Transformers\StarTransformer;
use App\Models\Star;
use Illuminate\Http\Request;

class StarController extends Controller
{
    public function index(Request $request)
    {
        if ($request->has('page_size')) {
            $pageSize = $request->get('page_size');
        } else {
            $pageSize = config('page_size');
        }

        $artists = Star::orderBy('name')->paginate($pageSize);

        return $this->response->paginator($artists, new StarTransformer());
    }

    public function all(Request $request)
    {
        $artists = Star::orderBy('name')->get();

        return $this->response->collection($artists, new StarTransformer());
    }
}

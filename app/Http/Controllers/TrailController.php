<?php

namespace App\Http\Controllers;

use App\Http\Requests\Trail\EditTrailRequest;
use App\Http\Requests\Trail\StoreTrailRequest;
use App\Http\Transformers\TrailTransformer;
use App\Models\Trail;
use Illuminate\Http\Request;

class TrailController extends Controller
{
    public function index(Request $request)
    {
        if ($request->has('page_size')) {
            $pageSize = $request->get('page_size');
        } else {
            $pageSize = config('page_size');
        }

        $clients = Trail::orderBy('created_at', 'desc')->paginate($pageSize);
        return $this->response->paginator($clients, new TrailTransformer());
    }

    public function store(StoreTrailRequest $request, Trail $trail)
    {

    }

    public function edit(EditTrailRequest $request, Trail $trail)
    {

    }

    public function delete(Request $request, Trail $trail)
    {

    }

    public function recover(Request $request, Trail $trail)
    {

    }

    public function detail(Request $request, Trail $trail)
    {
        return $this->response->item($trail, new TrailTransformer());
    }
}

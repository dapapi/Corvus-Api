<?php

namespace App\Http\Controllers;

use App\Http\Requests\Period\PeriodEditRequest;
use App\Http\Requests\Period\PeriodStoreRequest;
use App\Http\Transformers\PeriodTransformer;
use App\Models\Period;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PeriodController extends Controller
{
    public function index(Request $request)
    {
        $pageSize = $request->get('page_size', config('app.page_size'));
        $paginator = Period::paginate($pageSize);
        return $this->response->paginator($paginator, new PeriodTransformer());
    }

    public function all(Request $request)
    {
        $collection = Period::get();
        return $this->response->collection($collection, new PeriodTransformer());
    }

    public function store(PeriodStoreRequest $request)
    {
        $payload = $request->all();
        try {
            $period = Period::create($payload);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->response->error('创建失败');
        }
        return $this->response->item($period, new PeriodTransformer());
    }

    public function edit(PeriodEditRequest $request, Period $period)
    {
        $payload = $request->all();
        try {
            $period->update($payload);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->response->error('修改失败');
        }
        return $this->response->accepted();
    }

    public function detail(Request $request, Period $period)
    {
        return $this->response->item($period, new PeriodTransformer());
    }

    public function delete(Request $request, Period $period)
    {
        $period->delete();
        return $this->response->noContent();
    }
}

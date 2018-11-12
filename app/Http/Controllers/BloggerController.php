<?php

namespace App\Http\Controllers;

use App\Events\OperateLogEvent;
use App\Http\Transformers\BloggerTransformer;
use App\Models\Blogger;
use App\Models\OperateEntity;
use App\OperateLogMethod;
use Illuminate\Http\Request;

class BloggerController extends Controller
{
    public function index(Request $request)
    {
        $payload = $request->all();
        $pageSize = $request->get('page_size', config('app.page_size'));

        $bloggers = Blogger::createDesc()->paginate($pageSize);
        return $this->response->paginator($bloggers, new BloggerTransformer());
    }

    public function show(Blogger $blogger)
    {
        // 操作日志
        $operate = new OperateEntity([
            'obj' => $blogger,
            'title' => null,
            'start' => null,
            'end' => null,
            'method' => OperateLogMethod::LOOK,
        ]);
        event(new OperateLogEvent([
            $operate,
        ]));
        return $this->response->item($blogger, new BloggerTransformer());
    }
}

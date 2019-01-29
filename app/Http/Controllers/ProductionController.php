<?php

namespace App\Http\Controllers;

use App\Http\Requests\Production\ProductionStoreRequest;
use App\Models\Blogger;
use Illuminate\Http\Request;

class ProductionController extends Controller
{
    /**
     * todo
     * 1. 新建作品
     * 2. 建关联任务
     * 3. 关联任务建对应问卷
     *
     * @param ProductionStoreRequest $request
     * @param Blogger $blogger
     * todo 推优功能写在另一对应控制器中
     */
    public function store(ProductionStoreRequest $request, Blogger $blogger)
    {
        $payload = $request->all();
        $blogger->productions()->create([
            'nickname' => $blogger->nickname,
            'videoname' => $payload['videoname'],
            'release_time' => $payload['release_time'],
            'read_proportion' => $payload['read_proportion'],
            'link' => $payload['link'],
            'advertising' => $payload['advertising'],
        ]);
    }
}

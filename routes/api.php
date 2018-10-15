<?php

$api = app('Dingo\Api\Routing\Router');

$api->version('v1', ['middleware' => ['bindings', 'cors']], function ($api) {
    # 测试模块
    if (config('app.api_debug')) {
        $api->get('/test/hello', 'App\Http\Controllers\TestController@hello');

        $api->post('/test/login', 'App\Http\Controllers\TestController@signin');
        $api->get('/test/wechat_token', 'App\Http\Controllers\TestController@getWechatToken');
        $api->get('/test/spider', 'App\Http\Controllers\TestController@spider');
        $api->get('/test/spider_change', 'App\Http\Controllers\TestController@spiderChange');
    }
});

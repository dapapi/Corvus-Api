<?php

$api = app('Dingo\Api\Routing\Router');

$api->version('v1', ['middleware' => ['bindings', 'cors']], function ($api) {
    # 测试模块
    if (config('app.api_debug')) {
        $api->get('/test/hello', 'App\Http\Controllers\TestController@hello');

        $api->post('/test/login', 'App\Http\Controllers\TestController@signin');
    }
    $api->get('/test/index', 'App\Http\Controllers\TaskController@index');
});

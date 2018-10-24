<?php

$api = app('Dingo\Api\Routing\Router');

$api->version('v1', ['middleware' => ['bindings', 'cors']], function ($api) {
    # 测试模块
    if (config('app.api_debug')) {
        $api->get('/test/hello', 'App\Http\Controllers\TestController@hello');

        $api->post('/test/login', 'App\Http\Controllers\TestController@signin');
    }

    // task
    $api->get('/tasks/{task}', 'App\Http\Controllers\TaskController@show');


    // department
    $api->get('/departments', 'App\Http\Controllers\DepartmentController@index');

    // user
    $api->get('/users', 'App\Http\Controllers\UserController@index');

});

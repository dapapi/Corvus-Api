<?php

$api = app('Dingo\Api\Routing\Router');

$api->version('v1', ['middleware' => ['bindings', 'cors']], function ($api) {
    # 测试模块
    if (config('app.api_debug')) {
        $api->get('/test/hello', 'App\Http\Controllers\TestController@hello');
        $api->post('/test/login', 'App\Http\Controllers\TestController@signin');
        $api->get('/test/array', 'App\Http\Controllers\TestController@testArray');
    }


    $api->group(['middleware' => 'auth:api', 'bindings'], function ($api) {
        // task
        $api->post('/tasks', 'App\Http\Controllers\TaskController@store');
        $api->get('/tasks', 'App\Http\Controllers\TaskController@index');
        $api->get('/tasks/my', 'App\Http\Controllers\TaskController@my');
        $api->get('/tasks/{task}', 'App\Http\Controllers\TaskController@show');
        $api->put('/tasks/{task}', 'App\Http\Controllers\TaskController@toggleStatus');
        $api->delete('/tasks/{task}', 'App\Http\Controllers\TaskController@destroy');
        $api->put('/tasks/{task}', 'App\Http\Controllers\TaskController@recoverDestroy');
        $api->post('/tasks/{task}/subtask', 'App\Http\Controllers\TaskController@store');
        $api->post('/tasks/{task}/participant', 'App\Http\Controllers\TaskController@addParticipant');
        $api->post('/tasks/{task}/participant_remove', 'App\Http\Controllers\TaskController@removeParticipant');
        $api->put('/tasks/{task}/privacy', 'App\Http\Controllers\TaskController@togglePrivacy');
        //任务关联资源
        $api->post('/projects/{project}/tasks/{task}/resource', 'App\Http\Controllers\TaskController@relevanceResource');
        $api->post('/projects/{project}/tasks/{task}/resource_relieve', 'App\Http\Controllers\TaskController@relieveResource');


    });


    // department
    $api->get('/departments', 'App\Http\Controllers\DepartmentController@index');

    // user
    $api->get('/users', 'App\Http\Controllers\UserController@index');

});

<?php

$api = app('Dingo\Api\Routing\Router');

$api->version('v1', ['middleware' => ['bindings', 'cors']], function ($api) {
    # 测试模块
    if (config('app.api_debug')) {
        $api->get('/test/hello', 'App\Http\Controllers\TestController@hello');
        $api->post('/test/login', 'App\Http\Controllers\TestController@signin');
        $api->get('/test/array', 'App\Http\Controllers\TestController@testArray');
        $api->get('/test/date', 'App\Http\Controllers\TestController@date');
    }


    $api->group(['middleware' => 'auth:api', 'bindings'], function ($api) {
        // task
        $api->post('/tasks', 'App\Http\Controllers\TaskController@store');
        $api->get('/tasks', 'App\Http\Controllers\TaskController@index');
        $api->get('/tasks/my', 'App\Http\Controllers\TaskController@my');
        $api->get('/tasks/my_all', 'App\Http\Controllers\TaskController@myAll');
        $api->get('/tasks/recycle_bin', 'App\Http\Controllers\TaskController@recycleBin');
        $api->get('/tasks/{task}', 'App\Http\Controllers\TaskController@show');
        $api->put('/tasks/{task}', 'App\Http\Controllers\TaskController@update');
        $api->post('/tasks/{task}/recover', 'App\Http\Controllers\TaskController@recoverDestroy');
        $api->delete('/tasks/{task}', 'App\Http\Controllers\TaskController@destroy');
        $api->put('/tasks/{task}/status', 'App\Http\Controllers\TaskController@toggleStatus');
        $api->put('/tasks/{task}/time_cancel', 'App\Http\Controllers\TaskController@cancelTime');
        $api->delete('/tasks/{task}/principal', 'App\Http\Controllers\TaskController@deletePrincipal');
        $api->post('/tasks/{task}/subtask', 'App\Http\Controllers\TaskController@store');
        $api->put('/tasks/{task}/privacy', 'App\Http\Controllers\TaskController@togglePrivacy');
        //模型用户(参与人)
        $api->post('/tasks/{task}/participant', 'App\Http\Controllers\ModuleUserController@addModuleUserParticipant');
        $api->put('/tasks/{task}/participant_remove', 'App\Http\Controllers\ModuleUserController@removeModuleUser');
        //任务关联资源
        $api->post('/projects/{project}/tasks/{task}/resource', 'App\Http\Controllers\TaskController@relevanceResource');
        $api->delete('/projects/{project}/tasks/{task}/resource_relieve', 'App\Http\Controllers\TaskController@relieveResource');


        // contact
        $api->get('/clients/{client}/contacts', 'App\Http\Controllers\ContactController@index');
//        $api->group(['middleware' => ''], function ($api) {
            $api->post('/clients/{client}/contacts', 'App\Http\Controllers\ContactController@store');
//        });
        $api->put('/clients/{client}/contacts/{contact}', 'App\Http\Controllers\ContactController@edit');
        $api->put('/clients/{client}/contacts/{contact}/recover', 'App\Http\Controllers\ContactController@recover');
        $api->delete('/clients/{client}/contacts/{contact}', 'App\Http\Controllers\ContactController@delete');
        $api->get('/clients/{client}/contacts/{contact}', 'App\Http\Controllers\ContactController@detail');

        // client
        $api->get('/clients', 'App\Http\Controllers\ClientController@index');
        $api->post('/clients', 'App\Http\Controllers\ClientController@store')->middleware('can:create,App\Models\Client');
        $api->put('/clients/{client}', 'App\Http\Controllers\ClientController@edit');
        $api->put('/clients/{client}/recover', 'App\Http\Controllers\ClientController@recover');
        $api->delete('/clients/{client}', 'App\Http\Controllers\ClientController@delete');
        $api->get('/clients/{client}', 'App\Http\Controllers\ClientController@detail');

        // trail
        $api->get('/trails', 'App\Http\Controllers\TrailController@index');
        $api->post('/trails', 'App\Http\Controllers\TrailController@store');
        $api->put('/trails/{trail}', 'App\Http\Controllers\TrailController@edit');
        $api->delete('/trails/{trail}', 'App\Http\Controllers\TrailController@delete');
        $api->get('/trails/{trail}', 'App\Http\Controllers\TrailController@detail');

    });


    // department
    $api->get('/departments', 'App\Http\Controllers\DepartmentController@index');

    // user
    $api->get('/users', 'App\Http\Controllers\UserController@index');


});

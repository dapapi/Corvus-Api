<?php

$api = app('Dingo\Api\Routing\Router');

$api->version('v1', ['middleware' => ['bindings', 'cors']], function ($api) {
    # 测试模块
    if (config('app.api_debug')) {
        $api->get('/test/hello', 'App\Http\Controllers\TestController@hello');
        $api->get('/test/operate_log', 'App\Http\Controllers\TestController@operateLog');
        $api->post('/test/login', 'App\Http\Controllers\TestController@signin');
        $api->get('/test/array', 'App\Http\Controllers\TestController@testArray');
        $api->get('/test/date', 'App\Http\Controllers\TestController@date');
        $api->get('/test/array_if', 'App\Http\Controllers\TestController@arrayIf');
    }


    $api->group(['middleware' => 'auth:api', 'bindings'], function ($api) {
        // user
        $api->get('/users/my', 'App\Http\Controllers\UserController@my');

        //task
        $api->post('/tasks', 'App\Http\Controllers\TaskController@store');
        $api->get('/tasks', 'App\Http\Controllers\TaskController@index');
        $api->get('/tasks/my', 'App\Http\Controllers\TaskController@my');
        $api->get('/tasks/my_all', 'App\Http\Controllers\TaskController@myAll');
        $api->get('/tasks/recycle_bin', 'App\Http\Controllers\TaskController@recycleBin');
        $api->get('/tasks/{task}', 'App\Http\Controllers\TaskController@show');
        $api->put('/tasks/{task}', 'App\Http\Controllers\TaskController@edit');
        $api->post('/tasks/{task}/recover', 'App\Http\Controllers\TaskController@recoverRemove');
        $api->delete('/tasks/{task}', 'App\Http\Controllers\TaskController@remove')->middleware('can:delete,task');
        $api->put('/tasks/{task}/status', 'App\Http\Controllers\TaskController@toggleStatus');
        $api->put('/tasks/{task}/time_cancel', 'App\Http\Controllers\TaskController@cancelTime');
        $api->delete('/tasks/{task}/principal', 'App\Http\Controllers\TaskController@deletePrincipal');
        $api->post('/tasks/{task}/subtask', 'App\Http\Controllers\TaskController@store');
        $api->put('/tasks/{task}/privacy', 'App\Http\Controllers\TaskController@togglePrivacy');
        //任务关联资源
        $api->post('/projects/{project}/tasks/{task}/resource', 'App\Http\Controllers\TaskController@relevanceResource');
        $api->delete('/projects/{project}/tasks/{task}/resource_relieve', 'App\Http\Controllers\TaskController@relieveResource');
        //模型用户(参与人)
        $api->post('/tasks/{task}/participant', 'App\Http\Controllers\ModuleUserController@addModuleUserParticipant');
        $api->put('/tasks/{task}/participant_remove', 'App\Http\Controllers\ModuleUserController@remove');
        //附件
        $api->get('/tasks/{task}/affix', 'App\Http\Controllers\AffixController@index');
        $api->get('/tasks/{task}/affixes/recycle_bin', 'App\Http\Controllers\AffixController@recycleBin');
        $api->post('/tasks/{task}/affix', 'App\Http\Controllers\AffixController@add');
        $api->post('/tasks/{task}/affixes/{affix}/download', 'App\Http\Controllers\AffixController@download');
        $api->delete('/tasks/{task}/affixes/{affix}', 'App\Http\Controllers\AffixController@remove');
        $api->post('/tasks/{task}/affixes/{affix}/recover', 'App\Http\Controllers\AffixController@recoverRemove');
        $api->get('/stars/{star}/affix', 'App\Http\Controllers\AffixController@index');
        $api->get('/stars/{star}/affixes/recycle_bin', 'App\Http\Controllers\AffixController@recycleBin');
        $api->post('/stars/{star}/affix', 'App\Http\Controllers\AffixController@add');
        $api->post('/stars/{star}/affixes/{affix}/download', 'App\Http\Controllers\AffixController@download');
        $api->delete('/stars/{star}/affixes/{affix}', 'App\Http\Controllers\AffixController@remove');
        $api->post('/stars/{star}/affixes/{affix}/recover', 'App\Http\Controllers\AffixController@recoverRemove');
        //跟进
        $api->get('/tasks/{task}/operate_log', 'App\Http\Controllers\OperateLogController@index');
        $api->post('/tasks/{task}/follow_up', 'App\Http\Controllers\OperateLogController@addFollowUp');
        $api->get('/projects/{project}/operate_log', 'App\Http\Controllers\OperateLogController@index');
        $api->post('/projects/{project}/follow_up', 'App\Http\Controllers\OperateLogController@addFollowUp');
        $api->get('/stars/{star}/operate_log', 'App\Http\Controllers\OperateLogController@index');
        $api->post('/stars/{star}/follow_up', 'App\Http\Controllers\OperateLogController@addFollowUp');

        //stars
        $api->post('/stars', 'App\Http\Controllers\StarController@store');
        $api->get('/stars', 'App\Http\Controllers\StarController@index');
        $api->get('/stars/all', 'App\Http\Controllers\StarController@all');
        $api->put('/stars/{star}', 'App\Http\Controllers\StarController@edit');
        //模型用户(宣传人)
        $api->post('/stars/{star}/publicity', 'App\Http\Controllers\ModuleUserController@addModuleUserPublicity');
        $api->put('/stars/{star}/publicity_remove', 'App\Http\Controllers\ModuleUserController@remove');

        //service
        $api->get('/services/request_qiniu_token', 'App\Http\Controllers\ServiceController@cloudStorageToken');

        // contact
        $api->get('/clients/{client}/contacts', 'App\Http\Controllers\ContactController@index');
        $api->get('/clients/{client}/contacts/all', 'App\Http\Controllers\ContactController@all');
//        $api->group(['middleware' => ''], function ($api) {
        $api->post('/clients/{client}/contacts', 'App\Http\Controllers\ContactController@store');
//        });
        $api->put('/clients/{client}/contacts/{contact}', 'App\Http\Controllers\ContactController@edit');
        $api->put('/clients/{client}/contacts/{contact}/recover', 'App\Http\Controllers\ContactController@recover');
        $api->delete('/clients/{client}/contacts/{contact}', 'App\Http\Controllers\ContactController@delete');
        $api->get('/clients/{client}/contacts/{contact}', 'App\Http\Controllers\ContactController@detail');

        // client
        $api->get('/clients', 'App\Http\Controllers\ClientController@index');
        $api->get('/clients/all', 'App\Http\Controllers\ClientController@all');
        $api->post('/clients', 'App\Http\Controllers\ClientController@store')->middleware('can:create,App\Models\Client');
        $api->put('/clients/{client}', 'App\Http\Controllers\ClientController@edit');
        $api->put('/clients/{client}/recover', 'App\Http\Controllers\ClientController@recover');
        $api->delete('/clients/{client}', 'App\Http\Controllers\ClientController@delete');
        $api->get('/clients/{client}', 'App\Http\Controllers\ClientController@detail');

        // trail
        $api->get('/trails', 'App\Http\Controllers\TrailController@index');
        $api->get('/trails/all', 'App\Http\Controllers\TrailController@all');
        $api->post('/trails', 'App\Http\Controllers\TrailController@store');
        $api->put('/trails/{trail}', 'App\Http\Controllers\TrailController@edit');
        $api->put('/trails/{trail}/recover', 'App\Http\Controllers\TrailController@recover');
        $api->delete('/trails/{trail}', 'App\Http\Controllers\TrailController@delete');
        $api->get('/trails/{trail}', 'App\Http\Controllers\TrailController@detail');

    });


    // department
    $api->get('/departments', 'App\Http\Controllers\DepartmentController@index');

    // user
    $api->get('/users', 'App\Http\Controllers\UserController@index');


});

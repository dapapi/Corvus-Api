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

    //resource
    $api->get('/resources', 'App\Http\Controllers\ResourceController@index');
    $api->get('/platforms', 'App\Http\Controllers\PlatformController@index');

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
        $api->get('/task_types', 'App\Http\Controllers\TaskTypeController@index');
        $api->get('/task_types/all', 'App\Http\Controllers\TaskTypeController@all');
        //关联任务查询
        $api->get('/projects/{project}/tasks', 'App\Http\Controllers\TaskController@findModuleTasks');
        $api->get('/clients/{client}/tasks', 'App\Http\Controllers\TaskController@findModuleTasks');
        $api->get('/stars/{star}/tasks', 'App\Http\Controllers\TaskController@findModuleTasks');
        $api->get('/trails/{trail}/tasks', 'App\Http\Controllers\TaskController@findModuleTasks');
        $api->get('/bloggers/{blogger}/tasks', 'App\Http\Controllers\TaskController@findModuleTasks');
        //任务关联资源
        $api->post('/projects/{project}/tasks/{task}/resource', 'App\Http\Controllers\TaskController@relevanceResource');
        $api->delete('/projects/{project}/tasks/{task}/resource_relieve', 'App\Http\Controllers\TaskController@relieveResource');
        $api->post('/clients/{client}/tasks/{task}/resource', 'App\Http\Controllers\TaskController@relevanceResource');
        $api->delete('/clients/{client}/tasks/{task}/resource_relieve', 'App\Http\Controllers\TaskController@relieveResource');
        $api->post('/stars/{star}/tasks/{task}/resource', 'App\Http\Controllers\TaskController@relevanceResource');
        $api->delete('/stars/{star}/tasks/{task}/resource_relieve', 'App\Http\Controllers\TaskController@relieveResource');
        $api->post('/trails/{trail}/tasks/{task}/resource', 'App\Http\Controllers\TaskController@relevanceResource');
        $api->delete('/trails/{trail}/tasks/{task}/resource_relieve', 'App\Http\Controllers\TaskController@relieveResource');
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
        $api->get('/projects/{project}/affix', 'App\Http\Controllers\AffixController@index');
        $api->get('/projects/{project}/affixes/recycle_bin', 'App\Http\Controllers\AffixController@recycleBin');
        $api->post('/projects/{project}/affix', 'App\Http\Controllers\AffixController@add');
        $api->post('/projects/{project}/affixes/{affix}/download', 'App\Http\Controllers\AffixController@download');
        $api->delete('/projects/{project}/affixes/{affix}', 'App\Http\Controllers\AffixController@remove');
        $api->post('/projects/{project}/affixes/{affix}/recover', 'App\Http\Controllers\AffixController@recoverRemove');
        $api->get('/stars/{star}/affix', 'App\Http\Controllers\AffixController@index');
        $api->get('/stars/{star}/affixes/recycle_bin', 'App\Http\Controllers\AffixController@recycleBin');
        $api->post('/stars/{star}/affix', 'App\Http\Controllers\AffixController@add');
        $api->post('/stars/{star}/affixes/{affix}/download', 'App\Http\Controllers\AffixController@download');
        $api->delete('/stars/{star}/affixes/{affix}', 'App\Http\Controllers\AffixController@remove');
        $api->post('/stars/{star}/affixes/{affix}/recover', 'App\Http\Controllers\AffixController@recoverRemove');
        $api->get('/bloggers/{blogger}/affix', 'App\Http\Controllers\AffixController@index');
        $api->get('/bloggers/{blogger}/affixes/recycle_bin', 'App\Http\Controllers\AffixController@recycleBin');
        $api->post('/bloggers/{blogger}/affix', 'App\Http\Controllers\AffixController@add');
        $api->post('/bloggers/{blogger}/affixes/{affix}/download', 'App\Http\Controllers\AffixController@download');
        $api->delete('/bloggers/{blogger}/affixes/{affix}', 'App\Http\Controllers\AffixController@remove');
        $api->post('/bloggers/{blogger}/affixes/{affix}/recover', 'App\Http\Controllers\AffixController@recoverRemove');
        $api->get('/clients/{client}/affix', 'App\Http\Controllers\AffixController@index');
        $api->get('/clients/{client}/affixes/recycle_bin', 'App\Http\Controllers\AffixController@recycleBin');
        $api->post('/clients/{client}/affix', 'App\Http\Controllers\AffixController@add');
        $api->post('/clients/{client}/affixes/{affix}/download', 'App\Http\Controllers\AffixController@download');
        $api->delete('/clients/{client}/affixes/{affix}', 'App\Http\Controllers\AffixController@remove');
        $api->post('/clients/{client}/affixes/{affix}/recover', 'App\Http\Controllers\AffixController@recoverRemove');
        $api->get('/trails/{trail}/affix', 'App\Http\Controllers\AffixController@index');
        $api->get('/trails/{trail}/affixes/recycle_bin', 'App\Http\Controllers\AffixController@recycleBin');
        $api->post('/trails/{trail}/affix', 'App\Http\Controllers\AffixController@add');
        $api->post('/trails/{trail}/affixes/{affix}/download', 'App\Http\Controllers\AffixController@download');
        $api->delete('/trails/{trail}/affixes/{affix}', 'App\Http\Controllers\AffixController@remove');
        $api->post('/trails/{trail}/affixes/{affix}/recover', 'App\Http\Controllers\AffixController@recoverRemove');
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
        $api->get('/stars/recycle_bin', 'App\Http\Controllers\StarController@recycleBin');
        $api->get('/stars/{star}', 'App\Http\Controllers\StarController@show');
        $api->post('/stars/{star}/recover', 'App\Http\Controllers\StarController@recoverRemove');
        $api->delete('/stars/{star}', 'App\Http\Controllers\StarController@remove');
        //获取明星作品列表
        $api->get('/stars/{star}/works', 'App\Http\Controllers\WorkController@index');
        //创建明星作品
        $api->post('/stars/{star}/works','App\Http\Controllers\WorkController@store');
        //模型用户(宣传人)
        $api->post('/stars/{star}/publicity', 'App\Http\Controllers\ModuleUserController@addModuleUserPublicity');
        $api->put('/stars/{star}/publicity_remove', 'App\Http\Controllers\ModuleUserController@remove');
        //分配经纪人
        $api->post('/stars/{star}/broker','App\Http\Controllers\ModuleUserController@addModuleUserBroker');
        //blogger
        $api->post('/bloggers', 'App\Http\Controllers\BloggerController@store');
        $api->get('/bloggers', 'App\Http\Controllers\BloggerController@index');
        $api->get('/bloggers/all', 'App\Http\Controllers\BloggerController@all');
        $api->get('/bloggers/{blogger}', 'App\Http\Controllers\BloggerController@show');
        $api->put('/bloggers/{blogger}', 'App\Http\Controllers\BloggerController@edit');
        $api->get('/bloggers/recycle_bin', 'App\Http\Controllers\BloggerController@recycleBin');
        $api->delete('/bloggers/{blogger}', 'App\Http\Controllers\BloggerController@remove');
        $api->post('/bloggers/{blogger}/recover', 'App\Http\Controllers\BloggerController@recoverRemove');

        //考勤
        //提交申请
        $api->post('/attendance','App\Http\Controllers\attendanceController@store');
        //我的考勤统计
        $api->get('/attendance/myselfstatistics','App\Http\Controllers\AttendanceController@myselfStatistics');
        //我的考勤请假统计
        $api->get('/attendance/myselfleavelstatistics','App\Http\Controllers\AttendanceController@myselfLeavelStatistics');
        //根据条件统计考勤  成员考勤--考勤统计
        $api->get('/attendance/statistics','App\Http\Controllers\AttendanceController@Statistics');
        //成员考勤--请假统计
       $api->get('/attendance/leavestatistics','App\Http\Controllers\AttendanceController@leaveStatistics');
       //考勤汇总 type 1:请假  2:加班 3:出差  4:外勤
        $api->get('/attendance/collect','App\Http\Controllers\AttendanceController@collect');
        //考勤日历
        $api->get('/attendance/calendar','App\Http\Controllers\AttendanceController@attendanceCalendar');
        //我申请的
        $api->get('/attendance/myapply','App\Http\Controllers\AttendanceController@myApply');
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
        //  report
        $api->get('/report', 'App\Http\Controllers\ReportController@index');
        $api->post('report', 'App\Http\Controllers\ReportController@store');
        $api->get('report/all', 'App\Http\Controllers\ReportController@all');
        $api->delete('report', 'App\Http\Controllers\ReportController@delete');
        //  launch
        $api->get('/launch', 'App\Http\Controllers\LaunchController@index');
        $api->get('/launch/all', 'App\Http\Controllers\LaunchController@all');
        $api->post('launch', 'App\Http\Controllers\LaunchController@store');
        // trail
        $api->get('/trails', 'App\Http\Controllers\TrailController@index');
        $api->get('/trails/all', 'App\Http\Controllers\TrailController@all');
        $api->get('/trails/search', 'App\Http\Controllers\TrailController@search');
        $api->post('/trails', 'App\Http\Controllers\TrailController@store');
        $api->put('/trails/{trail}', 'App\Http\Controllers\TrailController@edit');
        $api->put('/trails/{trail}/recover', 'App\Http\Controllers\TrailController@recover');
        $api->delete('/trails/{trail}', 'App\Http\Controllers\TrailController@delete');
        $api->get('/trails/{trail}', 'App\Http\Controllers\TrailController@detail');
        $api->get('/trails/type', 'App\Http\Controllers\TrailController@type');

        // stars
        $api->get('/stars', 'App\Http\Controllers\StarController@index');
        $api->get('/stars/all', 'App\Http\Controllers\StarController@all');

        // project
        $api->get('/projects', 'App\Http\Controllers\ProjectController@index');
        $api->get('/projects/search', 'App\Http\Controllers\ProjectController@search');
        $api->get('/projects/my_all', 'App\Http\Controllers\ProjectController@myAll');
        $api->get('/projects/my', 'App\Http\Controllers\ProjectController@my');
        $api->post('/projects', 'App\Http\Controllers\ProjectController@store');
        $api->get('/projects/{project}', 'App\Http\Controllers\ProjectController@detail');
        $api->put('/projects/{project}', 'App\Http\Controllers\ProjectController@edit');
        $api->put('/projects/{project}/status', 'App\Http\Controllers\ProjectController@changeStatus');
        $api->delete('/projects/{project}', 'App\Http\Controllers\ProjectController@delete');

        // template field
        $api->get('/project_fields', 'App\Http\Controllers\TemplateFieldController@getFields');

        // industry
        $api->get('/industries/all', 'App\Http\Controllers\IndustryController@all');

        $api->post('/personnel', 'App\Http\Controllers\PersonnelManageController@store');

        // calendar
        $api->get('/calendars/all', 'App\Http\Controllers\CalendarController@all');
        $api->post('/calendars', 'App\Http\Controllers\CalendarController@store');
        $api->get('/calendars/{calendar}', 'App\Http\Controllers\CalendarController@detail');
        $api->put('/calendars/{calendar}', 'App\Http\Controllers\CalendarController@edit');

        // schedule
        $api->get('/schedules', 'App\Http\Controllers\ScheduleController@index');
        $api->post('/schedules', 'App\Http\Controllers\ScheduleController@store');
        $api->put('/schedules/{schedule}', 'App\Http\Controllers\ScheduleController@edit');
        $api->get('/schedules/{schedule}', 'App\Http\Controllers\ScheduleController@detail');
        $api->delete('/schedules/{schedule}', 'App\Http\Controllers\ScheduleController@delete');
        $api->put('/schedules/{schedule}/recover', 'App\Http\Controllers\ScheduleController@recover');

        // material
        $api->get('/materials/all', 'App\Http\Controllers\MaterialController@all');

        // personnel
        $api->get('/personnel_list', 'App\Http\Controllers\PersonnelManageController@index');
        $api->put('/personnel/{user}/status', 'App\Http\Controllers\PersonnelManageController@statusEdit');
        $api->post('/personal/{user}', 'App\Http\Controllers\PersonnelManageController@storePersonal');
        $api->put('/edit/{user}/personal/{personalDetail}', 'App\Http\Controllers\PersonnelManageController@editPersonal');
        $api->put('/edit/{user}/jobs/{personalJob}', 'App\Http\Controllers\PersonnelManageController@editJobs');
        $api->post('/jobs/{user}', 'App\Http\Controllers\PersonnelManageController@storeJobs');
        $api->post('/salary/{user}', 'App\Http\Controllers\PersonnelManageController@storeSalary');
        $api->put('/edit/{user}/salary/{personalSalary}', 'App\Http\Controllers\PersonnelManageController@editSalary');
        $api->post('/security/{user}', 'App\Http\Controllers\PersonnelManageController@storeSecurity');
        $api->get('/personnel/{user}', 'App\Http\Controllers\PersonnelManageController@detail');

        // department
        $api->get('/departments', 'App\Http\Controllers\DepartmentController@index');
        // user
        $api->get('/users', 'App\Http\Controllers\UserController@index');


        //$api->put('/personnel/{user}/status', 'App\Http\Controllers\PersonnelManageController@statusEdit');


        $api->post('/materials', 'App\Http\Controllers\MaterialController@store');
        $api->put('/materials/{material}', 'App\Http\Controllers\MaterialController@edit');
        $api->delete('/materials/{material}', 'App\Http\Controllers\MaterialController@delete');
        $api->get('/materials/{material}', 'App\Http\Controllers\MaterialController@detail');

        // approval_groups
        $api->get('/approval_groups/all', 'App\Http\Controllers\ApprovalGroupController@all');
        $api->post('/approval_groups', 'App\Http\Controllers\ApprovalGroupController@store');
        $api->put('/approval_groups/{approval_group}', 'App\Http\Controllers\ApprovalGroupController@edit');
        $api->delete('/approval_groups/{approval_group}', 'App\Http\Controllers\ApprovalGroupController@delete');
        $api->get('/approval_groups/{approval_group}', 'App\Http\Controllers\ApprovalGroupController@detail');

    });
});

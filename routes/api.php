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
    $api->put('/users/telephone', 'App\Http\Controllers\UserController@telephone');

    # 原微信公众号绑定用户
    $api->post('/wechat/merge', 'App\Http\Controllers\Wechat\OfficialController@mergeUser');

    # 微信开放平台
    $api->any('/wechat_open', 'App\Http\Controllers\Wechat\OpenPlatformController@serve');
    $api->get('/wechat_open/oauth', 'App\Http\Controllers\Wechat\OpenPlatformController@getLoginUrl');
    $api->get('/wechat_open/oauth/callback', 'App\Http\Controllers\Wechat\OpenPlatformController@oauthCallback');

    # 服务
    $api->get('services/request_token', 'App\Http\Controllers\ServiceController@requestToken');
    $api->get('services/send_sms_code', 'App\Http\Controllers\ServiceController@sendSMSCode');

    $api->post('/users/telephone', 'App\Http\Controllers\UserController@telephone');

    //resource
    $api->get('/resources', 'App\Http\Controllers\ResourceController@index');
    $api->get('/platforms', 'App\Http\Controllers\PlatformController@index');

    $api->get('/download', 'App\Http\Controllers\ExcelController@download');
    $api->group(['middleware' => ['auth:api', 'bindings','checkpower']], function ($api) {

        // user
        $api->get('/users/my', 'App\Http\Controllers\UserController@my');
        $api->get('/users/{user}', 'App\Http\Controllers\UserController@show');
        //修改密码
        $api->put('/users/{user}', 'App\Http\Controllers\UserController@editpassword');


        // 自定义筛选集中
        $api->get('/trails/filter_fields', 'App\Http\Controllers\FilterFieldController@index');
        $api->post('/trails/filter', 'App\Http\Controllers\TrailController@getFilter');
        $api->get('/stars/filter_fields', 'App\Http\Controllers\FilterFieldController@index');
        $api->post('/stars/filter', 'App\Http\Controllers\StarController@getFilter');
        $api->get('/bloggers/filter_fields', 'App\Http\Controllers\FilterFieldController@index');
        $api->post('/bloggers/filter', 'App\Http\Controllers\BloggerController@getFilter');
        $api->get('/projects/filter_fields', 'App\Http\Controllers\FilterFieldController@index');
        $api->post('/projects/filter', 'App\Http\Controllers\ProjectController@getFilter');
        $api->get('/clients/filter_fields', 'App\Http\Controllers\FilterFieldController@index');
        $api->post('/clients/filter', 'App\Http\Controllers\ClientController@getFilter');
        $api->get('/pool/filter_fields', 'App\Http\Controllers\FilterFieldController@index');
        $api->post('/pool/filter', 'App\Http\Controllers\SeasPoolController@getFilter');

        //task
        $api->get('/tasks/filter', 'App\Http\Controllers\TaskController@filter');
        $api->post('/tasks', 'App\Http\Controllers\TaskController@store');
        $api->get('/tasks', 'App\Http\Controllers\TaskController@index');
        $api->get('/tasks/my', 'App\Http\Controllers\TaskController@my');
        $api->get('/tasks/mylist', 'App\Http\Controllers\TaskController@myList');

        $api->get('/tasks/my_all', 'App\Http\Controllers\TaskController@myAll');
        $api->get('/tasks/recycle_bin', 'App\Http\Controllers\TaskController@recycleBin');
        $api->get('/tasks/{task}', 'App\Http\Controllers\TaskController@show');
        $api->put('/tasks/{task}', 'App\Http\Controllers\TaskController@edit');
        $api->post('/tasks/{task}/recover', 'App\Http\Controllers\TaskController@recoverRemove');
        $api->delete('/tasks/{task}', 'App\Http\Controllers\TaskController@remove');
        $api->get('/tasksAll', 'App\Http\Controllers\TaskController@tasksAll');

//            ->middleware('can:delete,task');
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
        $api->post('/blogger/{blogger}/tasks/{task}/resource', 'App\Http\Controllers\TaskController@relevanceResource');
        $api->delete('/blogger/{blogger}/tasks/{task}/resource_relieve', 'App\Http\Controllers\TaskController@relieveResource');
        $api->post('/trails/{trail}/tasks/{task}/resource', 'App\Http\Controllers\TaskController@relevanceResource');
        $api->delete('/trails/{trail}/tasks/{task}/resource_relieve', 'App\Http\Controllers\TaskController@relieveResource');
        //模型用户(参与人)
        $api->post('/tasks/{task}/participant', 'App\Http\Controllers\ModuleUserController@addModuleUserParticipant');
        $api->put('/tasks/{task}/participant_remove', 'App\Http\Controllers\ModuleUserController@remove');

        $api->post('/calendars/participants', 'App\Http\Controllers\ModuleUserController@addModuleUserBatchParticipant');
//        $api->put('/calendars/{calendar}/participant_remove', 'App\Http\Controllers\ModuleUserController@remove');
        //附件
        $api->get('/repositorys/{repository}/affix', 'App\Http\Controllers\AffixController@index');
        $api->post('/repositorys/{repository}/affix', 'App\Http\Controllers\AffixController@add');
        $api->get('/schedules/{schedule}/affix', 'App\Http\Controllers\AffixController@index');
        $api->post('/schedules/{schedule}/affix', 'App\Http\Controllers\AffixController@add');
        $api->delete('/schedules/{schedule}/affixes/{affix}', 'App\Http\Controllers\AffixController@remove');
        $api->get('/announcements/{announcement}/affix', 'App\Http\Controllers\AffixController@index');
        $api->post('/announcements/{announcement}/affix', 'App\Http\Controllers\AffixController@add');
        $api->get('/tasks/{task}/affix', 'App\Http\Controllers\AffixController@index');
        $api->get('/tasks/{task}/affixes/recycle_bin', 'App\Http\Controllers\AffixController@recycleBin');
        $api->post('/tasks/{task}/affix', 'App\Http\Controllers\AffixController@add');
        $api->post('/tasks/{task}/affixes/{affix}/download', 'App\Http\Controllers\AffixController@download');
        $api->delete('/tasks/{task}/affixes/{affix}', 'App\Http\Controllers\AffixController@remove');
        $api->post('/tasks/{task}/affixes/{affix}/recover', 'App\Http\Controllers\AffixController@recoverRemove');
        $api->get('/reports/{report}/affix', 'App\Http\Controllers\AffixController@index');
        $api->post('/reports/{report}/affix', 'App\Http\Controllers\AffixController@add');


        $api->post("/attendance/{attendance}/affix", "App\Http\Controllers\AffixController@add");
        $api->get('/attendance/{attendance}/affix', 'App\Http\Controllers\AffixController@index');
        // 隐私设置
        $api->post('/bloggers/{blogger}/privacyUser', 'App\Http\Controllers\privacyUserController@store');
        $api->post('/projects/{project}/privacyUser', 'App\Http\Controllers\privacyUserController@store');
        $api->get('/privacyUsers', 'App\Http\Controllers\privacyUserController@detail');
        $api->put('/projects/{project}/privacyUser', 'App\Http\Controllers\privacyUserController@edit');
        $api->put('/bloggers/{blogger}/privacyUser', 'App\Http\Controllers\privacyUserController@edit');
        //  $api->delete('/report/{report}/affixes/{report}', 'App\Http\Controllers\AffixController@remove');
        //   $api->post('/report/{report}/affixes/{report}/recover', 'App\Http\Controllers\AffixController@recoverRemove');
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

        // 评论
        $api->get('/repositorys/{repository}/show_comment', 'App\Http\Controllers\CommentLogController@index');
        $api->post('/repositorys/{repository}/add_comment/{commentlog}', 'App\Http\Controllers\CommentLogController@addaddComment');
        $api->post('/repositorys/{repository}/add_comment', 'App\Http\Controllers\CommentLogController@addComment');
        //跟进
        // 简报 问题跟进
        $api->get('/issues/{issues}/operate_log', 'App\Http\Controllers\OperateLogController@myindex');
        $api->post('/issues/{issues}/follow_up', 'App\Http\Controllers\OperateLogController@addFollowUp');
        $api->get('/report/{report}/operate_log', 'App\Http\Controllers\OperateLogController@index');
        $api->post('/report/{report}/follow_up', 'App\Http\Controllers\OperateLogController@addFollowUp');
        $api->get('/tasks/{task}/operate_log', 'App\Http\Controllers\OperateLogController@index');
        $api->post('/tasks/{task}/follow_up', 'App\Http\Controllers\OperateLogController@addFollowUp');
        $api->get('/blogger/{blogger}/operate_log', 'App\Http\Controllers\OperateLogController@index');
        $api->post('/blogger/{blogger}/follow_up', 'App\Http\Controllers\OperateLogController@addFollowUp');
        $api->get('/projects/{project}/operate_log', 'App\Http\Controllers\OperateLogController@index');
        $api->post('/projects/{project}/follow_up', 'App\Http\Controllers\OperateLogController@addFollowUp');
        $api->get('/stars/{star}/operate_log', 'App\Http\Controllers\OperateLogController@index');
        $api->post('/stars/{star}/follow_up', 'App\Http\Controllers\OperateLogController@addFollowUp');
        $api->get('/trails/{trail}/operate_log', 'App\Http\Controllers\OperateLogController@index');
        $api->post('/trails/{trail}/follow_up', 'App\Http\Controllers\OperateLogController@addFollowUp');
        $api->get('/clients/{client}/operate_log', 'App\Http\Controllers\OperateLogController@index');
        $api->post('/clients/{client}/follow_up', 'App\Http\Controllers\OperateLogController@addFollowUp');
        $api->get('/contracts/{contract}/operate_log', 'App\Http\Controllers\OperateLogController@index');
        $api->post('/contracts/{contract}/follow_up', 'App\Http\Controllers\OperateLogController@addFollowUp');
        $api->post('/approval_instances/{instance}/follow_up', 'App\Http\Controllers\OperateLogController@addFollowUp');
        $api->get('/approval_instances/{instance}/operate_log', 'App\Http\Controllers\OperateLogController@index');
        //stars
        $api->get('/stars/export', 'App\Http\Controllers\StarController@export')->middleware('export');
        $api->post('/stars/import', 'App\Http\Controllers\StarController@import');
        $api->post('/stars', 'App\Http\Controllers\StarController@store');
        $api->get('/stars', 'App\Http\Controllers\StarController@index');
        $api->get('/stars/all', 'App\Http\Controllers\StarController@all');
        $api->put('/stars/{star}', 'App\Http\Controllers\StarController@edit');
        $api->get('/stars/recycle_bin', 'App\Http\Controllers\StarController@recycleBin');
        $api->get('/stars/{star}', 'App\Http\Controllers\StarController@show');
        $api->post('/stars/{star}/recover', 'App\Http\Controllers\StarController@recoverRemove');
        $api->delete('/stars/{star}', 'App\Http\Controllers\StarController@remove');
        $api->get('/stars/{star}/gettaskandprojejct', 'App\Http\Controllers\StarController@getFiveTaskAndProjejct');
        //获取明星作品列表
        $api->get('/stars/{star}/works', 'App\Http\Controllers\WorkController@index');
        //创建明星作品
        $api->post('/stars/{star}/works', 'App\Http\Controllers\WorkController@store');
        //模型用户(宣传人)
        $api->post('/stars/{star}/publicity', 'App\Http\Controllers\ModuleUserController@addModuleUserPublicity');

        $api->put('/stars/{star}/publicity_remove', 'App\Http\Controllers\ModuleUserController@remove');
        //分配经纪人
        $api->post('/stars/{star}/broker', 'App\Http\Controllers\ModuleUserController@addModuleUserBroker');
        //获取艺人和博主的联合列表
        $api->get('/starandblogger','App\Http\Controllers\StarController@getStarAndBlogger');
        //为多个博主艺人分配多个经纪人宣传人制作人
        $api->post('/distribution/person', 'App\Http\Controllers\ModuleUserController@addMore');
        $api->delete('/star/{star}/affixes/{affix}', 'App\Http\Controllers\AffixController@remove');
        //导入 导出
        //->middleware('export')
        $api->get('/bloggers/export', 'App\Http\Controllers\BloggerController@export')->middleware('export');
        $api->post('/bloggers/import', 'App\Http\Controllers\BloggerController@import');
        //blogger
        $api->post('/bloggers', 'App\Http\Controllers\BloggerController@store');
        // 分配制作人
        $api->post('/bloggers/{blogger}', 'App\Http\Controllers\BloggerController@producerStore');
        //  $api->post('/bloggers/follow/add', 'App\Http\Controllers\BloggerController@follow_store');
        $api->get('/bloggers', 'App\Http\Controllers\BloggerController@index');
        $api->get('/bloggers/all', 'App\Http\Controllers\BloggerController@all');
        $api->get('/bloggers/select', 'App\Http\Controllers\BloggerController@select');
        //获取类型
        $api->get('/bloggers/gettype', 'App\Http\Controllers\BloggerController@gettypename');
        //添加作品
        $api->post('/bloggers/new/production', 'App\Http\Controllers\BloggerController@productionStore');
        //查询任务  是否有问卷
        $api->get('/bloggers/{task}/taskblogger', 'App\Http\Controllers\BloggerController@taskBloggerProductionIndex');
        // 查看作品
        $api->get('/bloggers/index/production', 'App\Http\Controllers\BloggerController@productionIndex');
        $api->get('/bloggers/{blogger}', 'App\Http\Controllers\BloggerController@show');
        $api->put('/bloggers/{blogger}', 'App\Http\Controllers\BloggerController@edit');
        $api->get('/bloggers/recycle_bin', 'App\Http\Controllers\BloggerController@recycleBin');
        $api->delete('/bloggers/{blogger}', 'App\Http\Controllers\BloggerController@remove');
        $api->post('/bloggers/{blogger}/recover', 'App\Http\Controllers\BloggerController@recoverRemove');
         //账单
        $api->get('/bloggers/{blogger}/bill', 'App\Http\Controllers\ProjectBillController@Index');
        $api->get('/stars/{star}/bill', 'App\Http\Controllers\ProjectBillController@Index');
        $api->get('/projects/{project}/bill', 'App\Http\Controllers\ProjectBillController@Index');
        $api->post('/projects/{project}/store/bill', 'App\Http\Controllers\ProjectBillController@store');
        //考勤
        //提交申请
        $api->post('/attendance', 'App\Http\Controllers\AttendanceController@store');
        //我的考勤统计
        $api->get('/attendance/myselfstatistics', 'App\Http\Controllers\AttendanceController@myselfStatistics');
        //我的考勤请假统计
        $api->get('/attendance/myselfleavelstatistics', 'App\Http\Controllers\AttendanceController@myselfLeavelStatistics');
        //根据条件统计考勤  成员考勤--考勤统计
        $api->get('/attendance/statistics', 'App\Http\Controllers\AttendanceController@statistics');
        //成员考勤--请假统计
        $api->get('/attendance/leavestatistics', 'App\Http\Controllers\AttendanceController@leaveStatistics');
        //考勤汇总 type 1:请假  2:加班 3:出差  4:外勤
        $api->get('/attendance/collect', 'App\Http\Controllers\AttendanceController@collect');
        //考勤日历
        $api->get('/attendance/calendar', 'App\Http\Controllers\AttendanceController@attendanceCalendar');
        //我申请的
        $api->get('/attendance/myapply', 'App\Http\Controllers\AttendanceController@myApply');
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
        $api->get('/clients/export', 'App\Http\Controllers\ClientController@export')->middleware('export');
        $api->post('/clients/import', 'App\Http\Controllers\ClientController@import');
        $api->get('/clients/filter', 'App\Http\Controllers\ClientController@filter');
        $api->get('/clients', 'App\Http\Controllers\ClientController@index');
        $api->get('/clients/all', 'App\Http\Controllers\ClientController@all');
        $api->post('/clients', 'App\Http\Controllers\ClientController@store');
//            ->middleware('can:create,App\Models\Client');
        $api->put('/clients/{client}', 'App\Http\Controllers\ClientController@edit');
        $api->put('/clients/{client}/recover', 'App\Http\Controllers\ClientController@recover');
        $api->delete('/clients/{client}', 'App\Http\Controllers\ClientController@delete');
        $api->get('/clients/{client}', 'App\Http\Controllers\ClientController@detail');
        $api->get('/clients/{client}/projects', 'App\Http\Controllers\ProjectController@getClientProject');
        $api->get('/clients/{client}/contracts', 'App\Http\Controllers\ContractController@getClientContracts');
        //announcement
        $api->get('/announcements', 'App\Http\Controllers\AnnouncementController@index');
        $api->get('/announcements/{announcement}', 'App\Http\Controllers\AnnouncementController@show');
        $api->put('/announcements/{announcement}', 'App\Http\Controllers\AnnouncementController@edit');
        $api->delete('/announcements/{announcement}', 'App\Http\Controllers\AnnouncementController@remove');
        $api->post('/announcements', 'App\Http\Controllers\AnnouncementController@store');
        //  report
        $api->get('/report', 'App\Http\Controllers\ReportController@index');
        $api->post('/report', 'App\Http\Controllers\ReportController@store');
        $api->get('/report/all', 'App\Http\Controllers\ReportController@all');
        $api->put('/report/{report}', 'App\Http\Controllers\ReportController@edit');
        $api->delete('/report', 'App\Http\Controllers\ReportController@delete');
        $api->get('/report/issues', 'App\Http\Controllers\ReportController@indexIssues');
        $api->post('/report/issues', 'App\Http\Controllers\ReportController@storeIssues');
        $api->put('/report/issues/{issues}', 'App\Http\Controllers\ReportController@editIssues');
        $api->put('/report/issues/order/template', 'App\Http\Controllers\ReportController@edit1Issues');
        $api->delete('/report/issues', 'App\Http\Controllers\ReportController@deleteIssues');
        // review
        $api->get('/review', 'App\Http\Controllers\ReviewController@index');
        $api->get('/review/{review}', 'App\Http\Controllers\ReviewController@show');
        $api->post('/review', 'App\Http\Controllers\ReviewController@store');
        $api->put('/review/{review}', 'App\Http\Controllers\ReviewController@edit');
        $api->put('/review/answer/{review}', 'App\Http\Controllers\ReviewController@editAnswer');
        $api->get('/review/my/template', 'App\Http\Controllers\ReviewController@myTemplate');
        $api->put('/review/my/template/{reviewtitle}', 'App\Http\Controllers\ReviewController@myTemplateEdit');
        $api->get('/review/member/template', 'App\Http\Controllers\ReviewController@memberTemplate');
        $api->get('/review/member/statistic', 'App\Http\Controllers\ReviewController@statistics');
        // Repository
        $api->get('/repositorys', 'App\Http\Controllers\RepositoryController@index');
        $api->post('/repositorys', 'App\Http\Controllers\RepositoryController@store');
        $api->get('/repositorys/{repository}', 'App\Http\Controllers\RepositoryController@show');
        $api->put('/repositorys/{repository}', 'App\Http\Controllers\RepositoryController@edit');
        $api->delete('/repositorys/{repository}', 'App\Http\Controllers\RepositoryController@delete');
        //  launch
        $api->get('/launch', 'App\Http\Controllers\LaunchController@index');
        $api->get('/launch/all', 'App\Http\Controllers\LaunchController@all');
        $api->get('/launch/all/draft', 'App\Http\Controllers\LaunchController@allDraft');
        $api->post('/launch', 'App\Http\Controllers\LaunchController@store');
        //简报存草稿
        $api->post('/launch/{draft}/draft', 'App\Http\Controllers\LaunchController@storeDraft');
        $api->get('/launch/draft', 'App\Http\Controllers\LaunchController@indexDraft');
        $api->delete('/launch/draft', 'App\Http\Controllers\LaunchController@deleteDraft');
        // $api->get('launch/issues', 'App\Http\Controllers\launchController@index_issues');

        // trail
        $api->get('/trails/export', 'App\Http\Controllers\TrailController@export')->middleware('export');
        $api->post('/trails/import', 'App\Http\Controllers\TrailController@import');
        $api->get('/trails/filter', 'App\Http\Controllers\TrailController@filter');
        $api->get('/trails/type', 'App\Http\Controllers\TrailController@type');
        $api->get('/trails', 'App\Http\Controllers\TrailController@index');
        $api->get('/trails/all', 'App\Http\Controllers\TrailController@all');
        $api->get('/trails/search', 'App\Http\Controllers\TrailController@search');
        $api->post('/trails', 'App\Http\Controllers\TrailController@store');
        $api->put('/trails/{trail}', 'App\Http\Controllers\TrailController@edit');
        $api->put('/trails/{trail}/recover', 'App\Http\Controllers\TrailController@recover');
        $api->put('/trails/{trail}/refuse', 'App\Http\Controllers\TrailController@refuse');
        $api->delete('/trails/{trail}', 'App\Http\Controllers\TrailController@delete');
        $api->get('/trails/{trail}', 'App\Http\Controllers\TrailController@detail');


        // stars
        $api->get('/stars', 'App\Http\Controllers\StarController@index');
        $api->get('/stars/all', 'App\Http\Controllers\StarController@all');

        //review
        //查看问题
      //  $api->get('/tasks/{reviewquestionnaire}/questions', 'App\Http\Controllers\ReviewQuestionController@index');
        $api->get('/reviews/{reviewquestionnaire}/questions', 'App\Http\Controllers\ReviewQuestionController@index');
        $api->post('/reviews/{reviewquestionnaire}/create', 'App\Http\Controllers\ReviewQuestionController@store');
        //查看问劵
        $api->get('/reviewquestionnaires', 'App\Http\Controllers\ReviewQuestionnaireController@index');
        $api->get('/reviewquestionnaires/{reviewquestionnaire}/show', 'App\Http\Controllers\ReviewQuestionnaireController@show');
        $api->post('/reviewquestionnaires/{production}/create', 'App\Http\Controllers\ReviewQuestionnaireController@store');
        $api->post('/reviewquestionnaires/{reviewquestionnaire}/create/excellent', 'App\Http\Controllers\ReviewQuestionnaireController@storeExcellent');

        //保存问劵
        $api->post('/reviews/{reviewquestionnaire}/store/Answer', 'App\Http\Controllers\ReviewQuestionController@storeAnswer');
        //查看问题对应选项
        $api->get('/reviews/{reviewquestionnaire}/questions/{reviewquestion}/items/index', 'App\Http\Controllers\ReviewQuestionItemController@index');
        $api->post('/reviews/{reviewquestionnaire}/questions/{reviewquestion}/items/store', 'App\Http\Controllers\ReviewQuestionItemController@store');
        $api->put('/reviews/{reviewquestionnaire}/questions/{reviewquestion}/items/{reviewquestionitem}/value', 'App\Http\Controllers\ReviewQuestionItemController@updateValue');






        // project
        $api->get('/projects/filter', 'App\Http\Controllers\ProjectController@filter');

        $api->get('/projects/all', 'App\Http\Controllers\ProjectController@all');
        $api->get('/projects', 'App\Http\Controllers\ProjectController@index');
        $api->get('/projects/export', 'App\Http\Controllers\ProjectController@export');
        $api->get('/projects/my_all', 'App\Http\Controllers\ProjectController@myAll');
        $api->get('/projects/my', 'App\Http\Controllers\ProjectController@my');
        $api->get('/projects/relate_client', 'App\Http\Controllers\ProjectController@getClient');
        $api->post('/projects', 'App\Http\Controllers\ProjectController@store');
        $api->post('projects/{project}/relates', 'App\Http\Controllers\ProjectController@addRelates');
        $api->get('projects/{project}/returned/money', 'App\Http\Controllers\ProjectController@indexReturnedMoney');
        $api->get('returned/money/{projectreturnedmoney}', 'App\Http\Controllers\ProjectController@showReturnedMoney');
        $api->get('money/type', 'App\Http\Controllers\ProjectController@getMoneType');
        $api->put('returned/money/{projectreturnedmoney}', 'App\Http\Controllers\ProjectController@editReturnedMoney');
        $api->post('projects/{project}/returned/money', 'App\Http\Controllers\ProjectController@addReturnedMoney');
        $api->post('projects/{project}/returned/{projectreturnedmoney}/money', 'App\Http\Controllers\ProjectController@addProjectRecord');
        $api->delete('returned/money/{projectreturnedmoney}', 'App\Http\Controllers\ProjectController@deleteReturnedMoney');
        //获取审批通过的项目
        $api->get('/get_has_approval_project', 'App\Http\Controllers\ProjectController@getHasApprovalProject');



        $api->get('/projects/{project}', 'App\Http\Controllers\ProjectController@detail');
        $api->get('/projects/{project}/course', 'App\Http\Controllers\ProjectController@allCourse');
        $api->put('/projects/{project}', 'App\Http\Controllers\ProjectController@edit');
        $api->put('/projects/{project}/course', 'App\Http\Controllers\ProjectController@course');
        $api->put('/projects/{project}/status', 'App\Http\Controllers\ProjectController@changeStatus');
        $api->delete('/projects/{project}', 'App\Http\Controllers\ProjectController@delete');
        //获取明星写的项目
        $api->get('/projects/star/{star}', 'App\Http\Controllers\ProjectController@getStarProject');

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
        $api->delete('/calendars/{calendar}', 'App\Http\Controllers\CalendarController@delete');

        //明星，博主日程
        $api->get('/schedules/getcalendar', 'App\Http\Controllers\ScheduleController@getCalendar');
        // schedule
        $api->get('/schedules', 'App\Http\Controllers\ScheduleController@index');
        $api->get('/schedules/list', 'App\Http\Controllers\ScheduleController@listIndex');
        $api->get('/schedules/all', 'App\Http\Controllers\ScheduleController@all');
        $api->post('/schedules', 'App\Http\Controllers\ScheduleController@store');
        $api->post('/schedules/{schedule}/tasks', 'App\Http\Controllers\ScheduleController@storeSchedulesTask');
        $api->get('/schedules/{schedule}/tasks', 'App\Http\Controllers\ScheduleController@indexSchedulesTask');
        $api->delete('/schedules/{schedule}/projects/{project}', 'App\Http\Controllers\ScheduleController@removeoneSchedulesRelate');
        $api->delete('/schedules/{schedule}/tasks/{task}', 'App\Http\Controllers\ScheduleController@removeoneSchedulesRelate');
        $api->delete('/schedules/{schedule}/tasks', 'App\Http\Controllers\ScheduleController@removeSchedulesTask');
        $api->put('/schedules/{schedule}', 'App\Http\Controllers\ScheduleController@edit');
        $api->get('/schedules/{schedule}', 'App\Http\Controllers\ScheduleController@detail');
        $api->delete('/schedules/{schedule}', 'App\Http\Controllers\ScheduleController@delete');
        $api->put('/schedules/{schedule}/recover', 'App\Http\Controllers\ScheduleController@recover');


        // material
        $api->get('/materials/all', 'App\Http\Controllers\MaterialController@all');

        // personnel
        $api->get('/personnel_list', 'App\Http\Controllers\PersonnelManageController@index');
        $api->get('/archive', 'App\Http\Controllers\PersonnelManageController@archivelist');
        $api->put('/personnel/{user}', 'App\Http\Controllers\PersonnelManageController@statusEdit');
        $api->post('/personal/{user}', 'App\Http\Controllers\PersonnelManageController@storePersonal');
        //修改基本信息
        $api->put('/edit/{user}/personal', 'App\Http\Controllers\PersonnelManageController@editPersonal');
        //修改个人信息
        $api->put('/edit/{user}/detail', 'App\Http\Controllers\PersonnelManageController@editPersonalDetail');

        $api->put('/edit/{user}/jobs/{personalJob}', 'App\Http\Controllers\PersonnelManageController@editJobs');
        $api->post('/jobs/{user}', 'App\Http\Controllers\PersonnelManageController@storeJobs');
        $api->post('/salary/{user}', 'App\Http\Controllers\PersonnelManageController@storeSalary');
        //修改薪资
        $api->put('/edit/{user}/salary', 'App\Http\Controllers\PersonnelManageController@editSalary');
        $api->post('/security/{user}', 'App\Http\Controllers\PersonnelManageController@storeSecurity');
        $api->get('/personnel/{user}', 'App\Http\Controllers\PersonnelManageController@detail');
        $api->get('/security/{user}', 'App\Http\Controllers\PersonnelManageController@securityDetail');
        $api->put('/personal/edit/{user}', 'App\Http\Controllers\PersonnelManageController@editUser');
        $api->get('/personnel/portal/{user}', 'App\Http\Controllers\PersonnelManageController@portal');//
        $api->get('/personnel/entry/{user}', 'App\Http\Controllers\PersonnelManageController@entryDetail');//
        $api->get('/entry', 'App\Http\Controllers\PersonnelManageController@entry');//
        $api->put('/audit/{user}', 'App\Http\Controllers\PersonnelManageController@audit');//
        $api->put('/personnel/position/{user}', 'App\Http\Controllers\PersonnelManageController@editPosition');//
        //获取公司列表
        $api->get('/company', 'App\Http\Controllers\PersonnelManageController@getCompany');



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

        //获取粉丝数据
        $api->get('/starreport/fensi', 'App\Http\Controllers\StarReportController@getStarFensi');

        //报表
        //商务报表
        $api->get("/reportfrom/commercialfunnel", "App\Http\Controllers\ReportFormController@CommercialFunnelReportFrom");
        //销售漏斗
        $api->get("/reportfrom/salesFunnel","App\Http\Controllers\ReportFormController@salesFunnel");
        //销售线索报表--线索报表
        $api->get("/reportfrom/trail","App\Http\Controllers\ReportFormController@trailReportFrom");
        //销售线索报表--线索新增
        $api->get("/reportfrom/newtrail","App\Http\Controllers\ReportFormController@newTrail");
        //销售线索报表--线索占比perTrail
//        $api->get("/reportfrom/pertrail","App\Http\Controllers\ReportFormController@perTrail");
        $api->get("/reportfrom/salesFunnel", "App\Http\Controllers\ReportFormController@salesFunnel");
        //销售线索报表--行业分析
        $api->get("/reportfrom/industryanalysis", "App\Http\Controllers\ReportFormController@industryAnalysis");
        //项目报表
        $api->get("/reportfrom/projectreport", "App\Http\Controllers\ReportFormController@projectReport");
        //项目新增
        $api->get("/reportfrom/newproject", "App\Http\Controllers\ReportFormController@newProject");
        //项目占比
        $api->get("/reportfrom/percentageofproject", "App\Http\Controllers\ReportFormController@percentageOfProject");
        //客户报表
        $api->get("/reportfrom/clientreport", "App\Http\Controllers\ReportFormController@clientReport");
        //客户分析
        $api->get("/reportfrom/clientanalysis", "App\Http\Controllers\ReportFormController@clientAnalysis");

        //艺人报表
        $api->get("/reportfrom/starreport", "App\Http\Controllers\ReportFormController@starReport");
        //艺人线索分析
        $api->get("/reportfrom/startrailanalysis", "App\Http\Controllers\ReportFormController@starTrailAnalysis");
        //艺人项目分析
//        $api->get("/reportfrom/starprojectanalysis", "App\Http\Controllers\ReportFormController@starProjectAnalysis");
        //博主报表
        $api->get("/reportfrom/bloggerreport", "App\Http\Controllers\ReportFormController@bloggerReport");
        //博主线索分析
//        $api->get("/reportfrom/bloggertrailanalysis", "App\Http\Controllers\ReportFormController@bloggerTrailAnalysis");
//        博主项目分析
//        $api->get("/reportfrom/bloggerprojectanalysis", "App\Http\Controllers\ReportFormController@bloggerProjectAnalysis");

        $api->get('/users', 'App\Http\Controllers\UserController@index');
        $api->get('/user/all', 'App\Http\Controllers\UserController@all');


        /*组织架构 部门管理*/
        //获取部门列表
        $api->get('/departments', 'App\Http\Controllers\DepartmentController@index');
        //通讯录 名字排序
        $api->get('/departments/crew', 'App\Http\Controllers\DepartmentController@show');
        //查看部门
        $api->get('/departments/{department}', 'App\Http\Controllers\DepartmentController@detail');
        //增加部门
        $api->post('/departments', 'App\Http\Controllers\DepartmentController@store');
        //编辑部门
        $api->put('/departments/{department}', 'App\Http\Controllers\DepartmentController@edit');
        //移动部门
        $api->put('/departments/mobile/{department}', 'App\Http\Controllers\DepartmentController@mobile');
        //删除部门
        $api->delete('/departments/remove/{department}', 'App\Http\Controllers\DepartmentController@remove');
        //获取选择成员
        $api->get('/departments/select/{department}', 'App\Http\Controllers\DepartmentController@select');
        //选择成员完成添加
        $api->put('/departments/member/{department}', 'App\Http\Controllers\DepartmentController@selectStore');

        $api->get('/departments_list', 'App\Http\Controllers\DepartmentController@departmentsList');
        //获取职位列表
        $api->get('/departments_jobs', 'App\Http\Controllers\DepartmentController@jobsList');


        /*组织架构 职位管理*/
        $api->get('/position', 'App\Http\Controllers\DepartmentController@positionList');
        $api->post('/position', 'App\Http\Controllers\DepartmentController@positionStore');
        $api->put('/position/{position}', 'App\Http\Controllers\DepartmentController@positionEdit');
        $api->delete('/position/{position}', 'App\Http\Controllers\DepartmentController@positionDel');

        //用户禁用列表
        $api->get('/position/disable', 'App\Http\Controllers\DepartmentController@disableList');
        $api->put('/position/disable/{user}', 'App\Http\Controllers\DepartmentController@disableEdit');

        
        /*公海池*/
        $api->get('/pool','App\Http\Controllers\SeasPoolController@index');
        //领取
        $api->post('/pool/receive','App\Http\Controllers\SeasPoolController@receive');
        //分配
        $api->post('/pool/allot','App\Http\Controllers\SeasPoolController@allot');
        //退回
        $api->post('/pool/refund/{trail}','App\Http\Controllers\SeasPoolController@refund');





        /*后台权限 分组 控制台*/
        $api->get('/console','App\Http\Controllers\ConsoleController@index');
        //获取分组信息
        $api->get('/console/group','App\Http\Controllers\ConsoleController@getGroup');
        //添加分组
        $api->post('/console/group','App\Http\Controllers\ConsoleController@storeGroup');
        //修改分组
        $api->put('/console/group/{groupRoles}','App\Http\Controllers\ConsoleController@editGroup');
        //删除分组
        $api->delete('/console/group/{groupRoles}','App\Http\Controllers\ConsoleController@deleteGroup');
        /*后台权限 角色 控制台*/
        $api->get('/console/role','App\Http\Controllers\ConsoleController@getRole');
        //添加角色
        $api->post('/console/role','App\Http\Controllers\ConsoleController@storeRole');
        //修改角色
        $api->put('/console/role/{role}','App\Http\Controllers\ConsoleController@editRole');
        //删除角色
        $api->delete('/console/role/{role}','App\Http\Controllers\ConsoleController@deleteRole');
        //移动角色
        $api->put('/console/mobile/{role}','App\Http\Controllers\ConsoleController@mobileRole');
        //组获取人员
        $api->get('/console/person/{role}','App\Http\Controllers\ConsoleController@rolePerson');
        //角色和用户关联
        $api->post('/console/relevancy/{role}','App\Http\Controllers\ConsoleController@setRoleUser');
        //功能列表
        $api->get('/console/feature/{role}','App\Http\Controllers\ConsoleController@feature');
        //功能角色关联
        $api->post('/console/feature/{role}','App\Http\Controllers\ConsoleController@featureRole');
        //获取数据权限
        $api->get('/console/scope/{role}','App\Http\Controllers\ConsoleController@scope');
        //获取部门负责人
        $api->get('/console/director', 'App\Http\Controllers\ConsoleController@directorList');


        //增加修改数据权限
        $api->post('/console/scope/{role}','App\Http\Controllers\ConsoleController@scopeStore');
        $api->post('/console/features/{role}','App\Http\Controllers\ConsoleController@featureRole');
        //获取数据权限
        $api->get('/console/scope/{user}','App\Http\Controllers\ConsoleController@scope');
        /*后台权限 数据范围 控制台*/
        $api->get('/scope/{user}/module/{dictionaries}','App\Http\Controllers\ScopeController@index');
        $api->get('/scope/{user}/operation/{dictionaries}','App\Http\Controllers\ScopeController@show');
        //获取当前用户有权限的模块
        $api->get('/console/getpowermodel','App\Http\Controllers\ConsoleController@getPowerModel');
        //验证权限
        $api->get('/console/checkpower','App\Http\Controllers\ConsoleController@checkPower');



        // 审批
        //我申请
        $api->get('/approvals_project/my','App\Http\Controllers\ApprovalFormController@myApply');
        //我的审批 待审批
        $api->get('/approvals_project/approval','App\Http\Controllers\ApprovalFormController@myApproval');
        //我的审批 已审批
        $api->get('/approvals_project/thenapproval','App\Http\Controllers\ApprovalFormController@myThenApproval');
        $api->get('/approvals_project/notify','App\Http\Controllers\ApprovalFormController@notify');
        $api->get('/approvals/contracts', 'App\Http\Controllers\ApprovalFormController@getContractForms');
        $api->get('/approvals', 'App\Http\Controllers\ApprovalFormController@getGeneralForms');
        $api->get('/approvals/{approval}/form_control', 'App\Http\Controllers\ApprovalFormController@getForm');

        /*合同列表*/
        //我申请列表
        $api->get('/approvals_contract/my','App\Http\Controllers\ApprovalContractController@myApply');
        //我审批的 待审批
        $api->get('/approvals_contract/approval','App\Http\Controllers\ApprovalContractController@myApproval');
        //我审批的 已审批
        $api->get('/approvals_contract/thenapproval','App\Http\Controllers\ApprovalContractController@myThenApproval');
        //知会我的
        $api->get('/approvals_contract/notify','App\Http\Controllers\ApprovalContractController@notify');

        /*合同管理*/
        //项目合同
        $api->get('/approvals_contract/project','App\Http\Controllers\ApprovalContractController@project');
        //经济合同
        $api->get('/approvals_contract/economic','App\Http\Controllers\ApprovalContractController@economic');

        //项目详情合同列表
        $api->get('/approvals_contract/projectList','App\Http\Controllers\ApprovalContractController@projectList');


        /*一般审批列表*/
        //我申请列表
        $api->get('/approvals_general/my','App\Http\Controllers\ApprovalGeneralController@myApply');
        //我审批的 待审批
        $api->get('/approvals_general/approval','App\Http\Controllers\ApprovalGeneralController@myApproval');
        //知会我的
        $api->get('/approvals_general/notify','App\Http\Controllers\ApprovalGeneralController@notify');


        // 获取审批实例
        $api->get('/approval_instances/{instance}', 'App\Http\Controllers\ApprovalFormController@detail');
        $api->get('/approvals/specific_contract', 'App\Http\Controllers\ApprovalFormController@getContractForm');
        // 合同和普通审批新建
        $api->post('/approvals/{approval}', 'App\Http\Controllers\ApprovalFormController@instanceStore');
        // 审批流
        $api->get('/approvals/chains', 'App\Http\Controllers\ApprovalFlowController@getChains');
        $api->get('/approvals/{approval}/participants', 'App\Http\Controllers\ApprovalParticipantController@getFixedParticipants');
        $api->get('/approval_instances/{instance}/chains', 'App\Http\Controllers\ApprovalFlowController@getMergeChains');
        $api->post('/approval_instances/{instance}/participant', 'App\Http\Controllers\ApprovalFlowController@changeParticipant');
        $api->put('/approval_instances/{instance}/agree', 'App\Http\Controllers\ApprovalFlowController@agree');
        $api->put('/approval_instances/{instance}/refuse', 'App\Http\Controllers\ApprovalFlowController@refuse');
        $api->put('/approval_instances/{instance}/transfer', 'App\Http\Controllers\ApprovalFlowController@transfer');
        $api->put('/approval_instances/{instance}/cancel', 'App\Http\Controllers\ApprovalFlowController@cancel');
        $api->put('/approval_instances/{instance}/discard', 'App\Http\Controllers\ApprovalFlowController@discard');

        //任务转私密
        $api->post('/task/secret/{task}', 'App\Http\Controllers\TaskController@secret');

        //获取消息
        $api->get('/getmsg','App\Http\Controllers\MessageController@index');
        //更改消息状态
        $api->get('/changestae','App\Http\Controllers\MessageController@changeSate');
        $api->get('/getmodules','App\Http\Controllers\MessageController@getModules');
        //移动端获取消息
        $api->get('/mobile_get_message','App\Http\Controllers\MessageController@MobileGetMessage');


        //数据字典
        //列表
        $api->get('/datadic/index','App\Http\Controllers\DataDictionaryController@index');
        $api->post('/datadic/add','App\Http\Controllers\DataDictionaryController@store');

        $api->get('data_dictionary/{pid}', 'App\Http\Controllers\DataDictionaryController@company');
        $api->get('data_dictionary/appraising/{pid}', 'App\Http\Controllers\DataDictionaryController@appraising');
        //艺人项目列表
        $api->get("/stars/{star}/project","App\Http\Controllers\ProjectController@getProjectList");
        $api->get("/bloggers/{blogger}/project","App\Http\Controllers\ProjectController@getProjectList");
    });
});

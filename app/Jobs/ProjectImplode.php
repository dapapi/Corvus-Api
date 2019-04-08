<?php

namespace App\Jobs;

use App\Models\Project;
use App\Models\ProjectImplode as Implode;
use App\Models\ProjectImplodeTalent;
use App\Models\Trail;
use App\ModuleableType;
use App\ModuleUserType;
use App\OperateLogMethod;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProjectImplode implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $project;
    protected $trail;

    /**
     * Create a new job instance.
     *
     * @param Project $project
     * @param Trail $trail
     */
    public function __construct(Project $project)
    {
        $this->project = $project;
        $this->trail = $project->trail;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $project = $this->project;

        $temp = Implode::find($project->id);
        if ($temp)
            return;

        $implodeArr = [
            'id' => $project->id,
            'form_instance_number' => $project->project_number,
            'project_name' => $project->title,
            'project_type' => $project->type,
            'project_priority' => $project->priority,
            'project_start_at' => $project->start_at,
            'project_end_at' => $project->end_at,
            'project_store_at' => $project->created_at,
            'principal_id' => $project->principal_id,
            'principal' => $project->principal->name,
            'projected_expenditure' => $project->projected_expenditure,
            'trail_fee' => '',
            'creator_id' => $project->creator_id,
            'creator' => $project->creator->name,
            'department_id' => $project->principal->department()->first()->id ?? null,
            'department' => $project->principal->department()->first()->name ?? null,
            'project_status' => $project->status,
            # 模版字段部分
            'sign_at' => DB::table('template_field_values')->where('field_id', 22)->where('project_id', $project->id)->value('value'),
            'launch_at' => DB::table('template_field_values')->where('field_id', 52)->where('project_id', $project->id)->value('value'),
            'platforms' => DB::table('template_field_values')->where('field_id', 11)->where('project_id', $project->id)->value('value'),
            'show_type' => DB::table('template_field_values')->where('field_id', 31)->where('project_id', $project->id)->value('value'),
            'guest_type' => DB::table('template_field_values')->where('field_id', 32)->where('project_id', $project->id)->value('value'),
            'record_at' => DB::table('template_field_values')->where('field_id', 34)->where('project_id', $project->id)->value('value'),
            'movie_type' => DB::table('template_field_values')->where('field_id', 7)->where('project_id', $project->id)->value('value'),
            'theme' => DB::table('template_field_values')->where('field_id', 9)->where('project_id', $project->id)->value('value'),
            'team_info' => DB::table('template_field_values')->where('field_id', 23)->where('project_id', $project->id)->value('value'),
            // 去掉text类型字段
//            'follow_up' => DB::table('template_field_values')->where('field_id', 24)->where('project_id', $project->id)->value('value'),
            'walk_through_at' => DB::table('template_field_values')->where('field_id', 25)->where('project_id', $project->id)->value('value'),
            'walk_through_location' => DB::table('template_field_values')->where('field_id', 26)->where('project_id', $project->id)->value('value'),
//            'walk_through_feedback' => DB::table('template_field_values')->where('field_id', 27)->where('project_id', $project->id)->value('value'),
//            'follow_up_result' => DB::table('template_field_values')->where('field_id', 28)->where('project_id', $project->id)->value('value'),
            'agreement_fee' => DB::table('template_field_values')->where('field_id', 55)->where('project_id', $project->id)->value('value'),
            'multi_channel' => DB::table('template_field_values')->where('field_id', 54)->where('project_id', $project->id)->value('value'),
            # trail 相关
        ];


        $implodeArr['expenditure'] = DB::table('project_bills')->where('project_kd_name', $project->title)->where('expense_type', '支出')->sum('money') ?? null;
        $implodeArr['revenue'] = DB::table('contracts')->where('project_id', $project->id)->where('type', '收入')->sum('contract_money') ?? null;

        $implodeArr['last_follow_up_at'] = $project->created_at;
        $implodeArr['latest_time'] = $project->created_at;
        if ($project->last_follow_up_at) {
            $implodeArr['latest_time'] = $project->last_follow_up_at;
            $implodeArr['last_follow_up_at'] = $project->created_at;
        }
        $implodeArr['last_updated_at'] = $project->last_updated_at;
        $lastFollowUp = $project->operateLogs()->where('method', OperateLogMethod::FOLLOW_UP)->orderBy('created_at', 'desc')->first();
        $implodeArr['last_follow_up_user_id'] = $lastFollowUp ? $lastFollowUp->user_id : null;
        if ($implodeArr['last_follow_up_user_id'])
            $implodeArr['last_follow_up_user_name'] = DB::table('users')->where('id', $implodeArr['last_follow_up_user_id'])->value('name');

        $trail = $project->trail;
        if ($trail) {
            $implodeArr['resource_type'] = $trail->resource_type;
            $implodeArr['trail_fee'] = $trail->fee;
            $implodeArr['cooperation_type'] = $trail->copperation_type;
            $implodeArr['trail_status'] = $trail->status;
            $implodeArr['client'] = $trail->client->company;

            $starExpectations = $trail->starExpectations()->pluck('starable_id')->toArray();
            $broker = DB::table('module_users')
                ->whereIn('moduleable_id', $starExpectations)
                ->where('moduleable_type', ModuleableType::STAR)
                ->where('type', ModuleUserType::BROKER)->pluck('user_id')->toArray();
            $brokerName = DB::table('users')->whereIn('id', $broker)->pluck('name')->toArray();
            $starName = DB::table('stars')->whereIn('id', $starExpectations)->pluck('name')->toArray();
            $teamM = DB::table('departments')
                ->leftJoin('department_user', 'departments.id', '=', 'department_user.department_id')
                ->whereIn('department_user.user_id', $broker)
                ->pluck('departments.name')->toArray();

            $bloggerExpectations = $trail->bloggerExpectations()->pluck('starable_id')->toArray();
            $producer = DB::table('module_users')
                ->whereIn('moduleable_id', $bloggerExpectations)
                ->where('moduleable_type', ModuleableType::BLOGGER)
                ->where('type', ModuleUserType::PRODUCER)->pluck('user_id')->toArray();
            $producerName = DB::table('users')->whereIn('id', $producer)->pluck('name')->toArray();
            $bloggerName = DB::table('bloggers')->whereIn('id', $bloggerExpectations)->pluck('nickname')->toArray();
            $teamProducer = DB::table('departments')
                ->leftJoin('department_user', 'departments.id', '=', 'department_user.department_id')
                ->whereIn('department_user.user_id', $producer)
                ->pluck('departments.name')->toArray();


            $implodeArr['team_m'] = implode(',', array_unique($teamM));
            $implodeArr['team_producer'] = implode(',', array_unique($teamProducer));
            $implodeArr['stars'] = implode(',', $starName);
            $implodeArr['star_ids'] = implode(',', $starExpectations);
            $implodeArr['bloggers'] = implode(',', $bloggerName);
            $implodeArr['blogger_ids'] = implode(',', $bloggerExpectations);
            $implodeArr['producer'] = implode(',', array_unique($producerName));
            $implodeArr['producer_id'] = implode(',', array_unique($producer));
            $implodeArr['broker'] = implode(',', array_unique($brokerName));
            $implodeArr['broker_id'] = implode(',', array_unique($broker));
        }

        DB::beginTransaction();
        try {
            Implode::create($implodeArr);
        } catch (Exception $exception) {
            Log::error('项目综合创建失败');
            Log::error($exception);
            DB::rollBack();
        }
        DB::commit();
    }
}

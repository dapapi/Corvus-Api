<?php

namespace App\Http\Transformers;
use App\ModuleableType;
use App\Models\ApprovalFlow\Change;
use App\Models\ApprovalForm\Business;
use App\Models\PrivacyUser;
use App\Models\Project;
use App\PrivacyType;
use App\TaskStatus;
use Illuminate\Support\Facades\Auth;
use League\Fractal\TransformerAbstract;
use Illuminate\Support\Facades\DB;


class ClientProjectTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['principal', 'creator', 'fields', 'trail', 'participants', 'relate_tasks', 'relate_projects','relate_project_courses','relate_project_bills_resource', 'tasks'];

    private  $isAll = true;

    public function __construct($isAll = true)
    {
        $this->isAll = $isAll;
    }

    public function transform(Project $project)
    {
        $user = Auth::guard('api')->user();

        if ($this->isAll) {
            $array = [
                'id' => hashid_encode($project->id),
                'form_instance_number' => $project->project_number,
                'title' => $project->title,
                'type' => $project->type,
                'privacy' => $project->privacy,
                'priority' => $project->priority,
                'status' => $project->status,
                'created_at' => $project->created_at->toDateTimeString(),
                'updated_at' => $project->updated_at->toDateTimeString(),
                'desc' => $project->desc,
                // 日志内容
                'power' =>  $project->power,
            ];
        }

//        //负责人
        $participants = DB::table('users')//
            ->where('users.id', $project->principal_id)
            ->select('users.id','users.name')->first();
        if($participants){
            $array['principal']['data']['id'] = hashid_encode($participants->id);
            $array['principal']['data']['name'] = $participants->name;
        }else{
            $array['principal']['data']['id'] = '';
            $array['principal']['data']['name'] = '';
        }

        $trails = DB::table('projects')
            ->join('trails', function ($join) {
                $join->on('trails.id', '=', 'projects.trail_id');
            })
            ->join('clients', function ($join) {
                $join->on('clients.id', '=', 'trails.client_id');
            })
        ->where('trails.id', $project->trail_id)->select('clients.id','clients.company')->first();
        if($trails){
            $array['trail']['data']['client']['data']['id'] = hashid_encode($trails->id);
            $array['trail']['data']['client']['data']['company'] = $trails->company;
        }else{

            $array['trail']['data']['client']['data']['id'] = '';
            $array['trail']['data']['client']['data']['company'] = '';
        }



        return $array;
    }

    public function includePrincipal(Project $project)
    {
        $principal = $project->principal;
        if (!$principal)
            return $this->null();

        return $this->item($principal, new UserTransformer());
    }

    public function includeCreator(Project $project)
    {
        $creator = $project->creator;
        if (!$creator)
            return $this->null();

        return $this->item($creator, new UserTransformer());
    }

    public function includeFields(Project $project)
    {
        $fields = $project->fields;
        if (!$fields)
            return $this->null();
        return $this->collection($fields, new FieldValueTransformer());
    }

    public function includeTrail(Project $project)
    {

        $user = Auth::guard('api')->user();
//        $setprivacy = true;
//        if($project ->creator_id != $user->id && $project->principal_id != $user->id) {
//            $array['moduleable_id'] = $project->id;
//            $array['moduleable_type'] = ModuleableType::PROJECT;
//            $array['moduleable_field'] = PrivacyType::FEE;
//            $array['is_privacy'] = PrivacyType::OTHER;
//            $array['user_id'] = $user->id;
//            $setprivacy = PrivacyUser::where($array)->first();


        //}
            $trail = $project->trail;
            if (!$trail)
                return null;
            if($trail->type == '5')
                return $this->item($trail, new TrailTransformer());
//            if (!$setprivacy)
//                return $this->item($trail, new TrailTransformer(true,$setprivacy));

            else
                return $this->item($trail, new TrailTransformer(true,$project,$user));

    }

    public function includeParticipants(Project $project)
    {
        $participants = $project->participants;
        if (!$participants)
            return $this->null();
        return $this->collection($participants, new UserTransformer());
    }

    public function includeRelateTasks(Project $project)
    {
        $tasks = $project->relateTasks;
        if (!$tasks)
            return $this->null();
        return $this->collection($tasks, new TaskTransformer());
    }
    public function includeRelateProjects(Project $project)
    {
        $projects = $project->relateProjects;
        if (!$projects)
            return $this->null();
        return $this->collection($projects, new ProjectTransformer());
    }
    public function includeRelateProjectCourses(Project $project)
    {
        $projects = $project->relateProjectCourse;
            if($projects == null){

            }else{
                    return $this->collection($projects, new ProjectCourseTransformer());
                }


    }
    public function includeRelateProjectBillsResource(Project $project)
    {

        $projectbill = $project->relateProjectBillsResource;

        if($projectbill == null){

        }else{
            return $this->collection($projectbill, new ProjectBillResourcesTransformer());
        }


    }

    public function includeTasks(Project $project)
    {
        $tasks = $project->tasks()->stopAsc()
            ->where('status',TaskStatus::NORMAL)
            ->limit(3)->get();
        if (!$tasks)
            return $this->null();
        return $this->collection($tasks, new TaskTransformer());
    }
}

<?php

namespace App\Http\Transformers;
use App\ModuleableType;
use App\Models\ApprovalFlow\Change;
use App\Models\ApprovalForm\Business;
use App\Models\PrivacyUser;
use App\Models\Project;
use App\PrivacyType;
use Illuminate\Support\Facades\Auth;
use League\Fractal\TransformerAbstract;

class ProjectTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['principal', 'creator', 'fields', 'trail', 'participants', 'relate_tasks', 'relate_projects','relate_project_courses','relate_project_bills_resource'];

    private  $isAll = true;

    public function __construct($isAll = true)
    {
        $this->isAll = $isAll;
    }

    public function transform(Project $project)
    {
        $user = Auth::guard('api')->user();
        $setprivacy1 =array();
        $Viewprivacy2 =array();
        $array['moduleable_id']= $project->id;
        $array['moduleable_type']= ModuleableType::PROJECT;
        $array['is_privacy']=  PrivacyType::OTHER;
        $setprivacy = PrivacyUser::where($array)->get(['moduleable_field'])->toArray();
        foreach ($setprivacy as $key =>$v){

            $setprivacy1[]=array_values($v)[0];

        }
        if($project->creator_id != $user->id){
            $array['user_id']= $user->id;
            $Viewprivacy = PrivacyUser::where($array)->get(['moduleable_field'])->toArray();

            if($Viewprivacy){
                foreach ($Viewprivacy as $key =>$v){
                    $Viewprivacy1[]=array_values($v)[0];
                }
                $setprivacy1  = array_diff($setprivacy1,$Viewprivacy1);
            }
        }
        $business = Business::where('form_instance_number', $project->project_number)->first();
        $count = Change::where('form_instance_number', $project->project_numer)->count('form_instance_number');
        if ($this->isAll) {
            $array = [
                'id' => hashid_encode($project->id),
                'form_instance_number' => $project->project_number,
                'title' => $project->title,
                'type' => $project->type,
                'privacy' => $project->privacy,
                'priority' => $project->priority,
                'status' => $project->status,
                'projected_expenditure'=> $project->projected_expenditure,
                'start_at' => $project->start_at,
                'end_at' => $project->end_at,
                'created_at' => $project->created_at->toDateTimeString(),
                'updated_at' => $project->updated_at->toDateTimeString(),
                'desc' => $project->desc,
                // 日志内容
                'last_follow_up_at' => $project->last_follow_up_at,
                'last_updated_user' => $project->last_updated_user,
                'last_updated_at' => $project->last_updated_at,

            ];
            if($setprivacy1 && $project->creator_id != $user->id)
                foreach ($setprivacy1 as $key =>$v){
                    $Viewprivacy2[$v]=$key;
                }
            $array = array_merge($array,$Viewprivacy2);
             foreach ($array as $key1 => $val1)
             {
                 foreach ($Viewprivacy2 as $key2 => $val2)
                 {

                     if($key1 === $key2){

                         unset($array[$key1]);
                     }
                 }
             }
            if ($business)
                $array['approval_status'] = $business->status->id;

            if ($count > 1)
                $array['approval_begin'] = 1;
            else
                $array['approval_begin'] = 0;

        } else {
            $array = [
                'id' => hashid_encode($project->id),
                'title' => $project->title,
            ];
        }

        return $array;
    }

    public function includePrincipal(Project $project)
    {
        $principal = $project->principal;
        if (!$principal)
            return null;

        return $this->item($principal, new UserTransformer());
    }

    public function includeCreator(Project $project)
    {
        $creator = $project->creator;
        if (!$creator)
            return null;

        return $this->item($creator, new UserTransformer());
    }

    public function includeFields(Project $project)
    {
        $fields = $project->fields;

        return $this->collection($fields, new FieldValueTransformer());
    }

    public function includeTrail(Project $project)
    {
        $trail = $project->trail;
        if (!$trail)
            return null;
        return $this->item($trail, new TrailTransformer());
    }

    public function includeParticipants(Project $project)
    {
        $participants = $project->participants;

        return $this->collection($participants, new UserTransformer());
    }

    public function includeRelateTasks(Project $project)
    {
        $tasks = $project->relateTasks;
        return $this->collection($tasks, new TaskTransformer());
    }

    public function includeRelateProjects(Project $project)
    {
        $projects = $project->relateProjects;
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
}

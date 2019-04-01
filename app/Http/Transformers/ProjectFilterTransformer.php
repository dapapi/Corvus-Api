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

class ProjectFilterTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['principal', 'creator', 'fields', 'trail', 'participants', 'relate_tasks', 'relate_projects','relate_project_courses','relate_project_bills_resource', 'tasks'];

    private  $isAll = true;

    public function __construct($isAll = true)
    {
        $this->isAll = $isAll;
    }

    public function transform(Project $project)
    {
            $array = [
                'id' => hashid_encode($project->id),
                'title' => $project->title,
                'created_at' => $project->created_at->toDateTimeString(),
                'last_follow_up_at' => $project->last_follow_up_at,

            ];
        return $array;
    }

    public function includePrincipal(Project $project)
    {
        $principal = $project->principalFilter;
        if (!$principal)
            return $this->null();

        return $this->item($principal, new UserFilterTransformer());
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

            $trail = $project->trailFilter;
            if (!$trail)
                return null;
            if($trail->type == '5')
                return $this->item($trail, new TrailFilterTransformer());
            else
                return $this->item($trail, new TrailFilterTransformer(true));

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

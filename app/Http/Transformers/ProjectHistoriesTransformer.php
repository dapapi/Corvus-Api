<?php

namespace App\Http\Transformers;

use App\Models\ProjectHistorie;
use App\Models\ApprovalFlow\Change;
use Illuminate\Support\Facades\DB;
use League\Fractal\TransformerAbstract;

class ProjectHistoriesTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['principal', 'creator', 'trail', 'participants', 'relate_tasks', 'relate_projects'];

    private  $isAll = true;
    protected $defaultIncludes= ['trail'];
    public function __construct($isAll = true)
    {
        $this->isAll = $isAll;
    }

    public function transform(ProjectHistorie $project)
    {
        $count = Change::where('form_instance_number', $project->project_number)->count('form_instance_number');

        if ($this->isAll) {
            $array = [
                'id' => hashid_encode($project->id),
                'title' => $project->title,
                'type' => $project->type,
                'privacy' => $project->privacy,
                'priority' => $project->priority,
                'projected_expenditure'=>$project->projected_expenditure,
                'status' => $project->status,
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
        } else {
            $array = [
                'id' => hashid_encode($project->id),
                'title' => $project->title,
            ];
        }
        if ($count > 1)
            $array['approval_begin'] = 1;
        else
            $array['approval_begin'] = 0;

        $projectInfo = DB::table('project_histories as projects')
            ->join('approval_form_business as bu', function ($join) {
                $join->on('projects.project_number', '=', 'bu.form_instance_number');
            })
            ->join('users', function ($join) {
                $join->on('projects.creator_id', '=', 'users.id');
            })

            ->leftjoin('position', function ($join) {
                $join->on('position.id', '=', 'users.position_id');
            })

            ->join('department_user', function ($join) {
                $join->on('department_user.user_id', '=', 'users.id');
            })
            ->join('departments', function ($join) {
                $join->on('departments.id', '=', 'department_user.department_id');
            })->select('users.name', 'departments.name as department_name', 'projects.project_number', 'bu.form_status', 'projects.created_at','position.name as position_name','bu.form_id')
            ->where('projects.project_number', $project->project_number)->get()->toArray();

        $array['name'] = $projectInfo[0]->name;
        $array['department_name']= $projectInfo[0]->department_name;
        $array['form_instance_number']= $projectInfo[0]->project_number;
        $array['form_status']= $projectInfo[0]->form_status;
        $array['created_at']= $projectInfo[0]->created_at;
        $array['position_name']= $projectInfo[0]->position_name;
        $array['form_id']= $projectInfo[0]->form_id;


        return $array;
    }

    public function includePrincipal(ProjectHistorie $project)
    {
        $principal = $project->principal;
        if (!$principal)
            return null;

        return $this->item($principal, new UserTransformer());
    }

    public function includeCreator(ProjectHistorie $project)
    {
        $creator = $project->creator;
        if (!$creator)
            return null;

        return $this->item($creator, new UserTransformer());
    }

    public function includeFields(ProjectHistorie $project)
    {
        $fields = $project->fields;

        return $this->collection($fields, new FieldValueHistoriesTransformer());
    }

    public function includeTrail(ProjectHistorie $project)
    {
        $trail = $project->trail;
        if (!$trail)
            return null;
        return $this->item($trail, new TrailTransformer());
    }

    public function includeParticipants(ProjectHistorie $project)
    {
        $participants = $project->participants;

        return $this->collection($participants, new UserTransformer());
    }

    public function includeRelateTasks(ProjectHistorie $project)
    {
        $tasks = $project->relateTasks;
        return $this->collection($tasks, new TaskTransformer());
    }

    public function includeRelateProjects(ProjectHistorie $project)
    {
        $projects = $project->relateProjects;
        return $this->collection($projects, new ProjectTransformer());
    }
}

<?php

namespace App\Http\Transformers;

use App\Models\ProjectHistorie;
use App\Models\ApprovalFlow\Change;

use League\Fractal\TransformerAbstract;

class ProjectHistoriesTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['principal', 'creator', 'trail', 'participants', 'relate_tasks', 'relate_projects'];

    private  $isAll = true;

    public function __construct($isAll = true)
    {
        $this->isAll = $isAll;
    }

    public function transform(ProjectHistorie $project)
    {
        $count = Change::where('form_instance_number', $project->project_numer)->count('form_instance_number');

        if ($this->isAll) {
            $array = [
                'id' => hashid_encode($project->id),
                'title' => $project->title,
                'type' => $project->type,
                'privacy' => $project->privacy,
                'priority' => $project->priority,
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

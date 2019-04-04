<?php

namespace App\Http\Transformers\Project;

use App\Http\Transformers\TrailTransformer;
use App\Http\Transformers\User\UserSimpleTransformer;
use App\Models\ApprovalFlow\Change;
use App\Models\ApprovalForm\Business;
use App\Models\Project;
use App\ModuleableType;
use App\PrivacyType;
use Illuminate\Support\Facades\Auth;
use League\Fractal\TransformerAbstract;

class ProjectDetailTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['trail', 'participants', 'relate_tasks', 'relate_projects', 'relate_project_courses', 'relate_project_bills_resource', 'tasks'];

    private $isAll = true;
    private $user = null;

    public function __construct($isAll = true)
    {
        $this->isAll = $isAll;
    }

    public function transform(Project $project)
    {
        $this->user = Auth::guard('api')->user();

        $business = Business::where('form_instance_number', $project->project_number)->first();
        $count = Change::where('form_instance_number', $project->project_numer)->count('form_instance_number');
        if ($this->isAll) {
            $array = [
                'id' => hashid_encode($project->id),
                'form_instance_number' => $project->project_number,
                'title' => $project->title,
                'principal' => [
                    'data' => [
                        'id' => hashid_encode($project->principal_id),
                        'name' => $project->principal_name,
                        'department' => [
                            'name' => $project->department
                        ],
                    ]
                ],
                'creator' => [
                    'data' => [
                        'id' => hashid_encode($project->creator_id),
                        'name' => $project->creator_name,
                    ]
                ],
                'type' => $project->type,
                'privacy' => $project->privacy,
                'priority' => $project->priority,
                'status' => $project->status,
                'projected_expenditure' => "" . $project->projected_expenditure,
                'start_at' => $project->start_at,
                'end_at' => $project->end_at,
                'created_at' => $project->created_at->toDateTimeString(),
                'updated_at' => $project->updated_at->toDateTimeString(),
                'desc' => $project->desc,
                // 日志内容
                'last_follow_up_at' => $project->last_follow_up_at,
                'last_updated_user' => $project->last_updated_user,
                'last_updated_at' => $project->last_updated_at,
                'powers' => $project->powers

            ];


            if ($project->creator_id != $this->user->id && $project->principal_id != $this->user->id) {
                foreach ($array as $key => $value) {
                    $result = PrivacyType::isPrivacy(ModuleableType::PROJECT, $key);
                    if ($result) {
                        $result = PrivacyType::excludePrivacy($this->user->id, $project->id, ModuleableType::PROJECT, $key);
                        if (!$result) {
                            $array[$key] = 'privacy';
                        }
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

        $tasks = $project->relateTasks;
        if (!$tasks)
            $array['relate_tasks'] = [];
        else {
            $taskArr = [];
            foreach ($project->relateTasks()->select('tasks.id', 'title')->cursor() as $task) {
                $taskArr[] = [
                    'id' => hashid_encode($task->id),
                    'title' => $task->title,
                ];
            }
            $array['relate_tasks'] = $taskArr;
        }
        $projects = $project->relateProjects;
        if (!$projects)
            $array['relate_tasks'] = [];
        else {
            $projectArr = [];
            foreach ($project->relateProjects()->select('projects.id', 'title')->cursor() as $item) {
                $projectArr[] = [
                    'id' => hashid_encode($item->id),
                    'title' => $item->title,
                ];
            }
            $array['relate_projects'] = $projectArr;
        }
        return $array;
    }

    public
    function includeTrail(Project $project)
    {
        $trail = $project->trail;
        if (!$trail)
            return null;
        if ($trail->type == '5')
            return $this->item($trail, new TrailTransformer());
//            if (!$setprivacy)
//                return $this->item($trail, new TrailTransformer(true,$setprivacy));

        else
            return $this->item($trail, new TrailTransformer(true, $project, $this->user));

    }

    public
    function includeParticipants(Project $project)
    {
        $participants = $project->participants;
        if (!$participants)
            return $this->null();
        return $this->collection($participants, new UserSimpleTransformer());
    }
}
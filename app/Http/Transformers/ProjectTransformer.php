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


class ProjectTransformer extends TransformerAbstract
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
                'projected_expenditure'=> "".$project->projected_expenditure,
                'start_at' => $project->start_at,
                'end_at' => $project->end_at,
                'created_at' => $project->created_at->toDateTimeString(),
                'updated_at' => $project->updated_at->toDateTimeString(),
                'desc' => $project->desc,
                // 日志内容
                'last_follow_up_at' => $project->last_follow_up_at,
                'last_updated_user' => $project->last_updated_user,
                'last_updated_at' => $project->last_updated_at,
                'power' =>  $project->power,
                'powers'    =>  $project->powers

            ];

//            if(!empty($setprivacy1)&& count($setprivacy1) > 0 && $project ->creator_id != $user->id && $project->principal_id != $user->id){
//               if(empty($setprivacy1)){

//                   $array1['moduleable_id']= $project->id;
//                   $array1['moduleable_type']= ModuleableType::PROJECT;
//                   $array1['is_privacy']=  PrivacyType::OTHER;
//                   $setprivacy = PrivacyUser::where($array1)->groupby('moduleable_field')->get(['moduleable_field'])->toArray();
//                   foreach ($setprivacy as $key =>$v){
//                       $setprivacy1[]=array_values($v)[0];
//
//                   }
//                   $setprivacy1 =  PrivacyType::getProject();
//               }
//                foreach ($setprivacy1 as $key =>$v){
//                    $Viewprivacy2[$v]=$key;
//                };
//            $array = array_merge($array,$Viewprivacy2);

//             foreach ($array as $key1 => $val1)
//             {
//                 foreach ($Viewprivacy2 as $key2 => $val2)
//                 {
//
//                     if($key1 === $key2 ){
//
//                         $array[$key1] ='privacy';
//                        //      unset($array[$key1]);
//
//                     }
//
//
//                 }
//               }
           // }


            if($project ->creator_id != $user->id && $project->principal_id != $user->id)
            {
                  foreach ($array as $key => $value)
                  {
                      $result = PrivacyType::isPrivacy(ModuleableType::PROJECT,$key);
                      if($result)
                      {
                          $result = PrivacyType::excludePrivacy($user->id,$project->id,ModuleableType::PROJECT, $key);
                          if(!$result)
                          {
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

        $trails = DB::table('trails')
            ->join('clients', function ($join) {
                $join->on('trails.id', '=', 'clients.id');
            })
        ->where('trails.id', $project->trail_id)
            ->select('trails.id','trails.title','clients.id as clients_id','clients.company')->first();
        if($trails){
            $array['trail']['data']['id'] = hashid_encode($trails->id);
            $array['trail']['data']['title'] = $trails->title;

            $array['client']['data']['id'] = hashid_encode($trails->clients_id);
            $array['client']['data']['company'] = $trails->company;
        }else{
            $array['trail']['data']['id'] = '';
            $array['trail']['data']['title'] = '';
            $array['client']['data']['id'] = '';
            $array['client']['data']['company'] = '';
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

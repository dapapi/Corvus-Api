<?php

namespace App\Listeners;

use App\Annotation\DescAnnotation;
use App\Entity\ProjectEntity;
use App\Events\OperateLogEvent;
use App\Events\ProjectDataChangeEvent;
use App\Models\Department;
use App\Models\DepartmentUser;
use App\Models\OperateEntity;
use App\Models\Project;
use App\Models\ProjectImplode;
use App\OperateLogMethod;
use App\User;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Rafrsr\LibArray2Object\Array2ObjectBuilder;

class ProjectDataChangeListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  ProjectDataChangeEvent  $event
     * @return void
     */
    public function handle(ProjectDataChangeEvent $event)
    {
        $arrayOperateLog = [];
        $class = new DescAnnotation(ProjectEntity::class);
        $oldModel = $event->oldModel;
        $newModel = $event->newModel;
        $oldData = $oldModel->toArray();
        $newData = $newModel->toArray();
        $old_task = Array2ObjectBuilder::create()->build()->createObject(ProjectEntity::class,$oldData);
        $new_task = Array2ObjectBuilder::create()->build()->createObject(ProjectEntity::class,$newData);
        foreach ($old_task as $key => $value){

            if ($value != $new_task->$key){
                $func = "get_".$key;
                $operateStartAt = new OperateEntity([
                    'obj' => $newModel,
                    'title' => $class->$key->desc(),
                    'start' => $old_task->$func(),
                    'end' => $new_task->$func(),
                    'method' => OperateLogMethod::UPDATE,
                    'field_name' =>  $key
                ]);
                $arrayOperateLog[] = $operateStartAt;
                $this->updateProjectImplode($key, $new_task->$key, $oldModel->id);
            }
        }
        event(new OperateLogEvent($arrayOperateLog));
    }

    private function updateProjectImplode($key, $value, $id)
    {
        $arr = [];
        switch ($key) {
            case 'title':
                $arr['project_name'] = $value;
                break;
            case 'type':
                $arr['project_type'] = $value;
                break;
            case 'principal_id':
                $arr['principal_id'] = $value;
                $arr['principal'] = User::find($value)->name;
                $departmentId = DepartmentUser::where('user_id', $value)->value('department_id');
                $arr['department_id'] = $departmentId;
                $arr['department'] = Department::find($departmentId)->name;
                break;
            case 'priority':
                $arr['project_priority'] = $value;
                break;
            case 'start_at':
                $arr['project_start_at'] = $value;
                break;
            case 'end_at':
                $arr['project_end_at'] = $value;
                break;
            default:
                break;
        }
        $model = ProjectImplode::find($id);
        $model->update($arr);
        $model->save();
        return ;
    }
}

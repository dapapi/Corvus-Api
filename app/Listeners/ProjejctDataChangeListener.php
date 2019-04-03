<?php

namespace App\Listeners;

use App\Annotation\DescAnnotation;
use App\Entity\ProjectEntity;
use App\Events\OperateLogEvent;
use App\Events\ProjectDataChangeEvent;
use App\Models\OperateEntity;
use App\Models\Project;
use App\Models\ProjectImplode;
use App\OperateLogMethod;
use App\User;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Rafrsr\LibArray2Object\Array2ObjectBuilder;

class ProjejctDataChangeListener
{
    private $projectImp;
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
        $this->projectImp = ProjectImplode::find($oldModel->id);
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
                $this->updateProjectImplode($key, $value);
            }
        }
        $this->projectImp->save();
        event(new OperateLogEvent($arrayOperateLog));
    }

    private function updateProjectImplode($key, $value)
    {
        switch ($key) {
            case 'title':
                $this->projectImp->project_name = $value;
                break;
            case 'type':
                $this->projectImp->project_type = $value;
                break;
            case 'principal_id':
                $this->projectImp->principal_id = $value;
                $this->projectImp->principal = User::find($value)->name;
                break;
            case 'priority':
                $this->projectImp->project_priority = $value;
                break;
            case 'start_at':
                $this->projectImp->project_start_at = $value;
                break;
            case 'end_at':
                $this->projectImp->project_end_at = $value;
                break;
            default:
                break;
        }
        return ;
    }
}

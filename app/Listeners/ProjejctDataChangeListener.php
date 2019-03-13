<?php

namespace App\Listeners;

use App\Annotation\DescAnnotation;
use App\Entity\ProjectEntity;
use App\Events\OperateLogEvent;
use App\Events\ProjectDataChangeEvent;
use App\Models\OperateEntity;
use App\Models\Project;
use App\OperateLogMethod;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Rafrsr\LibArray2Object\Array2ObjectBuilder;

class ProjejctDataChangeListener
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
            }
        }
        event(new OperateLogEvent($arrayOperateLog));
    }
}

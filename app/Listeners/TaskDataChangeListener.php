<?php

namespace App\Listeners;

use App\Annotation\DescAnnotation;
use App\Entity\TaskEntity;
use App\Events\OperateLogEvent;
use App\Events\TaskDataChangeEvent;
use App\Models\OperateEntity;
use App\OperateLogMethod;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Rafrsr\LibArray2Object\Array2ObjectBuilder;

class TaskDataChangeListener
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
     * @param  TaskDataChangeEvent  $event
     * @return void
     */
    public function handle(TaskDataChangeEvent $event)
    {
        $arrayOperateLog = [];
        $class = new DescAnnotation(TaskEntity::class);
        $oldModel = $event->oldModel;
        $newModel = $event->newModel;
        $oldData = $oldModel->toArray();
        $newData = $newModel->toArray();
        $old_task = Array2ObjectBuilder::create()->build()->createObject(TaskEntity::class,$oldData);
        $new_task = Array2ObjectBuilder::create()->build()->createObject(TaskEntity::class,$newData);
        foreach ($old_task as $key => $value){

            if ($value != $new_task->$key){
                $func = "get_".$key;
                $operateStartAt = new OperateEntity([
                    'obj' => $newModel,
                    'title' => $class->$key->desc(),
                    'start' => $old_task->$func(),
                    'end' => $new_task->$func(),
                    'method' => OperateLogMethod::UPDATE,
                ]);
                $arrayOperateLog[] = $operateStartAt;
            }
        }
        event(new OperateLogEvent($arrayOperateLog));
    }
}

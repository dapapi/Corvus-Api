<?php

namespace App\Listeners;

use App\Annotation\DescAnnotation;
use App\Entity\AimEntity;
use App\Events\AimDataChangeEvent;
use App\Events\OperateLogEvent;
use App\Models\OperateEntity;
use App\OperateLogMethod;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Rafrsr\LibArray2Object\Array2ObjectBuilder;

class AimDataChangeListener
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
     * @param  object  $event
     * @return void
     */
    public function handle(AimDataChangeEvent $event)
    {
        $arrayOperateLog = [];
        $class = new DescAnnotation(AimEntity::class);
        $oldModel = $event->oldModel;
        $newModel = $event->newModel;
        $oldData = $oldModel->toArray();
        $newData = $newModel->toArray();
        $old_task = Array2ObjectBuilder::create()->build()->createObject(AimEntity::class,$oldData);
        $new_task = Array2ObjectBuilder::create()->build()->createObject(AimEntity::class,$newData);
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
}

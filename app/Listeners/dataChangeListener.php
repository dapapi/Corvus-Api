<?php

namespace App\Listeners;

use App\Annotation\DescAnnotation;
use App\Entity\TaskEntity;
use App\Events\dataChangeEvent;
use App\Events\OperateLogEvent;
use App\Models\OperateEntity;
use App\Models\Trail;
use App\OperateLogMethod;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class dataChangeListener
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
     * @param  dataChangeEvent  $event
     * @return void
     */
    public function handle(dataChangeEvent $event)
    {
        $arrayOperateLog = [];
        $class = new DescAnnotation(TaskEntity::class);
//        dd($class->getProperties());
        $oldModel = $event->oldModel;
        $newModel = $event->newModel;
        $oldData = $oldModel->toArray();
        $newData = $newModel->toArray();
        foreach ($oldData as $key => $value){

            if ($value != $newData[$key]){
                $operateStartAt = new OperateEntity([
                    'obj' => $newModel,
                    'title' => $class->$key->desc(),
                    'start' => $value,
                    'end' => $newData[$key],
                    'method' => OperateLogMethod::UPDATE,
                ]);
                $arrayOperateLog[] = $operateStartAt;
            }
        }
        event(new OperateLogEvent($arrayOperateLog));
    }
}

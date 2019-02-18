<?php

namespace App\Listeners;

use App\Entity\TrailEntity;
use App\Events\TrailDataChangeEvent;
use App\Models\Trail;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class TrailDataChangeListener
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
     * @param  TrailDataChangeEvent  $event
     * @return void
     */
    public function handle(TrailDataChangeEvent $event)
    {
        $arrayOperateLog = [];
        $class = new DescAnnotation(TrailEntity::class);
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

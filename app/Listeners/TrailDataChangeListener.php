<?php

namespace App\Listeners;

use App\Annotation\DescAnnotation;
use App\Entity\TrailEntity;
use App\Events\OperateLogEvent;
use App\Events\TrailDataChangeEvent;
use App\Models\OperateEntity;
use App\Models\Trail;
use App\OperateLogMethod;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Rafrsr\LibArray2Object\Array2ObjectBuilder;

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
        $old_trail = Array2ObjectBuilder::create()->build()->createObject(TrailEntity::class,$oldData);
        $new_trail = Array2ObjectBuilder::create()->build()->createObject(TrailEntity::class,$newData);
        foreach ($old_trail as $key => $value){

            if ($value != $new_trail->$key){
                $func = "get_".$key;
                $operateStartAt = new OperateEntity([
                    'obj' => $newModel,
                    'title' => $class->$key->desc(),
                    'start' => $old_trail->$func(),
                    'end' => $new_trail->$func(),
                    'method' => OperateLogMethod::UPDATE,
                ]);
                $arrayOperateLog[] = $operateStartAt;
            }
        }
        event(new OperateLogEvent($arrayOperateLog));
    }
}

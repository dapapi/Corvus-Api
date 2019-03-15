<?php

namespace App\Listeners;

use App\Annotation\DescAnnotation;
use App\Entity\StarEntity;
use App\Events\OperateLogEvent;
use App\Events\StarDataChangeEvent;
use App\Models\OperateEntity;
use App\Models\Star;
use App\OperateLogMethod;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Rafrsr\LibArray2Object\Array2ObjectBuilder;

class StarDataChangeListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {

    }

    /**
     * Handle the event.
     *
     * @param  StarDataChangeEvent  $event
     * @return void
     */
    public function handle(StarDataChangeEvent $event)
    {
        $arrayOperateLog = [];
        $class = new DescAnnotation(StarEntity::class);
        $oldModel = $event->oldModel;
        $newModel = $event->newModel;
        $oldData = $oldModel->toArray();
        $newData = $newModel->toArray();
        $old_star = Array2ObjectBuilder::create()->build()->createObject(StarEntity::class,$oldData);
        $new_star = Array2ObjectBuilder::create()->build()->createObject(StarEntity::class,$newData);
        foreach ($old_star as $key => $value){

            if ($value != $new_star->$key){
                $func = "get_".$key;
                $operateStartAt = new OperateEntity([
                    'obj' => $newModel,
                    'title' => $class->$key->desc(),
                    'start' => $old_star->$func(),
                    'end' => $new_star->$func(),
                    'method' => OperateLogMethod::UPDATE,
                    'field_name' =>  $key
                ]);
                $arrayOperateLog[] = $operateStartAt;
            }
        }
        event(new OperateLogEvent($arrayOperateLog));
    }
}

<?php

namespace App\Listeners;

use App\Annotation\DescAnnotation;
use App\Entity\BloggerEntity;
use App\Entity\StarEntity;
use App\Events\BloggerDataChangeEvent;
use App\Events\OperateLogEvent;
use App\Models\OperateEntity;
use App\OperateLogMethod;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Rafrsr\LibArray2Object\Array2ObjectBuilder;

class BloggerDataChangeListener
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
     * @param  BloggerDataChangeEvent  $event
     * @return void
     */
    public function handle(BloggerDataChangeEvent $event)
    {
        $arrayOperateLog = [];
        $class = new DescAnnotation(BloggerEntity::class);
        $oldModel = $event->oldModel;
        $newModel = $event->newModel;
        $oldData = $oldModel->toArray();
        $newData = $newModel->toArray();
        $old_blogger = Array2ObjectBuilder::create()->build()->createObject(BloggerEntity::class,$oldData);
        $new_blogger = Array2ObjectBuilder::create()->build()->createObject(BloggerEntity::class,$newData);
        foreach ($old_blogger as $key => $value){

            if ($value != $new_blogger->$key){
                $func = "get_".$key;
                $operateStartAt = new OperateEntity([
                    'obj' => $newModel,
                    'title' => $class->$key->desc(),
                    'start' => $old_blogger->$func(),
                    'end' => $new_blogger->$func(),
                    'method' => OperateLogMethod::UPDATE,
                    'field_name' =>  $key
                ]);
                $arrayOperateLog[] = $operateStartAt;
            }
        }
        event(new OperateLogEvent($arrayOperateLog));
    }
}

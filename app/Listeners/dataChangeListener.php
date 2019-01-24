<?php

namespace App\Listeners;

use App\Annotation\DescAnnotation;
use App\Entity\TaskEntity;
use App\Events\dataChangeEvent;
use App\Models\Trail;
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
        $class = new DescAnnotation(TaskEntity::class);
//        dd($class->getProperties());
        $oldModel = $event->oldModel;
        $newModel = $event->newModel;
        $oldData = $oldModel->toArray();
        $newData = $newModel->toArray();
        foreach ($oldData as $key => $value){
            dump($key);
            dump($class->$key->desc());
            if ($value != $newData[$key]){

            }
        }
    }
}

<?php

namespace App\Listeners;

use App\Annotation\DescAnnotation;
use App\Entity\ClientEntity;
use App\Events\ClientDataChangeEvent;
use App\Events\OperateLogEvent;
use App\Models\OperateEntity;
use App\Models\ProjectImplode;
use App\OperateLogMethod;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Rafrsr\LibArray2Object\Array2Object;
use Rafrsr\LibArray2Object\Array2ObjectBuilder;

class ClientDataChangeListener
{
    private $projectImp = null;

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
     * @param  ClientDataChangeEvent $event
     * @return void
     */
    public function handle(ClientDataChangeEvent $event)
    {
        $arrayOperateLog = [];
        $class = new DescAnnotation(ClientEntity::class);
        $oldModel = $event->oldModel;
        $newModel = $event->newModel;
        if ($oldModel->trail && $oldModel->trail->project)
            $this->projectImp = ProjectImplode::find($oldModel->id);
        $oldData = $oldModel->toArray();
        $newData = $newModel->toArray();
        $old_client = Array2ObjectBuilder::create()->build()->createObject(ClientEntity::class, $oldData);
        $new_client = Array2ObjectBuilder::create()->build()->createObject(ClientEntity::class, $newData);
        foreach ($old_client as $key => $value) {
            if ($value != $new_client->$key) {
                $fun = "get_" . $key;
                $operateStartAt = new OperateEntity([
                    'obj' => $newModel,
                    'title' => $class->$key->desc(),
                    'start' => $old_client->$fun(),
                    'end' => $new_client->$fun(),
                    'method' => OperateLogMethod::UPDATE,
                    'field_name' => $key
                ]);
                $arrayOperateLog[] = $operateStartAt;
                $this->updateProjectImplode($key, $value);
            }
        }
        if ($this->projectImp)
            $this->projectImp->save();
        event(new OperateLogEvent($arrayOperateLog));
    }

    private function updateProjectImplode($key, $value)
    {
        if ($this->projectImp)
            switch ($key) {
                case 'company':
                    $this->projectImp->client = $value;
                    break;
                default:
                    break;
            }
        return;
    }
}

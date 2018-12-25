<?php

namespace App\Repositories;

use App\Models\Module;
use App\Models\ProjectResource;
use App\Models\Schedule;
use App\ModuleableType;
use Exception;

class ProjectResourceRepository
{
    public function addProjectResource(array $add, array $del, $model)
    {
        $add = $this->arrayHashDecode($add);
        $del = $this->arrayHashDecode($del);

        if ($model instanceof Schedule) {
            $moduleId = $this->getModuleId($model, 'schedules');
            $moduleType = ModuleableType::SCHEDULE;
        } else {
            $moduleId = null;
            $moduleType = null;
        }

        try {
            foreach ($del as $id) {
                ProjectResource::find($id)->delete;
            }
            foreach ($add as $id) {
                ProjectResource::create([
                    'project_id' => $id,
                    'resourceable_id' => $model->id,
                    'resourceable_type' => $moduleType,
                    'resource_id' => $moduleId,
                ]);
            }

        } catch (Exception $exception) {
            throw $exception;
        }

    }

    public function arrayHashDecode($array) : array
    {
        foreach ($array as &$id) {
            $id = hashid_decode($id);
        }
        unset($id);

        return $array;
    }

    public function getModuleId($model, $str)
    {
        return Module::where('code', $str)->first()->id;
    }
}

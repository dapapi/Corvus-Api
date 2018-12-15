<?php

namespace App\Repositories;

use App\Models\Affix;
use App\Models\Blogger;
use App\Models\Repository;
use App\Models\Report;
use App\Models\Announcement;
use App\Models\Client;
use App\Models\Project;
use App\Models\Star;
use App\Models\Task;
use App\Models\Issues;
use App\Models\Trail;
use App\Models\Type;
use App\User;

class CommentLogRepository
{
    public function getObject($model)
    {
        $obj = null;
        if ($model instanceof Task && $model->id) {
            $obj = $model;
        } else if ($model instanceof Project && $model->id) {
            $obj = $model;
        } else if ($model instanceof Star && $model->id) {
            $obj = $model;
        } else if ($model instanceof Client && $model->id) {
            $obj = $model;
        } else if ($model instanceof Trail && $model->id) {
            $obj = $model;
        } else if ($model instanceof Blogger && $model->id) {
            $obj = $model;
        }else if ($model instanceof Report && $model->id) {
            $obj = $model;
        }else if ($model instanceof Issues && $model->id) {
            $obj = $model;
        }else if ($model instanceof Announcement && $model->id) {
            $obj = $model;
        }else if ($model instanceof Repository && $model->id) {
            $obj = $model;
        }

        //TODO class type
        return $obj;
    }

}

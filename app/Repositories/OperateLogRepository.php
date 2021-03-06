<?php

namespace App\Repositories;

use App\Models\Affix;
use App\Models\Aim;
use App\Models\ApprovalForm\Business;
use App\Models\ApprovalForm\Instance;
use App\Models\Blogger;
use App\Models\Contract;
use App\Models\Repository;
use App\Models\Report;
use App\Models\Calendar;
use App\Models\Schedule;
use App\Models\Announcement;
use App\Models\Client;
use App\Models\Project;
use App\Models\Star;
use App\Models\Supplier;
use App\Models\Task;
use App\Models\Issues;
use App\Models\Trail;
use App\Models\Type;
use App\Models\CommentLog;
use App\User;

class OperateLogRepository
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
        }else if ($model instanceof Schedule && $model->id) {
            $obj = $model;
        } else if ($model instanceof Calendar && $model->id) {
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
        }else if ($model instanceof Contract && $model->id){
            $obj = $model;
        }else if ($model instanceof Instance && $model->form_instance_id){
            $obj = $model;
        }else if ($model instanceof Business && $model->id){
            $obj = $model;
        }else if ($model instanceof Supplier && $model->id){
            $obj = $model;
        }else if ($model instanceof Aim && $model->id){
            $obj = $model;
        }

        //TODO class type
        return $obj;
    }

}

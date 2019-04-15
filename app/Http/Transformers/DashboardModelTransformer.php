<?php

namespace App\Http\Transformers;

use App\Models\Blogger;
use App\Models\Client;
use App\Models\Project;
use App\Models\Star;
use App\Models\Task;
use Illuminate\Database\Eloquent\Model;
use League\Fractal\TransformerAbstract;

class DashboardModelTransformer extends TransformerAbstract
{
    public function transform(Model $model)
    {
        $arr = [
            'id' => hashid_encode($model->id),
            'name' => $model->title,
            'created_at' => $model->t,
        ];

        $avatar = null;
        if ($model instanceof Project)
            $avatar = Project::find($model->id)->principal->icon_url;
        elseif ($model instanceof Task)
            $avatar = Task::find($model->id)->principal->icon_url;
        elseif ($model instanceof Client)
            $avatar = Client::find($model->id)->principal->icon_url;
        elseif ($model instanceof Star)
            $avatar = Star::find($model->id)->broker()->first()->icon_url;
        elseif ($model instanceof Blogger)
            $avatar = Blogger::find($model->id)->publicity()->first()->icon_url;

        $arr['avatar'] = $avatar;

        return $arr;
    }
}
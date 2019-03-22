<?php

namespace App\Http\Transformers;

use App\Models\Project;
use Illuminate\Database\Eloquent\Model;
use League\Fractal\TransformerAbstract;

class DashboardModelTransformer extends TransformerAbstract
{
    public function transform(Model $model)
    {
        return [
            'id' => hashid_encode($model->id),
            'name' => $model->title,
            'created_at' => $model->t,
        ];
    }
}
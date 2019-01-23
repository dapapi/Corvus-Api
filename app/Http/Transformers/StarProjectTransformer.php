<?php

namespace App\Http\Transformers;

use App\Models\Project;
use League\Fractal\TransformerAbstract;

class StarProjectTransformer extends TransformerAbstract
{
    public function transform(Project $project)
    {
        return [
            "id"    =>  $project->id,
            "title" =>  $project->title,
            "principal"    =>   $this->getPrincipalName($project),
            "company"  => $this->getCompanyName($project),
        ];
    }
    private function getPrincipalName($project)
    {
        $result = $project->principal()->select('name')->first();
        return $result == null ? null : $result->name;
    }
    private function getCompanyName($project)
    {
        $result = $project->leftJoin("trails as t",function ($join){
            $join->on("project.trail_id","t,id");
        })->leftJoin("clients as c",function ($join){
            $join->on("c.id","t.client_id");
        })->select('company')->where('project.id',$project->id)
            ->first();
        return $result == null ? null : $result->company;
    }
}
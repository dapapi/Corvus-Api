<?php

namespace App\Http\Transformers;

use App\Models\Department;
use App\Models\DepartmentUser;
use App\Models\Trail;
use App\Models\TrailStar;
use App\ModuleableType;
use App\PrivacyType;
use App\User;
use League\Fractal\ParamBag;
use Illuminate\Support\Facades\Auth;
use League\Fractal\TransformerAbstract;
use Illuminate\Support\Facades\DB;


class TrailFilterTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['principal', 'client', 'stars', 'contact', 'recommendations',
        'expectations', 'project','starexpectations','bloggerexpectations','starrecommendations','bloggerrecommendations','lockuser'];
    private $isAll = true;

    private $project;


    public function __construct($isAll = true)
    {
        $this->isAll = $isAll;


    }


    public function transform(Trail $trail)
    {
        if ($this->isAll) {
            $array = [
                'id' => hashid_encode($trail->id),
                'fee' => "".$trail->fee,

            ];
        } else {
            $array = [
                'id' => hashid_encode($trail->id),
                'title' => $trail->title,
                'brand' => $trail->brand,
            ];
        }
        return $array;


    }



    public function includeExpectations(Trail $trail)
    {
        $expectations = $trail->bloggerExpectations;
        if (count($expectations) <= 0) {
            $expectations = $trail->expectations;
            return $this->collection($expectations, new StarFilterTransformer());
        } else {
            return $this->collection($expectations, new BloggerFilterTransformer());
        }
    }





}
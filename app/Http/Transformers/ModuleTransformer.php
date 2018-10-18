<?php

namespace App\Http\Transformers;

use App\Models\Module;
use League\Fractal\ParamBag;
use League\Fractal\TransformerAbstract;

class ModuleTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['actions'];
    private $validParams = ['type'];

    public function transform(Module $module)
    {
        $array = [
            'id' =>  hashid_encode($module->id),
            'name' => $module->name,
            'icon' => $module->icon,
            'code' => $module->code,
        ];

        return $array;
    }

    public function includeActions(Module $module, ParamBag $params = null)
    {
        if ($params === null)
            return $this->collection($module->actions, new ActionTransformer());

        $usedParams = array_keys(iterator_to_array($params));
        if ($invalidParams = array_diff($usedParams, $this->validParams)) {
            throw new \Exception(sprintf(
                'Invalid param(s): "%s". Valid param(s): "%s"',
                implode(',', $usedParams),
                implode(',', $this->validParams)
            ));
        }

        $types = $params->get('type');

        $typeArr = explode('|', $types);

        $actions = $module->actions()->whereIn('type', $typeArr)->get();

        return $this->collection($actions, new ActionTransformer());
    }
}
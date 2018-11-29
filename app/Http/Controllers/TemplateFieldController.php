<?php

namespace App\Http\Controllers;

use App\Http\Requests\TemplateField\GetTemplateFieldRequest;
use App\Http\Transformers\TemplateFieldTransformer;
use App\Models\Project;
use App\Models\TemplateField;
use Illuminate\Http\Request;

class TemplateFieldController extends Controller
{
    public function getFields(GetTemplateFieldRequest $request)
    {
        $type = $request->get('type');
        // 用来区分创建还是展示
        $status = $request->get('status', 1);
        switch ($type) {
            case Project::TYPE_MOVIE :
                $fieldsRes = TemplateField::where('module_type', Project::TYPE_MOVIE);
                break;
            case Project::TYPE_VARIETY :
                $fieldsRes = TemplateField::where('module_type', Project::TYPE_VARIETY);
                break;
            case Project::TYPE_ENDORSEMENT :
                $fieldsRes = TemplateField::where('module_type', Project::TYPE_ENDORSEMENT);
                break;
            default:
                $fieldsRes = null;
                break;
        }

        if ($fieldsRes) {
            if ($status == 1) {
                $fields = $fieldsRes->where('status', 1)->get();
            } else {
                $fields = $fieldsRes->get();
            }
            return $this->response->collection($fields, new TemplateFieldTransformer());
        } else {
            return $this->response->noContent();
        }
    }
}

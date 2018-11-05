<?php

namespace App\Http\Controllers;

use App\Http\Requests\TemplateField\GetTemplateFieldRequest;
use App\Models\Project;
use App\Models\TemplateField;
use Illuminate\Http\Request;

class TemplateFieldController extends Controller
{
    public function getFields(GetTemplateFieldRequest $request)
    {
        $type = $request->get('type');
        switch ($type) {
            case Project::TYPE_MOVIE :
                $fieldsRes = TemplateField::where('module_type', Project::TYPE_MOVIE);
                break;
            case Project::TYPE_VARIETY:
                $fieldsRes = TemplateField::where('module_type', Project::TYPE_VARIETY);
                break;
            case Project::TYPE_ENDORSEMENT :
                $fieldsRes = TemplateField::where('module_type', Project::TYPE_MOVIE);
                break;
            default:
                $fieldsRes = null;
                break;
        }

        if ($fieldsRes)
            $fields = $fieldsRes->get();
    }
}

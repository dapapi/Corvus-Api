<?php

namespace App\Http\Controllers;

use App\Http\Transformers\IndustryTransformer;
use App\Models\Industry;
use Illuminate\Http\Request;

class IndustryController extends Controller
{

    public function all(Request $request)
    {
        $industries = Industry::all();

        return $this->response->collection($industries, new IndustryTransformer());
    }
}
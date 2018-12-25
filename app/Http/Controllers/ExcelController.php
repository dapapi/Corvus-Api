<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ExcelController extends Controller
{
    public function download(Request $request)
    {
        $file = $request->get('filename', null);

        if ($file)
            return response()->download(storage_path() . '/app/exports/' . $file);
        return $this->response->errorBadRequest('文件不存在');
    }
}

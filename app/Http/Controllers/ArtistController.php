<?php

namespace App\Http\Controllers;

use App\Http\Transformers\ArtistTransformer;
use App\Models\Artist;
use Illuminate\Http\Request;

class ArtistController extends Controller
{
    public function index(Request $request)
    {
        if ($request->has('page_size')) {
            $pageSize = $request->get('page_size');
        } else {
            $pageSize = config('page_size');
        }

        $artists = Artist::orderBy('name')->paginate($pageSize);

        return $this->response->paginator($artists, new ArtistTransformer());
    }
}

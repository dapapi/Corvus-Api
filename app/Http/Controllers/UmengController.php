<?php

namespace App\Http\Controllers;

use App\Repositories\UmengRepository;
use Illuminate\Http\Request;

class UmengController extends Controller
{
    protected $umeng;
    public function __construct(UmengRepository $umeng)
    {
        $this->umeng = $umeng;
    }
    public function sendMsg()
    {
        $this->umeng->sendMsgToAndriodTest();
    }
}

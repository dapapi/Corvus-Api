<?php

namespace App\Http\Controllers;

use App\Repositories\UmengRepository;

class UmengController extends Controller
{
    protected $umengRepository;
    public function __construct(UmengRepository $umengRepository)
    {
        $this->umengRepository = $umengRepository;
    }
    public function sendMsg()
    {
        $this->umengRepository->sendMsgToAndriodTest();
    }
}

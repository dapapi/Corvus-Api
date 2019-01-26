<?php

namespace App\Repositories;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Support\Facades\Log;

class HttpRepository
{

    private $client;
    private $jar;

    public function __construct()
    {
        $this->getClient();
    }
    public function getClient()
    {
        $this->client = new Client();
    }
    public function request($method,$uri,$header,$params)
    {
        try{
            $this->jar = new CookieJar();
            $this->jar = $this->client->request($method,$uri,[
                'cookies'    =>  $this->jar,
                'verify'=> false,
                "headers"    =>  $header,
                "form_params"   =>  $params
            ]);
            return $this->jar->getStatusCode() == 200 ? true : false;
        }catch (\Exception $e){
            Log::error($e);
            return false;
        }
    }


}

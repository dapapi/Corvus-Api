<?php
/**
 * Created by PhpStorm.
 * User: apple
 * Date: 2019-02-18
 * Time: 12:22
 */

namespace Tests\Unit;


class PassportCreateTokenTest
{
    public function personalAccessClient(){
        $client = Passport::personalAccessClient();
        dd($client);
    }
}
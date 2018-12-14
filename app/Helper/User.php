<?php
/**
 * Created by PhpStorm.
 * User: apple
 * Date: 2018/12/12
 * Time: 4:32 PM
 */

namespace App\Helper;


class User
{
    public $userId;
    public $userName;
    public function __toString()
    {
        return json_encode($this);
    }
}
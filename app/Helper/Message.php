<?php
/**
 * Created by PhpStorm.
 * User: apple
 * Date: 2018/12/12
 * Time: 4:32 PM
 */

namespace App\Helper;


class Message
{
    public $from;
    public $to;
    public $action;
    public $link;
    public $message;
    public function __toString()
    {
        return json_encode($this);
    }
}
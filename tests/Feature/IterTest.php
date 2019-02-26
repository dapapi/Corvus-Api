<?php
/**
 * Created by PhpStorm.
 * User: apple
 * Date: 2019-02-25
 * Time: 13:27
 */

class person
{
    public $a;
    public $b;
    public $c;
}
$p = new person();
$p->a=120;
$p->b=140;
$p->c=160;

foreach ($p as $key => $v){
    echo $key.":".$v."\n";
}
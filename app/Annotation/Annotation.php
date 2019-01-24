<?php
namespace App\Annotation;
use App\Entity\EntityInterface;

/**
 * Created by PhpStorm.
 * User: apple
 * Date: 2019-01-23
 * Time: 21:52
 */

interface Annotation
{
    public function parse($docComment);
}
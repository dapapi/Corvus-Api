<?php
/**
 * Created by PhpStorm.
 * User: apple
 * Date: 2019-01-23
 * Time: 22:03
 */

namespace App\Annotation;


class DescAnnotation implements Annotation
{
    private $_class;
    private $proprtites = [];
    private $property;
    public function __construct($class)
    {
        $this->_class = new \ReflectionClass($class);
        foreach ($this->_class->getProperties() as $property){
            $this->proprtites[$property->getName()] = [
                    "property"  =>  $property->getName(),
                    "docComment"    =>  $this->parse($property->getDocComment())
            ];
        }
    }
    public function getProperties()
    {
        return $this->proprtites;
    }
    public function parse($docComment)
    {
        preg_match('/@desc.*\n/',$docComment,$a);
        return $a[0];
    }

//    private function getDesc()
//    {
//
//    }
    private function getPropertity($name)
    {
        $key = array_key_exists($name,$this->proprtites);
        return $this->proprtites[$key][$name]["property"];
    }
    private function getDesc($name)
    {
        $key = array_key_exists($name,$this->proprtites);
        dump($key,$name);
        $desc = $this->proprtites[$key][$name]["docComment"];
        $desc = trim($desc,"@desc");
        $desc = trim($desc);
        $this->property = null;
        return $desc;
    }
    public function __call($name, $arguments)
    {
//        return $this->getPropertity($name);
        if ($name == "desc"){
            return $this->getDesc($this->property);
        }

    }
    public function __get($name)
    {
        $this->property = $name;
        return $this;
    }
    public function __set($name, $value)
    {

    }

}
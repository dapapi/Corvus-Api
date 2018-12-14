<?php

namespace Tests\Feature;


use PHPUnit\DbUnit\TestCaseTrait;
//use Tests\TestCase;
use PHPUnit\Framework\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\MyApp_DbUnit_ArrayDataSet;
//include '../data/client.php';
//include '../data/contact.php.php';
//include '../data/deparment.php';
//include '../data/User.php';
//include '../data/deparment_user.php.php';
class UserTest extends TestCase
{
    use TestCaseTrait;

    static private $pdo = null;
    private $conn = null;
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testExample()
    {
        $this->assertTrue(true);
    }
    public function getConnection()
    {
        if($this->conn === null){
            if(self::$pdo == null){
                self::$pdo = new \PDO($GLOBALS['DB_DSN'],$GLOBALS['DB_USER'],$GLOBALS['DB_PASSWD']);
            }
            $this->conn = $this->createDefaultDBConnection(self::$pdo,$GLOBALS['DB_DBNAME']);
        }
        return $this->conn;
    }
    public function getDataSet()
    {
        $user = include __DIR__.'/../data/User.php';
//        return $this->createXMLDataSet('user.xml');
        return new MyApp_DbUnit_ArrayDataSet($user);
    }
}

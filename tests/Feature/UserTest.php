<?php

namespace Tests\Feature;


use PHPUnit\DbUnit\TestCaseTrait;
//use Tests\TestCase;
use PHPUnit\Framework\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

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
        return $this->createXMLDataSet('user.xml');
    }
}

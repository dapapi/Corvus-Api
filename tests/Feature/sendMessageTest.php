<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class sendMessageTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testExample()
    {
        $payload = [
                "title"=> "影视项目测试",
                "type"=> 1,
                "principal_id"=> 1994731356,
                "priority"=> 3,
                "desc"=> "项目描述",
                "fields"=> [
                        "1994731356"=> "成熟艺人",
                    "774465993"=>  7.9,
                    "1718463094"=> "2018-11-23",
                    "531752163"=> "梵石ITwon",
                    "1475617040"=> "3个月",
                    "255353757"=> "横店",
                    "1199356938"=> "电影",
                    "1812690257"=> "王尧"
                ],
                "trail"=> [
                        "id"=> 1994731356
                ]
            ];
        $this->json('post','https://api.corvus.dev/projects',$payload)
            ->assertStatus('200')
            ->assertJson(['ddd'=>'ddd']);
    }
}

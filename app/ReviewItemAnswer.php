<?php

namespace App;


class ReviewItemAnswer {



    public static function getUsers()
    {           $users = array();
                $users[] = ['user_id' => 116];
                $users[] = ['user_id' => 124];
                $users[] = ['user_id' => 125];
                $users[] = ['user_id' => 126];
                $users[] = ['user_id' => 127];
                $users[] = ['user_id' => 128];
                $users[] = ['user_id' => 129];
                $users[] = ['user_id' => 130];
                $users[] = ['user_id' => 132];
                $users[] = ['user_id' => 135];
                $users[] = ['user_id' => 136];
                $users[] = ['user_id' => 137];
                $users[] = ['user_id' => 138];
                $users[] = ['user_id' => 139];
                $users[] = ['user_id' => 140];
                $users[] = ['user_id' => 141];
                $users[] = ['user_id' => 142];
                $users[] = ['user_id' => 143];
                $users[] = ['user_id' => 144];
                $users[] = ['user_id' => 145];
                $users[] = ['user_id' => 146];
                $users[] = ['user_id' => 147];
                $users[] = ['user_id' => 148];
                $users[] = ['user_id' => 149];
                $users[] = ['user_id' => 150];
        return $users;
    }
    public static function getIssue()
    {           $issues = array();
        $issues[] = ['titles' => '创意（包括原创性，独特性）'];
        $issues[] = ['titles' => '传播（有没有共鸣、转发点）'];
        $issues[] = ['titles' => '吸引力及完播度（测评类视频可重点考量完播度)'];
        $issues[] = ['titles' => '制作（是否制作精良，是否看得下去）'];
        $issues[] = ['titles' => '立意（是否有价值）'];

                return $issues;
    }
    public static function getAnswer()
    {           $issues = array();
        $issues[0][] = ['answer' => '选题和内容都太棒了，完全没想到还能从这个角度做视频','value'=>'30'];
        $issues[0][] = ['answer' => '选题和内容新颖独特，很棒的原创视频','value'=>'25'];
        $issues[0][] = ['answer' => '选题和内容比较新颖，让人眼前一亮','value'=>'20'];
        $issues[0][] = ['answer' => '选题比较普通，内容的创意也感觉一般','value'=>'15'];
        $issues[0][] = ['answer' => '选题和内容感觉很多人都做过了，自己的创意比较少','value'=>'10'];
        $issues[0][] = ['answer' => '选题和内容与别人视频有比较大的雷同，有抄袭嫌疑','value'=>'5'];
        $issues[0][] = ['answer' => '选题和内容完全抄袭别人视频，毫无创意','value'=>'0'];
        $issues[1][] = ['answer' => '完全被戳到了，看完肯定会转发','value'=>'25'];
        $issues[1][] = ['answer' => '很有共鸣，看完很想转发','value'=>'20'];
        $issues[1][] = ['answer' => '比较有共鸣，看完会转发','value'=>'15'];
        $issues[1][] = ['answer' => '有一些共鸣，可能会转发','value'=>'10'];
        $issues[1][] = ['answer' => '其中有几处觉得还是有共鸣的，但是不想转发','value'=>'5'];
        $issues[1][] = ['answer' => '完全没被戳到，一点也不想转发','value'=>'0'];
        $issues[2][] = ['answer' => '非常有吸引力/不需要拖跩，想一直看到最后','value'=>'15'];
        $issues[2][] = ['answer' => '是有吸引力的/凑合可以看到最后','value'=>'10'];
        $issues[2][] = ['answer' => '只有几处还有点意思/需要拖拽，或者中途会退出','value'=>'5'];
        $issues[2][] = ['answer' => '很无聊/完全看不下去','value'=>'0'];
        $issues[3][] = ['answer' => '画面很精致，特效恰到好处，节奏感很强','value'=>'20'];
        $issues[3][] = ['answer' => '画面清晰稳定，特效与视频内容相契合，有节奏感','value'=>'15'];
        $issues[3][] = ['answer' => '画面和特效中规中矩，节奏感一般','value'=>'10'];
        $issues[3][] = ['answer' => '画面模糊或晃动，特效比较杂乱，欠缺节奏感','value'=>'5'];
        $issues[3][] = ['answer' => '画面粗糙，特效凌乱，毫无节奏感','value'=>'0'];
        $issues[4][] = ['answer' => '学习到了很有用的干货，或者表达了很有意义的观点','value'=>'10'];
        $issues[4][] = ['answer' => '学习到了一些干货，或者表达了一些还不错的观点','value'=>'5'];
        $issues[4][] = ['answer' => '没学到什么有用的东西，或者表达的观点感觉很一般','value'=>'0'];

        return $issues;
    }

}

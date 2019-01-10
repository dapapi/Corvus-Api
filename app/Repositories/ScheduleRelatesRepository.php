<?php

namespace App\Repositories;


use App\Models\ScheduleRelate;
use Exception;
use Illuminate\Support\Facades\Auth;


class ScheduleRelatesRepository
{

    public function addScheduleRelate($p_ids, $model,$type)
    {


        $user = Auth::guard('api')->user();
        $array = [
            'user_id' =>  $user->id,
            'schedule_id' =>$model->id,
            'moduleable_type' => $type
        ];

        $participantDeleteIds = ScheduleRelate::where('moduleable_type', $array['moduleable_type'])->where('schedule_id',  $array['schedule_id'])->where('user_id', $array['user_id'])->get(['id'])->toArray();

        foreach ($participantDeleteIds as $key => &$participantDeleteId) {
            try {
                 $moduleUser = ScheduleRelate::where($participantDeleteId)->first();
                if ($moduleUser) {//数据存在则从数据库中删除
                    $moduleUser->delete();
                } else {//不存在则将ID从要删除的参与人或者宣传人列表中删除
                    array_splice($participantDeleteIds, $key, 1);
                }
            } catch (Exception $e) {
                array_splice($participantDeleteIds, $key, 1);
            }
        }

        $p_ids = array_unique($p_ids);//去除参与人或者宣传人列表的重复值

        foreach ($p_ids as $key => &$p_id) {
            try {
                $p_id = hashid_decode($p_id);
                $array['moduleable_id'] = $p_id;
                $moduleUser = ScheduleRelate::where('moduleable_type', $array['moduleable_type'])->where('moduleable_id',$array['moduleable_id'])->where('user_id', $array['user_id'])->where('schedule_id',  $array['schedule_id'])->first();

                if (!$moduleUser) {//不存在则添加
                    ScheduleRelate::create($array);

                } else {//存在则从列表中删除
                    array_splice($p_ids, $key, 1);
//                    $participantDeleteIds[] = $participantId;
//                    //要求一个接口可以完成添加人和删除人,已经存在的删除
//                    $moduleUser->delete();
                }
            } catch (Exception $e) {
                array_splice($p_ids, $key, 1);
            }
        }
        //返回添加成功或者删除成功的参与人和宣传人
        return [$p_ids, $participantDeleteIds];
    }

}

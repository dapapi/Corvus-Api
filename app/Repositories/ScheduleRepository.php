<?php

namespace App\Repositories;

use App\Models\Calendar;
use App\Models\Schedule;
use Carbon\Carbon;
use function GuzzleHttp\Psr7\copy_to_string;
use Illuminate\Support\Facades\DB;

class ScheduleRepository
{
    /**
     * 查找日程  明星，艺人
     * @param $starable_type
     * @param $starable_id
     * @param $moth
     */
    public static function selectCalendar($starable_type,$starable_id,$date)
    {
        $time = Carbon::parse($date);

        $start_time = $time->copy()->firstOfMonth();
        $end_time = $time->copy()->lastOfMonth()->endOfDay();
//        DB::connection()->enableQueryLog();
        $result = (new Schedule())->setTable("s")->from("schedules as s")
            ->leftJoin('calendars as c','s.calendar_id','=','c.id')
            ->where('c.starable_id',$starable_id)
            ->where('c.starable_type',$starable_type)
            ->where('s.start_at','<',$end_time)
            ->where('s.end_at','>',$start_time)
            ->get();
//        $sql = DB::getQueryLog();
        foreach ($result as $value){
            $start_at = Carbon::parse($value['start_at']);
            $end_at = Carbon::parse($value['end_at']);
            if($start_time->timestamp > $start_at->timestamp){
                $start_at = $start_time;
            }
            if($end_time->timestamp < $end_at->timestamp){
                $end_at = $end_time;
            }
            $start_day = $start_at->dayOfYear;
            $end_day = $end_at->dayOfYear;
            $schedule_list = [];
            for($day = $start_day;$day <= $end_day;$day++){
                $curr_date = $start_time->addDay($start_day-$day);
                $schedule_list[$curr_date->toDateString()] = $value;
            }
        }
        return $schedule_list;
    }
}

<?php

namespace App\Http\Transformers;

use App\Models\Blogger;
use App\TaskStatus;
use DemeterChain\B;
use Illuminate\Support\Facades\DB;
use League\Fractal\TransformerAbstract;

class BloggerDetailTransformer extends TransformerAbstract
{
    public function transform(Blogger $blogger)
    {
        return [
            'id' => hashid_encode($blogger->id),
            'nickname' => $blogger->nickname,
            'platform_id' => $blogger->platform_id,    //平台
            'avatar' => $blogger->avatar,
            'communication_status' => $blogger->communication_status,//沟通状态
            'intention' => boolval($blogger->intention),//与我司签约意向
            'intention_desc' => $blogger->intention_desc,//不与我司签约原因
            'sign_contract_at' => $blogger->sign_contract_at,//签约日期
            'level' => $blogger->level,//博主级别
            'hatch_star_at' => $blogger->hatch_star_at,//孵化期开始时间
            'hatch_end_at' => $blogger->hatch_end_at,//孵化期结束时间
            'sign_contract_status' => $blogger->sign_contract_status,//签约状态
            'icon' => $blogger->icon, // 头像
            'desc' => $blogger->desc,//描述/备注
            'gender' => $blogger->gender,
            'cooperation_demand' => $blogger->cooperation_demand,//合作需求
            'terminate_agreement_at' => $blogger->terminate_agreement_at,//解约日期
            'sign_contract_other' => $blogger->sign_contract_other,//是否签约其他公司
            'sign_contract_other_name' => $blogger->sign_contract_other_name,//签约公司名称
            'status' => $blogger->status,
            'platform'=> $blogger->platform,
            'douyin_id' => $blogger->douyin_id,//微博url
            'douyin_fans_num' => $blogger->douyin_fans_num,//微博粉丝数
            'weibo_url'=> $blogger->weibo_url,//微博url
            'weibo_fans_num'=> $blogger->weibo_fans_num,//微博粉丝数
            'xiaohongshu_url'=> $blogger->xiaohongshu_url,//微博url
            'xiaohongshu_fans_num'=> $blogger->xiaohongshu_fans_num,//微博粉丝数
            'created_at'=> $blogger->created_at->toDateTimeString(),
            'last_follow_up_at' => $blogger->last_follow_up_at,
            'last_updated_user' => $blogger->last_updated_user,
            'last_updated_at' => $blogger->last_updated_at,
            'updated_at' => $blogger->updated_at->toDateTimeString(),
            'power' =>  $blogger->power,//对博主是否有编辑权限
            'powers' => $blogger->powers,
            'tasks' => $this->getTasks($blogger),
            'affixes'   =>  $this->includeAffixes($blogger),
            'creator'   =>  $this->getCreator($blogger),
            'broker'    =>  $this->getBroker($blogger),
            'publicity' =>  $this->getPublicity($blogger),
        ];

    }
    public function getCreator(Blogger $blogger)
    {
        $user = $blogger->creator()->select('id','name')->first();

        $user->department = $user->department()->value('name');
        return $user;
//        return $this->item($user,new UserTransformer());
    }
    public function includeAffixes(Blogger $blogger)
    {
        $affixes = $blogger->affixes()->createDesc()->get();
        return ['data'=>$affixes];
    }
    public function getPublicity(Blogger $blogger){
        $users = $blogger->publicity()->select('users.id','users.name')->get();
        foreach ($users as $user){
            $department = $user->department()->value('name') ;
            $user->department = $department;
        }
        return $users;
    }
    public function getBroker(Blogger $blogger)
    {
        DB::connection()->enableQueryLog();
        $users = $blogger->publicity()->select('users.id','users.name')->get();
//        dd(DB::getQueryLog());
        foreach ($users as $user){
            $department = $user->department()->value('name') ;
            $user->department = $department;
        }
        return $users;
    }
    public function getTasks(Blogger $blogger)
    {
        $tasks = $blogger->tasks()->select('tasks.id','tasks.status','tasks.title','tasks.end_at',DB::raw('users.name as principal_name'))->stopAsc()
            ->LeftJoin('users','tasks.principal_id','users.id')
            ->where('tasks.status',TaskStatus::NORMAL)->searchData()
            ->limit(3)->get();
        return $tasks;
    }
}
<?php
namespace App\Entity;
use App\Gender;
use App\Models\BloggerType;
use App\Models\Platform;
use App\SignContractStatus;
use App\User;
use App\Whether;

/**
 * Created by PhpStorm.
 * User: apple
 * Date: 2019-01-23
 * Time: 15:12
 */

class BloggerEntity
{
    /**
     *@desc 
     */
   public $id;

    /**
     *@desc 昵称
     */
   public $nickname;

    /**
     *@desc 平台
     */
   public $platform_id;

    /**
     *@desc 沟通状态
     */
   public $communication_status;

    /**
     *@desc 与我公司签约意向
     */
   public $intention;

    /**
     *@desc 不与我公司签约原因
     */
   public $intention_desc;

    /**
     *@desc 签约日期
     */
   public $sign_contract_at;

    /**
     *@desc 级别
     */
   public $level;

    /**
     *@desc 孵化期开始时间
     */
   public $hatch_star_at;

    /**
     *@desc 孵化期结束时间
     */
   public $hatch_end_at;

    /**
     *@desc 制作人
     */
   public $producer_id;

    /**
     *@desc 签约状态
     */
   public $sign_contract_status;

    /**
     *@desc icon
     */
   public $icon;

    /**
     *@desc 备注
     */
   public $desc;

    /**
     *@desc 状态
     */
   public $status;

    /**
     *@desc 博主类型
     */
   public $type_id;

    /**
     *@desc 头像
     */
   public $avatar;

    /**
     *@desc 创建者
     */
   public $creator_id;

    /**
     *@desc 性别
     */
   public $gender;

    /**
     *@desc 合作需求
     */
   public $cooperation_demand;

    /**
     *@desc 解约日期
     */
   public $terminate_agreement_at;

    /**
     *@desc 是否与其他公司签约
     */
   public $sign_contract_other;

    /**
     *@desc 签约公司名称
     */
   public $sign_contract_other_name;

    /**
     *@desc 
     */
   private $created_at;

    /**
     *@desc 
     */
   private $updated_at;

    /**
     *@desc 
     */
   private $deleted_at;

    /**
     *@desc 平台
     */
   public $platform;

    /**
     *@desc 抖音
     */
   public $douyin_id;

    /**
     *@desc 签约时抖音粉丝数
     */
   public $douyin_fans_num;

    /**
     *@desc 微博主页地址
     */
   public $weibo_url;

    /**
     *@desc 签约时微博粉丝数
     */
   public $weibo_fans_num;

    /**
     *@desc 小红书连接
     */
   public $xiaohongshu_url;

    /**
     *@desc 签约时小红书粉丝数
     */
   public $xiaohongshu_fans_num;

   public function get_id()
   {
       return $this->id;
   }
   public function get_nickname()
   {
       return $this->nickname;
   }
   public function get_platform_id()
   {
       $platform = Platform::find($this->platform_id);
       return $platform == null ? null : $platform->name;
   }
   public function get_communication_status()
   {
       return CommunicationStatus::getStr($this->communication_status);
   }
   public function get_intention()
   {
       return Whether::getStr($this->intention);
   }
   public function get_intention_desc()
   {
       return $this->intention_desc;
   }
   public function get_sign_contract_at()
   {
       return $this->sign_contract_at;
   }
   public function get_level()
   {
       return BloggerLevel::getStr($this->level);
   }
   public function get_hatch_star_at()
   {
       return $this->hatch_star_at;
   }
   public function get_hatch_end_at()
   {
       return $this->hatch_end_at;
   }
   public function get_producer_id()
   {
       $user = User::find($this->producer_id);
       return $user == null ? null : $user->name;
   }
   public function get_sign_contract_status()
   {
       return SignContractStatus::getStr($this->sign_contract_status);
   }
   public function get_icon()
   {
       return $this->icon;
   }
   public function get_desc()
   {
       return $this->desc;
   }
   public function get_status()
   {
       return $this->status;
   }
   public function get_type_id()
   {
       $blogger = BloggerType::find($this->type_id);
       return $blogger == null ? null : $blogger->name;
   }
   public function get_avatar()
   {
       return $this->avatar;
   }
   public function get_creator_id()
   {
       $user = User::find($this->creator_id);
       return $user == null ? null: $user->name;
   }
   public function get_gender()
   {
       return Gender::getStr($this->gender);
   }
   public function get_cooperation_demand()
   {
       return $this->cooperation_demand;
   }
   public function get_terminate_agreement_at()
   {
       return $this->terminate_agreement_at;
   }
   public function get_sign_contract_other()
   {
       return Whether::getStr($this->sign_contract_other);
   }
   public function get_sign_contract_other_name()
   {
       return $this->sign_contract_other_name;
   }
   public function get_created_at()
   {
       return $this->created_at;
   }
   public function get_updated_at()
   {
       return $this->updated_at;
   }
   public function get_deleted_at()
   {
       return $this->deleted_at;
   }
   public function get_platform()
   {
       return $this->platform;
   }
   public function get_douyin_id()
   {
       return $this->douyin_id;
   }
   public function get_douyin_fans_num()
   {
       return $this->douyin_fans_num;
   }
   public function get_weibo_url()
   {
       return $this->weibo_url;
   }
   public function get_weibo_fans_num()
   {
       return $this->weibo_fans_num;
   }
   public function get_xiaohongshu_url()
   {
       return $this->xiaohongshu_url;
   }
   public function get_xiaohongshu_fans_num()
   {
       return $this->xiaohongshu_fans_num;
   }

}
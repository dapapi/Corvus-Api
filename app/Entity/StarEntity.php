<?php
namespace App\Entity;
use App\CommunicationStatus;
use App\Gender;
use App\SignContractStatus;
use App\StarSource;
use App\User;
use App\Whether;

/**
 * Created by PhpStorm.
 * User: apple
 * Date: 2019-01-23
 * Time: 15:12
 */

class StarEntity
{
    /** 主键id
     *@desc 
     */
   public $id;

    /**
     *@desc 姓名
     */
   public $name;

    /**
     *@desc 描述
     */
   public $desc;

    /**
     *@desc 经纪人
     */
   public $broker_id;

    /**
     *@desc 头像
     */
   public $avatar;

    /**
     *@desc 性别
     */
   public $gender;

    /**
     *@desc 生日
     */
   public $birthday;

    /**
     *@desc 电话
     */
   public $phone;

    /**
     *@desc 微信
     */
   public $wechat;

    /**
     *@desc 邮箱
     */
   public $email;

    /**
     *@desc 艺人来源
     */
   public $source;

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
     *@desc 是否签约其他公司
     */
   public $sign_contract_other;

    /**
     *@desc 签约公司名称
     */
   public $sign_contract_other_name;

    /**
     *@desc 签约日期
     */
   public $sign_contract_at;

    /**
     *@desc 签约状态
     */
   public $sign_contract_status;

    /**
     *@desc 解约日期
     */
   public $terminate_agreement_at;

    /**
     *@desc 创建者
     */
   public $creator_id;

    /**
     *@desc 项目状态
     */
   public $status;

    /**
     *@desc 合同类型
     */
   public $type;

    /**
     *@desc 创建时间
     */
   public $created_at;

    /**
     *@desc 更新时间
     */
   public $updated_at;

    /**
     *@desc 删除时间
     */
   public $deleted_at;

    /**
     *@desc 社交平台
     */
   public $platform;

    /**
     *@desc 微博主页地址
     */
   public $weibo_url;

    /**
     *@desc 微博粉丝数
     */
   public $weibo_fans_num;

    /**
     *@desc 百科地址
     */
   public $baike_url;

    /**
     *@desc 百科粉丝数
     */
   public $baike_fans_num;

    /**
     *@desc 抖音id
     */
   public $douyin_id;

    /**
     *@desc 抖音粉丝数
     */
   public $douyin_fans_num;

    /**
     *@desc 其他地址
     */
   public $qita_url;

    /**
     *@desc 其他粉丝数
     */
   public $qita_fans_num;

    /**
     *@desc 星探
     */
   public $artist_scout_name;

    /**
     *@desc 地区
     */
   public $star_location;

   public function get_id()
   {
       return $this->id;
   }
   public function get_name()
   {
       return $this->name;
   }
   public function get_desc()
   {
       return $this->desc;
   }
   public function get_broker_id()
   {
       return $this->broker_id;
   }
   public function get_avatar()
   {
       return $this->avatar;
   }
   public function get_gender()
   {
       return Gender::getStr($this->gender);
   }
   public function get_birthday()
   {
       return $this->birthday;
   }
   public function get_phone()
   {
       return $this->phone;
   }
   public function get_wechat()
   {
       return $this->wechat;
   }
   public function get_email()
   {
       return $this->email;
   }
   public function get_source()
   {
       return StarSource::getStr($this->source);
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
   public function get_sign_contract_other()
   {
       return Whether::getStr($this->sign_contract_other);
   }
   public function get_sign_contract_other_name()
   {
       return $this->sign_contract_other_name;
   }
   public function get_sign_contract_at()
   {
       return $this->sign_contract_at;
   }
   public function get_sign_contract_status()
   {
       return SignContractStatus::getStr($this->sign_contract_status);
   }
   public function get_terminate_agreement_at()
   {
       return $this->terminate_agreement_at;
   }
   public function get_creator_id()
   {
       $user = User::find($this->creator_id);
       return $user == null ? null : $user->name;
   }
   public function get_status()
   {
       return $this->status;
   }
   public function get_type()
   {
       return $this->type;
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
   public function get_weibo_url()
   {
       return $this->weibo_url;
   }
   public function get_weibo_fans_num()
   {
       return $this->weibo_fans_num;
   }
   public function get_baike_url()
   {
       return $this->baike_url;
   }
   public function get_baike_fans_num()
   {
       return $this->baike_fans_num;
   }
   public function get_douyin_id()
   {
       return $this->douyin_id;
   }
   public function get_douyin_fans_num()
   {
       return $this->douyin_fans_num;
   }
   public function get_qita_url()
   {
       return $this->qita_url;
   }
   public function get_qita_fans_num()
   {
       return $this->qita_fans_num;
   }
   public function get_artist_scout_name()
   {
       return $this->artist_scout_name;
   }
   public function get_star_location()
   {
       return $this->star_location;
   }

}
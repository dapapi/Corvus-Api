<?php
namespace App\Entity;
use App\Models\Client;
use App\Models\Contact;
use App\Models\DataDictionarie;
use App\Models\Industry;
use App\Models\Trail;
use App\User;

/**
 * Created by PhpStorm.
 * User: apple
 * Date: 2019-01-23
 * Time: 15:12
 */

class TrailEntity
{
    /**
     *@desc 主键id
     */
   public $id;

    /**
     *@desc 线索名称
     */
   public $title;

    /**
     *@desc 品牌
     */
   public $brand;

    /**
     *@desc 负责人
     */
   public $principal_id;

    /**
     *@desc 行业
     */
   public $industry_id;

    /**
     *@desc 客户
     */
   public $client_id;

    /**
     *@desc 公司
     */
   public $contact_id;

    /**
     *@desc 创建者
     */
   public $creator_id;

    /**
     *@desc 线索类型
     */
   public $type;

    /**
     *@desc 线索状态
     */
   public $status;

    /**
     *@desc 合作类型
     */
   public $cooperation_type;

    /**
     *@desc 优先级
     */
   public $priority;

    /**
     *@desc 锁价
     */
   public $lock_status;

    /**
     *@desc 锁价人
     */
   public $lock_user;

    /**
     *@desc 锁价时间
     */
   public $lock_at;

    /**
     *@desc 销售进展
     */
   public $progress_status;

    /**
     *@desc 线索来源
     */
   public $resource;

    /**
     *@desc 来源类型
     */
   public $resource_type;

    /**
     *@desc 预计订单收入
     */
   public $fee;

    /**
     *@desc 描述
     */
   public $desc;

    /**
     *@desc 删除时间
     */
   private $deleted_at;

    /**
     *@desc 创建时间
     */
   private $created_at;

    /**
     *@desc 更新时间
     */
   private $updated_at;

    /**
     *@desc 所属公海
     */
   public $pool_type;

    /**
     *@desc 领取
     */
   public $take_type;

    /**
     *@desc 提醒
     */
   private $receive;

   public function get_id()
   {
       return $this->id;
   }
   public function get_title()
   {
       return $this->title;
   }
   public function get_brand()
   {
       return $this->brand;
   }
   public function get_principal_id()
   {
       $user = User::find($this->principal_id);
       return $user == null ? null : $user->name;
   }
   public function get_industry_id()
   {
       $industry = Industry::find($this->industry_id);
       return $industry == null ? null : $industry->name;
   }
   public function get_client_id()
   {
       $client = Client::find($this->client_id);
       return $client == null ? null : $client->company;
   }
   public function get_contact_id()
   {
       $contact = Contact::find($this->contact_id);
       return $contact == null ? null : $contact->name;
   }
   public function get_creator_id()
   {
       $user = User::find($this->creator_id);
       return $user == null ? null : $user->name;
   }
   public function get_type()
   {
       $type = null;
       switch ($this->type){
           case Trail::TYPE_MOVIE:
               $type = "影视项目";
               break;
           case Trail::TYPE_VARIETY:
               $type = "综艺项目";
               break;
           case Trail::TYPE_ENDORSEMENT:
               $type = "商务代言";
               break;
           case Trail::TYPE_PAPI:
               $type = "papi项目";
               break;
           case Trail::TYPE_BASE:
               $type = "基础项目";
               break;
       }
       return $type;
   }
   public function get_status()
   {
       $status = null;
       switch ($this->status){
           case Trail::PROGRESS_BEGIN:
               $status = "开始接洽";
               break;
           case Trail::PROGRESS_REFUSE:
               $status = "主动拒绝";
               break;
           case Trail::PROGRESS_CANCEL:
               $status = "客户拒绝";
               break;
           case Trail::PROGRESS_TALK:
               $status = "进入谈判";
               break;
           case Trail::PROGRESS_INTENTION:
               $status = "意向签约";
               break;
           case Trail::PROGRESS_SIGNING:
               $status = "签约中";
               break;
           case Trail::PROGRESS_SIGNED:
               $status = "签约完成";
               break;
           case Trail::PROGRESS_EXECUTE:
               $status = "待执行";
               break;
           case Trail::PROGRESS_EXECUTING:
               $status = "在执行";
               break;
           case Trail::PROGRESS_EXECUTED:
               $status = "已执行";
               break;
           case Trail::PROGRESS_PAYBACK:
               $status = "客户回款";
               break;
           case Trail::PROGRESS_FEEDBACK:
               $status = "客户分析及项目复盘";
               break;
           case Trail::PROGRESS_PROJECT_COMPLETE:
               $status = "完成";
               break;
           case Trail::PROGRESS_ARCHIVE:
               $status = "归档";
       }
       return $status;
   }
   public function get_cooperation_type()
   {
       $cooperation_type =  (new DataDictionarie())->getName(DataDictionarie::COOPERATION_TYPE,$this->cooperation_type);
       return $cooperation_type;
   }
   public function get_priority()
   {
       $res = null;
       switch ($this->priority){
           case Trail::PRIORITY_C:
               $res = "C";
               break;
           case Trail::PRIORITY_B:
               $res = "B";
               break;
           case Trail::PRIORITY_A:
               $res = "A";
               break;
           case Trail::PRIORITY_S:
               $res = "S";
               break;
       }
       return $res;
   }
   public function get_lock_status()
   {

       return $this->lock_status == 1 ? "锁价":"未锁价";
   }
   public function get_lock_user()
   {
       $user = User::find($this->lock_user);
       return $user == null ? null : $user->name;
   }
   public function get_lock_at()
   {
       return $this->lock_at;
   }
   public function get_progress_status()
   {
       $progress_status = null;
       switch ($this->progress_status){
           case Trail::STATUS_UNCONFIRMED:
               $progress_status = "未确认";
               break;
           case Trail::STATUS_CONFIRMED:
               $progress_status = "已确认";
               break;
           case Trail::STATUS_DELETE:
               $progress_status = "已删除";
               break;
           case Trail::STATUS_REFUSE:
               $progress_status = "已拒绝";
               break;
       }
       return $progress_status;
   }
   public function get_resource()
   {
       if ($this->resource_type == 4 || $this->resource_type == 5){
           if (!empty($this->resource)){
               $user = User::find($this->resource);
               return $user == null ? null : $user->name;
           }
           return null;
       }
       return $this->resource;
   }
   public function get_resource_type()
   {
       return (new DataDictionarie())->getName(DataDictionarie::RESOURCE_TYPE,$this->resource_type);
   }
   public function get_fee()
   {
       return $this->fee;
   }
   public function get_desc()
   {
       return $this->desc;
   }
   public function get_deleted_at()
   {
       return $this->deleted_at;
   }
   public function get_created_at()
   {
       return $this->created_at;
   }
   public function get_updated_at()
   {
       return $this->updated_at;
   }
   public function get_pool_type()
   {
       if ($this->pool_type == 1){
           return "商务公海";
       }
       if ($this->pool_type == 2){
           return "影视公海";
       }
       if ($this->pool_type == 3){
           return "综艺公海";
       }
       return $this->pool_type;
   }
   public function get_take_type()
   {
       if ($this->take_type == 1){
           return "未领取";
       }
       if ($this->take_type == 2){
           return "领取";
       }
       return $this->take_type;
   }
   public function get_receive()
   {
       return $this->receive;
   }

}
<?php
namespace App\Entity;
use App\Models\Client;

/**
 * Created by PhpStorm.
 * User: apple
 * Date: 2019-01-23
 * Time: 15:12
 */

class ClientEntity
{
    /**
     *@desc 
     */
   public $id;

    /**
     *@desc 公司名称
     */
   public $company;

    /**
     *@desc 客户类型
     */
   public $type;

    /**
     *@desc 客户状态
     */
   public $status;

    /**
     *@desc 级别
     */
   public $grade;

    /**
     *@desc 省
     */
   public $province;

    /**
     *@desc 市
     */
   public $city;

    /**
     *@desc 地区
     */
   public $district;

    /**
     *@desc 详细地址
     */
   public $address;

    /**
     *@desc 负责人
     */
   public $principal_id;

    /**
     *@desc 创建人
     */
   public $creator_id;

    /**
     *@desc 客户评级
     */
   public $client_rating;

    /**
     *@desc 规模
     */
   public $size;

    /**
     *@desc 备注
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
     *@desc 直客保护截止时间
     */
   public $protected_client_time;

   public function get_id()
   {
       return $this->id;
   }
   public function get_company()
   {
       return $this->company;
   }
   public function get_type()
   {
       return $this->type;
   }
   public function get_status()
   {
       return $this->status;
   }
   public function get_grade()
   {
       $grade = null;
       switch ($this->grade){
           case Client::GRADE_NORMAL:
               $grade = "直客";
                break;
           case Client::GRADE_PROXY:
               $grade = "代理公司";
               break;
       }
       return $grade;
   }
   public function get_province()
   {
       return $this->province;
   }
   public function get_city()
   {
       return $this->city;
   }
   public function get_district()
   {
       return $this->district;
   }
   public function get_address()
   {
       return $this->address;
   }
   public function get_principal_id()
   {
       return $this->principal_id;
   }
   public function get_creator_id()
   {
       return $this->creator_id;
   }
   public function get_client_rating()
   {
       return $this->client_rating;
   }
   public function get_size()
   {
       $size = null;
       switch ($this->size){
           case Client::SIZE_LISTED:
               $size = "上市公司";
               break;
           case Client::SIZE_TOP500:
               $size = "500强";
               break;
       }
       return $size;
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
   public function get_protected_client_time()
   {
       return $this->protected_client_time;
   }
}
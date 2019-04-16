<?php
namespace App\Entity;
/**
 * Created by PhpStorm.
 * User: apple
 * Date: 2019-01-23
 * Time: 15:12
 */

class AimEntity
{
    /**
     *@desc 主键id
     */
   public $id;

    /**
     *@desc 目标名称
     */
   public $title;

    /**
     *@desc 目标范围
     */
   public $range;

    /**
     *@desc 部门
     */
   public $department_name;

    /**
     *@desc 周期
     */
   public $period_name;

    /**
     *@desc 目标类型
     */
   public $type;

    /**
     *@desc 金额类型
     */
   public $amount_type;

    /**
     *@desc 目标金额
     */
   public $amount;

    /**
     *@desc 维度
     */
   public $position;

    /**
     *@desc 艺人级别
     */
   public $talent_level;

    /**
     *@desc 目标级别
     */
   public $aim_level;

    /**
     *@desc 负责人
     */
   public $principal_name;

    /**
     *@desc 目标描述
     */
   public $desc;

    /**
     *@desc 目标进度
     */
   public $percentage;

    /**
     *@desc 目标状态
     */
   public $status;

    /**
     *@desc 截止日期
     */
   public $deadline;

   public function get_id()
   {
       return $this->id;
   }
   public function get_title()
   {
       return $this->title;
   }
   public function get_range()
   {
       return $this->range;
   }
   public function get_department_name()
   {
       return $this->department_name;
   }
   public function get_period_name()
   {
       return $this->period_name;
   }
   public function get_type()
   {
       return $this->type;
   }
   public function get_amount_type()
   {
       return $this->amount_type;
   }
   public function get_amount()
   {
       return $this->amount;
   }
   public function get_position()
   {
       return $this->position;
   }
   public function get_talent_level()
   {
       return $this->talent_level;
   }
   public function get_aim_level()
   {
       return $this->aim_level;
   }
   public function get_principal_name()
   {
       return $this->principal_name;
   }
   public function get_desc()
   {
       return $this->desc;
   }
   public function get_percentage()
   {
       return $this->percentage;
   }
   public function get_status()
   {
       if ($this->status) {
           return '已结束';
       } else
           return '进行中';
   }

}
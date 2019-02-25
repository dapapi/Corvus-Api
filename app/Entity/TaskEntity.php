<?php
namespace App\Entity;
use App\Models\TaskType;
use App\TaskPriorityStatus;
use App\TaskStatus;
use App\User;

/**
 * Created by PhpStorm.
 * User: apple
 * Date: 2019-01-23
 * Time: 15:12
 */

class TaskEntity
{
    /**
     *@desc 主键id
     */
   public $id;

    /**
     *@desc 任务名称
     */
   public $title;

    /**
     *@desc 任务类型
     */
   public $type_id;

    /**
     *@desc 任务父ID
     */
   public $task_pid;

    /**
     *@desc 创建人
     */
   public $creator_id;

    /**
     *@desc 参与人
     */
   public $principal_id;

    /**
     *@desc 任务状态
     */
   public $status;

    /**
     *@desc 优先级
     */
   public $priority;

    /**
     *@desc 描述
     */
   public $desc;

    /**
     *@desc 
     */
   public $privacy;

    /**
     *@desc 开始时间
     */
   public $start_at;

    /**
     *@desc 结束时间
     */
   public $end_at;

    /**
     *@desc 完成时间
     */
   public $complete_at;

    /**
     *@desc 终止时间
     */
   public $stop_at;

    /**
     *@desc 创建时间
     */
   private $created_at;

    /**
     *@desc 更新时间
     */
   private $updated_at;

    /**
     *@desc 删除时间
     */
   private $deleted_at;

   public function get_id()
   {
       return $this->id;
   }
   public function get_title()
   {
       return $this->title;
   }
   public function get_type_id()
   {
       $task_type = TaskType::find($this->type_id);
       return $task_type == null ? null : $task_type->title;
   }
   public function get_task_pid()
   {
       return $this->task_pid;
   }
   public function get_creator_id()
   {
       $user = User::find($this->creator_id);
       return $user == null ? null : $user->name;
   }
   public function get_principal_id()
   {
       $user = User::find($this->principal_id);
       return $user == null ? null : $user->name;
   }
   public function get_status()
   {
       $status = null;
       switch ($this->status){
           case TaskStatus::NORMAL:
               $status = "正常";
               break;
           case TaskStatus::COMPLETE:
               $status = "完成";
               break;
           case TaskStatus::TERMINATION:
               $status = "终止";
               break;
       }
       return status;
   }
   public function get_priority()
   {
       return TaskPriorityStatus::getStr($this->priority);
   }
   public function get_desc()
   {
       return $this->desc;
   }
   public function get_privacy()
   {
       if ($this->privacy ==0){
           return "公开";
       }
       if ($this->privacy == 1){
           return "私密";
       }
       return $this->privacy;
   }
   public function get_start_at()
   {
       return $this->start_at;
   }
   public function get_end_at()
   {
       return $this->end_at;
   }
   public function get_complete_at()
   {
       return $this->complete_at;
   }
   public function get_stop_at()
   {
       return $this->stop_at;
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

}
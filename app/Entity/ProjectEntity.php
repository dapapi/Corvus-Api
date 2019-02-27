<?php
namespace App\Entity;
use App\Models\DataDictionarie;
use App\Models\Project;
use App\Models\Trail;
use App\Priority;
use App\User;

/**
 * Created by PhpStorm.
 * User: apple
 * Date: 2019-01-23
 * Time: 15:12
 */

class ProjectEntity
{
    /**
     *@desc 主键id
     */
   public $id;

    /**
     *@desc 项目编号
     */
   public $project_number;

    /**
     *@desc 项目名称
     */
   public $title;

    /**
     *@desc 负责人
     */
   public $principal_id;

    /**
     *@desc 创建人
     */
   public $creator_id;

    /**
     *@desc 销售线索
     */
   public $trail_id;

    /**
     *@desc 私密
     */
   public $privacy;

    /**
     *@desc 优先级
     */
   public $priority;

    /**
     *@desc 项目状态
     */
   public $status;

    /**
     *@desc 项目类型
     */
   public $type;

    /**
     *@desc 描述
     */
   public $desc;

    /**
     *@desc 
     */
   private $deleted_at;

    /**
     *@desc 项目停止时间
     */
   public $stop_at;

    /**
     *@desc 项目完成时间
     */
   public $complete_at;

    /**
     *@desc 支出
     */
   public $projected_expenditure;

    /**
     *@desc 项目截止时间
     */
   public $end_at;

    /**
     *@desc 项目开始时间
     */
   public $start_at;

    /**
     *@desc 
     */
   private $created_at;

    /**
     *@desc 
     */
   private $updated_at;

   public function get_id()
   {
       return $this->id;
   }
   public function get_project_number()
   {
       return $this->project_number;
   }
   public function get_title()
   {
       return $this->title;
   }
   public function get_principal_id()
   {
       $user = User::find($this->principal_id);
       return $user == null ? null : $user->name;
   }
   public function get_creator_id()
   {
       $user = User::find($this->creator_id);
       return $user == null ? null : $user->name;
   }
   public function get_trail_id()
   {   $trail = Trail::find($this->trail_id);
       return $trail == null ? null : $trail->title;
   }
   public function get_privacy()
   {
       if ($this->privacy == 1){
           return "私有";
       }else{
           return "公开";
       }
   }
   public function get_priority()
   {
       return (new DataDictionarie())->getName(DataDictionarie::PRIORITY,$this->priority);
   }
   public function get_status()
   {
       return (new Project())->getProjectStatus($this->status);
   }
   public function get_type()
   {
       return (new Project())->getProjectType($this->type);
   }
   public function get_desc()
   {
       return $this->desc;
   }
   public function get_deleted_at()
   {
       return $this->deleted_at;
   }
   public function get_stop_at()
   {
       return $this->stop_at;
   }
   public function get_complete_at()
   {
       return $this->complete_at;
   }
   public function get_projected_expenditure()
   {
       return $this->projected_expenditure;
   }
   public function get_end_at()
   {
       return $this->end_at;
   }
   public function get_start_at()
   {
       return $this->start_at;
   }
   public function get_created_at()
   {
       return $this->created_at;
   }
   public function get_updated_at()
   {
       return $this->updated_at;
   }

}
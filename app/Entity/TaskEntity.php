<?php
namespace App\Entity;
/**
 * Created by PhpStorm.
 * User: apple
 * Date: 2019-01-23
 * Time: 15:12
 */

class TaskEntity
{
    /**
     *@desc 主键
     */
   private $id;

    /**
     *@desc 任务名称
     */
   private $title;

    /**
     *@desc 任务类型
     */
   private $type_id;

    /**
     *@desc 任务父ID
     */
   private $task_pid;

    /**
     *@desc 创建人
     */
   private $creator_id;

    /**
     *@desc 参与人
     */
   private $principal_id;

    /**
     *@desc 任务状态
     */
   private $status;

    /**
     *@desc 优先级
     */
   private $priority;

    /**
     *@desc 描述
     */
   private $desc;

    /**
     *@desc 隐私设置
     */
   private $privacy;

    /**
     *@desc 开始时间
     */
   private $start_at;

    /**
     *@desc 结束时间
     */
   private $end_at;

    /**
     *@desc 完成时间
     */
   private $complete_at;

    /**
     *@desc 终止时间
     */
   private $stop_at;

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


}
<?php
namespace App\Entity;
/**
 * Created by PhpStorm.
 * User: apple
 * Date: 2019-01-23
 * Time: 15:12
 */

class ScheduleEntity
{
    /**
     *@desc 主键
     */
   private $id;

    /**
     *@desc 标题
     */
   private $title;

    /**
     *@desc 日历
     */
   private $calendar_id;

    /**
     *@desc 是否全天
     */
   private $is_allday;

    /**
     *@desc 仅参与人可见
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
     *@desc 位置
     */
   private $position;

    /**
     *@desc 重复
     */
   private $repeat;

    /**
     *@desc 会议室
     */
   private $material_id;

    /**
     *@desc 创建者
     */
   private $creator_id;

    /**
     *@desc 类型
     */
   private $type;

    /**
     *@desc 状态
     */
   private $status;

    /**
     *@desc 描述
     */
   private $desc;

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
     *@desc 提醒
     */
   private $remind;


}
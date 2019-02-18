<?php
namespace App\Entity;
/**
 * Created by PhpStorm.
 * User: apple
 * Date: 2019-01-23
 * Time: 15:12
 */

class TrailEntity
{
    /**
     *@desc 主键
     */
   private $id;

    /**
     *@desc 线索名称
     */
   private $title;

    /**
     *@desc 品牌
     */
   private $brand;

    /**
     *@desc 负责人id
     */
   private $principal_id;

    /**
     *@desc 行业
     */
   private $industry_id;

    /**
     *@desc 客户
     */
   private $client_id;

    /**
     *@desc 联系人
     */
   private $contact_id;

    /**
     *@desc 创建人
     */
   private $creator_id;

    /**
     *@desc 线索类型
     */
   private $type;

    /**
     *@desc 线索状态
     */
   private $status;

    /**
     *@desc 合作类型
     */
   private $cooperation_type;

    /**
     *@desc 优先级
     */
   private $priority;

    /**
     *@desc 锁价状态
     */
   private $lock_status;

    /**
     *@desc 线索进度
     */
   private $progress_status;

    /**
     *@desc 线索来源
     */
   private $resource;

    /**
     *@desc 线索来源类型
     */
   private $resource_type;

    /**
     *@desc 预计订单收入
     */
   private $fee;

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
     *@desc 所属公海
     */
   private $pool_type;

    /**
     *@desc 领取
     */
   private $take_type;

    /**
     *@desc 提醒
     */
   private $receive;


}
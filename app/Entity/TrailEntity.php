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
     *@desc 
     */
   private $id;

    /**
     *@desc 
     */
   private $title;

    /**
     *@desc 
     */
   private $brand;

    /**
     *@desc 
     */
   private $principal_id;

    /**
     *@desc 
     */
   private $industry_id;

    /**
     *@desc 
     */
   private $client_id;

    /**
     *@desc 
     */
   private $contact_id;

    /**
     *@desc 
     */
   private $creator_id;

    /**
     *@desc 
     */
   private $type;

    /**
     *@desc 
     */
   private $status;

    /**
     *@desc 
     */
   private $cooperation_type;

    /**
     *@desc 
     */
   private $priority;

    /**
     *@desc 
     */
   private $lock_status;

    /**
     *@desc 
     */
   private $progress_status;

    /**
     *@desc 
     */
   private $resource;

    /**
     *@desc 
     */
   private $resource_type;

    /**
     *@desc 
     */
   private $fee;

    /**
     *@desc 
     */
   private $desc;

    /**
     *@desc 
     */
   private $deleted_at;

    /**
     *@desc 
     */
   private $created_at;

    /**
     *@desc 
     */
   private $updated_at;

    /**
     *@desc 所属公海 1商务 2影视 3综艺
     */
   private $pool_type;

    /**
     *@desc 领取 1未领取 2领取
     */
   private $take_type;

    /**
     *@desc 提醒
     */
   private $receive;


}
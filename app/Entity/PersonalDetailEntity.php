<?php
namespace App\Entity;
/**
 * Created by PhpStorm.
 * User: apple
 * Date: 2019-01-23
 * Time: 15:12
 */

class PersonalDetailEntity
{
    /**
     *@desc 主键
     */
   private $id;

    /**
     *@desc 身份证url
     */
   private $id_card_url;

    /**
     *@desc 护照号
     */
   private $passport_code;

    /**
     *@desc 身份证号
     */
   private $id_number;

    /**
     *@desc 工资银行卡号1
     */
   private $card_number_one;

    /**
     *@desc 工资银行卡号2
     */
   private $card_number_two;

    /**
     *@desc 信用卡
     */
   private $credit_card;

    /**
     *@desc 公积金
     */
   private $accumulation_fund;

    /**
     *@desc 开户行
     */
   private $opening;

    /**
     *@desc 上家公司
     */
   private $last_company;

    /**
     *@desc 岗位职责
     */
   private $responsibility;

    /**
     *@desc 合同
     */
   private $contract;

    /**
     *@desc 地址
     */
   private $address;

    /**
     *@desc 入职时间
     */
   private $entry_time;

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

    /**
     *@desc 用户id
     */
   private $user_id;


}
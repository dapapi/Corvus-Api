<?php
/**
 * Class OperateLogMethod
 * @package App
 * 操作日志方法
 */

namespace App;


abstract class OperateLogMethod
{
    const CREATE = 1;//创建
    const UPDATE = 2;//修改
    const DELETE = 3;//删除
    const FOLLOW_UP = 4;//跟进
    const LOOK = 5;//查看
    const UPDATE_PRIVACY = 6;//修改隐私
    const PUBLIC = 7;//公开
    const PRIVACY = 8;//私密
    const TERMINATION = 9;//终止
    const COMPLETE = 10;//完成
    const ACTIVATE = 11;//激活
    const ADD = 12;//添加
    const RECOVER = 13;//恢复
    const UPLOAD_AFFIX = 14;//上传附件
    const DOWNLOAD_AFFIX = 15;//下载附件
    const UPDATE_SIGNIFICANCE = 16;//修改重要
    const DELETE_OTHER = 17;//删除其他
    const RECOVER_OTHER = 18;//恢复其他
    const ADD_PERSON = 19;//添加人
    const DEL_PERSON = 20;//删除人
    const RELEVANCE_RESOURCE = 21;//关联资源
    const UN_RELEVANCE_RESOURCE = 22;//解除关联资源
    const DEL_PRINCIPAL = 23;//删除负责人
    const CANCEL = 24;//取消
    const RENEWAL = 25;//更新
    const TRANSFER = 26;//调岗
    const REFUSE = 27;//拒绝
    const ADD_WORK = 28;//添加作品
    const ADD_TASK_RESOURCE = 29 ;//为艺人添加任务
    const CREATE_SIGNING_CONTRACTS=30;//创建签约合同
    const CREATE_RESCISSION_CONTRACTS = 31;//创建解约合同
    const ADD_PRODUCTION = 32;//微博主添加做品
//    const ADD_TRAIL_TASK = 33;//创建销售线索关联任务
//    const ADD_CLIENT_TASK = 34;//创建客户任务
    const ADD_CLIENT_CONTRACTS = 33;//为客户创建联系人
    const ADD_RELATE = 34;//添加关联
    const STATUS_FROZEN = 35;//项目撤单
    const ADD_PRIVACY = 36;//隐私设置
    const CREATE_CONTRACTS = 37;//创建合同
    const APPROVAL_AGREE = 38;//审批同意
    const APPROVAL_REFUSE = 39;//拒绝审批
    const APPROVAL_TRANSFER = 40;//转交审批
    const APPROVAL_CANCEL = 41;//撤销审批
    const APPROVAL_DISCARD = 42;//作废审批
    const ALLOT = 43;//分配销售线索
    const RECEIVE = 44;//领取销售线索
    const REFUND_TRAIL = 45;//退回线索


}
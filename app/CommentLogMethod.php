<?php
/**
 * Class OperateLogMethod
 * @package App
 * 操作日志方法
 */

namespace App;


abstract class CommentLogMethod
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
    const ADD_COMMENT = 28;//添加评论
    const SHOW_COMMENT = 28;//查看评论
}
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
    const PRIVACY_UPDATE = 6;//隐私修改
    const PUBLIC = 7;//公开
    const PRIVACY = 8;//私密
    const TERMINATION = 9;//终止
    const COMPLETE = 10;//完成
    const ACTIVATE = 11;//激活
    const ADD = 12;//添加
    const RECOVER = 13;//恢复
    const UPLOAD_AFFIX = 14;//上传附件
    const DOWNLOAD_AFFIX = 15;//下载附件
}
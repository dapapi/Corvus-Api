<?php

namespace App\TriggerPoint;


class ApprovalTriggerPoint
{
    const AGREE = 1;//同意审批
    const REFUSE = 2;//拒绝审批
    const TRANSFER = 3;//转交审批
    const WAIT_ME = 4;//待我审批
    const NOTIFY = 5;//知会
    const REMIND = 6;//提醒
}
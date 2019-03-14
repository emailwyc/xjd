<?php

namespace app\admin\model\sms;

use think\Model;

class Business extends Model
{
    // 表名
    protected $name = 'business_sms_rules';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
}

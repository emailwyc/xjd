<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/1/6
 * Time: 15:09
 */

namespace app\admin\model\sys;
use think\Model;

class Expectconfig extends Model{
// 表名
    protected $name = 'expect_config';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
}

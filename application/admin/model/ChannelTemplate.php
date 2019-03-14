<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace app\admin\model;
use think\Model;
/**
 * Description of ChannelAgency
 *
 * @author tomato
 */
class ChannelTemplate extends Model{
    //put your code here
    //put your code here
    // 表名
    protected $name = 'channel_template';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
}

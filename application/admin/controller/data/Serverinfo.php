<?php
/**
 * Created by PhpStorm.
 * User: Ksh
 * Date: 2018/12/20
 * Time: 17:01
 */

namespace app\admin\controller\sms;
class Serverinfo {
    public function get_used_status() {
        $fp = popen('top -b -n 2 | grep -E "^(Cpu|Mem|Tasks)"', "r");//获取某一时刻系统cpu和内存使用情况
        $rs = "";
        while (!feof($fp)) {
            $rs .= fread($fp, 1024);
        }
        pclose($fp);
        $sys_info = explode("\n", $rs);
        $cpu_info = explode(",", $sys_info[4]);  //CPU占有量  数组
        $mem_info = explode(",", $sys_info[5]); //内存占有量 数组

        //CPU占有量
        $cpu_usage = trim(trim($cpu_info[0], 'Cpu(s): '), '%us');  //百分比
        //内存占有量
        $mem_total = trim(trim($mem_info[0], 'Mem: '), 'k total');
        $mem_used = trim($mem_info[1], 'k used');
        $mem_usage = round(100 * intval($mem_used) / intval($mem_total), 2);  //百分比

    }
}
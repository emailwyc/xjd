<?php

namespace app\api\controller\manger;

use app\common\controller\Api;
use think\Db;
use xjd\util\Timeutil;

/**
 * 服务器监控:实时监控商户应用服务器状态
 */
class Info extends Api {
    //如果$noNeedLogin为空表示所有接口都需要登录才能请求
    //如果$noNeedRight为空表示所有接口都需要验证权限才能请求
    //如果接口已经设置无需登录,那也就无需鉴权了
    // 无需登录的接口,*表示全部
    protected $noNeedLogin = ['*'];
    // 无需鉴权的接口,*表示全部
    protected $noNeedRight = ['*'];

    public function _initialize() {
        parent::_initialize();
    }

    public function index() {
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
        $re['cpu_usage'] = trim(trim($cpu_info[0], 'Cpu(s): '), '%us');  //百分比
        //内存占有量
        $mem_total = trim(trim($mem_info[0], 'Mem: '), 'k total');
        $mem_used = trim($mem_info[1], 'k used');
        $re['mem_usage'] = round(100 * intval($mem_used) / intval($mem_total), 2);  //百分比
        $this->success('成功', $re);
    }
}

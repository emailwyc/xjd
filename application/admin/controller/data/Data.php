<?php

namespace app\admin\controller\sms;

use app\common\controller\Backend;
use think\Db;
use xjd\util\Timeutil;

class Data extends Backend {
    public function _initialize() {
        parent::_initialize();
    }

    public function index() {
        $timeUtil = new Timeutil();
        list($before, $end) = $timeUtil->getTodayTime();
        //今日新增客户 == 注册
        $where['createtime'] = array("between", [$before, $end]);
        $re['reg'] = Db::name('user')->where($where)->count();
        // 今日新增认证客户
        $re['rz'] = Db::name('user_info')->where($where)->count();
        //今日新增放款客户,同一个客户也算吧？
        $re['fk'] = Db::name('order')->where($where)->count('DISTINCT(uid)');
        //放款总额
        $where_fkze['status'] = array('in', '8,9,10');
        $re['fkze'] = Db::name('order')->where($where_fkze)->sum('amount');
        //还款+逾期
        $where_hk_yq['status'] = array('in', '9,10');
        $re['hk_yq'] = Db::name('order')->where($where_hk_yq)->sum('amount');
        //逾期
        $where_yq['status'] = 10;
        $re['yq'] = Db::name('order')->where($where_yq)->sum('overcost');
        //还款
        $re['hk'] = $re['hk_yq'] - $re['yq'];
        //逾期1-3   1-2
        $where_yq_time['status'] = 10;
        $where_yq_time['overday'] = array('between', [1, 2]);
        $re['yq_1_3'] = Db::name('order')->where($where_yq_time)->count();
        //逾期3-7  3-6
        $where_yq_time['overday'] = array('between', [3, 6]);
        $re['yq_3_7'] = Db::name('order')->where($where_yq_time)->count();
        //逾期7-15 7 -14
        $where_yq_time['overday'] = array('between', [3, 14]);
        $re['yq_7_15'] = Db::name('order')->where($where_yq_time)->count();
        //逾期15 >=15
        $where_yq_time['overday'] = array('>=', '15');
        $re['yq_15'] = Db::name('order')->where($where_yq_time)->count();
        $this->view->assign('re', $re);
        $this->view->fetch();
    }
}

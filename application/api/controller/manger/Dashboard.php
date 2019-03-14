<?php

namespace app\api\controller\manger;

use app\common\controller\Api;
use think\Db;
use xjd\util\Timeutil;

/**
 * Dashboard(仪表盘)
 */
class Dashboard extends Api {
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
        $re = array();
        $re['regcount'] = $this->regCount();
        list($re['jkamount'], $re['jkcount']) = $this->jkData();
        list($re['sq'], $re['sh'], $re['shtg'], $re['dfk'], $re['fkz'], $re['yfk']) = $this->orderinfo();
        $this->success('成功', $re);
    }

    //注册量
    public function regCount() {
        $timeUtil = new Timeutil();
        list($before, $end) = $timeUtil->getTodayTime();
        $where['createtime'] = array("between", [$before, $end]);
        $count = Db::name('user')->where($where)->count();

        return $count;
    }

    //总的借款金额
    public function jkData() {
        $total_amount = Db::name('order')->sum('amount');
        $count = Db::name('order')->count();

        return array($total_amount, $count);
    }

    //订单信息
    public function orderinfo() {
        // TODO: 2018/12/15  where group 一条sql也行
        //申请量
        $re[] = Db::name('order')->where(array('status' => 0))->count();
        //审核量
        $re[] = Db::name('order')->where(array('status' => array('in', '1,2,3,4')))->count();
        //审核通过量
        $re[] = Db::name('order')->where(array('status' => 5))->count();
        //待放款
        $re[] = Db::name('order')->where(array('status' => 6))->sum('amount');
        //放款中
        $re[] = Db::name('order')->where(array('status' => 7))->sum('amount');
        //已放款
        $re[] = Db::name('order')->where(array('status' => 8))->sum('amount');

        return $re;
    }
}

<?php

namespace app\api\controller\manger;

use app\common\controller\Api;
use think\Db;
use xjd\util\Timeutil;

/**
 * 成本
 */
class Coast extends Api {
    //如果$noNeedLogin为空表示所有接口都需要登录才能请求
    //如果$noNeedRight为空表示所有接口都需要验证权限才能请求
    //如果接口已经设置无需登录,那也就无需鉴权了
    // 无需登录的接口,*表示全部
    protected $noNeedLogin = ['*'];
    // 无需鉴权的接口,*表示全部
    protected $noNeedRight = ['*'];
    public $sms_price   = 1;
    public $rcert_price = 1;
    public $risk_price  = 1;

    public function _initialize() {
        parent::_initialize();
    }

    public function index() {
        //短信
        $where['type'] = 1;
        $sms_count = Db::name('coast')->where($where)->count();
        $re['sms'] = $sms_count * $this->sms_price;
        //实名
        $where['type'] = array('in','2,3,4');
        $rcert_count = Db::name('coast')->where($where)->count();
        $re['rcert'] = $rcert_count * $this->rcert_price;
        //风控
        $where['type'] = array('in','5,6,7,8');
        $risk_count = Db::name('coast')->where($where)->count();
        $re['risk'] = $risk_count * $this->risk_price;
        $this->success('成功', $re);
    }
}

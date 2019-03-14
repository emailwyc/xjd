<?php

namespace app\api\controller\manger;

use app\common\controller\Api;
use think\Db;
use xjd\util\Timeutil;
use think\Log;

/**
 * 查看审核日志
 */
class Shlog extends Api
{
    //如果$noNeedLogin为空表示所有接口都需要登录才能请求
    //如果$noNeedRight为空表示所有接口都需要验证权限才能请求
    //如果接口已经设置无需登录,那也就无需鉴权了
    // 无需登录的接口,*表示全部
    protected $noNeedLogin = ['*'];
    // 无需鉴权的接口,*表示全部
    protected $noNeedRight = ['*'];

    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 查询
     */
    public function index()
    {
        $params = $this->request->param();
        if (empty($params['uid'])) {
            $this->error('参数错误：uid');
        }
        $re = array();
//        $order_id = Db::name('order')->where(array('uid'=>$params['uid']))->value('MAX(id)');
//
//        if(empty($order_id)){
//            $this->success('成功',$re);
//        }

        $order = Db::name('order')->where(array('uid' => $params['uid']))->order("id desc")->find();
        $order_id = !empty($order["id"]) ? $order["id"] : 0;
        if (!$order_id) {
            $this->success('成功', $re);
        }
        $file = "id,order_id,order_status,sh_result,`desc`, DATE_FORMAT(FROM_UNIXTIME(create_time),'%Y-%m-%d %H:%i:%s') create_time,review";
        $list = Db::name('sh_log')->where(array('order_id' => $order_id))->field($file)->order('create_time desc')->select();
        $re["amount"] = $order["amount"];
        $re["cycle"] = $order["cycle"];
        $re["pay"] = $order["pay"];
        $re["list"] = $list;

        Log::write("返回值:" . print_r($re, true), "error");
        $this->success('成功', $re);
    }
}

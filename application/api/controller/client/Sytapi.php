<?php

namespace app\api\controller\client;

use app\common\controller\Api;
use think\Db;
use think\Log;
use xjd\util\Credit;
use xjd\util\ExportExcel;
use xjd\util\Fypay;
use xjd\util\Sms;
use xjd\util\Syt;
use xjd\util\Timeutil;

/**
 * 收银台
 */
class Sytapi extends Api {
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

    public function notifyurl() {
        $Syt = new Syt();
        $ips = array('101.200.45.56', '47.94.234.204');
        $ip = Timeutil::getIp();
        Log::write(__FUNCTION__.': ip='.$ip, 'error');
        $json = file_get_contents('php://input');
        $key = $Syt->key;
        $arr = json_decode($json, true);
        Log::write(__FUNCTION__.': arr='.print_r($arr, true), 'error');
        $jsonBase64 = base64_encode(json_encode($arr['paramsJson']));
        $jsonBase64Md5 = md5($jsonBase64);
        $sign = strtoupper(md5($key.$jsonBase64Md5));
        Log::write(__FUNCTION__.': sign='.$sign, 'error');
        $order_id = $arr['paramsJson']['data']['orderId'];
        $code = $arr['paramsJson']['code'];
        $amount = $arr['paramsJson']['data']['orderAmount'];
        $orderInfo = Db::name('mem_cash')->where(array('order_id' => $order_id))->find();
        if (empty($orderInfo)) {
            echo 'error';die;
        }
        if(2 == $orderInfo['status']){
            echo 'success';die;
        }
        if ($amount != $orderInfo['real_amount']) {
            $order_data['memo'] = '下单金额与实际支付金额不符，下单金额为：'.$amount;
            $order_data['status'] = 3;
            Db::name('mem_cash')->where(array('order_id' => $order_id))->update($order_data);
            echo 'error';die;
        }
        if (!in_array($ip, $ips)) {
            Log::write(__FUNCTION__.': ip异常', 'error');
            //ip 来源失败   无效回调
            $order_data['memo'] = '支付异常：'.$ip;
            $order_data['status'] = 3;
            Db::name('mem_cash')->where(array('order_id' => $order_id))->update($order_data);
            echo 'error';die;
        }
        if ($sign != $arr['sign']) {
            $order_data['memo'] = '支付异常：签名异常';
            $order_data['status'] = 3;
            Db::name('mem_cash')->where(array('order_id' => $order_id))->update($order_data);
        } else {
            if ('000000' === $code) {
                $order_data['memo'] = '成功';
                $order_data['status'] = 2;
                Db::name('mem_cash')->where(array('order_id' => $order_id))->update($order_data);
                //账户表余额增加
                $this->user_account($amount,1);
                //账户变更表增加记录
            } else {
                $order_data['memo'] = '失败原因：'.$code;
                $order_data['status'] = 3;
                Db::name('mem_cash')->where(array('order_id' => $order_id))->update($order_data);
            }
            //更改订单状态
            echo 'success';
        }
    }
}

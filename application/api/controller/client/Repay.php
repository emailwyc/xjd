<?php

namespace app\api\controller\client;

use app\common\controller\Api;
use fast\Random;
use function Sodium\crypto_box_keypair_from_secretkey_and_publickey;
use think\Db;
use xjd\util\Credit;
use xjd\util\ExportExcel;
use xjd\util\Fypay;
use xjd\util\Timeutil;
use think\Log;

/**
 * 还款
 */
class Repay extends Api
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



    //orde 订单表  order_repay 详情
    //还款
    public function repay()
    {
        $params = $this->request->param();
        if (empty($params['repay_id'])) {//订单的还款信息
            $this->error('参数错误：repay_id');
        }
        $order_repay = Db::name('order_repay')->where(array('id' => $params['repay_id']))->find();
        $order = Db::name('order')->where(array('id' => $order_repay['order_id']))->find();
        $checkBank = Db::name('user_bankcard')->where(array('uid' => $order['uid'], 'fy_status' => 4))
            ->find();
        if (empty($checkBank)) {
            $this->error('请先绑定银行卡', null, 24);
        }
        if (empty($order_repay) || empty($order)) {
            $this->error('订单不存在', null, 30);
        }
        if(2 == $order_repay['status'] || 4 == $order_repay['status'] || 6 == $order_repay['status'] || 8 == $order_repay['status']){
            $this->error('不满足还款条件');
        }
       // $repay_time = date("Y-m-d", $order_repay["repay_time"]);
        $repay_time = $order_repay["repay_time"];
        $status = $order_repay['status'];
        $today = Timeutil::todayTime();
        if (2 == $status || 4 == $status) {
            $this->success('已还款');
        } elseif (1 == $status && $today <= $repay_time) {//正常还款
            $real_amount = $order_repay['total_amount'];
            $data['status'] = 2;
            $data['update_time'] = time();
            $data['real_amount'] = $real_amount;
            $data['real_time'] = time();
            $order_data['status'] = 9;
        } elseif ((1 == $status && $today > $repay_time) || 3 == $status) {//逾期
            $real_amount = $order_repay['total_amount'] + $order_repay['penalty'];
            $data['status'] = 4;
            $data['update_time'] = time();
            $data['real_amount'] = $real_amount;
            $data['real_time'] = time();
            $order_data['status'] = 9;
            //$order_data['overcost'] = $order_repay['penalty'];
            //$order_data['overday'] = Timeutil::interval($order['starttime'], $order['starttime']);
        } else {
            $this->error('订单不满足还款条件', null, 3);
        }
        //更新额度结束
        $re = Db::name('order_repay')->where(array('id' => $params['repay_id']))->update($data);
        if (!$re) {
            $this->error('更新订单追踪失败', null, 4);
        }
        //还款-- 先更新状态，便于失败订单查询原因等
        $Fypay = new Fypay();
        $obj = array();
        $obj['TYPE'] = '03';
        $obj['VERSION'] = $Fypay->VERSION;
        $obj['MCHNTCD'] = $Fypay->MCHNTCD;
        $obj['MCHNTORDERID'] = $order_repay['order_code'];
        $obj['USERID'] = $checkBank['user_code'];
        $obj['PROTOCOLNO'] = $checkBank['protocolno'];
        $obj['AMT'] = $data['real_amount'] * 100;
        $obj['BACKURL'] = $Fypay->backurl;
        $obj['USERIP'] = $this->request->ip();
        $sign = $Fypay->setSign($obj);
        $obj['NEEDSENDMSG'] = 0;
        $obj['SIGNTP'] = 'MD5';
        $obj['SIGN'] = $sign;
        $re = $Fypay->orderPay($obj);
        if (!empty($re['ORDERID'])) {
            $updata['pay_order_id'] = $re['ORDERID'];
        }
        if ('0000' != $re['RESPONSECODE'] || $re['AMT'] != $obj['AMT']) {
            $updata['pay_status'] = 3;
            $re = Db::name('order_repay')->where(array('id' => $params['repay_id']))->update($updata);
            if (!$re) {
                $this->error('更新数据失败', null, 20);
            }
            $this->error('支付失败：' . $re['RESPONSEMSG'], null, 34);
        }
        $updata['pay_status'] = 2;
        $re = Db::name('order_repay')->where(array('id' => $params['repay_id']))->update($updata);
        if (!$re) {
            $this->error('支付成功更新数据失败', null, 25);
        }
        //更新额度
        $sys = Db::name('sys_config')->find();
        $sjdz = $sys['sjdz'];
        $xjzd = $sys['xjzd'];
        $quota = Db::name('user')->where(array('id' => $order['uid']))->value('quota');
        $quota = ($quota + $sjdz) > $xjzd ? $xjzd : $quota + $sjdz;
        Db::name('user')->where(array('id' => $order['uid']))->update(array('quota' => $quota));
        $re = Db::name('order')->where(array('id' => $order_repay['order_id']))->update($order_data);
        if (!$re) {
            $this->error('更新订单失败', null, 5);
        }
        Log::write("返回值:13212", 'error');
        $this->success('成功');
    }

    //续期
    public function renewal()
    {
        $params = $this->request->param();
        if (empty($params['repay_id'])) {//订单的还款信息
            $this->error('参数错误：repay_id');
        }
        if (empty($params['day'])) {//订单的还款信息
            $this->error('参数错误：day');
        }
        $expect_config = Db::name('expect_config')->find();
        if ($params['day'] < $expect_config['min_num'] || $params['day'] > $expect_config['num']) {
            $this->error('不支持的展期天数');
        }
        $order_repay = Db::name('order_repay')->where(array('id' => $params['repay_id']))->find();
        $order = Db::name('order')->where(array('id' => $order_repay['order_id']))->find();

        if (empty($order_repay) || empty($order)) {
            $this->error('订单不存在', null, 30);
        }

        if(2 == $order_repay['status'] || 4 == $order_repay['status'] || 6 == $order_repay['status'] || 8 == $order_repay['status']){
            $this->error('不满足展期条件');
        }
        $repay_time = date("Y-m-d", $order_repay["repay_time"]);

        $status = $order_repay['status'];
        //计算罚金
        $max_rate = round($order_repay['corpus'] * ($expect_config['max_rate']/100), 2);
        $yj_rate = round($order_repay['corpus'] * ($expect_config['min_rate']/100) * $params['day'], 2);
        $real_rate = $yj_rate > $max_rate ? $max_rate : $yj_rate;
        if (3 == $status) {//逾期续期 展期金额+罚金
            $data['real_amount'] = $real_rate + $order_repay['penalty'];
            $data['status'] = 8;
            $data['update_time'] = time();
            $data['zq_time'] = time();
            $data['zq_amount'] = $data['real_amount'];
        } else {//正常续期 利息
            $data['status'] = 6;
            $data['update_time'] = time();
            $data['real_amount'] = $real_rate;
            $data['zq_amount'] = $data['real_amount'];
            $data['zq_time'] = time();
        }
        $checkBank = Db::name('user_bankcard')->where(array('uid' => $order['uid'], 'fy_status' => 4))
            ->find();
        if (empty($checkBank)) {
            $this->error('请先绑定银行卡', null, 24);
        }
        //还款
        $Fypay = new Fypay();
        $obj = array();
        $obj['TYPE'] = '03';
        $obj['VERSION'] = $Fypay->VERSION;
        $obj['MCHNTCD'] = $Fypay->MCHNTCD;
        $obj['MCHNTORDERID'] = $order_repay['order_code'];
        $obj['USERID'] = $checkBank['user_code'];
        $obj['PROTOCOLNO'] = $checkBank['protocolno'];
        $obj['AMT'] = $data['real_amount'] * 100;
        $obj['BACKURL'] = $Fypay->backurl;
        $obj['USERIP'] = $this->request->ip();
        $sign = $Fypay->setSign($obj);
        $obj['NEEDSENDMSG'] = 0;
        $obj['SIGNTP'] = 'MD5';
        $obj['SIGN'] = $sign;
        $re = $Fypay->orderPay($obj);
        if (!empty($re['ORDERID'])) {
            $updata['pay_order_id'] = $re['ORDERID'];
        }
        if ('0000' != $re['RESPONSECODE'] || $re['AMT'] != $obj['AMT']) {
            $updata['pay_status'] = 3;
            $re = Db::name('order_repay')->where(array('id' => $params['repay_id']))->update($updata);
            if (!$re) {
                $this->error('更新数据失败', null, 20);
            }
            $this->error('支付失败：' . $re['RESPONSEMSG'], null, 34);
        }

        $re = Db::name('order_repay')->where(array('id' => $params['repay_id']))->update($data);
        if (!$re) {
            $this->error('更新订单追踪失败', null, 4);
        }

        $updata['pay_status'] = 2;
        $re = Db::name('order_repay')->where(array('id' => $params['repay_id']))->update($updata);
        if (!$re) {
            $this->error('支付成功更新数据失败', null, 25);
        }
        //order 变成正常状态
        $order_data['rollnum'] = $order['rollnum'] + 1;
        $order_data['status'] = 8;
        $re = Db::name('order')->where(array('id' => $order_repay['order_id']))->update($order_data);
        if (!$re) {
            $this->error('更新订单失败', null, 5);
        }
        if(empty($order_repay['repay_time'])){
            $today = Timeutil::todayTime();
        }else{
            $today = $order_repay['repay_time'];
        }
        $repay_time = $today + $params['day'] * 24 * 60 * 60;
        $re = $this->generateOrderRepay($order_repay['order_id'], $repay_time);
        if (!$re) {
            $this->error('更新订单追踪失败', null, 6);
        }
        $this->success('成功');
    }

    public function generateOrderRepay($order_id, $repay_time)
    {
        $where['id'] = $order_id;
        $order = Db::name('order')->where($where)->find();
        //更新order starttime endtime  --begin
        $time_data['endtime'] = $repay_time;
        Db::name('order')->where($where)->update($time_data);
        //更新order starttime endtime  --end
        $data['repay_time'] = $repay_time;
        $data['total_amount'] = $order['pay'];
        $data['corpus'] = $order['amount'];//本金
        $data['accrual'] = $order['cost'];//利息
        $data['status'] = 1;
        $data['order_id'] = $order_id;
        $data['create_time'] = time();
        $data['update_time'] = time();
        $data['order_code'] = Random::uuid();
        $re = Db::name('order_repay')->insert($data);
        if (!$re) {
            return false;
        }

        return true;
    }
}

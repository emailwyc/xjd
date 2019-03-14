<?php

namespace app\api\controller\client;

use app\common\controller\Api;
use fast\Random;
use think\Db;
use think\Log;
use xjd\util\Credit;
use xjd\util\ExportExcel;
use xjd\util\Fypay;
use xjd\util\Timeutil;

/**
 * 富友支付
 */
class Fuypay extends Api {
    //如果$noNeedLogin为空表示所有接口都需要登录才能请求
    //如果$noNeedRight为空表示所有接口都需要验证权限才能请求
    //如果接口已经设置无需登录,那也就无需鉴权了
    // 无需登录的接口,*表示全部
    protected $noNeedLogin = ['*'];
    // 无需鉴权的接口,*表示全部
    protected $noNeedRight = ['*'];
   // private   $VERSION     = '1.0';
   // private   $msg         = 'mjrzpnqqfc7xxs5sl4m595tsk7iu5g3l';//商户密钥
   // private   $MCHNTCD     = '0001000F2004430';//商户号
    private   $VERSION;
    private   $msg;
    private   $MCHNTCD;
    private   $IDTYPE      = 0;
    private   $fypay;

    public function _initialize() {
        parent::_initialize();
        $this->fypay = new Fypay();
        $this->VERSION = $this->fypay->VERSION;
        $this->msg = $this->fypay->msg;
        $this->MCHNTCD = $this->fypay->MCHNTCD;
    }

    public function backurl() {
        $params = $this->request->param();
        Log::write(__FUNCTION__.print_r($params, true), 'error');
        //校验签名
        /*
        $info = $params['TYPE'].'|'.$params['VERSION'].'|'.$params['RESPONSECODE'].'|'.$params['MCHNTCD'].'|'
                .$params['MCHNTORDERID'].'|'
                .$params['ORDERID'].'|'.$params['AMT'].'|'.$params['BANKCARD'].'|'.$this->fypay->msg;
        $md5Info = md5($info);
        if ($md5Info != $params['SIGN']) {
            $this->error('签名验证失败');
        }
        $order_repay = Db::name('order_repay')->where(array('order_code' => $params['MCHNTORDERID']))->find();
        //校验金额是否一致
        if ($order_repay['real_amount'] != $params['AMT'] || '0000' != $params['RESPONSECODE']) {
            //更新订单为失败状态
            Db::name('order_repay')->where(array('order_code' => $params['MCHNTORDERID']))->update(
                array('status' => 3)
            );
        }
        Db::name('order_repay')->where(array('order_code' => $params['MCHNTORDERID']))->update(array('status' => 2));
        */

        return 200;
    }

    public function notify_success() {
        //http://47.104.69.18/index.php/fypay/notify
        $params = $this->request->param();
        Log::write(__FUNCTION__.': params='.print_r($params, true), 'error');
        $code = $params['orderno'];
        $accntno = $params['accntno'];
        $bankcard = Db::name('user_bankcard')->where(array('cardid' => $accntno))->find();
        $order = Db::name('order')->where(array('fy_code' => $code))->find();
        if (empty($order) || empty($bankcard)) {
            return 2;//订单不存在
        }
        $real_amount = round(($order['pay'] - $order['cost']) * 100,2);
        Log::write(__FUNCTION__.': real_amount='.$real_amount,'error');
        if ($real_amount != $params['amt']) {
            Log::write(__FUNCTION__.': 金额不符合','error');
            return 3;//金额不符合
        }
        $sign = $this->fypay->mchntcd.'|'.$this->fypay->mchntkey.'|'.$code.'|'.$order['fk_time'].'|'.$accntno.'|'
                .$params['amt'];
        Log::write(__FUNCTION__.': sign='.$sign,'error');
        //$sign = strtoupper(md5($sign));
        $sign = md5($sign);
        Log::write(__FUNCTION__.': sign2='.$sign,'error');
        if ($sign != $params['mac']) {
            Log::write(__FUNCTION__.': 验证签名错误','error');
            return 4;//验证签名错误
        }
        $data['status'] = 8;
        $data['fuorderno'] = $params['fuorderno'];
        $re_update = Db::name('order')->where(array('fy_code' => $code))->update($data);
        if (false === $re_update) {
            return 5;//更新数据失败
        }
        $this->generateOrderRepay($order['id']);
        //记录日志
        $desc = '放款成功';
        $sh_log = Db::name('sh_log')->where(array('desc' => $desc, 'order_id' => $order['id']))->find();
        if (empty($sh_log)) {
            $sh['order_id'] = $order['id'];
            $sh['order_status'] = $order['status'];
            $sh['sh_result'] = 1;
            $sh['desc'] = $desc;
            $sh['create_time'] = time();
            $sh['review'] = 3;
            Db::name('sh_log')->insert($sh);
        }

        return 1;
    }

    public function notify_fail() {
        //http://47.104.69.18/index.php/fypay/refund
        // TODO: 2019/1/15  有没有交易查询接口，处理交易异常情况
        $params = $this->request->param();
        Log::write(__FUNCTION__.': params='.print_r($params, true), 'error');
        $code = $params['orderno'];
        $accntno = $params['accntno'];
        $bankcard = Db::name('user_bankcard')->where(array('cardid' => $accntno))->find();
        $order = Db::name('order')->where(array('fy_code' => $code))->find();
        if (empty($order) || empty($bankcard)) {
            return 2;//订单不存在
        }
        $real_amount = round(($order['pay'] - $order['cost']) * 100,2);
        Log::write(__FUNCTION__.': real_amount='.$real_amount,'error');
        if ($real_amount != $params['amt']) {
            Log::write(__FUNCTION__.': 金额不符合','error');
            return 3;//金额不符合
        }
        $sign = $this->fypay->mchntcd.'|'.$this->fypay->mchntkey.'|'.$code.'|'.$order['fk_time'].'|'.$accntno.'|'
                .$params['amt'];
        Log::write(__FUNCTION__.': sign='.$sign,'error');
        //$sign = strtoupper(md5($sign));
        $sign = md5($sign);
        Log::write(__FUNCTION__.': sign2='.$sign,'error');
        if ($sign != $params['mac']) {
            Log::write(__FUNCTION__.': 验证签名错误','error');
            return 4;//验证签名错误
        }
        $data['status'] = 16;
        $data['futporderno'] = $params['futporderno'];
        $data['fuorderno'] = $params['fuorderno'];
        $re_update = Db::name('order')->where(array('fy_code' => $code))->update($data);
        if (false === $re_update) {
            return 5;//更新数据失败
        }
        //记录日志
        $desc = '放款失败';
        $sh_log = Db::name('sh_log')->where(array('desc' => $desc, 'order_id' => $order['id']))->find();
        if (empty($sh_log)) {
            $sh['order_id'] = $order['id'];
            $sh['order_status'] = $order['status'];
            $sh['sh_result'] = 2;
            $sh['desc'] = $desc;
            $sh['create_time'] = time();
            $sh['review'] = 3;
            Db::name('sh_log')->insert($sh);
        }

        return 1;
    }

    public function generateOrderRepay($order_id) {
        $repay_time = Timeutil::todayTime();//需要加上还款周期
        $where['id'] = $order_id;
        $order = Db::name('order')->where($where)->find();
        //更新order starttime endtime  --begin
        $time_data['starttime'] = $repay_time;
        if($order['cycle'] < 1 || empty($order['cycle'])){//不应该出现这种情况
            $order['cycle'] = 1;
        }
//        $time_data['endtime'] = $repay_time + ($order['cycle']-1) * 24 * 60 * 60;
        $time_data['endtime'] = $repay_time + $order['cycle'] * 86400-1;
        Db::name('order')->where($where)->update($time_data);
        //更新order starttime endtime  --end
        $data['repay_time'] = $repay_time + $order['cycle'] * 86400-1;
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

    //发送短信验证码接口
    public function bindMsg() {
        $params = $this->request->param();
        $need_params = array('uid', 'cardno', 'mobile', 'bank', 'province', 'city');
        $check = $this->params_verify($params, $need_params);
        if (!empty($check)) {
            $this->error('参数错误：'.$check);
        }
        $user_info = Db::name('user_info')->where(array('uid' => $params['uid']))->find();
        if (empty($user_info)) {
            $this->error('参数错误111：uid');
        }
        $code = Random::uuid();
        //一个人只能绑定一张卡
        $checkBank = Db::name('user_bankcard')->where(array('uid' => $params['uid'], 'fy_status' => 4))
                       ->find();
        if (!empty($checkBank)) {
            $this->error('该用户已绑定过银行卡', null, 24);
        }
        $bankcard = Db::name('user_bankcard')->where(array('uid' => $params['uid'], 'cardid' => $params['cardno']))
                      ->find();
        if (!empty($bankcard)) {
            if (4 == $bankcard['fy_status']) {
                $this->error('该银行卡已绑定，请先解绑', null, 10);
            } elseif (2 == $bankcard['fy_status']) {
                $this->success('成功');
            } else {//重新申请
                $re = Db::name('user_bankcard')->where(array('id' => $bankcard['id']))->update(array('fy_status' => 1,'code'=>$code));
                if ($re === false) {
                    $this->error('生成订单失败', null, 2);
                }
            }
            $user_code = $bankcard['user_code'];
        } else {
            $data['cardid'] = $params['cardno'];
            $data['uid'] = $params['uid'];
            $data['user_code'] = Random::uuid();
            $data['bank'] = $params['bank'];
            $data['province'] = $params['province'];
            $data['city'] = $params['city'];
            $data['createtime'] = time();
            $data['updatetime'] = time();
            $data['fy_status'] = 1;
            $data['mobile'] = $params['mobile'];
            $data['code'] = $code;
            $re = Db::name('user_bankcard')->insert($data);
            if (!$re) {
                $this->error('生成订单失败', null, 3);
            }
            $user_code = $data['user_code'];
        }
        $obj = array();
        $obj['VERSION'] = $this->VERSION;
        $obj['MCHNTSSN'] = $code;
        $obj['MCHNTCD'] = $this->MCHNTCD;
        $obj['USERID'] = $user_code;
        $obj['ACCOUNT'] = $user_info['realname'];//银行卡账户名持卡人
        $obj['CARDNO'] = $params['cardno'];//银行卡号
        $obj['IDTYPE'] = $this->IDTYPE;
        $obj['IDCARD'] = $user_info['cardid'];//身份证号
        $obj['MOBILENO'] = $params['mobile'];//手机号
        $sign = $this->setSign($obj);
        $obj['SIGN'] = $sign;
        $obj['TRADEDATE'] = date("Ymd");
        $re = $this->fypay->bindMsg($obj);
        if ('0000' != $re['RESPONSECODE']) {//不能存在重复申请的情况，需要解绑后才能重新绑定
            $re = Db::name('user_bankcard')->where(array('code' => $code))->update(array('fy_status' => 3));
            if (!$re) {
                $this->error('更新数据失败', null, 20);
            }
            $this->error('绑定失败：'.$re['RESPONSEMSG'], null, 4);
        }
        $re = Db::name('user_bankcard')->where(array('code' => $code))->update(array('fy_status' => 2));
        if (!$re) {
            $this->error('更新数据失败', null, 20);
        }
        $this->success('成功');
    }

    //协议卡绑定
    public function bindCommit() {
        $params = $this->request->param();
        //传卡号，校验卡
        $need_params = array('uid', 'cardno', 'msgcode');
        $check = $this->params_verify($params, $need_params);
        if (!empty($check)) {
            $this->error('参数错误：'.$check);
        }
        $uid = $params["uid"];
        $user_info = Db::name('user_info')->where(array('uid' => $params['uid']))->find();
        if (empty($user_info)) {
            $this->error('参数错误：uid');
        }
        $bankcard = Db::name('user_bankcard')->where(array('uid' => $params['uid'], 'cardid' => $params['cardno']))
                      ->find();
        if (empty($bankcard)) {
            $this->error('该卡号不存在');
        }
        //必须处于短信成功后
        if (4 == $bankcard['fy_status']) {
            $this->error('该银行卡已绑定，请先解绑', null, 10);
        } elseif (2 == $bankcard['fy_status']) {
        } else {
            $this->error('请先短信验证', null, 2);
        }
        $code = $bankcard['code'];
        $obj = array();
        $obj['VERSION'] = $this->VERSION;
        $obj['MCHNTSSN'] = $code;
        $obj['MCHNTCD'] = $this->MCHNTCD;
        $obj['USERID'] = $bankcard['user_code'];
        $obj['ACCOUNT'] = $user_info['realname'];//银行卡账户名持卡人
        $obj['CARDNO'] = $params['cardno'];//银行卡号
        $obj['IDTYPE'] = $this->IDTYPE;
        $obj['IDCARD'] = $user_info['cardid'];//身份证号
        $obj['MOBILENO'] = $bankcard['mobile'];//手机号
        $obj['MSGCODE '] = $params['msgcode'];//手机号
        $sign = $this->setSign($obj);
        $obj['SIGN'] = $sign;
        $obj['TRADEDATE'] = date("Ymd");
        $re = $this->fypay->bindCommit($obj);
        if ('0000' != $re['RESPONSECODE']) {//不能存在重复申请的情况，需要解绑后才能重新绑定
            $re = Db::name('user_bankcard')->where(array('code' => $code))->update(array('fy_status' => 5));
            if (!$re) {
                $this->error('更新数据失败', null, 20);
            }
            $this->error('绑定失败：'.$re['RESPONSEMSG'], null, 4);
        }
        $protocolno = $re['PROTOCOLNO'];
        $re = Db::name('user_bankcard')->where(array('code' => $code))->update(
            array('fy_status' => 4, 'protocolno' => $protocolno)
        );
        if (!$re) {
            $this->error('更新数据失败', null, 20);
        }
        //绑卡成功 通过银行卡验证
        $re = Db::name("user_authinfo")->where(["uid" => $uid, "code" => "yhk"])->find();
        if ($re) {
            Db::name("user_authinfo")->where(["uid" => $uid, "code" => "yhk"])->update(
                ["status" => 2, "updatetime" => time()]
            );
        } else {
            $data['uid'] = $uid;
            $data['code'] = 'yhk';
            $data['createtime'] = time();
            $data['updatetime'] = time();
            $data['status'] = 2;
            Db::name("user_authinfo")->insert($data);
        }
        $this->success('成功');
    }

    //协议解绑
    public function unbind() {
        $params = $this->request->param();
        $need_params = array('uid', 'cardno');
        $check = $this->params_verify($params, $need_params);
        if (!empty($check)) {
            $this->error('参数错误：'.$check);
        }
        $user_info = Db::name('user_info')->where(array('uid' => $params['uid']))->find();
        if (empty($user_info)) {
            $this->error('参数错误：uid');
        }
        $bankcard = Db::name('user_bankcard')->where(array('uid' => $params['uid'], 'cardid' => $params['cardno']))
                      ->find();
        if (empty($bankcard)) {
            $this->error('该卡号不存在');
        }
        //必须处于短信成功后
        if (6 == $bankcard['fy_status']) {
            $this->error('该银行卡已解绑', null, 10);
        } elseif (4 == $bankcard['fy_status']) {
        } else {
            $this->error('该银行卡未绑定', null, 2);
        }
        $protocolno = $bankcard['protocolno'];
        $obj = array();
        $obj['VERSION'] = $this->VERSION;
        $obj['MCHNTCD'] = $this->MCHNTCD;
        $obj['USERID'] = $bankcard['user_code'];
        $obj['PROTOCOLNO'] = $protocolno;//协议号
        $sign = $this->setSign($obj);
        $obj['SIGN'] = $sign;
        $re = $this->fypay->unbind($obj);
        if ('0000' != $re['RESPONSECODE']) {//不能存在重复申请的情况，需要解绑后才能重新绑定
            $re = Db::name('user_bankcard')->where(array('id' => $bankcard['id']))->update(array('fy_status' => 7));
            if (!$re) {
                $this->error('更新数据失败', null, 20);
            }
            $this->error('解绑失败：'.$re['RESPONSEMSG'], null, 4);
        }
        $re = Db::name('user_bankcard')->where(array('id' => $bankcard['id']))->update(
            array('fy_status' => 6)
        );
        if (!$re) {
            $this->error('更新数据失败', null, 20);
        }
        $this->success('成功');
    }

    public function setSign($data) {
        /* $info2 = $data['VERSION'].'|'.$data['MCHNTSSN'].'|'.$data['MCHNTCD'].'|'.$data['USERID'].'|'.$data['ACCOUNT']
                  .'|'
                  .$data['CARDNO'].'|'.$data['IDTYPE'].'|'.$data['IDCARD'].'|'.$data['MOBILENO'].'|'.$key;
        */
        $info = '';
        foreach ($data as $key => $value) {
            $info = $info.$value.'|';
        }
        $info = $info.$this->msg;
        Log::write(__FUNCTION__.': info='.$info, 'error');
        $re = md5($info);

        return $re;
    }
}

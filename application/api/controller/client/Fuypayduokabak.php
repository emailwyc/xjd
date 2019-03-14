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
class Fuypayduokabak extends Api {
    //如果$noNeedLogin为空表示所有接口都需要登录才能请求
    //如果$noNeedRight为空表示所有接口都需要验证权限才能请求
    //如果接口已经设置无需登录,那也就无需鉴权了
    // 无需登录的接口,*表示全部
    protected $noNeedLogin = ['*'];
    // 无需鉴权的接口,*表示全部
    protected $noNeedRight = ['*'];
    private   $VERSION     = '1.0';
    private   $msg         = '5old71wihg2tqjug9kkpxnhx9hiujoqj';//商户密钥
    private   $MCHNTCD     = '0002900F0096235';//商户号
    private   $IDTYPE      = 0;
    private   $fypay;

    public function _initialize() {
        parent::_initialize();
        $this->fypay = new Fuypay();
    }

    public function backurl() {
        $params = $this->request->param();
        //校验签名
        //校验金额是否一致
        $this->success('成功', null, 200);
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
            $this->error('参数错误：uid');
        }
        $bankcard = Db::name('user_bankcard')->where(array('uid' => $params['uid'], 'cardid' => $params['cardno']))
                      ->find();
        if (!empty($bankcard)) {
            if (4 == $bankcard['fy_status']) {
                $this->error('该银行卡已绑定，请先解绑', null, 10);
            } elseif (2 == $bankcard['fy_status']) {
                $this->success('成功');
            } else {//重新申请
                $re = Db::name('user_bankcard')->where(array('id' => $bankcard['id']))->update(array('fy_status' => 1));
                if (!$re) {
                    $this->error('生成订单失败', null, 2);
                }
            }
            $code = $bankcard['code'];
        } else {
            $data['cardid'] = $params['cardno'];
            $data['uid'] = $params['uid'];
            $data['bank'] = $params['bank'];
            $data['province'] = $params['province'];
            $data['city'] = $params['city'];
            $data['createtime'] = time();
            $data['updatetime'] = time();
            $data['fy_status'] = 1;
            $data['mobile'] = $params['mobile'];
            $data['code'] = Random::uuid();
            $re = Db::name('user_bankcard')->insert($data);
            if (!$re) {
                $this->error('生成订单失败', null, 3);
            }
            $code = $data['code'];
        }
        $obj = array();
        $obj['VERSION'] = $this->VERSION;
        $obj['MCHNTSSN'] = $code;
        $obj['MCHNTCD'] = $this->MCHNTCD;
        $obj['USERID'] = $params['uid'];
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
        $obj['USERID'] = $params['uid'];
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
        $obj['USERID'] = $params['uid'];
        $obj['PROTOCOLNO'] = $protocolno;//手机号
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

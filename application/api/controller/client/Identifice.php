<?php

namespace app\api\controller\client;

use app\common\controller\Api;
use think\Db;
use think\Log;
use xjd\util\Credit;
use xjd\util\ExportExcel;
use xjd\util\Timeutil;

/**
 * 贷后管理/订单管理
 */
class Identifice extends Api
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

    //运营商mno和淘宝tb
    public function back()
    {
        $params = $this->request->param();
        Log::write(__FUNCTION__ . ': params=' . print_r($params, true), 'error');
        if (empty($params['params'])) {
            $this->error('系统错误');
        }
        $data = $params['params'];
        Log::write(__FUNCTION__ . ': data1=' . $data, 'error');
        $data = urldecode(urldecode($data));
        Log::write(__FUNCTION__ . ': data2=' . $data, 'error');
        $info = json_decode($data, true);
        $code = $info['extraParam'];
        $idcard = $info['certNo'];
        $uid = Db::name('user_info')->where(array('cardid' => $idcard))->value('uid');
        //$expiretime = Db::name('user_authtype')->value('expiretime');
        $where['code'] = $code;
        $where['uid'] = $uid;
        $authinfo = Db::name('user_authinfo')->where($where)->find();
        if ($authinfo) {
            $this->success('成功', $info);//根据透传参数判断是成功还是失败
        }
        $data['code'] = $code;
        $data['uid'] = $uid;
        $data['createtime'] = time();
        $data['updatetime'] = time();
        $data['status'] = 1;
        $re = Db::name('user_authinfo')->insert($data);
        if (!$re) {
            $this->error('保存数据失败', null, 2);
        }
        $this->success('成功', $info);
    }

    //运营商mno和淘宝tb
    public function databack()
    {
        $params = $this->request->param();
        Log::write(__FUNCTION__ . ': params=' . print_r($params, true), 'error');
        //if (empty($params['params'])) {
        //    $this->error('系统错误');
        // }
        if (empty($params)) {
            $this->error('系统错误');
        }
        //$data = $params['params'];
        //Log::write(__FUNCTION__.': data1='.$data, 'error');
        // $data = urldecode(urldecode($data));
        //Log::write(__FUNCTION__.': data2='.$data, 'error');
        $info = $params;
        $dataType = $info['dataType'];
        $certNo = $info['certNo'];
        $name = $info['name'];
        $mobile = $info['mobile'];
        $expiretime = Db::name('user_authtype')->value('expiretime');
        $uid = Db::name("user")->where(array('mobile'=>$mobile))->value('id');
//        $uid = Db::name('user_info')->where(array('cardid' => $certNo))->value('uid');
        $where['code'] = $dataType;
        $where['uid'] = $uid;
        $credit = new  Credit();
        $type = 1;
        if ('mno' == $dataType) {//运营商mno
            $type = 2;
            $cost_type = 5;
            coast(16,$uid,1);
        } elseif ('tb' == $dataType) {//淘宝tb
            $type = 1;
            $cost_type = 6;
            coast(15,$uid,1);
        } else {
            $this->error('获取参数失败');
        }
        $re = $credit->credit_html($type, $name, $certNo, $mobile);
        if (!$re) {//认证失败
            $this->setMessage($uid, $dataType . '_f');
            //$data['status'] = 0;
            $data['updatetime'] = time();
            $re_data['resultCode'] = 'CCOM1000';
            $re_data['resultDesc'] = '成功';
            Log::write(__FUNCTION__ . ': 返回失败结果=' . print_r($re_data, true), 'error');
            return json_encode($re_data);
        } else {
            $this->setMessage($uid, $dataType);
            //$this->setCoast($cost_type);
            //$data['status'] = 2;
            $data['authdata'] = $re;
            $data['updatetime'] = time();
            $data['expiretime'] = time() + $expiretime * 24 * 60 * 60;
        }
        Db::name('user_authinfo')->where($where)->update($data);
        $re_data['resultCode'] = 'CCOM1000';
        $re_data['resultDesc'] = '成功';
        Log::write(__FUNCTION__ . ': 返回成功结果=' . print_r($re_data, true), 'error');
        return json_encode($re_data);
        // $this->success('成功');
    }

    public function credit()
    {
        $params = $this->request->param();
        if (empty($params['order_id'])) {
            $this->error('参数错误：order_id');
        }
        if (empty($params['code'])) {
            $this->error('参数错误：code');
        }
        if ('zxy' == $params['code']) {
            $this->zxy($params['uid']);
        }
        $where['code'] = $params['code'];
        $where['uid'] = Db::name('order')->where(array('id' => $params['order_id']))->value('uid');
        $re = Db::name('user_authinfo')->where($where)->find();
        if (empty($re) || time() > $re['expiretime']) {
            $this->error('获取数据失败,用户未认证');
        }
        $this->success('获取数据成功', $re);
    }

    public function credit_user()
    {
        $params = $this->request->param();
        if (empty($params['uid'])) {
            $this->error('参数错误：uid');
        }
        if (empty($params['code'])) {
            $this->error('参数错误：code');
        }
        if ('zxy' == $params['code']) {
            $this->zxy($params['uid']);
        }
        $where['code'] = $params['code'];
        $where['uid'] = $params['uid'];
        $re = Db::name('user_authinfo')->where($where)->find();
        if (empty($re) || time() > $re['expiretime']) {
            $this->error('获取数据失败,用户未认证');
        }
        $this->success('获取数据成功', $re);
    }

    public function zxy($uid)
    {
        //淘宝的话，顺便查询资信云
        $where['code'] = 'zxy';
        $where['uid'] = $uid;
        $expiretime = Db::name('user_authtype')->value('expiretime');
        $re = Db::name('user_authinfo')->where($where)->find();
        if (empty($re) || time() > $re['expiretime']) {
            $user_info = Db::name('user_info')->where(array('uid' => $uid))->find();
            $mobile = Db::name('user')->where(array('id' => $uid))->value('mobile');
            $credit = new  Credit();
            $re = $credit->credit_html(3, $user_info['realname'], $user_info['cardid'], $mobile);
            if (!$re) {//认证失败
                $data['status'] = 0;
                $data['updatetime'] = time();
                $this->setMessage($uid, 'zxy_f');
            } else {
                $this->setMessage($uid, 'zxy');
                //$this->setCoast(7);
                $data['status'] = 2;
                $data['authdata'] = $re;
                $data['updatetime'] = time();
                $data['expiretime'] = time() + $expiretime * 24 * 60 * 60;
            }
            Db::name('user_authinfo')->where($where)->update($data);
        }
    }

    //多头
    public function doutou()
    {
        $params = $this->request->param();
        if (empty($params['order_id'])) {
            $this->error('参数错误：order_id');
        }
        $result['content'] = Db::name('duotou')->where(array('order_id' => $params['order_id']))->value('content');
        if (!empty($result['content'])) {
            $this->success('保存多头数据成功', $result);
        }
        //懒得连查了
        $uid = Db::name('order')->where(array('id' => $params['order_id']))->value('uid');
        $userInfo = Db::name('user_info')->where(array('uid' => $uid))->find();
        $realname = $userInfo['realname'];
        $cardid = $userInfo['cardid'];
        $credit = new Credit();
        $re = $credit->duotou($realname, $cardid);
        $this->setMessage($uid, 'duotou');
        //$this->setCoast(8);
        $data['content'] = json_encode($re);
        $data['order_id'] = $params['order_id'];
        $result = Db::name('duotou')->insert($data);
        if (!$result) {
            $this->error('保存多头数据失败');
        }
        $this->success('保存多头数据成功', $data['content']);
    }


    public function device()
    {
        $params = $this->request->param();
        if (empty($params['platform']) || ('h5' != $params['platform'] && 'android' != $params['platform'] && 'ios' != $params['platform'])) {
            $this->error('参数错误：platform');
        }
        if (empty($params['tokenKey'])) {
            $this->error('参数错误：tokenKey');
        }
        if (empty($params['uid'])) {
            $this->error('参数错误：uid');
        }
        coast(17,$params['uid'],1);
        $content = '';
        $credit = new Credit();
        $num = 0;
        while (true){
            if($num >= 3){
               break;
            }
            $re = $credit->device($params['platform'], $params['tokenKey']);
            if ($re) {
                $content = $re;
                break;
            }else{
                sleep(1);
                $num = $num + 1;
            }
        }
        $where['uid'] = $params['uid'];
        $where['platform'] = $params['platform'];
        $where['tokenKey'] = $params['tokenKey'];
        $info = Db::name('device')->where($where)->find();
        if (!empty($info)) {
            $re = Db::name('device')->where($where)->update(array('content' => $content, "updatetime" => time()));
            if ($re === false) {
                $this->error('更新数据失败');
            }
        } else {
            $where['content'] = $content;
            $where['createtime'] = time();
            $where['updatetime'] = time();
            $re = Db::name('device')->insert($where);
            if (!$re) {
                $this->error('保存数据失败');
            }
        }
        $this->success('成功');
    }


    public function sixcontacts(){
        $params = $this->request->param();
        if (empty($params['id'])) {
            $this->error('参数错误：id');
        }

        $where['id'] = $params['id'];
        $order = Db::name('order')->where($where)->find();
        if (empty($order['uid'])) {
            $this->error('获取信息失败');
        }
        $info['id'] = $order['uid'];
        $user = Db::name('user')->where($info)->find();
        if (empty($user['mobile'])) {
            $this->error('获取信息失败');
        }
        $info_two['uid'] = $order['uid'];;
        $user_info = Db::name('user_info')->where($info_two)->find();

        if (empty($user_info)) {
            $this->error('获取信息失败');
        }
        $credit = new  Credit();

        $re = $credit->credit('2', $user_info['realname'], $user_info['cardid'], $user['mobile']);
        $data = json_decode($re,true);
        $mnoCommonlyConnectMobiles = $data['data']['mnoCommonlyConnectMobiles'];

        if($mnoCommonlyConnectMobiles){
            $info_three['uid'] = $order['uid'];
            $txlinfo = Db::name("user_telebook")->where($info_three)->select();
            foreach($mnoCommonlyConnectMobiles as $key => $val){
                $mnoCommonlyConnectMobiles[$key]['linkname'] = '暂无';

                foreach($txlinfo as $k=>$v){

                    if($val['mobile'] == $v['phone']){

                        $mnoCommonlyConnectMobiles[$key]['linkname'] = $v['linkname'];
                    }
                }

            }
        }
        echo "<pre>";
//        print_r($mnoCommonlyConnectMobiles);die;
    }

    public function reprot(){
        $params = $this->request->param();
        $flow = $params['flowNo'];
        if(empty($flow)){
            return '';
        }
        $report = Db::name("credit_report")->where(array('flowno' => $flow))->find();
        echo $report['result_content'];
    }
}

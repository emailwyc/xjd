<?php

namespace app\api\controller\client;

use app\common\controller\Api;
use fast\Random;
use think\Db;
use think\Log;
use xjd\util\Credit;
use xjd\util\ExportExcel;
use xjd\util\Fypay;
use xjd\util\Getarea;
use xjd\util\Sms;
use xjd\util\Timeutil;

/**
 *  提现
 */
class Withdraw extends Api
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

    //申请提现
    public function draw()
    {
        $params = $this->request->param();
        if (empty($params['uid'])) {
            $this->error('参数错误：uid');
        }
        if (empty($params['amount'])) {
            $this->error('参数错误：amount');
        }
        if (empty($params['cycle'])) {//周期
            $this->error('参数错误：cycle');
        }
        $uid = $params['uid'];
        //校验
        //$this->checkAuth($uid, $params);
        //只有一笔订单
        $where['uid'] = $uid;
        $order = Db::name('order')->where($where)->where(array('status' => array('neq', '9,14,15')))->find();
        $sql = Db::name('order')->getLastSql();
        Log::write(__FUNCTION__ . ': sql=' . $sql, 'error');
        if ($order) {
            // $this->setMessage($uid, 'c_have');
            $this->error('已有借款', null, 7);
        }
        //创建订单
        $order_id = $this->createOrder($params);
        if (!$order_id) {
            $this->error('订单创建异常', null, 8);
        }
        //机审
        //$this->autoReview($params['uid'], $order_id);
        $this->decisionCheck($params['uid'], $order_id);
        //风险体检报告
        //$this->creditCheck($params['uid'], $order_id);
        //自动分配分配审核员
        $this->zd_allot($order_id, $uid);
        //测试
        //$this->generateOrderRepay($order_id);
        //手动分配 ，结束 管理后台分配
        $this->success("提交成功,等待审核");
    }

    //测试
    public function generateOrderRepay($order_id)
    {
        $repay_time = Timeutil::todayTime();
        $where['id'] = $order_id;
        $order = Db::name('order')->where($where)->find();
        //更新order starttime endtime  --begin
        $time_data['starttime'] = $repay_time;
        $time_data['endtime'] = $repay_time + $order['cycle'] * 24 * 60 * 60;
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

    public function zd_allot($order_id, $uid)
    {
        //审核人员
        $data['allotdcid'] = $this->getMin(1);
        if (empty($data['allotdcid'])) {
            $this->setMessage($uid, 'shy_f');
            $this->error('分配审核员失败', null, 45);
        }
        //财务人员
        $data['allotfcid'] = $this->getMin(2);
        if (empty($data['allotdcid'])) {
            $this->setMessage($uid, 'shy_f');
            $this->error('分配财务人员失败', null, 46);
        }
        $re = Db::name('order')->where(array('id' => $order_id))->update($data);
        if ($re) {
            $this->setAllot($data['allotdcid']);
            $this->setAllot($data['allotfcid']);
        } else {
            $this->setMessage($uid, 'shy_f');
            $this->error('分配审核员失败', null, 15);
        }
    }

    public function setAllot($uid)
    {
        $info = Db::name('allot')->where(array('uid' => $uid))->find();
        if (empty($info)) {
            $data['uid'] = $uid;
            $data['num'] = 1;
            $data['allot_time'] = time();
            Db::name('allot')->insert($data);
        }
        $data['num'] = $info['num'] + 1;
        $data['allot_time'] = time();
        Db::name('allot')->where(array('uid' => $uid))->update($data);
    }

    public function getMin($type)
    {
        // TODO 待优化，不允许使用自增主键ID
        //11  审核员组  $type =1
         //10  放款组   $type=2
        if(1 == $type){
            $grop_id = 11;
        }else{
            $grop_id = 10;
        }
        //先查看是否有用户未分配过
        $groupinfo = Db::name('auth_group_access')->alias('aga')
            ->where("NOT EXISTS (SELECT  1 from d_allot where UID = aga.UID AND type =$type)")
            ->where(array('aga.group_id' => $grop_id))
            ->find();
        $sql = Db::name('auth_group_access')->getLastSql();
        Log::write(__FUNCTION__ . '：sql=' . $sql, 'error');
        if (!empty($groupinfo)) {
            return $groupinfo['uid'];
        }
        $where['type'] = $type;
        $min_num = Db::name('allot')->where($where)->min('num');
        $info = Db::name('allot')
            ->where($where)
            ->where(array('num' => $min_num))
            ->group('allot_time')
            ->having('allot_time = MIN(allot_time)')
            ->find();

        return $info['uid'];
    }

    public function autoReview($uid, $order_id)
    {
        //sh_log 数据
        $examine = Db::name('examine_config')->find();
        $order = Db::name('order')->where(array('id' => $order_id))->find();
        $userInfo = Db::name('user_info')->where(array('uid' => $uid))->find();
        $data['order_id'] = $order_id;
        $data['order_status'] = $order['status'];
        $data['review'] = 1;
        $data['create_time'] = time();
        //年龄
        $min_age = $examine['min_age'];
        $max_age = $examine['max_age'];
        $age = $this->getAgeByID($userInfo['cardid']);
        if ($age < $min_age || $age > $max_age) {
            $data['sh_result'] = 2;
            $data['desc'] = '年龄不达标';
            $this->setMessage($uid, 'age_f');
            Db::name('sh_log')->insert($data);
            Db::name('order')->where(array('id' => $order_id))->update(array('status' => 12));
            $this->error('年龄不达标', null, 10);
        } else {
            $data['sh_result'] = 1;
            $data['desc'] = '年龄达标';
            Db::name('sh_log')->insert($data);
        }
        //芝麻信用积分
        $score = $userInfo['score'];
        if ($examine['score'] > $score) {
            $data['sh_result'] = 2;
            $data['desc'] = '芝麻积分不达标';
            $this->setMessage($uid, 'zm_f');
            Db::name('sh_log')->insert($data);
            Db::name('order')->where(array('id' => $order_id))->update(array('status' => 12));
            $this->error('芝麻积分不达标', null, 11);
        } else {
            $data['sh_result'] = 1;
            $data['desc'] = '芝麻积分达标';
            Db::name('sh_log')->insert($data);
        }
        // TODO 失信  13
        // TODO 地区 身份证的前六位
        $area = $index = substr($userInfo['cardid'], 0, 6);
        if (strpos($examine['area'], $area) !== false) {//包含
            $data['sh_result'] = 2;
            $data['desc'] = '所在地区不达标';
            $this->setMessage($uid, 'area_f');
            Db::name('sh_log')->insert($data);
            Db::name('order')->where(array('id' => $order_id))->update(array('status' => 12));
            $this->error('所在地区不达标', null, 12);
        } else {//不包含
            $data['sh_result'] = 1;
            $data['desc'] = '所在地区达标';
            Db::name('sh_log')->insert($data);
        }
        // TODO 是否强制获取 14
        Db::name('order')->where(array('id' => $order_id))->update(array('status' => 13));
    }

    public function createOrder($params)
    {
        $rate = Db::name('sys_config')->value('jkzd');
        $data['code'] = Timeutil::msectime() . $params['uid'];//时间戳加uuid
        $data['uid'] = $params['uid'];
        $data['amount'] = $params['amount'];
//        $data['pay'] = $params['amount'] + $params['amount'] * $rate * ($params['cycle'] - 1);
        $data['pay'] = $params['amount'];
        $data['cost'] = $params['amount'] * ($params['cycle'] - 1) * $rate;
        $data['createtime'] = time();
        $data['status'] = 0;
        $data['cycle'] = $params['cycle'];
        $data['type'] = 1;//首借
        $count = Db::name('order')->where(array('uid' => $params['uid'], 'status' => 9))->count();
        if ($count > 0) {
            $data['type'] = 2;//续借
            $data['status'] = 6;
        }
        $re = Db::name('order')->insertGetId($data);
        if (!$re) {
            return false;
        }

        return $re;
    }

    public function checkAuth($uid, $params = array())
    {
        // TODO:  金额校验
        if (!empty($params['amount'])) {
            $quota = $this->quota($uid);
            if ($params['amount'] != $quota) {
                //$this->setMessage($uid, 'c_amount');
                $this->error('提现金额错误', null, 20);
            }
        }
        //身份证实名校验
        $where['uid'] = $uid;
        $userInfo = Db::name('user_info')->where($where)->find();
        if (empty($userInfo['cardid'])) {
            // $this->setMessage($uid, 'c_idcard');
            $this->error('实名认证失败', null, 2);
        }
        //银行卡
        $bankcard = Db::name('user_bankcard')->where($where)->find();
        if (empty($bankcard['cardid'])) {
            //$this->setMessage($uid, 'c_yhk');
            $this->error('银行卡信息认证失败', null, 3);
        }
        //只有一笔订单
        $order = Db::name('order')->where($where)->where(array('status' => array('neq', '9,14,15')))->find();
        $sql = Db::name('order')->getLastSql();
        Log::write(__FUNCTION__ . ': sql=' . $sql, 'error');
        if ($order) {
            //$this->setMessage($uid, 'c_have');
            $this->error('已有借款', null, 7);
        }
        //征信认证
        $where['code'] = array('in', 'mno,tb');
        $authinfo = Db::name('user_authinfo')->where($where)->select();
        $sql2 = Db::name('user_authinfo')->getLastSql();
        Log::write(__FUNCTION__ . ': sql2=' . $sql2, 'error');
        Log::write(__FUNCTION__ . ': authinfo=' . print_r($authinfo, true), 'error');
        if (empty($authinfo) || count($authinfo) < 2) {
            //$this->setMessage($uid, 'c_zx');
            $this->error('征信认证失败', null, 31);
        }
        $codes = array();
        foreach ($authinfo as $data) {
            $code = $data['code'];
            Log::write(__FUNCTION__ . ': code=' . $code, 'error');
            if ($code == "zxy") {
                continue;
            }
            /*if (!in_array($code, $codes)) {
                $codes[] = $code;
            }*/
            $re_code['mno'] = 4;
            $re_code['tb'] = 5;
            $re_code['zxy'] = 6;
//            if (empty($data['authdata']) || 2 != $data['status'] || time() > $data['expiretime']) {
            if (2 != $data['status']) {
                //$this->setMessage($uid, $code.'_f');
                $this->error('征信认证失败', null, $re_code[$code]);
            }
        }
        /* Log::write(__FUNCTION__ . ': code=' . print_r($codes,true), 'error');
         if (!in_array('mno', $codes)) {
             $this->setMessage($uid, 'mno_f');
             $this->error('征信认证失败', null, 4);
         } elseif (!in_array('tb', $codes)) {
             $this->setMessage($uid, 'tb_f');
             $this->error('征信认证失败', null, 5);
         } else {

         }*/
//        elseif (!in_array('zxy', $codes)) {
//            $this->setMessage($uid,'zxy_f');
//            $this->error('征信认证失败', null, 6);
//        }
    }

    public function quota($uid)
    {
        //用户完成的订单数
        $quota = Db::name('user')->where(array('id' => $uid))->value('quota');

        return $quota;
    }

    function getAgeByID($id)
    {
        //过了这年的生日才算多了1周岁
        if (empty($id)) {
            return '';
        }
        $date = strtotime(substr($id, 6, 8));
        //获得出生年月日的时间戳
        $today = strtotime('today');
        //获得今日的时间戳 111cn.net
        $diff = floor(($today - $date) / 86400 / 365);
        //得到两个日期相差的大体年数
        //strtotime加上这个年数后得到那日的时间戳后与今日的时间戳相比
        $age = strtotime(substr($id, 6, 8) . ' +' . $diff . 'years') > $today ? ($diff + 1) : $diff;

        return $age;
    }

    //计费点
    public function point()
    {
        //用户完成的订单数
        $params = $this->request->param();
        if (empty($params['uid'])) {
            $this->error('参数错误：uid');
        }
        $status = 0;
        if (Db::name("order")->where(["uid" => $params['uid'], "status" => 8])->find()) {
            $status = 1;
//        } elseif (Db::name("order")->where(["uid" => $params["uid"], "status" => array("not in", [9, 11, 12, 14, 15, 16])])->find()) {
        } elseif (Db::name("order")->where(["uid" => $params["uid"], "status" => array("in", [0, 1, 2, 3, 4, 5, 6, 7, 8, 10, 11, 12, 13, 14, 15, 16])])->find()) {
            $status = 2;
        } else {
            $status = 0;
        }
        Log::write('----------------------------' . $status, 'error');
        if ($status == 0) {
            $this->checkAuth($params['uid']);
        }
        Log::write('----------------------------', 'error');
        $quota = Db::name('user')->where(array('id' => $params['uid']))->value('quota');
        if (empty($quota)) {
            $sys = Db::name('sys_config')->find();
            $sql = Db::name('sys_config')->getLastSql();
            Log::write(__FUNCTION__ . ': sql=' . $sql, 'error');
            Log::write(__FUNCTION__ . ': sys=' . print_r($sys, true), 'error');
            if (empty($sys)) {
                $this->error('配置异常');
            }
            $sjqb = $sys['sjqb'];
            $sjdz = $sys['sjdz'];
            $xjzd = $sys['xjzd'];
            $count = Db::name('order')->where(array('uid' => $params['uid'], 'status' => 9))->count();
            $quota = ($sjqb + $sjdz * $count) > $xjzd ? $xjzd : $sjqb + $sjdz * $count;
            Log::write(__FUNCTION__ . ': quota=' . $quota, 'error');
            $quota = round($quota, 2);
            Log::write(__FUNCTION__ . ': quota2=' . $quota, 'error');
            $re_up = Db::name('user')->where(array('id' => $params['uid']))->update(array('quota' => $quota));
            if (!$re_up) {
                $this->error('更新用户额度失败');
            }
        }

        $re['status'] = $status;
        $re['quota'] = $quota;
        $re['cycle'] = 7;// TODO: 2019/1/2  借款周期
        Log::write("数据" . print_r($re, true), 'error');
        $this->success('成功', $re);
    }

    public function trial(){
        $params = $this->request->param();
        $re = array();
        if (empty($params['uid'])) {
            $this->error('参数错误：uid');
        }
        $quota = Db::name('user')->where(array('id' => $params['uid']))->value('quota');
        $sys = Db::name('sys_config')->find();
        if (empty($quota)) {
            if (empty($sys)) {
                $this->error('配置异常');
            }
            $sjqb = $sys['sjqb'];
            $sjdz = $sys['sjdz'];
            $xjzd = $sys['xjzd'];
            $count = Db::name('order')->where(array('uid' => $params['uid'], 'status' => 9))->count();
            $quota = ($sjqb + $sjdz * $count) > $xjzd ? $xjzd : $sjqb + $sjdz * $count;
            $quota = round($quota, 2);
        }
            $re['amount'] = $quota;//借款金额
            $re['day'] = 7;//借款期限
            $rate =  round($quota * 6 *  $sys['jkzd'],2);
            $re['real_amount'] =  round($quota - $rate,2);//到账金额
            $re['bank'] = Db::name('user_bankcard')->where(array('uid' => $params['uid'],'status'=>1,'fy_status'=>4))->value('cardid');
            if(!empty($re['bank'])){
                $re['bank'] = '(****'.substr($re['bank'],-4).')';//银行卡
            }
            $re['rate'] = $rate;//综合服务费
            $re['principal'] = $quota;//应还本金
            $re['yh_rate'] =  $rate;//应还利息
            $re['yh_real_amount'] =  $re['real_amount'];//应还利息
            $this->success('成功', $re);


    }

    public function decisionCheck($uid, $order_id)
    {
        //身份证实名校验
        $where['uid'] = $uid;
        $user = Db::name('user')->where(array('id' => $uid))->find();
        $userInfo = Db::name('user_info')->where($where)->find();
        $deviceInfo = Db::name('device')->where($where)->order('updatetime desc')->find();
        $tokenKey = !empty($deviceInfo['tokenKey']) ? $deviceInfo['tokenKey'] : '';
        $info = array();
        $info['mobile'] = !empty($user['mobile']) ? $user['mobile'] : '';
        $info['certNo'] = !empty($userInfo['cardid']) ? $userInfo['cardid'] : '';
        $info['name'] = !empty($userInfo['realname']) ? $userInfo['realname'] : '';
        $info['ip'] = Timeutil::getIp();
        $info['longitude'] = !empty($userInfo['longitude']) ? $userInfo['longitude'] : '';//经度
        $info['latitude'] = !empty($userInfo['latitude']) ? $userInfo['latitude'] : '';//纬度
        $info['tokenKey'] = $tokenKey;
        $credit = new Credit();
        $re = $credit->decision($info);
        coast(17, $uid, 1);
        if ('BQS000' != $re['resultCode']) {
            $orderStatus['status'] = 12;
            Db::name('order')->where(array('id' => $order_id))->update($orderStatus);
            $this->error('机审异常', null, 400);
        }
        $data['order_id'] = $order_id;
        $data['review'] = 1;
        $data['create_time'] = time();
        if ('Reject' == $re['finalDecision']) {
            $orderStatus['status'] = 12;
            $data['sh_result'] = 2;
            $data['order_status'] = 12;
            $data['desc'] = '机审失败';
        } else {
            $orderStatus['status'] = 13;
            $data['sh_result'] = 1;
            $data['order_status'] = 13;
            $data['desc'] = '机审成功';
        }
        Db::name('sh_log')->insert($data);
        Db::name('order')->where(array('id' => $order_id))->update($orderStatus);
        $this->addAecision($order_id, $re);
    }

    public function addAecision($order_id, $re)
    {
        $result['content'] = Db::name('decision')->where(array('order_id' => $order_id))->value('content');
        if (empty($result['content'])) {
            //$this->setCoast(9);
            $data['content'] = json_encode($re);
            $data['order_id'] = $order_id;
            Db::name('decision')->insert($data);
        }
    }

    public function addCreditReport($order_id, $re)
    {
        $result['content'] = Db::name('credit_report')->where(array('order_id' => $order_id))->value('content');
        if (empty($result['content'])) {
            //$this->setCoast(9);
            $data['content'] = json_encode($re);
            $data['order_id'] = $order_id;
            Db::name('credit_report')->insert($data);
        }
    }

    public function creditCheck($uid, $order_id)
    {
        //身份证实名校验
        $where['uid'] = $uid;
        $user = Db::name('user')->where(array('id' => $uid))->find();
        $userInfo = Db::name('user_info')->where($where)->find();
        //$deviceInfo = Db::name('device')->where($where)->order('updatetime desc')->find();
        //$tokenKey = !empty($deviceInfo['tokenKey']) ? $deviceInfo['tokenKey'] : '';
        $info = array();
        $info['mobile'] = !empty($user['mobile']) ? $user['mobile'] : '';
        $info['certNo'] = !empty($userInfo['cardid']) ? $userInfo['cardid'] : '';
        $info['name'] = !empty($userInfo['realname']) ? $userInfo['realname'] : '';
        $info['ip'] = Timeutil::getIp();
        $info['longitude'] = !empty($userInfo['longitude']) ? $userInfo['longitude'] : '';//经度
        $info['latitude'] = !empty($userInfo['latitude']) ? $userInfo['latitude'] : '';//纬度
        //$info['tokenKey'] = $tokenKey;
        $credit = new Credit();
        $re = $credit->creditReport($info);
        if ('BQS000' != $re['resultCode']) {
            // $orderStatus['status'] = 12;
            // Db::name('order')->where(array('id' => $order_id))->update($orderStatus);
            $this->error('机审异常', null, 400);
        }
        /*$data['order_id'] = $order_id;
        $data['review'] = 1;
        $data['create_time'] = time();
        if ('Reject' == $re['finalDecision']) {
            $orderStatus['status'] = 12;
            $data['sh_result'] = 2;
            $data['order_status'] = 12;
            $data['desc'] = '机审失败';
        } else {
            $orderStatus['status'] = 13;
            $data['sh_result'] = 1;
            $data['order_status'] = 13;
            $data['desc'] = '机审成功';
        }
        Db::name('sh_log')->insert($data);
        Db::name('order')->where(array('id' => $order_id))->update($orderStatus);*/
        $this->addCreditReport($order_id, $re);
    }
}

<?php

namespace app\admin\controller\account;

use app\common\controller\Backend;
use fast\Random;
use think\Db;
use think\Exception;
use think\Lang;
use think\Log;
use think\Session;
use xjd\util\Fypay;
use xjd\util\Timeutil;

/**
 * 测试管理
 *
 * @icon fa fa-circle-o
 */
class Loan extends Backend {
    /**
     * Load模型对象
     *
     * @var \app\admin\model\Load
     */
    protected $model = null;

    public function _initialize() {
        parent::_initialize();
        $this->model = new \app\admin\model\Load;
        $this->view->assign("weekList", $this->model->getWeekList());
        $this->view->assign("flagList", $this->model->getFlagList());
        $this->view->assign("genderdataList", $this->model->getGenderdataList());
        $this->view->assign("hobbydataList", $this->model->getHobbydataList());
        $this->view->assign("statusList", $this->model->getStatusList());
        $this->view->assign("stateList", $this->model->getStateList());
    }

    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */
    /**
     * 查看
     */
    public function index() {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            $type = $this->request->request("type");
           // print_r($type);
            $wheretype = "";
            switch ($type){
                case "checking":
                    $wheretype = "o.status in (6,12,13)";
                    break;
                case "pass":
                    $wheretype = "o.status=8";
                    break;
                case "reject":
                    $wheretype = "o.status=16";
                    break;
                default:
                    $wheretype = "1=1";
                    break;
            }
            $where_neq['o.status'] = array('neq','11,14');
            //$wheretype = " o.status in  (2,5,6,7,8,15,16)";
//            $wheretype = " o.status = 6";
            $field
                = "u.id as uid,u.username,i.realname,o.id as id,o.fk_time,o.type ut,o.code,o.amount,o.pay,o.createtime,o.endtime,o.cycle,o.`status`,a1.nickname as dcnickname,a2.nickname as fcnickname";
            $field = $field.',dc.channel_name';
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = Db::name("order")->alias("o")
                       ->field($field)
                       ->join("d_user u", "u.id = o.uid", "inner")
                       ->join("d_user_info i", "i.uid = u.id", "inner")
                       ->join("d_admin a1", "a1.id  = o.allotdcid", "left")
                       ->join("d_admin a2", "a2.id  = o.allotfcid", "left")
                       ->join("d_channel dc", "dc.channel_code  = u.channel_code", "left")
                       ->where($where)
                       ->where($wheretype)
                       ->where($where_neq)
                       ->order($sort, $order)
                       ->count();
            $list = Db::name("order")->alias("o")
                      ->field($field)
                      ->join("d_user u", "u.id = o.uid", "inner")
                      ->join("d_user_info i", "i.uid = u.id", "inner")
                      ->join("d_admin a1", "a1.id  = o.allotdcid", "left")
                      ->join("d_admin a2", "a2.id  = o.allotfcid", "left")
                      ->join("d_channel dc", "dc.channel_code  = u.channel_code", "left")
                      ->where($where)
                      ->where($wheretype)
                      ->where($where_neq)
                      ->order($sort, $order)
                      ->limit($offset, $limit)
                      ->select();
            $sql = Db::name("order")->getLastSql();
            Log::write(__FUNCTION__.' sql ='.$sql,'error');
            foreach ($list as &$v) {
                $v["usertype"] = $v['ut'] == 1 ? "首借" : "续借";
                $v["ordertime"] = date("Y-m-d H:i:s", $v["createtime"]);
                $v["payendtime"] = $v["endtime"] == 0
                    ? date("Y-m-d H:i:s", $v["createtime"] + 7 * 24 * 3600)
                    : date(
                        "Y-m-d H:i:s", $v["endtime"]
                    );
                $v["orderstatus"] = Db::name("order_type")->where(["typeid" => $v["status"]])->value("name");
            }
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }

        return $this->view->fetch();
    }

    public function fk($ids = "") {

        if (empty($ids)){
            $ids = $_POST['ids'];
        }
        if ($ids) {
            //判断状态 必须 status = 6
            //放款 改成 7 放款中
            //根据放款回调，完成以下状态
            //更改order状态
            //生成订单最终
            //记录放款状态 sh_log message
            $where['id'] = $ids;
            $order = Db::name('order')->where($where)->find();
//            if (6 != $order['status']) {
//                if(16 != $order['status']) {
//                    if(12 != $order['status']) {
//                        if(13 != $order['status']) {
//                            $this->error('订单不满足放款条件');
//                        }
//                    }
//                }
//            }

            if ($order['status']!=6){

                return false;
//                $this->error('订单不满足放款条件');

            }
            //修改code
            $code = Timeutil::msectime() . $ids;
            $update_code = Db::name('order')->where($where)->update(array('fy_code'=>$code));
            if(false === $update_code){
                $this->error('配置流水号失败');
            }



            $fcidinfo = Session::get('admin');
            $fcid = 0;
            if (!empty($fcidinfo['id'])){
                $fcid = $fcidinfo['id'];
            }
            $order_status['fcid'] = $fcid;
            $order['fy_code'] = $code;
            $re = $this->pay($order['uid'], $order);
            $order_status['fk_time'] = date("Ymd", time());
            $order_status['fk_time_detail'] = time();
            $order_status['status'] = 16;//放款失败
            if ('000000' == $re['ret'] || 'AAAAAA' == $re['ret']) {
                $order_status['status'] = 7;//放款中
            }
            $re_update = Db::name('order')->where($where)->update($order_status);
            if (!$re_update) {
//                $this->error('更新数据失败');
                return false;

            }
//            $this->success();
            return true;
        }
//        $this->error(__('Parameter %s can not be empty', 'ids'));

        return false;
    }

    public function pay($uid, $order,$fk_time  ='') {
        $where['uid'] = $uid;
        $userInfo = Db::name('user_info')->where($where)->find();
        $bankcard = Db::name('user_bankcard')->where($where)->find();
        $fy = new  Fypay();
        $data['ver'] = '1.0';
        $data['merdt'] = date("Ymd", time());
        $data['orderno'] = $order['fy_code'];
        // $data['bankno'] = '0104';
        // $data['cityno'] = '1000';
        //$data['branchnm'] = '中国银行股份有限公司北京西单支行';
        $data['accntno'] = $bankcard['cardid'];
        $data['accntnm'] = $userInfo['realname'];
        $data['amt'] = round(($order['pay'] - $order['cost']) * 100,2);//分
        $data['mobile'] = $bankcard['mobile'];
        $data['addDes'] = '1';
        $re = $fy->dbzf($data);

        return $re;
    }

    public function pass($ids,$type)
    {
        //$type = 1  通过 2  拒绝
        $row = Db::name('order')->where(['id' => $ids])->find();
        if (!$row)
            $this->error(__('No Results were found'));
        $row['clickType'] = $type;
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    public function editPass(){
        //2 6 15
        $params = $this->request->post("row/a");
        Log::write(__FUNCTION__.': params='.print_r($params,true),'error');
        $clickType = $params['clickType'];
        Log::write(__FUNCTION__.': clickType='.$clickType,'error');
        $data['id'] = $params['id'];
        $data['rem'] = $params['rem'];
        Log::write(__FUNCTION__.': data='.print_r($data,true),'error');
        $orderInfo = Db::name('order')->where(array('id'=>$params['id']))->find();
        if(2 != $orderInfo['status'] && 6 != $orderInfo['status'] && 15 != $orderInfo['status']){
            $this->error('订单不满足审核条件');
        }
        if(1 == $clickType){//通过--待放款
            $data['status'] = 6;
        }elseif (2 == $clickType){//拒绝
            $data['status'] = 15;
        }
        $re = Db::name('order')->update($data);
        if($re === false){
            $this->error('更新数据失败');
        }
        $this->success();

    }

    public function refuse($ids = "")
    {
        if ($ids) {
            $where['id'] = $ids;
            $order = Db::name('order')->where($where)->find();
            if (empty($order)) {
                $this->error('订单不存在');
            }
            if(2 != $order['status'] && 6 != $order['status'] && 15 != $order['status']){
                $this->error('订单不满足审核条件');
            }
            if (8 == $order['status']) {
                $this->error('已放款，禁止审核');
            }
            $data['id'] = $ids;
            $data['status'] = 15;
            $re = Db::name('order')->update($data);
            if ($re === false) {
                $this->error('更新数据失败');
            }
            $this->success();
        }
        $this->error(__('Parameter %s can not be empty', 'ids'));
    }


    public function repay_bak($ids) {
        if(empty($ids)){
            $this->error('请指定订单号');
        }
        $params['repay_id'] = $ids;
        $order_repay = Db::name('order_repay')->where(array('id' => $params['repay_id']))->find();
        if(empty($order_repay)){
            $this->error('未找到订单追踪');
        }
        $order = Db::name('order')->where(array('id' => $order_repay['order_id']))->find();
        if(empty($order_repay)){
            $this->error('订单号错误');
        }
        $checkBank = Db::name('user_bankcard')->where(array('uid' => $order['uid'], 'fy_status' => 4))
                       ->find();
        if (empty($checkBank)) {
            $this->error('请先绑定银行卡', null, 24);
        }
        $status = $order_repay['status'];
        $today = Timeutil::todayTime();
        if (2 == $status || 4 == $status) {
            $this->success('已还款');
        } elseif (1 == $status && $today < $order_repay) {//正常还款
            $real_amount = $order_repay['total_amount'];
            $data['status'] = 2;
            $data['update_time'] = time();
            $data['real_amount'] = $real_amount;
            $order_data['status'] = 9;
        } elseif ((1 == $status && $today > $order_repay) || 3 == $status) {//逾期
            $real_amount = $order_repay['total_amount'] + $order_repay['penalty'];
            $data['status'] = 4;
            $data['update_time'] = time();
            $data['real_amount'] = $real_amount;
            $order_data['status'] = 9;
            $order_data['overcost'] = $order_repay['penalty'];
            $order_data['overday'] = Timeutil::interval($order['starttime'], $order['starttime']);
        } else {
            $this->error('订单不满足还款条件', null, 3);
        }
        //更新额度
        $sys = Db::name('sys_config')->find();
        $sjdz = $sys['sjdz'];
        $xjzd = $sys['xjzd'];
        $quota = Db::name('user')->where(array('id' => $order['uid']))->value('quota');
        $quota = ($quota + $sjdz) > $xjzd ? $xjzd : $quota + $sjdz;
        Db::name('user')->where(array('id' => $order['uid']))->update(array('quota' => $quota));
        //更新额度结束
        $re = Db::name('order_repay')->where(array('id' => $params['repay_id']))->update($data);
        if (!$re) {
            $this->error('更新订单追踪失败', null, 4);
        }
        $re = Db::name('order')->where(array('id' => $order_repay['order_id']))->update($order_data);
        if (!$re) {
            $this->error('更新订单失败', null, 5);
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
        if(!empty($re['ORDERID'])){
            $updata['pay_order_id'] = $re['ORDERID'];
        }
        if ('0000' != $re['RESPONSECODE'] || $re['AMT'] != $obj['AMT']) {
            $updata['pay_status'] = 3;
            $re = Db::name('order_repay')->where(array('id' => $params['repay_id']))->update($updata);
            if (!$re) {
                $this->error('更新数据失败', null, 20);
            }
            $this->error('支付失败：'.$re['RESPONSEMSG'], null, 34);
        }
        $updata['pay_status'] = 2;
        $re = Db::name('order_repay')->where(array('id' => $params['repay_id']))->update($updata);
        if (!$re) {
            $this->error('支付成功更新数据失败', null, 25);
        }

        $this->success('成功');
    }

    public function repay($ids = "") {
        if ($ids) {
            //判断状态 必须 status = 6
            //放款 改成 7 放款中
            //根据放款回调，完成以下状态
            //更改order状态
            //生成订单最终
            //记录放款状态 sh_log message
            $where['id'] = $ids;
            $order = Db::name('order')->where($where)->find();
            if(8 == $order['status']){
                $this->error('已放款，禁止线下放款');
            }
            if (6 != $order['status']) {
                if(12 != $order['status']) {
                    if(13 != $order['status']) {
                        if(7 != $order['status']) {
                            if(16 != $order['status']) {
                                $this->error('订单不满足放款条件');
                            }
                        }
                    }
                }
            }
            //$re = $this->pay($order['uid'], $order);
            $order_status['fk_time'] = date("Ymd", time());
            $order_status['fk_time_detail'] = time();
            $order_status['status'] = 8;//放款成功
           // if ('000000' == $re['ret'] || 'AAAAAA' == $re['ret']) {
            //    $order_status['status'] = 7;//放款中
            //}
            $re_update = Db::name('order')->where($where)->update($order_status);
            if (!$re_update) {
                $this->error('更新数据失败');
            }
            $this->generateOrderRepay($ids);
            $this->success();
        }
        $this->error(__('Parameter %s can not be empty', 'ids'));
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
        $endtime = $repay_time + $order['cycle'] * 86400 -1;
        $time_data['endtime'] = $endtime;
        Db::name('order')->where($where)->update($time_data);
        //更新order starttime endtime  --end
        $data['repay_time'] = $endtime;
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


    //放款审核通过
    public function approve($ids = null){


        if (empty($ids) || !is_numeric($ids)){
            $this->error('订单id不正确');
        }
        $status = Db::name('order')->where(['id'=>$ids])->value('status');
        if (!in_array($status,[12,14,15,16])){
            $this->error('订单不符合要求');

        }

        $time  = time();
        $log_in = [
            'order_id'=>$ids,
            'order_status'=>6,
            'sh_result'=>1,
            'desc'=>'等待放款',
            'create_time'=>time(),
            'review'=>2
        ];
        try{
            $a = Db::name('sh_log')->insert($log_in);
            $re = Db::name('order')->where(['id'=>$ids])->update(['status'=>6]);
            if ($re && $a){
                $this->success('修改成功');
            }else{
                log::write(__FUNCTION__ . $ids . '订单更新失败');
                $this->error('更新失败');
            }

        }catch (Exception $e){
            log::write(__FUNCTION__ . $ids . '订单更新失败');
            $this->error('更新失败');

        }

    }


    //放款确认
    public function depositConfirm($ids = null , $uid = null){

        if ($this->request->isPost()){
            $a = $this->fk($ids);
            if ($a){
                $this->success();
            }else{
                $this->error('该订单不可放款');
            }
        }
        $field = 'ui.realname,ub.cardid,ub.bank,o.pay,o.cost,u.rem,o.cycle';
        $where = [
            'o.id'=>$ids
        ];

        $data = Db::name('order')->alias('o')
            ->join('user u','u.id = o.uid')
            ->join('user_bankcard ub','ub.uid =o.uid')
            ->join('user_info ui','ui.uid = o.uid')
            ->field($field)
            ->where($where)
            ->find();

        $data['ids']  = $ids;
        $data['rqlv'] = Db::name('sys_config')->find(1)['rqlv'];
        $data['fkje'] = round($data['pay']-$data['cost'],2);

        $r_time = strtotime(date('Ymd',time())) + $data['cycle'] * 86400-1;

        $data['hkrq'] =  date('Y-m-d H:s:i',$r_time);
        $this->assign("data", $data);
        return $this->view->fetch();

    }

    //修改额度
    public function editLimit($ids = null){

//        $data['cost'] = $params['amount'] * ($params['cycle'] - 1) * $rate;


        $data  = Db::name("order")->where('id','=',$ids)->find();
        $amount = $data['amount'];

        if ($data['status']!=6){
            return $this->error('非可放款状态禁止修改金额');
        }
        if ($this->request->isPost()){

            $amount = $_POST['amount'];
            if ($amount)
            {
                $rate = Db::name('sys_config')->value('jkzd');
                $cost = round($amount * ($data['cycle'] -1) * $rate,2);

                $result = Db::name('order')->where(['id'=>$ids])->update(['amount'=>$amount,'cost'=>$cost,'pay'=>$amount]);
                if ($result === false)
                {
                    $this->error($data->getError());
                }
                $this->success();
            }
            $this->error();
        }

        $this->view->assign('amount',$amount);
        $this->view->assign('ids',$ids);
        return $this->view->fetch();


//        $data['cost'] = $params['amount'] * ($params['cycle'] - 1) * $rate;


    }

//http://47.104.69.18/index.php/order/userorderRepayDetail
//
}

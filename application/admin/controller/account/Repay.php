<?php

namespace app\admin\controller\account;

use app\common\controller\Backend;
use fast\Random;
use think\Db;
use think\Log;
use xjd\util\Fypay;
use xjd\util\Timeutil;

/**
 *
 *
 * @icon fa fa-circle-o
 */
class Repay extends Backend {
    /**
     * Repay模型对象
     *
     * @var \app\admin\model\order\Repay
     */
    protected $model = null;
    public $status
        = array(
            "1"  => '待处理',
            "2"     => '正常还款',
            "3" => '逾期',
            "4"  => '逾期还款',
            "5"     => '续期',
            "6" => '展期',
            "7"  => '逾期续期',
            "8"     => '逾期展期',
        );

    public function _initialize() {
        parent::_initialize();
        $this->model = new \app\admin\model\order\Repay;
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
            $today = Timeutil::todayTime();
            $type = $this->request->request("type");
            $wheretype = "";
            switch ($type) {
                case "checking":
                    $wheretype = "orep.status in (6,8)";
                    break;
                case "pass":
                    $wheretype = "orep.status in (2,4)";
                    break;
                case "due":
                    $wheretype = "orep.repay_time =".$today;
                    break;
                default:
                    $wheretype = "1=1";
                    break;
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = Db::name("order_repay")->alias("orep")
                       ->join("d_order dor", "dor.id = orep.order_id", "left")
                       ->join("d_user du", "dor.uid = du.id", "left")
                       ->join("d_user_info ui", "ui.uid = du.id", "left")
                       ->join("d_channel dc", "dc.channel_code  = du.channel_code", "left")
                       ->join("d_admin a1", "a1.id  = dor.allotdcid", "left")
                       ->join("d_admin a2", "a2.id  = dor.allotfcid", "left")
                       ->where($where)
                       ->where($wheretype)
                       ->count();
            $fild
                = 'orep.id,orep.order_id,du.mobile,ui.realname,orep.total_amount,orep.zq_amount,orep.real_amount,dor.type ut,dor.starttime,dor.fk_time_detail';
            $fild = $fild.",orep.repay_time,orep.real_time,orep.zq_time,orep.status";
            $fild = $fild.",channel_name,a1.nickname allotdce,a2.nickname allotfce";
            $list = Db::name("order_repay")->alias("orep")
                      ->field($fild)
                      ->join("d_order dor", "dor.id = orep.order_id", "left")
                      ->join("d_user du", "dor.uid = du.id", "left")
                      ->join("d_user_info ui", "ui.uid = du.id", "left")
                      ->join("d_channel dc", "dc.channel_code  = du.channel_code", "left")
                      ->join("d_admin a1", "a1.id  = dor.allotdcid", "left")
                      ->join("d_admin a2", "a2.id  = dor.fcid", "left")
                      ->order('orep.id desc,orep.order_id desc')
                      ->where($where)
                      ->where($wheretype)
                      ->limit($offset, $limit)
                      ->select();
            foreach ($list as &$value){
                $value["usertype"] = $value['ut'] == 1 ? "首借" : "续借";
                $value['status'] = $this->status[$value['status']];
            }
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        $total = $this->total();
        $this->view->assign("total", $total);

        return $this->view->fetch();
    }

    public function total() {
        //到期总笔数
        $today = Timeutil::todayTime_1();

        $where_dqzbs['endtime'] = array('=', $today);
//        $re['dqzbs'] = Db::name('order')->where($where_dqzbs)->where(array('endtime' => array('<>', 0)))->count();
        $re['dqzbs'] = Db::name('order')->where($where_dqzbs)->where(array('status' => 8))->count();
        //到期金额
//        $re['dqje'] = Db::name('order')->where($where_dqzbs)->where(array('endtime' => array('<>', 0)))->sum('pay');
        $re['dqje'] = Db::name('order')->where($where_dqzbs)->where(array('status' => 8))->sum('pay');

        //还款笔数
        $where_hkbs['real_time'] = ['between',[strtotime(date('Y-m-d',time())),$today]];
        $re['hkbs'] = Db::name('order_repay')->where($where_hkbs)->where(array('pay_status' => 2))->count();
        //还款金额
        $re['hkje'] = Db::name('order_repay')->where($where_hkbs)->where(array('pay_status' => 2))->sum('real_amount');
        //未还笔数
        $where_whbs['status'] = array('in', '8,10');
        $re['whbs'] = Db::name('order')->where($where_dqzbs)->where($where_whbs)->count();
        //未还金额
        $re['whje'] = Db::name('order')->where($where_dqzbs)->where($where_whbs)->sum('pay');
        //展期笔数
        //$where_zqbs['rollnum'] = array('>', 0);
        $end = $today;
        $before = strtotime(date('Ymd',$today));
        $where_zqbs['zq_time'] = array('between',[$before,$end]);
        $re['zqbs'] = Db::name('order_repay')->where($where_zqbs)->count();
        //展期金额
        $re['zqje'] = Db::name('order_repay')->where($where_zqbs)->sum('zq_amount');

        return $re;
    }

    public function del($ids = "") {
        if ($ids) {
            $data['status'] = 9;
            Log::write(__FUNCTION__.': data='.print_r($data, true), 'error');
            $order_repay = Db::name('order_repay')->where(array('id' => $ids))->order('id desc')->find();
            $orderCheck = Db::name('order')->where(array('id' => $order_repay['order_id']))->find();
            /*if(8 == $orderCheck['status']){
                $this->error('已放款，结清失败');
            }*/
            if (!empty($order_repay)) {
                $real_amount = $order_repay['total_amount'];
                $repay['status'] = 2;
                $repay['update_time'] = time();
                $repay['real_amount'] = $real_amount;
                $repay['real_time'] = time();
                $result = Db::name('order_repay')->where(array('id' => $order_repay['id']))->update($repay);
                if ($result === false) {
                    $this->error('结清失败');
                }
                $sys = Db::name('sys_config')->find();
                $sjdz = $sys['sjdz'];
                $xjzd = $sys['xjzd'];
                $quota = Db::name('user')->where(array('id' => $result['uid']))->value('quota');
                $quota = ($quota + $sjdz) > $xjzd ? $xjzd : $quota + $sjdz;
                Db::name('user')->where(array('id' => $result['uid']))->update(array('quota' => $quota));
                $order_data['status'] = 9;
                $re = Db::name('order')->where(array('id' => $order_repay['order_id']))->update($order_data);
                if ($re === false) {
                    $this->error('更新订单失败', null, 5);
                }

            }
            $this->success();
        }
        $this->error(__('Parameter %s can not be empty', 'ids'));
    }


    public function black($ids,$type='')
    {
        //$type = 1  通过 2  拒绝  3 取消
        //$row = $this->model->get(['id' => $ids]);
        //Log::write(__FUNCTION__.': ids='.$ids,'error');
        $order_repay = Db::name('order_repay')->where(array('id' => $ids))->order('id desc')->find();
        //Log::write(__FUNCTION__.': order_repay='.print_r($order_repay,true),'error');
        $order = Db::name('order')->where(array('id'=>$order_repay['order_id']))->find();
        if (!$order)
            $this->error(__('No Results were found'));
        $row = Db::name('user')->where(array('id'=>$order['uid']))->find();
        Log::write(__FUNCTION__.': row='.print_r($row,true),'error');
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    public function editBlack(){
        $params = $this->request->post("row/a");
        //$data['status'] = $params['status'];
        $data['quota'] = $params['quota'];
        $data['id'] = $params['id'];
        Log::write(__FUNCTION__.': data='.print_r($data,true),'error');
        $re = Db::name('user')->update($data);
        if($re === false){
            $this->error('更新数据失败');
        }
        $this->success();

    }



    public function zhanqi($ids,$type='')
    {
        //$type = 1  通过 2  拒绝  3 取消
        $where['id']  = $ids;
        $list = Db::name('order_repay')->where($where)->find();
        Log::write(__FUNCTION__.': list='.print_r($list,true),'error');
        //$days = [1,2,3,4,5,6,7];
        $days = array();
        $expect_config = Db::name('expect_config')->find();
        $min_num = $expect_config['min_num'];
        $num = $expect_config['num'];
        for ($x=$min_num; $x<=$num; $x++) {
            $days[] = $x;
        }
        $this->assign("ids", $ids);
        $this->assign("days", $days);
        $this->assign("expect_config", $expect_config);
        $this->view->assign("row", $list);
        return $this->view->fetch();
    }

    public function editzhanqi(){
        $param = $this->request->post("row/a");
        if(empty($param['day'])){
            $this->error('请选择展期天数');
        }
        $params['repay_id'] =  $param['id'];
        $params['day'] =  $param['day'];
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
        $max_rate = round($order_repay['corpus'] *  ($expect_config['max_rate']/100), 2);
        $yj_rate = round($order_repay['corpus'] *($expect_config['min_rate']/100) * $params['day'], 2);
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
        $re = Db::name('order_repay')->where(array('id' => $params['repay_id']))->update($data);
        if (!$re) {
            $this->error('更新订单追踪失败', null, 4);
        }
        //还款
        /*$Fypay = new Fypay();
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
        }*/
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


        $this->success();

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

    /**
     * 编辑
     * order 逾期天数 逾期金额  还款时间 状态
     * repay_order 逾期金额  还款时间 状态
     */
    public function edit($ids = null) {
        Log::write(__FUNCTION__.': ids='.$ids, 'error');
        $row = $this->model->get($ids);
        Log::write(__FUNCTION__.': row='.$row, 'error');
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                $this->error(__('You have no permission'));
            }
        }
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            Log::write(__FUNCTION__.': params='.print_r($params, true), 'error');
            if ($params) {
                try {
                    $data['overcost'] = $params['overcost'];
                    $data['overday'] = 0;
                    $data['endtime'] = strtotime("today");
                    $data['status'] = 8;
                    Log::write(__FUNCTION__.': data='.print_r($data, true), 'error');
                    $result = Db::name('order')->where(array('id' => $ids))->update($data);
                    if (!$result) {
                        $this->error($row->getError());
                    }
                    $order_repay = Db::name('order_repay')->where(array('order_id' => $ids))->order('id desc')->find();
                    if (!empty($order_repay)) {
                        $repay['repay_time'] = strtotime("today");
                        $repay['penalty'] = $params['overcost'];
                        $repay['status'] = 1;
                        $repay['update_time'] = time();
                        $re = Db::name('order_repay')->where(array('id' => $order_repay['id']))->update($repay);
                        if (!$re) {
                            $this->error($row->getError());
                        }
                    }
                    $this->success();
                } catch (\think\exception\PDOException $e) {
                    $this->error($e->getMessage());
                } catch (\think\Exception $e) {
                    $this->error($e->getMessage());
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $this->view->assign("row", $row);

        return $this->view->fetch();
    }
}

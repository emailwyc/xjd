<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use think\Config;
use think\Db;
use xjd\util\Timeutil;

/**
 * 控制台
 *
 * @icon fa fa-dashboard
 * @remark 用于展示当前系统中的统计数据、统计报表及重要实时数据
 */
class Dashboard extends Backend
{

    /**
     * 查看
     */
    public function index()
    {
        //合同金额
        $result['htAmount'] = Db::name("order")->where('status','in',[8,9])->value('sum(amount)');
        $result['fkCount'] = Db::name("order")->where('status','in',[8,9])->count();
        $result['bjAmount'] = Db::name("order")->where('status','in',[8,9])->value('sum(amount)-sum(cost)');

        $result['hkAmount'] = Db::name("order_repay")->where('status','=',2)->value('sum(total_amount)') + Db::name("order_repay")->where('status','in',[6,8])->value('sum(zq_amount)');
        $result['bxhkCount'] = Db::name("order_repay")->where('status','=',2)->count();
        $result['bxhkAmount'] =  Db::name("order_repay")->where('status','=',2)->value('sum(total_amount)');
        $result['zqCount'] = Db::name("order_repay")->where('status','in',[6,8])->count();
        $result['zqAmount'] = Db::name("order_repay")->where('status','in',[6,8])->value('sum(zq_amount)');

        $result['zcdsAmount'] =  Db::name("order")->where('status','=',8)->value('sum(pay)');
        $result['dsCount'] =  Db::name("order")->where('status','=',8)->count();
        $result['dsAmount'] =  Db::name("order")->where('status','=',8)->value('sum(amount)-sum(cost)');

        $result['yqAmount'] =  Db::name("order_repay")->where('status','=',3)->value('sum(total_amount)+sum(zq_amount)');
        $result['yqCount'] =  Db::name("order_repay")->where('status','=',3)->count();
        $result['yqbjAmount'] =  Db::name("order_repay")->where('status','=',3)->value('sum(total_amount)');
        $result['yqfxAmount'] =  Db::name("order_repay")->where('status','=',3)->value('sum(over_amount)');

        $result['zpykAmount'] = $result['hkAmount']-$result['bjAmount'];
        //回款率的计算方式是：（回款笔数+展期笔数）/（放款笔数+展期笔数-代收笔数）
        //$result['hkRate'] = $result['hkAmount'] ? $result['bxhkAmount']/$result['hkAmount']*100 :0;
        $result['hkRate'] = $result['hkAmount'] ? ($result['bxhkCount']+$result['zqCount'])/($result['fkCount']+$result['zqCount']-$result['dsCount'])*100 :0;
        //回款盈亏的计算方式：（合同金额-实放本金-逾期本金）
        $result['hkykAmount'] = $result['hkAmount']-$result['bjAmount']-$result['yqAmount'];


        $this->view->assign('row',$result);
        return $this->view->fetch();
    }

    public function getDayInfo(){
        $day = $this->request->request('date');
        $start = $day ? strtotime($day) : strtotime(date('Ymd'));
        $end = $start + 86400;
        $where['createtime'] = array("between", [$start, $end]);

        //今日注册
        $result['regCount'] = Db::name('user')->where($where)->count();

        //进件单数
        $result['jjds'] = Db::name('order')->where($where)->count();
        //新增
        $jjxzWhere['createtime'] = array("between", [$start, $end]);
        $jjxzWhere['type'] = array("=", 1);
        $result['jjxz'] = Db::name('order')->where($jjxzWhere)->count();
        //复借
        $jjfjWhere['createtime'] = array("between", [$start, $end]);
        $jjfjWhere['type'] = array("=", 2);
        $result['jjfj'] =Db::name('order')->where($jjfjWhere)->count();

        //通过单数
        $tgdsWhere['createtime'] = array("between", [$start, $end]);
        $tgdsWhere['status'] = array("=",5);
        $result['tgds'] = Db::name('order')->where($tgdsWhere)->count();
        //新增
        $tgxzWhere['createtime'] = array("between", [$start, $end]);
        $tgxzWhere['status'] = array("=",5);
        $tgxzWhere['type'] = array("=",1);
        $result['tgxz'] =  Db::name('order')->where($tgxzWhere)->count();
        //复借
        $tgfjWhere['createtime'] = array("between", [$start, $end]);
        $tgfjWhere['status'] = array("=",5);
        $tgfjWhere['type'] = array("=",2);
        $result['tgfj'] = Db::name('order')->where($tgfjWhere)->count();

        //放款单数
        $fkdsWhere['fk_time'] = array("between", [$start, $end]);
        $fkdsWhere['status'] = array("=",8);
        $result['fkds'] = Db::name('order')->where($fkdsWhere)->count();
        //新增
        $fkxzWhere['fk_time'] = array("between", [$start, $end]);
        $fkxzWhere['status'] = array("=",8);
        $fkxzWhere['type'] = array("=", 1);
        $result['fkxz'] =  Db::name('order')->where($fkxzWhere)->count();
        //复借
        $fkfjWhere['fk_time'] = array("between", [$start, $end]);
        $fkfjWhere['status'] = array("=",8);
        $fkfjWhere['type'] = array("=", 2);
        $result['fkfj'] = Db::name('order')->where($fkfjWhere)->count();

        //进件转化率
        $result['jjRate'] = $result['regCount'] ? round(($result['jjxz']/$result['regCount'])*100,2) : 0;
        
        //到期笔数
        $dqWhere['repay_time'] = array("between", [$start, $end]);
        $result['dqCount'] = Db::name('order_repay')->where($dqWhere)->count();
        //首借
        $dqsjWhere['r.repay_time'] = array("between", [$start, $end]);
        $dqsjWhere['o.type'] = array("=",1);
        $result['dqsj'] = Db::name('order_repay')->alias('r')
                ->join('order o','r.order_id=o.id', 'LEFT')
                ->where($dqsjWhere)
                ->count();
        //复借
        $dqfjWhere['r.repay_time'] = array("between", [$start, $end]);
        $dqfjWhere['o.type'] = array("=",2);
        $result['dqfj'] = Db::name('order_repay')->alias('r')
                ->join('order o','r.order_id=o.id', 'LEFT')
                ->where($dqfjWhere)
                ->count();
        //回款笔数
        $hkWhere['real_time'] = array("between", [$start, $end]);
        $hkWhere['pay_status'] = array("=",2);
        $result['hkCount'] = Db::name('order_repay')->where($hkWhere)->count();
        //到期回款
        $dqhkWhere['pay_status'] = array("=",2);
        $dqhkWhere['real_time'] = array("between", [$start, $end]);
        $dqhkWhere['repay_time'] = array("between", [$start, $end]);
        $result['dqhk'] = Db::name('order_repay')->where($dqhkWhere)->count();
        $dqhkAmount = Db::name('order_repay')->where($dqhkWhere)->value('sum(total_amount)');

        //提前回款
        $tqhkWhere['pay_status'] = array("=",2);
        $tqhkWhere['real_time'] = array("between", [$start, $end]);
        $tqhkWhere['repay_time'] = array(">",strtotime(date('Y-m-d').' 23:59:59'));
        $result['tqhk'] = Db::name('order_repay')->where($tqhkWhere)->count();
        $tqhkAmount = Db::name('order_repay')->where($tqhkWhere)->value('sum(total_amount)');
        //逾期回款
        $yqhkWhere['pay_status'] = array("=",2);
        $yqhkWhere['real_time'] = array("between", [$start, $end]);
        $yqhkWhere['repay_time'] = array("<",strtotime(date('Y-m-d')));
        $result['yqhk'] = Db::name('order_repay')->where($yqhkWhere)->count();
        $yqhkAmount = Db::name('order_repay')->where($yqhkWhere)->value('sum(total_amount)');

        //到期未还
        $dqwhWhere['repay_time'] = array("between", [$start, $end]);
        $dqwhWhere['real_time'] = array("<",1);
        $dqwhWhere['pay_status'] = array("in",[1,3]);
        $result['dqwh'] = Db::name('order_repay')->where($dqwhWhere)->count();
        //到期展期
        $dqzqWhere['repay_time'] = array("between", [$start, $end]);
        $dqzqWhere['zq_time'] = array("between", [$start, $end]);
        $result['dqzq'] = Db::name('order_repay')->where($dqzqWhere)->count();


        //到期回款率
        $result['dqhkRate'] = $dqhkAmount ? round(($dqhkAmount/($dqhkAmount+$tqhkAmount+$yqhkAmount))*100,2) : 0;
        //首借率
        $result['sjRate'] =  $result['jjds'] ? round(($result['jjxz']/$result['jjds'])*100,2) : 0;
        //复借率
        $result['fjRate'] = $result['jjds'] ? round(($result['jjfj']/$result['jjds'])*100,2) : 0;

        echo json_encode($result);
    }

    public function regCount() {
        $timeUtil = new Timeutil();
        list($before, $end) = $timeUtil->getTodayTime();
        $where['createtime'] = array("between", [$before, $end]);
        $count = Db::name('user')->where($where)->count();
        return $count;
    }

    public function jkData(){
         $total_amount = Db::name('order')->sum('amount');
         $count = Db::name('order')->count();
         return array($total_amount,$count);
    }

    public function orderinfo() {
        // TODO: 2018/12/15  where group 一条sql也行
        //申请量
        $re[] = Db::name('order')->where(array('status' => 0))->count();
        //审核量
        $re[] = Db::name('order')->where(array('status' => array('in', '1,2,3,4')))->count();
        //审核通过量
        $re[] = Db::name('order')->where(array('status' => 5))->count();
        //待放款
        $re[] = Db::name('order')->where(array('status' => 6))->sum('amount');
        //放款中
        $re[] = Db::name('order')->where(array('status' => 7))->sum('amount');
        //已放款
        $re[] = Db::name('order')->where(array('status' => 8))->sum('amount');

        return $re;
    }

}

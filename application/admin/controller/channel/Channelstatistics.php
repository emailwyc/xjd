<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace app\admin\controller\channel;

use app\common\controller\Backend;

use think\Db;
/**
 * Description of Channel
 *
 * @author tomato
 */
class Channelstatistics extends Backend{
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
    }

    public function index(){
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            $req = $this->request->request();
            $search = json_decode($req['filter'],true);
            $where = [];
            foreach($search as $k=>$v){
                switch ($k){
                    case "channel_code":
                        $where['c.channel_code'] = ['like','%'.$v.'%'];
                        break;
                    case "username":
                        $where['u.username'] = ['like','%'.$v.'%'];
                        break;
                    case "mobile":
                        $where['u.mobile'] = ['like','%'.$v.'%'];
                        break;
                    case "createtime":
                        $arr = explode(' - ',$v);
                        $start = strtotime($arr[0]);
                        $end = strtotime($arr[1]);
                        $where['u.createtime'] = ['between',[$start,$end]];
                        break;
                }
            }
            //list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $sort = $req['sort'];
            $order = $req['order'];
            $offset = $req['offset'];
            $limit = $req['limit'];
            $total = Db::name("channel")
                ->alias('c')
                ->join('d_user_channel uc', 'c.channel_code = uc.channel_code', 'LEFT')
                ->join('d_user u', 'uc.user_id = u.id', 'LEFT')
                ->join('d_order o', 'u.id = o.uid', 'LEFT')
                ->where($where)
                ->count();
            $list = Db::name("channel")
                ->alias('c')
                ->join('d_user_channel uc', 'c.channel_code = uc.channel_code', 'LEFT')
                ->join('d_user u', 'uc.user_id = u.id', 'LEFT')
                ->join('d_order o', 'u.id = o.uid', 'LEFT')
                ->field("c.id,c.channel_code,c.settle_type,u.username,u.mobile,FROM_UNIXTIME(u.createtime,'%Y-%m-%d %H:%i:%s') as createtime,o.amount,o.status")
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            $statusName = array('待审核', '资料审核', '资料审核通过', '放款审核通过', '审核通过
', '待放款', '放款中', '已放款', '已还款', '逾期', '未通过', '机审失败', '机审成功', '资料审核失
败', '财务审核失败');
            foreach($list as $k=>$v){
                $list[$k]['status'] = $v['amount'] > 0 ? $statusName[$v['status']] : '';
                $list[$k]['iscomplete'] = $v['amount'] > 0 ? '是' : '否';
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }

        //注册量
        $row['regCount'] = Db::name("user")->where("channel_code!=''")->count();
        //完整进件累计
        $uidArr = Db::name("user")->where("channel_code!=''")->value('id');
        $row['jjCount'] = Db::name("order")->where('uid','in',$uidArr)->group('uid')->count();
        //进件转化率
        $row['jjRate'] = $row['regCount'] ? round(($row['jjCount']/$row['regCount'])*100,2) : 0;
        //审核通过累计
        $orderCount = Db::name("order")->where('uid','in',$uidArr)->count();
        $shWhere['status'] = ['in',[4,5,6,7,8,9,10]];
        $shWhere['uid'] = ['in',$uidArr];
        $row['shCount'] = Db::name("order")->where($shWhere)->count();
        $row['shRate'] = $orderCount ? round(($row['shCount']/$row['jjCount'])*100,2) : 0;
        //放款累计
        $fkWhere['status'] = ['in',[8,9,10]];
        $fkWhere['uid'] = ['in',$uidArr];
        $row['fkCount'] =  Db::name("order")->where($fkWhere)->count();
        $row['fkRate'] = $orderCount ? round(($row['fkCount']/$row['shCount'])*100,2) : 0;
        //到期应还，
        $dqyhWhere['o.uid'] = ['in',$uidArr];
        $dqyhWhere['or.repay_time'] = ['<',time()];
        $dqyhWhere['or.real_time'] = ['<',1];
        $dqyhWhere['or.pay_status'] = ['in',[1,3]];
        $row['dqyhCount'] = Db::name("order")
            ->alias('o')
            ->join('d_order_repay or', 'o.id = or.order_id', 'LEFT')
            ->where($dqyhWhere)
            ->count();
        //到期逾期
        $dqyqWhere['o.uid'] = ['in',$uidArr];
        $dqyqWhere['or.status'] = ['in','=',3];
        $row['dqyqCount'] =  Db::name("order")
            ->alias('o')
            ->join('d_order_repay or', 'o.id = or.order_id', 'LEFT')
            ->where($dqyqWhere)
            ->count();
        $row['yqRate'] = $row['dqyhCount'] ? round(($row['dqyqCount']/$row['dqyhCount'])*100,2) : 0;

        //实收笔数
        $actualWhere['o.uid'] = ['in',$uidArr];
        $actualWhere['or.status'] = ['in',[2,4]];
        $row['actualCount'] = Db::name("order")
            ->alias('o')
            ->join('d_order_repay or', 'o.id = or.order_id', 'LEFT')
            ->where($actualWhere)
            ->count();
        //展期笔数
        $zqWhere['o.uid'] = ['in',$uidArr];
        $zqWhere['or.status'] = ['=',6];
        $row['zqCount'] = Db::name("order")
            ->alias('o')
            ->join('d_order_repay or', 'o.id = or.order_id', 'LEFT')
            ->where($zqWhere)
            ->count();
        //续借笔数
        $fjWhere['uid'] = ['in',$uidArr];
        $fjWhere['type'] = ['=',2];
        $row['fjCount'] = Db::name("order")->where($fjWhere)->count();

        $count = Db::name("order")->where('uid','in',$uidArr)->group('uid')->count();
        $row['fjRate'] = $row['jjCount'] ? round(($row['fjCount']/$count)*100,2) : 0;
        $this->view->assign('row',$row);
        return $this->view->fetch();
    }
}

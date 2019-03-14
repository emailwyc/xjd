<?php

namespace app\admin\controller\account;

use app\common\controller\Backend;
use think\Db;
use think\Log;
use xjd\util\Timeutil;

/**
 * 测试管理
 *
 * @icon fa fa-circle-o
 */
class Statistics extends Backend
{
    /**
     * Statistics模型对象
     *
     * @var \app\admin\model\Statistics
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Statistics;
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
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $params = $this->request->param();

            $filter = $this->request->request("filter", "");
            $filter = (array)json_decode($filter, TRUE);
            $datatime = !empty($filter["before"]) ? explode(" - ", $filter["before"]) : [];

            if($datatime && count($datatime)==2){
                $params['before'] = $datatime[0];
                $params['end'] = $datatime[1];
            }
            if (empty($params['end'])) {
                $params['end'] = date('Y-m-d')." 23:59:59";
            }
            if (empty($params['before'])) {
                //相对于结束时间一个月前
                $params['before'] = date('Y-m-d')." 00:00:00";
            }
            $before = $params['before'];
            $end = $params['end'];
            $dates = $this->getDateFromRange($before, $end);
            $total = count($dates);
            $list = $this->detail($dates, $offset, $limit);
            // $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        $total = $this->total();
        $this->view->assign("total", $total);

        return $this->view->fetch();
    }

    public function total()
    {
        //到期总笔数
        $today = Timeutil::todayTime();
        $where_dqzbs['endtime'] = array('<=', $today);
        $re['dqzbs'] = Db::name('order')->where($where_dqzbs)->where(array('endtime' => array('<>', 0)))->count();
        //首借笔数
        $re['sjbs'] = Db::name('order')->where(array('type' => 1))->count();
        //复借笔数
        $re['fjbs'] = Db::name('order')->where(array('type' => 2))->count();
        //逾期笔数
        $where_yqbs['overday'] = array('>', 0);
        $re['yqbs'] = Db::name('order')->where($where_yqbs)->count();
        //首借逾期笔数
        $re['yjyq'] = Db::name('order')->where($where_yqbs)->where(array('type' => 1))->count();
        //复借逾期笔数
        $re['fjyq'] = Db::name('order')->where($where_yqbs)->where(array('type' => 2))->count();
        //逾期率
        $allcount = Db::name('order')->count();
        $re['yql'] = $allcount == 0 ? 0 : round($re['yqbs'] * 100 / $allcount,2) . '%';
        //首借逾期率
        $re['sjyql'] =  $allcount == 0 ? 0 : round($re['yjyq'] * 100 / $allcount,2) . '%';
        //复借逾期率
        if($re['fjyq']>0){
            //$re['fjyql'] = round($re['fjyq'] * 100 / $allcount) . '%';
            $re['fjyql'] = round($re['fjyq'] * 100 / ($re['fjyq']),2).'%';
        }else{
            $re['fjyql'] = '0%';
        }


        return $re;
    }

    public function detail($dates, $offset, $limit)
    {
        $data_need = array_slice($dates, $offset, $limit);
        //到期笔数
//        $where_dqzbs['endtime'] = array('in', $data_need);
        $where_dqzbs = [
            'endtime'=>['in',$data_need],
            'status'=>8
        ];
        $dqzbs = Db::name('order')->field("endtime,count(id) num")->where($where_dqzbs)->group('endtime')->select();
        $re['dqzbs'] = $this->setArray($dqzbs);
        //逾期1-3   1-2 t0
        $where_yq_time['status'] = 9;//已还款
        $where_yq_time['endtime'] = array('in', $data_need);//今日还款
        $where_yq_time['overday'] = array('between', [1, 2]);
        $yq_1_3 = Db::name('order')->field("endtime,count(id) num")->where($where_yq_time)->group('endtime')
            ->select();
        $re['yq_1_3'] = $this->setArray($yq_1_3);
        //逾期3-7  3-6 t1
        $where_yq_time['overday'] = array('between', [3, 6]);
        $yq_3_7 = Db::name('order')->field("endtime,count(id) num")->where($where_yq_time)->group('endtime')
            ->select();
        $re['yq_3_7'] = $this->setArray($yq_3_7);
        //逾期7-15 7 -14 t2
        $where_yq_time['overday'] = array('between', [3, 14]);
        $yq_7_15 = Db::name('order')->where($where_yq_time)->group('endtime')->select();
        $re['yq_7_15'] = $this->setArray($yq_7_15);
        //逾期15 >=15 t3
        $where_yq_time['overday'] = array('>=', '15');
        $yq_15 = Db::name('order')->field("endtime,count(id) num")->where($where_yq_time)->group('endtime')
            ->select();
        $re['yq_15'] = $this->setArray($yq_15);
        //总逾期数
        $where_yq_time['overday'] = array('>', 0);
        $yq_all = Db::name('order')->field("endtime,count(id) num")->where($where_yq_time)->group('endtime')
            ->select();
        $re['yq_all'] = $this->setArray($yq_all);
        Log::write(__FUNCTION__ . ': yq_all=' . print_r($re['yq_all'], true), 'error');
        //总笔数
        $allcount = Db::name('order')->count();
        //首借到期笔数
        $sjdqzbs = Db::name('order')->field("endtime,count(id) num")->where($where_dqzbs)->where(
            array('type' => 1)
        )->group('endtime')->select();
        $re['sjdqzbs'] = $this->setArray($sjdqzbs);
        //首借逾期笔数
        $sjyjzbs = Db::name('order')->field("endtime,count(id) num")->where($where_dqzbs)->where(
            'overday','>',0 )
        ->where(array('type' => 1))
            ->group('endtime')->select();
        $re['sjyjzbs'] = $this->setArray($sjyjzbs);
        //复借到期笔数
        $fjdqzbs = Db::name('order')->field("endtime,count(id) num")->where($where_dqzbs)->where(
            array('type' => 2)
        )->group('endtime')->select();
        $re['fjdqzbs'] = $this->setArray($fjdqzbs);
        //复借逾期笔数
        $fjyjzbs = Db::name('order')->field("endtime,count(id) num")->where($where_dqzbs)->where(
            'overday','>',0
        )->where(array('type' => 2))
            ->group('endtime')->select();
        $re['fjyjzbs'] = $this->setArray($fjyjzbs);
        foreach ($data_need as $key => $value) {
            $data['time'] = date('Y-m-d', $value);
            $data['dqzbs'] = !empty($re['dqzbs'][$value]) ? $re['dqzbs'][$value] : 0;
            $data['yq_1_3'] = !empty($re['yq_1_3'][$value]) ? $re['yq_1_3'][$value] : 0;
            $data['yq_3_7'] = !empty($re['yq_3_7'][$value]) ? $re['yq_3_7'][$value] : 0;
            $data['yq_7_15'] = !empty($re['yq_7_15'][$value]) ? $re['yq_7_15'][$value] : 0;
            $data['yq_15'] = !empty($re['yq_15'][$value]) ? $re['yq_15'][$value] : 0;
            $data['sjdqzbs'] = !empty($re['sjdqzbs'][$value]) ? $re['sjdqzbs'][$value] : 0;
            $data['sjyjzbs'] = !empty($re['sjyjzbs'][$value]) ? $re['sjyjzbs'][$value] : 0;
            $data['fjdqzbs'] = !empty($re['fjdqzbs'][$value]) ? $re['fjdqzbs'][$value] : 0;
            $data['fjyjzbs'] = !empty($re['fjyjzbs'][$value]) ? $re['fjyjzbs'][$value] : 0;
            if ($data['dqzbs'] > 0) {
                $yq_all_data = !empty($re['yq_all'][$value]) ? $data['yq_all'][$value] : 0;
                $re['t0yql'] =round($data['yq_1_3'] * 100 / ($data['dqzbs']),2).'%';
                $re['allyql'] = round($yq_all_data * 100 / ($data['dqzbs']),2).'%';
                $data['sjyqldata'] = round($data['sjyjzbs'] * 100 / ($data['dqzbs']),2).'%';
            } else {
                $data['t0yql'] = '0%';
                $data['allyql'] = '0%';
                $data['sjyqldata'] = '0%';
            }
            if($data['fjdqzbs'] >0){
                $data['fjyqldata'] = round($data['fjyjzbs'] * 100 / ($data['fjdqzbs']),2).'%';
            }else{
                $data['fjyqldata'] = '0%';
            }
            $re_data[] = $data;
        }

        return $re_data;
    }

    public function setArray($arrays)
    {
        $re = array();
        foreach ($arrays as $array) {
            $re[$array['endtime']] = $array['num'];
        }

        return $re;
    }

    //时间范围内的每一天
    function getDateFromRange($startdate, $enddate)
    {
        $stimestamp = strtotime($startdate);
        $etimestamp = strtotime($enddate);
        // 计算日期段内有多少天
        $days = ceil(($etimestamp - $stimestamp) / 86400)+1;
        // 保存每天日期
        $date = array();
        for ($i = 1; $i < $days; $i++) {
            //$date[] = date('Y-m-d', $stimestamp + (86400 * $i));
            $date[] = $stimestamp + (86399 * $i);
        }
        rsort($date);

        return $date;
    }
}

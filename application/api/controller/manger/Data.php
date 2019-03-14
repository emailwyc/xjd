<?php

namespace app\api\controller\manger;

use app\common\controller\Api;
use think\Db;
use xjd\util\Timeutil;

/**
 * 数据统计
 */
class Data extends Api {
    //如果$noNeedLogin为空表示所有接口都需要登录才能请求
    //如果$noNeedRight为空表示所有接口都需要验证权限才能请求
    //如果接口已经设置无需登录,那也就无需鉴权了
    // 无需登录的接口,*表示全部
    protected $noNeedLogin = ['*'];
    // 无需鉴权的接口,*表示全部
    protected $noNeedRight = ['*'];

    public function _initialize() {
        parent::_initialize();
    }

    public function index() {
        $timeUtil = new Timeutil();
        list($before, $end) = $timeUtil->getTodayTime();
        //今日新增客户 == 注册
        $where['createtime'] = array("between", [$before, $end]);
        $re['reg'] = Db::name('user')->where($where)->count();
        //今日代收
        $re['ds_jr'] = Db::name('user_repayment')
                         ->where(array('create_time' => array("between", [$before, $end])))
                         ->where(array('is_collection' => 2))
                         ->sum('amount');
        // 今日新增认证客户
        $re['rz'] = Db::name('user_info')->where($where)->count();
        //今日新增放款客户,同一个客户也算吧？
        $fk_ordes = Db::name('order')->where($where)->group('uid')->select();
        $re['fk'] = count($fk_ordes);
        //放款总额
        $where_fkze['status'] = array('in', '8,9,10');
        $re['fkze'] = Db::name('order')->where($where_fkze)->sum('amount');
        //还款+逾期
        $where_hk_yq['status'] = array('in', '9,10');
        $re['hk_yq'] = Db::name('order')->where($where_hk_yq)->sum('amount');
        //逾期
        $where_yq['status'] = 10;
        $re['yq'] = Db::name('order')->where($where_yq)->sum('overcost');
        //还款
        $re['hk'] = $re['hk_yq'] - $re['yq'];
        //逾期1-3   1-2
        $where_yq_time['status'] = 10;
        $where_yq_time['overday'] = array('between', [1, 2]);
        $re['yq_1_3'] = Db::name('order')->where($where_yq_time)->count();
        //逾期3-7  3-6
        $where_yq_time['overday'] = array('between', [3, 6]);
        $re['yq_3_7'] = Db::name('order')->where($where_yq_time)->count();
        //逾期7-15 7 -14
        $where_yq_time['overday'] = array('between', [3, 14]);
        $re['yq_7_15'] = Db::name('order')->where($where_yq_time)->count();
        //逾期15 >=15
        $where_yq_time['overday'] = array('>=', '15');
        $re['yq_15'] = Db::name('order')->where($where_yq_time)->count();
        //代收总额
        $re['ds_amount'] = Db::name('user_repayment')->where(array('is_collection' => 2))->sum('amount');
        $this->success('成功', $re);
    }

    public function fkInfo() {
        $params = $this->request->param();
        if (empty($params['end'])) {
            $params['end'] = date('Y-m-d');
        }
        if (empty($params['before'])) {
            //相对于结束时间一个月前
            $params['before'] = date('Y-m-d', strtotime('-1 month', strtotime($params['end'])));
        }
        $where["o.createtime"] = array("between", [strtotime($params['before']), strtotime($params['end'])]);
        if (!empty($params['uid'])) {
            $where['o.uid'] = $params['uid'];
        }
        $page = isset($params['page']) && $params['page'] > 0 ? $params['page'] : 1;
        $offset = isset($params['offset']) && $params['offset'] > 0 ? $params['offset'] : 10;
        $re['list'] = Db::name('order')->alias('o')
                        ->field("o.*,d.nickname,a1.nickname dcidname,a2.nickname allotfcidname")
                        ->join('d_user d', ' o.uid = d.id ', 'LEFT')
                        ->join('d_admin a1', ' o.dcid = a1.id ', 'LEFT')
                        ->join('d_admin a2', ' o.allotfcid = a2.id ', 'LEFT')
                        ->where($where)
                        ->page($page, $offset)
                        ->order('o.createtime desc')
                        ->select();
        $re['count'] = Db::name('order')->alias('o')
                         ->where($where)
                         ->count();
        $this->success('成功', $re);
    }

    public function repayment() {
        $params = $this->request->param();
        if (empty($params['end'])) {
            $params['end'] = date('Y-m-d');
        }
        if (empty($params['before'])) {
            //相对于结束时间一个月前
            $params['before'] = date('Y-m-d', strtotime('-1 month', strtotime($params['end'])));
        }
        $where["ur.create_time"] = array("between", [strtotime($params['before']), strtotime($params['end'])]);
        if (!empty($params['uid'])) {
            $where['ur.mem_id'] = $params['uid'];
        }
        $page = isset($params['page']) && $params['page'] > 0 ? $params['page'] : 1;
        $offset = isset($params['offset']) && $params['offset'] > 0 ? $params['offset'] : 10;
        $re['list'] = Db::name('user_repayment')->alias('ur')
                        ->field("ur.*,d.nickname")
                        ->join('d_user d', ' ur.mem_id = d.id ', 'LEFT')
                        ->where($where)
                        ->page($page, $offset)
                        ->order('ur.create_time desc')
                        ->select();
        $re['count'] = Db::name('user_repayment')->alias('ur')
                         ->where($where)
                         ->count();
        $this->success('成功', $re);
    }
}

<?php

namespace app\api\controller\manger;

use app\common\controller\Api;
use fast\Random;
use think\Db;
use xjd\util\Credit;
use xjd\util\ExportExcel;
use xjd\util\Timeutil;

/**
 * 贷后管理/订单管理
 */
class Order extends Api
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

    public function index()
    {
        $params = $this->request->param();
        if (empty($params['type'])) {
            $this->error('参数错误：type');
        }
        $type = 8;
        if (1 == $params['type']) {//正常还款
            $type = 8;
        } elseif (2 == $params['type']) {//逾期客户
            $type = 10;
        } else {
            $this->error('参数错误：type');
        }
        $where['o.status'] = $type;
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
        $mem_counts = Db::name("order")->field('count(DISTINCT(uid)) count')
            ->where(array('status' => $type))
            ->find();
        $re['mem_count'] = $mem_counts['count'];
        $re['amount'] = Db::name("order")
            ->where(array('status' => $type))
            ->sum('pay');
        $this->success('成功', $re);
    }

    public function urge()
    {
        $params = $this->request->param();
        if (empty($params['end'])) {
            $params['end'] = date('y-m-d h:i:s');
        }
        if (empty($params['before'])) {
            //相对于结束时间一个月前
            $params['before'] = date('y-m-d h:i:s', strtotime('-1 month', strtotime($params['end'])));
        }
        $where["ur.create_time"] = array("between", [strtotime($params['before']), strtotime($params['end'])]);
        $page = isset($params['page']) && $params['page'] > 0 ? $params['page'] : 1;
        $offset = isset($params['offset']) && $params['offset'] > 0 ? $params['offset'] : 10;
        $re['list'] = Db::name('urge')
            ->alias('ur')
            ->field("ur.*,d.nickname")
            ->join('d_admin d', ' ur.urge_id = d.id ', 'LEFT')
            ->where($where)
            ->page($page, $offset)
            ->order('ur.create_time desc')
            ->select();
        // echo Db::name('urge')->getLastSql();
        $re['count'] = Db::name('urge')->alias('ur')
            ->where($where)
            ->count();
        $this->success('成功', $re);
    }

    public function users()
    {
        $params = $this->request->param();
        if (empty($params['end'])) {
            $params['end'] = date('Y-m-d');
        }
        if (empty($params['before'])) {
            //相对于结束时间一个月前
            $params['before'] = date('Y-m-d', strtotime('-1 month', strtotime($params['end'])));
        }
        $where["u.createtime"] = array("between", [strtotime($params['before']), strtotime($params['end'])]);
        if (!empty($params['uid'])) {
            $where['u.id'] = $params['uid'];
        }
        $file = 'u.id,u.nickname,u.mobile,u.createtime,u.`status`,di.score,di.`status` dstatus,di.checkid';
        $re['list'] = Db::name('user')->alias('u')->field($file)
            ->join('d_user_info di', ' u.id = di.uid ', 'LEFT')
            ->where($where)
            ->select();
        $re['count'] = Db::name('user')->alias('u')->field($file)
            ->join('d_user_info di', ' u.id = di.uid ', 'LEFT')
            ->where($where)
            ->count();
        $this->success('成功', $re);
    }

    public function delUsers()
    {
        $params = $this->request->param();
        if (empty($params['uid'])) {
            $this->error('参数错误：uid');
        }
        $where['id'] = $params['uid'];
        $re = Db::name('user')->where($where)->update(array('status' => 1));
        if (!$re) {
            $this->error('设置失败');
        }
        $this->success('设置成功');
    }

    public function setOrders()
    {
        // TODO: 资料审核  财务审核
        $params = $this->request->param();
        if (empty($params['id'])) {
            $this->error('参数错误：id');
        }
        if (empty($params['status'])) {
            $this->error('参数错误：status');
        }
        if (empty($params['allotid'])) {//审核人员
            $this->error('参数错误：allotid');
        }
        $where['id'] = $params['id'];
        $re = Db::name('order')->where($where)->update(array('status' => $params['status']));
        if (!$re) {
            $this->error('设置失败');
        }
        //记录审核状态
        //$admin = Db::name('admin')->where(array('id'=>$params['allotid']))->value('nickname');
        $status = $params['status'];
        $data['order_id'] = $params['id'];
        $data['order_status'] = $params['status'];
        $data['review'] = 2;//人工审核
        $data['create_time'] = time();
        if ($status == 2) {// 2 资料审核通过
            $data['sh_result'] = 1;
            $data['desc'] = '资料审核通过';
        } elseif ($status == 14) { //14 资料审核失败
            $data['sh_result'] = 2;
            $data['desc'] = '资料审核失败';
        } elseif ($status == 15) {//15 财务审核失败
            $data['sh_result'] = 2;
            $data['desc'] = '财务审核失败';
        } elseif ($status == 5) {//5 财务审核通过
            $data['sh_result'] = 1;
            $data['desc'] = '财务审核通过';
        }
        Db::name('sh_log')->insert($data);
        // TODO: 资料审核  财务审核  审核完后 生成订单详情 还款信息  需确定审核流程
        // TODO: 订单开始日期是审核通过后？ 放款时间呢？
        // TODO: 放款后执行？？？
        $order = Db::name('order')->where($where)->find();
        $data['repay_time'] = '';// TODO: 2019/1/4 放款之后
        $data['total_amount'] = $order['pay'];
        $data['corpus'] = $order['amount'];//本金
        $data['accrual'] = $order['cost'];//利息
        $data['status'] = 1;
        $data['order_id'] = $params['id'];
        $data['create_time'] = time();
        $data['update_time'] = time();
        $data['order_code'] = Random::uuid();
        Db::name('order_repay')->insert($data);
        $this->success('设置成功');
    }

    /**
     * 订单管理
     */
    public function orderManger()
    {
        $params = $this->request->param();
        $where['o.status'] = array('in', '0,1,2,3,4,5');
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
        if (!empty($params['type'])) {
            $where['o.status'] = $params['type'];
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
        if (isset($_GET['submit']) && $_GET['submit'] == '导出数据') {
            // if(1){
            $hs_ee_obj = new ExportExcel();
            $expTitle = "订单审核";
            $expCellName = array(
                array("nickname", "姓名"),
                array("mobile", "手机号"),
                array("pay_user_cnt", "贷款开始日期"),
                array("starttime", "贷款结束日期"),
                array("endtime", "贷款金额"),
                array("amount", "应打款金额"),
                array("pay", "应还金额"),
                array("createtime", "申请借款时间"),
                array("dcidname", "负责人"),
                array("status", "审核失败原因")
            );
            $info = Db::name('order')->alias('o')
                ->field("o.*,d.nickname,a1.nickname dcidname,a2.nickname allotfcidname")
                ->join('d_user d', ' o.uid = d.id ', 'LEFT')
                ->join('d_admin a1', ' o.dcid = a1.id ', 'LEFT')
                ->join('d_admin a2', ' o.allotfcid = a2.id ', 'LEFT')
                ->where($where)
                ->page($page, $offset)
                ->order('o.createtime desc')
                ->select();
            $expTableData = $info;
            $hs_ee_obj->export($expTitle, $expCellName, $expTableData);
        }
        $this->success('成功', $re);
    }

    public function setExamineWay()
    {
        $params = $this->request->param();
        $params['set_time'] = time();
        $info = Db::name('examine_way')->find();
        if (empty($info)) {
            $re = Db::name('examine_way')->insert($params);
            if (!$re) {
                $this->error('设置数据失败');
            }
            $this->success('设置数据成功');
        }
        $re = Db::name('examine_way')->where(array('id' => $info['id']))->update($params);
        if (!$re) {
            $this->error('设置数据失败');
        }
        $this->success('设置数据成功');
    }

    public function examineWay()
    {
        $re = Db::name('examine_way')->find();
        $this->success('成功', $re);
    }

    /**
     * 手动分配
     *
     * @param $order_id
     * @param $allotdcid
     * @param $allotfcid
     */
    public function sd_allot($order_id, $allotdcid, $allotfcid)
    {
        $msg = '';
        //分配方式
        $way = Db::name('examine_way')->value('way');
        if (!empty($way['way']) && 1 == $way['way']) {
            $this->error('已设置自动分配，无需手动分配');
        }
        if (empty($order_id) || empty($allotdcid) || empty($allotfcid)) {
            $this->error('参数错误');
        }
        $info = Db::name('order')->where(array('id' => $order_id))->find();
        if (empty($info)) {
            $this->error('参数错误：order_id');
        }
        if (!empty($info['allotdcid']) && !empty($info['allotfcid'])) {
            $this->error('已分配无需分配');
        }
        if (empty($info['allotdcid'])) {//审核人员
            $re_allotdcid = Db::name('order')->where(array('id' => $order_id))->update(
                array('allotdcid' => $allotdcid)
            );
            if ($re_allotdcid) {//成功记录分配
                $this->setAllot($allotdcid);
                $msg = $msg . '审核人员分配成功';
            } else {
                $msg = $msg . '审核人员分配失败';
            }
        } else {
            $re_allotdcid = false;
            $msg = $msg . '审核人员已分配';
        }
        if (empty($info['allotfcid'])) {//财务人员
            $re_allotfcid = Db::name('order')->where(array('id' => $order_id))->update(
                array('allotfcid' => $allotfcid)
            );
            if ($re_allotfcid) {//成功记录分配
                $this->setAllot($allotfcid);
                $msg = $msg . '_财务人员分配成功';
            } else {
                $msg = $msg . '_财务人员分配失败';
            }
        } else {
            $re_allotfcid = false;
            $msg = $msg . '财务人员已分配';
        }
        if (!$re_allotdcid || !$re_allotfcid) {
            $this->error($msg);
        }
        $this->success('分配成功');
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

    public function zd_allot($order_id)
    {
        //审核人员
        $re[] = $this->getMin(1);
        //财务人员
        $re[] = $this->getMin(2);

        return $re;
    }

    public function getMin($type)
    {
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

    public function credit()
    {
        $params = $this->request->param();
        if (empty($params['order_id'])) {
            $this->error('参数错误：order_id');
        }
        if (empty($params['type'])) {
            $this->error('参数错误：type');
        }
        $uid = Db::name('order')->where(array('id' => $params['order_id']))->value('uid');
        $user = Db::name('user')->where(array('id' => $uid))->find();
        $userInfo = Db::name('user_info')->where(array('uid' => $uid))->find();
        $credit = new  Credit();
        $re = $credit->credit($params['type'], $userInfo['realname'], $userInfo['cardid'], $user['mobile']);
        // $this->setCoast(3);
        if (!$re) {
            $this->error('获取数据失败');
        }
        $this->success('获取数据成功', $re);
    }

    public function phone()
    {
        $params = $this->request->param();
        if (empty($params['order_id'])) {
            $this->error('参数错误：order_id');
        }
        $uid = Db::name('order')->where(array('id' => $params['order_id']))->value('uid');
        $re = Db::name('user_telebook')->where(array('uid' => $uid))->select();
        $this->success('成功', $re);
    }

    public function baseInfo()
    {
        $params = $this->request->param();
        if (empty($params['order_id'])) {
            $this->error('参数错误：order_id');
        }
        $uid = Db::name('order')->where(array('id' => $params['order_id']))->value('uid');
        $user = Db::name('user')->where(array('id' => $uid))->find();
        $userInfo = Db::name('user_info')->where(array('uid' => $uid))->find();
        $re['realname'] = $userInfo['realname'];
        $re['cardid'] = $userInfo['cardid'];
        $re['age'] = $this->getAgeByID($re['cardid']);
        $re['card_address'] = $userInfo['card_address'];
        $re['dw_address'] = $userInfo['dw_address'];
        $re['phone'] = $userInfo['phone'];
        $re['score'] = $userInfo['score'];
        $re['mobile'] = $user['mobile'];
        $re['avatar'] = !empty($user['avatar']) ? $this->url . $user['avatar'] : '';
        $re['idfrontpic'] = !empty($user['idfrontpic']) ? $this->url . $user['idfrontpic'] : '';
        $re['idbackpic'] = !empty($user['idbackpic']) ? $this->url . $user['idbackpic'] : '';
        $this->success('成功', $re);
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

    public function credit_user()
    {
        $params = $this->request->param();
        if (empty($params['uid'])) {
            $this->error('参数错误：uid');
        }
        $uid = $params['uid'];
        $user = Db::name('user')->where(array('id' => $uid))->find();
        $userInfo = Db::name('user_info')->where(array('uid' => $uid))->find();
        $credit = new  Credit();
        $re = $credit->credit($params['type'], $userInfo['realname'], $userInfo['cardid'], $user['mobile']);
        // $this->setCoast(3);
        if (!$re) {
            $this->error('获取数据失败');
        }
        $this->success('获取数据成功', $re);
    }

    public function phone_user()
    {
        $params = $this->request->param();
        if (empty($params['uid'])) {
            $this->error('参数错误：uid');
        }
        $uid = $params['uid'];
        $re = Db::name('user_telebook')->where(array('uid' => $uid))->select();
        $this->success('成功', $re);
    }

    public function baseInfo_user()
    {
        $params = $this->request->param();
        if (empty($params['uid'])) {
            $this->error('参数错误：uid');
        }
        $uid = $params['uid'];
        $user = Db::name('user')->where(array('id' => $uid))->find();
        $userInfo = Db::name('user_info')->where(array('uid' => $uid))->find();
        $re['realname'] = $userInfo['realname'];
        $re['cardid'] = $userInfo['cardid'];
        $re['age'] = $this->getAgeByID($re['cardid']);
        $re['card_address'] = $userInfo['card_address'];
        $re['dw_address'] = $userInfo['dw_address'];
        $re['phone'] = $userInfo['phone'];
        $re['score'] = $userInfo['score'];
        $re['mobile'] = $user['mobile'];
        $re['avatar'] = !empty($user['avatar']) ? $this->url . $user['avatar'] : '';
        $re['idfrontpic'] = !empty($user['idfrontpic']) ? $this->url . $user['idfrontpic'] : '';
        $re['idbackpic'] = !empty($user['idbackpic']) ? $this->url . $user['idbackpic'] : '';
        $this->success('成功', $re);
    }

    //订单还款详情
    public function orderRepayDetail()
    {
        $params = $this->request->param();
        if (empty($params['order_id'])) {
            $this->error('参数错误：order_id');
        }
        // TODO: 定时任务统计订单状态
        $re = Db::name('order_repay')->where(array('order_id' => $params['order_id']))->select();
        foreach ($re as &$data) {
            $today = strtotime(date('Y-m-d', time()));
            $data['repay_data'] = date("Y-m-d ", $data['repay_time']);
            $data['is_penalty'] = 1;
            if ($data['status'] == 1 && $today < $data['repay_time']) {
                $data['is_penalty'] = 2;
            }
        }
        $this->success('成功', $re);
    }

    //订单还款详情
    public function userorderlist()
    {
        $params = $this->request->param();
        if (empty($params['uid'])) {
            $this->error('参数错误：uid');
        }
        // TODO: 定时任务统计订单状态
        $re = Db::name('order')->where(array('uid' => $params['uid']))->order("id desc")->select();
        $payMoney = "";
        foreach ($re as &$data) {
            $data['endtime'] = $data["endtime"] ? date("Y-m-d", $data['endtime']) : $data['endtime'];
            $data['statue'] = Db::name("order_type")->where(["typeid" => $data['status']])->value('name');
            $data["statue"] = $data['status'] == 13 ? "审核中" : $data["statue"];
        }
        //查看需要还款的金额
        $payMoney = Db::name('order')->where(array('uid' => $params['uid'], "status" => ["in", [6, 7, 8, 10]]))->value("pay");
        if (!$payMoney) {
            $payMoney = Db::name('order')->where(array('uid' => $params['uid'], "status" => ["in", [0, 1, 2, 3, 4, 5, 13]]))->find() ? "审核中" : "暂无账单";
        }

        $return["payMoney"] = $payMoney;
        $return["list"] = $re;
        $this->success('成功', $return);
    }


    //查看某个用户当前最新的订单状态订单状态追踪
    public function userorderRepayDetail()
    {
        $params = $this->request->param();
        if (empty($params['uid'])) {
            $this->error('参数错误：uid');
        }
        $where['status'] = 8;
        $orders = Db::name("order")->where(['uid' => $params['uid']])->where($where)->order("id desc")->find();
        if ($orders) {
            $order_id = $orders["id"];
            //先去查询当前订单的最新状态
            $status = [];
            $day = $orders['endtime'] ? ceil(($orders['endtime'] - time()) / 86400) : 0;
            $order_pay = Db::name("order_repay")->where(["order_id" => $order_id])->order("id desc")->find();
            $status['repay'] = [
                "repay_id" => !empty($order_pay['id']) ? $order_pay['id'] : 0,
                "repay_data" => date("Y-m-d H:i:s", $orders['createtime']),
                "status" => Db::name("order_type")->where(["typeid" => $orders['status']])->value('name'),
                "is_penalty" => 1,
                "amount" => $orders['amount'],
                "pay" => $orders['pay'],
                "cost" => $orders['cost'],
                "createtime" => $orders['createtime'] ? date("Y-m-d", $orders['createtime']) : $orders['createtime'],
                "starttime" => $orders['starttime'] ? date("Y-m-d", $orders['starttime']) : $orders['starttime'],
                "endtime" => $orders['endtime'] ? date("Y-m-d", $orders['endtime']) : $orders['endtime'],
                "day" => $day >= 0 ? $day : 0,
            ];

            /*$re = Db::name('order_repay')->where(array('order_id' => $order_id))->order("id desc")->select();
            foreach ($re as $data) {
                $today = strtotime(date('Y-m-d', time()));
                $data['repay_data'] = date("Y-m-d H:i:s", $data['repay_time']);
                $data['is_penalty'] = 1;
                if ($data['status'] == 1 && $today < $data['repay_time']) {
                    $data['is_penalty'] = 2;
                }
                $data['status'] = Db::name("order_type")->where(["typeid"=>$data['status']])->value('name');

                $status[] = $data;
            }*/
            $status['bank'] = Db::name('user_bankcard')->field("bank,cardid")->where(["uid" => $params['uid']])->find();
            $status['config'] = Db::name('expect_config')->find();
            if ($status['config']) {
                $status['config']["is_open"] = $status['config']["is_open"] == 1 ? 2 : 1;
            }
//            $status['config']['is_open'] = time() < $orders['endtime'] ? 1 : $status['config']['is_open'];
            $this->success('成功', $status);
        }
        $this->success('成功', []);
    }
}

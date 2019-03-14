<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use think\Db;
use think\Config;
use think\Log;
use think\Session;

/**
 *
 *
 * @icon fa fa-circle-o
 */
class Order extends Backend
{

    /**
     * Order模型对象
     * @var \app\admin\model\Order
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Order;

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
            $where['status'] = 8;
            $total = $this->model
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        //总人数
        $mem_counts = Db::name("order")->field('count(DISTINCT(uid)) count')
            ->where(array('status' => 8))
            ->find();
        $mem_count = $mem_counts['count'];
        $money_sum = Db::name("order")
            ->where(array('status' => 8))
            ->sum('pay');
        $this->assign('mem_count', $mem_count);
        return $this->view->fetch();
    }


    public function fullorderindex()
    {
        $admininfo = Session::get('admin');
        $aid = !empty($admininfo["id"]) ? $admininfo["id"] : 0;
        $group_id = Db::name("auth_group_access")->where(["uid" => $aid])->value("group_id");
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            $type = $this->request->request("type");
            $wheretype = "";
            switch ($type) {
                case "checking":
//                    $wheretype = "o.status in (0,12,13)";
                    $wheretype = "o.status in (0,1)";
                    break;
                case "pass":
//                    $wheretype = "o.status in  (5,6,7,8,10)";
                    $wheretype = "o.status in  (5,6,13)";
                    break;
                case "reject":
                    $wheretype = "o.status in  (11,12,14,15)";
                    break;
                default:
                    $wheretype = "1=1";
                    break;
            }
            $wheretype .= $group_id == 11 ? " and (o.allotdcid=" . $aid . " or (o.allotdcid=0 and o.dcid=" . $aid . "))" : "";
            // 只展示首借的用户,复借直接进入进件放款
            $where_usertype['o.type'] = 1;

            $field = "u.id as uid,u.username,i.realname,MAX(o.id) as id,o.type ut,o.code,SUM(CASE WHEN o.`status`=9 THEN 1 ELSE 0 END) AS payordernum,o.amount,o.pay,o.createtime,o.endtime,o.cycle,o.`status`,a1.nickname as dcnickname,a2.nickname as fcnickname";
            $field = $field . ',dc.channel_name,o.rem';
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
                ->where($where_usertype)
                ->where("o.id in (SELECT MAX(id) as mid FROM d_order GROUP BY uid)")
                ->group("o.uid")
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
                ->where($where_usertype)
                ->where("o.id in (SELECT MAX(id) as mid FROM d_order GROUP BY uid)")
                ->group("o.uid")
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            foreach ($list as &$v) {
                $v["usertype"] = $v['ut'] == 1 ? "首借" : "续借";
                $v["ordertime"] = date("Y-m-d H:i:s", $v["createtime"]);
                $v["payendtime"] = $v["endtime"] == 0 ? date("Y-m-d 23:59:59", $v["createtime"] + ($v['cycle']-1) * 24 * 3600) : date("Y-m-d H:i:s", $v["endtime"]);

                $v["orderstatus"] = Db::name("order_type")->where(["typeid" => $v["status"]])->value("name");
            }
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        //是否显示分配按钮
        $this->assign("group_id", $group_id);
        return $this->view->fetch();
    }

    public function regorderlist()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $field = "u.id as uid,u.username,i.realname,u.createtime,u.status,u.jointime";
            $field = $field . ',dc.channel_name,u.rem';
            $otherwhere = "NOT EXISTS(SELECT 1 FROM d_order WHERE uid = u.id)";
            $total = Db::name("user")->alias("u")
                ->join("d_user_info i", "i.uid = u.id")
                ->join("d_channel dc", "dc.channel_code  = u.channel_code", "left")
                ->where($where)
                ->where($otherwhere)
                ->order($sort, $order)
                ->count();
            $list = Db::name("user")->alias("u")
                ->field($field)
                ->join("d_user_info i", "i.uid = u.id")
                ->join("d_channel dc", "dc.channel_code  = u.channel_code", "left")
                ->where($where)
                ->where($otherwhere)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            foreach ($list as &$v) {
                $v["createtime"] = $v["createtime"] ? $v["createtime"] : $v["jointime"];
                $v["createtime"] = date("Y-m-d H:i:s", $v["createtime"]);
                //认证项
                $authinfo = Db::name("user_authinfo")->alias("u")
                    ->join("d_user_authtype a", "a.code = u.code")->where(["u.uid" => $v['uid'], 'u.status' => 2, 'u.code' => array("<>", "zxy")])->order("u.code")->select();
                $a = [];
                if ($authinfo) {
                    foreach ($authinfo as $value) {
                        $a[] = $value["name"];
                    }
                }

                $v["authinfo"] = implode(",", $a);
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }


    public function pass($ids, $type)
    {
        //$type = 1  通过 2  拒绝  3 取消
        $row = $this->model->get(['id' => $ids]);
        if (!$row)
            $this->error(__('No Results were found'));
        $row['clickType'] = $type;
        $this->view->assign("row", $row->toArray());
        return $this->view->fetch();
    }

    public function editPass()
    {
        $params = $this->request->post("row/a");
        $where['id'] = $params['id'];
        $order = Db::name('order')->where($where)->find();
        if (8 == $order['status']) {
            $this->error('已放款，禁止审核通过');
        }
        $clickType = $params['clickType'];
        $data['id'] = $params['id'];
        $data['rem'] = $params['rem'];
        if (1 == $clickType) {//通过
            $data['status'] = 6;
        } elseif (2 == $clickType) {//拒绝
            $data['status'] = 11;
        } else {//取消
            $data['status'] = 11;
        }
        $re = Db::name('order')->update($data);
        if ($re === false) {
            $this->error('更新数据失败');
        }
        $this->success();

    }

    public function through($ids = "")
    {
        if ($ids) {
            $where['id'] = $ids;
            $order = Db::name('order')->where($where)->find();
            if (empty($order)) {
                $this->error('订单不存在');
            }
            if (8 == $order['status']) {
                $this->error('已放款，禁止审核');
            }
            $data['id'] = $ids;
            $data['status'] = 6;
            $re = Db::name('order')->update($data);
            if ($re === false) {
                $this->error('更新数据失败');
            }
            $this->success();
        }
        $this->error(__('Parameter %s can not be empty', 'ids'));
    }

    public function refuse($ids = "")
    {
        if ($ids) {
            $where['id'] = $ids;
            $order = Db::name('order')->where($where)->find();
            if (empty($order)) {
                $this->error('订单不存在');
            }
            if (8 == $order['status']) {
                $this->error('已放款，禁止审核');
            }
            $data['id'] = $ids;
            $data['status'] = 11;
            $re = Db::name('order')->update($data);
            if ($re === false) {
                $this->error('更新数据失败');
            }
            $this->success();
        }
        $this->error(__('Parameter %s can not be empty', 'ids'));
    }


    public function user($ids, $type)
    {
        //$type = 1  备注 2  废弃
        //$row = $this->model->get(['id' => $ids]);
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            Log::write(__FUNCTION__ . ': params=' . print_r($params, true), 'error');
            $clickType = $params['clickType'];
            Log::write(__FUNCTION__ . ': clickType=' . $clickType, 'error');
            $data['id'] = $params['id'];
            $data['rem'] = $params['rem'];
            Log::write(__FUNCTION__ . ': data=' . print_r($data, true), 'error');
            if (2 == $clickType) {//通过
                $data['status'] = 'black';
            }
            $re = Db::name('user')->update($data);
            if ($re === false) {
                $this->error('更新数据失败');
            }
            $this->success();
        }
        $row = Db::name('user')->where(array('id' => $ids))->find();
        if (!$row)
            $this->error(__('No Results were found'));
        $row['clickType'] = $type;
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    public function userEditPass()
    {
        $params = $this->request->post("row/a");
        Log::write(__FUNCTION__ . ': params=' . print_r($params, true), 'error');
        $clickType = $params['clickType'];
        Log::write(__FUNCTION__ . ': clickType=' . $clickType, 'error');
        $data['id'] = $params['id'];
        $data['rem'] = $params['rem'];
        Log::write(__FUNCTION__ . ': data=' . print_r($data, true), 'error');
        if (2 == $clickType) {//通过
            $data['status'] = 'black';
        }
        $re = Db::name('user')->update($data);
        if ($re === false) {
            $this->error('更新数据失败');
        }
        $this->success();

    }


    public function allotdc()
    {
        //$type = 1  通过 2  拒绝  3 取消
        //$row = $this->model->get(['id' => $ids]);
        $params = $this->request->param();
        if (empty($params['ids'])) {
            $this->error('请先选择订单');
        }
        $order = Db::name("order")->where("id in (" . $params['ids'] . ") and status in (2,3,4,5,6,7,8,9,10,14,15,16)")->find();
        if ($order) {
            $this->error("存在不符合条件的进件");
        }
        //$type = 1  通过 2  拒绝  3 取消
        $where['ga.group_id'] = ["in",[1,11]];
        $list = Db::name('auth_group_access')->alias('ga')
            ->join('d_admin ad', 'ad.id = ga.uid', 'LEFT')
            ->where($where)
            ->select();
        $this->assign("ids", $params['ids']);
        $this->view->assign("row", $list);
        return $this->view->fetch();
    }


    public function editAllotdc()
    {
        $params = $this->request->post("row/a");
        Log::write(__FUNCTION__ . ': params=' . print_r($params, true), 'error');
        if (empty($params['allotdcid'])) {
            $this->error('请指定资料审核员');
        }
        $where['id'] = array('in', $params['id']);//订单id
        $data['allotdcid'] = $params['allotdcid'];
        Log::write(__FUNCTION__ . ': data=' . print_r($data, true), 'error');
        $re = Db::name('order')->where($where)->update($data);
        if ($re === false) {
            $this->error('更新数据失败');
        }
        $this->success();

    }


}

<?php

namespace app\admin\controller\account;

use app\admin\model\Admin;
use app\common\controller\Backend;
use think\Db;
use think\Log;
use think\Session;

/**
 *
 *
 * @icon fa fa-circle-o
 */
class Over extends Backend {
    /**
     * Over模型对象
     *
     * @var \app\admin\model\Over
     */
    protected $model = null;

    public function _initialize() {
        parent::_initialize();
        $this->model = new \app\admin\model\Over;
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

        $admin = Session::get('admin') ;
        $where_id = [];
        if (isset($admin['id']) && ($admin['id']!=1)){
            $uid = $admin['id'];
            $where_id['ad2.id'] =$uid;
        }

        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }

            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
           // $_where['o.overday'] = array('>', '0');
            $_where['o.status'] = array('in','8,10');
            $total = Db::name('order')->alias('o')
                                 ->join('d_user d', ' o.uid = d.id ', 'LEFT')
                                 ->join('d_admin a1', ' o.allotdcid = a1.id ', 'LEFT')
                                 ->join('d_admin a2', ' o.allotfcid = a2.id ', 'LEFT')
                                 ->join("d_channel dc", "dc.channel_code  = d.channel_code", "left")
                                 ->join("d_admin ad", "dc.admin_id  = ad.id", "left")
                                 ->join("d_user_info dui", "dui.uid  = d.id", "left")
                                 ->where($where)
                                 ->where($_where)
                                 ->order($sort, $order)
                                 ->count();


            $list = Db::name('order')->alias('o')
                                ->field("o.*,dui.realname,d.nickname,d.mobile,a1.nickname dcidname,a2.nickname allotfcidname,dc.channel_name,ad.nickname agfrom,ad2.nickname over_mem")
                                ->join('d_user d', ' o.uid = d.id ', 'LEFT')
                                ->join('d_admin a1', ' o.allotdcid = a1.id ', 'LEFT')
                                ->join('d_admin a2', ' o.allotfcid = a2.id ', 'LEFT')
                                ->join("d_channel dc", "dc.channel_code  = d.channel_code", "left")
                                ->join("d_admin ad", "dc.admin_id  = ad.id", "left")
                                ->join("d_admin ad2", "o.over_mem  = ad2.id", "left")
                                ->join("d_user_info dui", "dui.uid  = d.id", "left")
                                ->where($where)
                                ->where($_where)
                                ->where($where_id)
                                ->order($sort, $order)
                                ->limit($offset, $limit)
                                ->select();
//            var_dump($list);die();
            $sql = Db::name('order')->getLastSql();
            Log::write(__FUNCTION__.': sql='.$sql,'error');
            foreach ($list as &$v) {
                // $v["usertype"] = $v['payordernum'] > 0 ? "续借" : "首借";
                $v["endtime"] = date("Y-m-d H:i:s", $v["endtime"]);
                // $v["payendtime"] = date("Y-m-d H:i:s", $v["createtime"] + 7 * 24 * 3600);
                $v["status"] = Db::name("order_type")->where(["typeid" => $v["status"]])->value("name");
            }
            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }

        return $this->view->fetch();
    }

    /**
     * 删除
     */
    public function jq($ids = "") {
        if ($ids) {
            $data['status'] = 9;
            Log::write(__FUNCTION__.': data='.print_r($data, true), 'error');
            //$result = Db::name('order')->where(array('id' => $ids))->update($data);
            //if ($result === false) {
            //    $this->error('结清失败');
           // }
            $order_repay = Db::name('order_repay')->where(array('order_id' => $ids))->order('id desc')->find();
            if (!empty($order_repay)) {
                $repay['status'] = 2;
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
                if (!$re) {
                    $this->error('更新订单失败', null, 5);
                }

            }
            $this->success();
        }
        $this->error(__('Parameter %s can not be empty', 'ids'));
    }

    public function pass($ids,$type='')
    {
        //$type = 1  通过 2  拒绝  3 取消
        //$row = $this->model->get(['id' => $ids]);
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            $where['id'] = $params['id'];
            $order =  Db::name('order')->where($where)->find();
            $over_rem = empty($order['over_rem'])?'':$order['over_rem'].'*;*';
            $data['id'] = $params['id'];
            if(!empty($params['over_rem'])){
                $data['over_rem'] = $over_rem.$params['over_rem'];
                Log::write(__FUNCTION__.': data='.print_r($data,true),'error');
                $re = Db::name('order')->update($data);
                if($re === false){
                    $this->error('更新数据失败');
                }
            }
            $this->success();
        }
        $row = Db::name('order')->where(array('id'=>$ids))->find();
        if (!$row)
            $this->error(__('No Results were found'));
        $row['clickType'] = $type;
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    public function editPass(){
        $params = $this->request->post("row/a");
        $where['id'] = $params['id'];
        $order =  Db::name('order')->where($where)->find();
        $over_rem = empty($order['over_rem'])?'':$order['over_rem'].'*;*';
        $data['id'] = $params['id'];
        if(!empty($params['over_rem'])){
            $data['over_rem'] = $over_rem.$params['over_rem'];
            Log::write(__FUNCTION__.': data='.print_r($data,true),'error');
            $re = Db::name('order')->update($data);
            if($re === false){
                $this->error('更新数据失败');
            }
        }

        $this->success();

    }

    public function black($ids,$type='')
    {
        //$type = 1  通过 2  拒绝  3 取消
        //$row = $this->model->get(['id' => $ids]);
        $order = Db::name('order')->where(array('id'=>$ids))->find();
        if (!$order)
            $this->error(__('No Results were found'));
        $row = Db::name('user')->where(array('id'=>$order['uid']))->find();
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    public function editBlack(){
        $params = $this->request->post("row/a");
        $data['status'] = $params['status'];
        $data['quota'] = $params['quota'];
        $data['id'] = $params['id'];
        Log::write(__FUNCTION__.': data='.print_r($data,true),'error');
        $re = Db::name('user')->update($data);
        if($re === false){
            $this->error('更新数据失败');
        }
        $this->success();

    }


    public function chuis($ids,$type='')
    {
        //$type = 1  通过 2  拒绝  3 取消
        $where['ga.group_id']  = 7;
        $list = Db::name('auth_group_access')->alias('ga')
                  ->join('d_admin ad', 'ad.id = ga.uid', 'LEFT')
                  ->where($where)
                  ->select();
        Log::write(__FUNCTION__.': list='.print_r($list,true),'error');
        $this->assign("ids", $ids);
        $this->view->assign("row", $list);
        return $this->view->fetch();
    }

    public function editChui(){
        $params = $this->request->post("row/a");
        if(empty($params['over_mem'])){
            $this->error('请指定催收人员');
        }
        $data['id'] = $params['id'];//订单id
        $data['over_mem'] = $params['over_mem'];
        Log::write(__FUNCTION__.': data='.print_r($data,true),'error');
        $re = Db::name('order')->update($data);
        if($re === false){
            $this->error('更新数据失败');
        }
        $this->success();

    }

    public function csry()
    {
        //$type = 1  通过 2  拒绝  3 取消
        //$row = $this->model->get(['id' => $ids]);
        $params =$this->request->param();
        if(empty($params['cyids'])){
            $this->error('请先选择订单');
        }
        //$type = 1  通过 2  拒绝  3 取消
        $where['ga.group_id']  = 7;
        $list = Db::name('auth_group_access')->alias('ga')
                  ->join('d_admin ad', 'ad.id = ga.uid', 'LEFT')
                  ->where($where)
                  ->select();
        $this->assign("ids",$params['cyids']);
        $this->view->assign("row", $list);
        return $this->view->fetch();
    }

    public function editCy(){
        $params = $this->request->post("row/a");
        Log::write(__FUNCTION__.': params='.print_r($params,true),'error');
        if(empty($params['over_mem'])){
            $this->error('请指定催收人员');
        }
        $where['id'] = array('in',$params['id']);//订单id
        $data['over_mem'] = $params['over_mem'];
        Log::write(__FUNCTION__.': data='.print_r($data,true),'error');
        $re = Db::name('order')->where($where)->update($data);
        if($re === false){
            $this->error('更新数据失败');
        }
        $this->success();

    }
}
///admin/account/over/csry/cyids/
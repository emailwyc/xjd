<?php

namespace app\admin\controller\account;

use app\common\controller\Backend;
use think\Db;
use think\Log;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Noraml extends Backend
{
    
    /**
     * Noraml模型对象
     * @var \app\admin\model\Noraml
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Noraml;

    }
    
    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */

    /**
     * 查看
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
            $_where['o.overday'] = 0;
            $_where['o.status'] = array('in','10,9');
            $total = Db::name('order')->alias('o')
                ->join('d_user d', ' o.uid = d.id ', 'LEFT')
                ->join('d_admin a1', ' o.allotdcid = a1.id ', 'LEFT')
                ->join('d_admin a2', ' o.allotfcid = a2.id ', 'LEFT')
                ->join("d_user_info dui", "dui.uid  = d.id", "LEFT")
                ->where($where)
                ->where($_where)
                ->order($sort, $order)
                ->count();
            $sql = Db::name('order')->getLastSql();
            Log::write(__FUNCTION__.': sql ='.$sql,'error');

            $list = Db::name('order')->alias('o')
                ->field("o.*,o.type ut,dui.realname,d.nickname,d.mobile,a1.nickname dcidname,a2.nickname allotfcidname")
                ->join('d_user d', ' o.uid = d.id ', 'LEFT')
                ->join('d_admin a1', ' o.allotdcid = a1.id ', 'LEFT')
                ->join('d_admin a2', ' o.allotfcid = a2.id ', 'LEFT')
                ->join("d_user_info dui", "dui.uid  = d.id", "LEFT")
                ->where($where)
                ->where($_where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            foreach ($list as &$v) {
                $v["usertype"] = $v['ut'] == 1 ? "首借" : "续借";
                $v["endtime"] = date("Y-m-d H:i:s", $v["endtime"]);
               // $v["payendtime"] = date("Y-m-d H:i:s", $v["createtime"] + 7 * 24 * 3600);
                $v["status"] = Db::name("order_type")->where(["typeid"=>$v["status"]])->value("name");
            }
            $sql = Db::name('order')->getLastSql();
            Log::write(__FUNCTION__.': sql='.$sql,'error');
            Log::write(__FUNCTION__.': list='.print_r($list,true),'error');

            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 编辑
     */
    public function edit($ids = NULL)
    {
        $row = $this->model->get($ids);
        if (!$row)
            $this->error(__('No Results were found'));
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                $this->error(__('You have no permission'));
            }
        }
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                        $row->validate($validate);
                    }
                    $result = $row->allowField(true)->save($params);
                    if ($result !== false) {
                        $this->success();
                    } else {
                        $this->error($row->getError());
                    }
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

    /**
     * 删除
     */
    public function del($ids = "")
    {
        if ($ids) {
            $data['status'] = 9;
            Log::write(__FUNCTION__.': data='.print_r($data, true), 'error');
            $result = Db::name('order')->where(array('id' => $ids))->update($data);
            if ($result === false) {
                $this->error('结清失败');
            }
            $order_repay = Db::name('order_repay')->where(array('order_id' => $ids))->order('id desc')->find();
            if (!empty($order_repay)) {
                if ($order_repay['status'] = 3) {
                    $repay['status'] = 4;
                } else {
                    $repay['status'] = 2;
                }
                $repay['status'] = 1;
                $re = Db::name('order_repay')->where(array('id' => $order_repay['id']))->update($repay);
                if ($result === false) {
                    $this->error('结清失败');
                }
            }
            $this->success();
        }
        $this->error(__('Parameter %s can not be empty', 'ids'));
    }
    

}

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
class Channelagency extends Backend{
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\ChannelAgency;
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
                    case "agency_name":
                        $where['agency_name'] = ['like','%'.$v.'%'];
                        break;
                    case "agency_code":
                        $where['agency_code'] = ['like','%'.$v.'%'];
                        break;
                    case "agency_phone":
                        $where['agency_phone'] = ['like','%'.$v.'%'];
                        break;
                    case "createtime":
                        $arr = explode(' - ',$v);
                        $start = strtotime($arr[0]);
                        $end = strtotime($arr[1]);
                        $where['createtime'] = ['between',[$start,$end]];
                        break;
                }
            }
            //list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $sort = $req['sort'];
            $order = $req['order'];
            $offset = $req['offset'];
            $limit = $req['limit'];
            $total = Db::name("channel_agency")
                ->where($where)
                ->count();
            $list = Db::name("channel_agency")
                ->field("id,agency_name,agency_code,agency_phone,FROM_UNIXTIME(createtime,'%Y-%m-%d %H:%i:%s') as createtime")
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }

    public function add(){
        if ($this->request->isPost()){
            $params = $this->request->post("row/a");
            if ($params)
            {
                $params['agency_name'] = $params['agency_name'];
                $params['agency_code'] = $params['agency_code'];
                $params['agency_phone'] = $params['agency_phone'];
                $params['createtime'] = time();
                $params['updatetime'] = time();
                $result = $this->model->save($params);
                if ($result === false)
                {
                    $this->error($this->model->getError());
                }
                $this->success();
            }
            $this->error();
        }
        return $this->view->fetch();
    }

    public function edit($ids=''){
        if ($this->request->isPost()){
            $row = $this->model->get(['id' => $ids]);
            if(!$row){
                $this->error('该渠道代理不存在');
            }
            $params = $this->request->post("row/a");
            if ($params)
            {
                $params['agency_name'] = $params['agency_name'];
                $params['agency_code'] = $params['agency_code'];
                $params['agency_phone'] = $params['agency_phone'];
                $params['updatetime'] = time();
                $result = $row->save($params);
                if ($result === false)
                {
                    $this->error($row->getError());
                }
                $this->success();
            }
            $this->error();
        }
        $row = Db::name("channel_agency")->where('id','=',$ids)->find();
        $this->view->assign('row',$row);
        return $this->view->fetch();
    }

    public function del($ids=''){
        if ($ids){
            $this->model->where('id', '=', $ids)->delete();
            $this->success();
        }
        $this->error();
    }
}

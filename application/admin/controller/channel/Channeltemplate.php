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
class Channeltemplate extends Backend{
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\ChannelTemplate;
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
                    case "template_name":
                        $where['template_name'] = ['like','%'.$v.'%'];
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
            $total = Db::name("channel_template")
                ->where($where)
                ->count();
            $list = Db::name("channel_template")
                ->field("id,template_name,template_preview_pc,template_preview_app,template_url,FROM_UNIXTIME(createtime,'%Y-%m-%d %H:%i:%s') as createtime")
                ->where($where)
                ->order($sort,'asc')
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
                $params['template_name'] = $params['template_name'];
                $params['template_preview_pc'] = $params['template_preview_pc'];
                $params['template_preview_app'] = $params['template_preview_app'];
                $params['template_url'] = $params['template_url'];
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
                $this->error('该渠道模板不存在');
            }
            $params = $this->request->post("row/a");
            if ($params)
            {
                $params['template_name'] = $params['template_name'];
                $params['template_preview_pc'] = $params['template_preview_pc'];
                $params['template_preview_app'] = $params['template_preview_app'];
                $params['template_url'] = $params['template_url'];
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
        $row = Db::name("channel_template")->where('id','=',$ids)->find();
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

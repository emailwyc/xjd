<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace app\admin\controller\channel;

use app\common\controller\Backend;
use think\Db;
use fast\Random;

/**
 * Description of Channel
 *
 * @author tomato
 */
class Channel extends Backend {

    protected $model = null;

    public function _initialize() {
        parent::_initialize();
        $this->model = new \app\admin\model\Channel;
    }

    public function index() {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            $req = $this->request->request();
            $search = json_decode($req['filter'], true);
            $where = [];
            foreach ($search as $k => $v) {
                switch ($k) {
                    case "channel_name":
                        $where['c.channel_name'] = ['like', '%' . $v . '%'];
                        break;
                    case "channel_code":
                        $where['c.channel_code'] = ['like', '%' . $v . '%'];
                        break;
                    case "settle_type":
                        $where['c.settle_type'] = ['like', '%' . $v . '%'];
                        break;
                    case "username":
                        $where['a.username'] = ['like', '%' . $v . '%'];
                        break;
                    case "template_name":
                        $where['t.template_name'] = ['like', '%' . $v . '%'];
                        break;
                    case "createtime":
                        $arr = explode(' - ', $v);
                        $start = strtotime($arr[0]);
                        $end = strtotime($arr[1]);
                        $where['c.createtime'] = ['between', [$start, $end]];
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
                    ->join('d_admin a', 'c.admin_id = a.id', 'LEFT')
                    ->join('d_channel_template t', 'c.template_id = t.id', 'LEFT')
                    ->group('c.id')
                    ->where($where)
                    ->count();
            $list = Db::name("channel")
                    ->alias('c')
                    ->join('d_admin a', 'c.admin_id = a.id', 'LEFT')
                    ->join('d_channel_template t', 'c.template_id = t.id', 'LEFT')
                    ->field("c.id,c.channel_name,c.channel_code,channel_url,c.settle_type,c.channel_desc,a.username,a.pw,t.template_name,t.template_preview_pc,t.template_preview_app,FROM_UNIXTIME(c.createtime,'%Y-%m-%d %H:%i:%s') as createtime")
                    ->where($where)
                    ->group('c.id')
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }

    private function createGuid($namespace = null) {
        static $guid = '';
        $uid = uniqid("", true);
        $data = $namespace;
        $data .= $_SERVER ['REQUEST_TIME'];  // 请求那一刻的时间戳
        $data .= $_SERVER ['HTTP_USER_AGENT'];  // 获取访问者在用什么操作系统
        $data .= $_SERVER ['SERVER_ADDR'];   // 服务器IP
        $data .= $_SERVER ['SERVER_PORT'];   // 端口号
        $data .= $_SERVER ['REMOTE_ADDR'];   // 远程IP
        $data .= $_SERVER ['REMOTE_PORT'];   // 端口信息

        $hash = strtoupper(hash('ripemd128', $uid . $guid . md5($data)));
        $guid = substr($hash, 0, 8) . '-' . substr($hash, 8, 4) . '-' . substr($hash, 12, 4) . '-' . substr($hash, 16, 4) . '-' . substr($hash, 20, 12);
        return $guid;
    }

    public function add() {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");

            if ($params) {
                $admin['username'] = $admin['nickname'] = 'channel' . date('YmdHis') . mt_rand(10, 99);
                $admin['pw'] = mt_rand(100000,999999);
                $admin['salt'] = Random::alnum();
                $admin['password'] = md5(md5($admin['pw']) . $admin['salt']);
                $admin['avatar'] = '/assets/img/avatar.png'; //设置新管理员默认头像。
                $admin['status'] = 'normal';
                $admin['createtime'] = $admin['updatetime'] = time();
                $result = model('Admin')->save($admin);
                if ($result === false) {
                    $this->error();
                } else {
                    $admin_id = model('Admin')->id;
                    $data = ['uid' => $admin_id, 'group_id' => 6];
                    $res = model('AuthGroupAccess')->save($data);
                    if ($res === false) {
                        $this->error();
                    } else {
                        $params['channel_name'] = $params['channel_name'];
                        $params['settle_type'] = $params['settle_type'];
                        $params['channel_desc'] = $params['channel_desc'];
                        $params['template_id'] = $params['template_id'];
                        $params['admin_id'] = $admin_id;
                        $params['channel_code'] = $this->createGuid();
                        if ($params['template_id']) {
                            $templateUrl = Db::name("channel_template")->where('id', '=', $params['template_id'])->value('template_url');
                            $params['channel_url'] = $templateUrl . '?channelCode=' . $params['channel_code'];
                        }
                        $params['createtime'] = time();
                        $params['updatetime'] = time();
                        $result = $this->model->save($params);
                        if ($result === false) {
                            $this->error();
                        }
                        $this->success();
                    }
                }
            }
            $this->error();
        }
        //$agency = Db::name("channel_agency")->column('id,agency_name');
        $template = Db::name("channel_template")->column('id,template_name');
        /*$admin = Db::name("admin")->alias('a')
                ->join('d_auth_group_access aga', 'a.id = aga.uid', 'LEFT')
                ->join('d_auth_group ag', 'aga.group_id = ag.id', 'LEFT')
                ->where('ag.type', '=', '6')
                ->column('a.id,a.nickname');*/
       // $this->view->assign('admin', $admin);
//        $this->view->assign('agency',$agency);
        $this->view->assign('template', $template);
        return $this->view->fetch();
    }

    public function edit($ids = '') {
        if ($this->request->isPost()) {
            $row = $this->model->get(['id' => $ids]);
            if (!$row) {
                $this->error('该渠道不存在');
            }
            $params = $this->request->post("row/a");
            if ($params) {
                $params['channel_name'] = $params['channel_name'];
                $params['settle_type'] = $params['settle_type'];
                $params['channel_desc'] = $params['channel_desc'];
                $params['template_id'] = $params['template_id'];
//                $params['agency_id'] = $params['agency_id'];
                if ($params['template_id'] != $row->template_id) {
                    $templateUrl = Db::name("channel_template")->where('id', '=', $params['template_id'])->value('template_url');
                    $params['channel_url'] = $templateUrl . '?channelCode=' . $row->channel_code;
                }
                $params['updatetime'] = time();
                $result = $row->save($params);
                if ($result === false) {
                    $this->error($row->getError());
                }
                $this->success();
            }
            $this->error();
        }
        $row = Db::name("channel")->where('id', '=', $ids)->find();
        //$agency = Db::name("channel_agency")->column('id,agency_name');
        $template = Db::name("channel_template")->column('id,template_name');
        $this->view->assign('row', $row);
//        $this->view->assign('agency',$agency);
        $this->view->assign('template', $template);
        return $this->view->fetch();
    }

    public function del($ids = '') {
        if ($ids) {
            $this->model->where('id', '=', $ids)->delete();
            $this->success();
        }
        $this->error();
    }

}

<?php

namespace app\admin\controller\user;

use app\common\controller\Backend;
use think\Db;
/**
 * 会员组管理
 *
 * @icon fa fa-users
 */
class Group extends Backend
{

    /**
     * @var \app\admin\model\UserGroup
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('UserGroup');
        $this->view->assign("statusList", $this->model->getStatusList());
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
                    case "username":
                        $where['username'] = ['like','%'.$v.'%'];
                        break;
                    case "nickname":
                        $where['nickname'] = ['like','%'.$v.'%'];
                        break;
                    case "email":
                        $where['email'] = ['like','%'.$v.'%'];
                        break;
                    case "mobile":
                        $where['mobile'] = ['like','%'.$v.'%'];
                        break;
                }
            }
            $where['u.status'] = ['=','black'];

            //list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $sort = $req['sort'];
            $order = $req['order'];
            $offset = $req['offset'];
            $limit = $req['limit'];
            $total = Db::name("user")->alias('u')
                ->where($where)
                ->count();
            $list = Db::name("user")->alias('u')
                ->join('user_info ui','ui.uid = u.id')
                ->field("u.id,u.username,ui.realname nickname,u.mobile,u.email,u.quota")
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }

    public function add()
    {
        $nodeList = \app\admin\model\UserRule::getTreeList();
        $this->assign("nodeList", $nodeList);
        return parent::add();
    }

    public function edit($ids = NULL)
    {
        if ($this->request->isPost()){
            $row = model('user')->get(['id' => $ids]);
            if(!$row){
                $this->error('该用户不存在');
            }
            $params = $this->request->post("row/a");
            if ($params)
            {
                $params['status'] = $params['status'];
                $params['quota'] = $params['quota'];
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
        $row = Db::name("user")->where('id','=',$ids)->find();
        $this->view->assign('row',$row);
        return $this->view->fetch();
    }

}

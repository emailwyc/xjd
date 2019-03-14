<?php

namespace app\admin\controller\user;

use app\common\controller\Backend;

/**
 * 会员管理
 *
 * @icon fa fa-user
 */

use think\Db;
use think\Session;

class User extends Backend
{

    protected $relationSearch = true;


    /**
     * @var \app\admin\model\User
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('User');
    }

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
            $req = $this->request->request();
            $search = json_decode($req['filter'],true);
            $where['u.status'] = ['=','normal'];
            $type = Session::get('userTabStatus');
            if($type == 'keeping'){
                $where['d.status'] = ['=',9];
                $where['d.id'] = ['not in',"select id from d_order group by id where type=2"];
            }
            $where_realname ='';
            foreach($search as $k=>$v){
                switch ($k){
                    case "id":
                        $where['u.id'] = ['=',$v];
                        break;
                    case "username":
                         $where['u.username'] = ['like','%'.$v.'%'];
                        break;
                    case "realname":
                        $where_realname['dui.realname'] = ['like','%'.$v.'%'];
                        break;
                    case "nickname":
                        $where['u.nickname'] = ['like','%'.$v.'%'];
                        break;
                    case "mobile":
                        $where['u.mobile'] = ['like','%'.$v.'%'];
                        break;
                    case "email":
                        $where['u.email'] = ['like','%'.$v.'%'];
                        break;
                    case "amount":
                        $where['d.amount'] = ['=',$v];
                        break;
                    case "pay":
                        $where['d.pay'] = ['=',$v];
                        break;
                    case "cost":
                        $where['d.cost'] = ['=',$v];
                        break;
                    case "overcost":
                        $where['d.overcost'] = ['=',$v];
                        break;
                    case "status":
                        $where['d.status'] = ['=',$v];
                        break;
                    case "createtime":
                        $arr = explode(' - ',$v);
                        $start = strtotime($arr[0]);
                        $end = strtotime($arr[1]);
                        $where['d.createtime'] = ['between',[$start,$end]];
                        break;
                    case "starttime":
                        $arr = explode(' - ',$v);
                        $start = strtotime($arr[0]);
                        $end = strtotime($arr[1]);
                        $where['d.starttime'] = ['between',[$start,$end]];
                        break;
                    case "endtime":
                        $arr = explode(' - ',$v);
                        $start = strtotime($arr[0]);
                        $end = strtotime($arr[1]);
                        $where['d.endcreate'] = ['between',[$start,$end]];
                        break;
                }
            }
            $sort = $req['sort'];
            $order = $req['order'];
            $offset = $req['offset'];
            $limit = $req['limit'];
            $total = Db::name("user")
                ->alias('u')
                ->where($where)
                ->join('(SELECT * FROM d_order o WHERE o.id in (SELECT MAX(id) as mid FROM d_order GROUP BY uid)) d', 'u.id=d.uid ', 'LEFT')
                ->count();
            $list = Db::name("user")
                ->alias('u')
                ->field("u.id,u.mobile,u.username,u.nickname,u.email,u.quota,d.amount,d.pay,d.cost,d.overcost,d.overday,d.createtime,d.starttime,d.endtime,d.status")
                ->field("dui.realname")
                ->join('(SELECT * FROM d_order o WHERE o.id in (SELECT MAX(id) as mid FROM d_order GROUP BY uid)) d', 'u.id = d.uid', 'LEFT')
                ->join("d_user_info dui", "dui.uid  = u.id", "LEFT")
                ->where($where)
                ->where($where_realname)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            $statusName = array('待审核', '资料审核', '资料审核通过','放款审核', '放款审核通过', '审核通过
', '待放款', '放款中', '已放款', '已还款', '逾期', '未通过', '机审失败', '机审成功', '资料审核失
败', '财务审核失败','放款失败');
            foreach ($list as $k => $v) {
                $list[$k]['createtime'] = $v['createtime'] ? date('Y-m-d H:i:s', $v['createtime']) : '';
                $list[$k]['starttime'] = $v['starttime'] ? date('Y-m-d H:i:s', $v['starttime']) : '';
                $list[$k]['endtime'] = $v['endtime'] ? date('Y-m-d H:i:s', $v['endtime']) : '';
                $list[$k]['status'] = $v['createtime'] ? $statusName[$v['status']] : '';
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        Session::set('userTabStatus','');
        return $this->view->fetch();
    }

    public function setStatus(){
        $req = $this->request->request();
        Session::set('userTabStatus',$req['type']);

    }

    /**
     * 编辑
     */
    public function edit($ids = NULL)
    {
        if ($this->request->isPost()){
            $row = $this->model->get(['id' => $ids]);
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

<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace app\admin\controller\channel;

use app\common\controller\Backend;
use \think\Session;
use think\Db;
/**
 * Description of Channel
 *
 * @author tomato
 */
class Channeluser extends Backend{
    protected $model = null;

    public function _initialize(){
        parent::_initialize();
    }

    public function index(){
        $id = Session::get('admin.id');
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            $req = $this->request->request();
            $search = json_decode($req['filter'],true);
            if(isset($search['createtime']) && $search['createtime']){
                $arr = explode(' - ',$search['createtime']);
                $start = strtotime($arr[0]);
                $end = strtotime($arr[1]);
                $regWhere['createtime'] = ['between',[$start,$end]];
                $orderWhere['createtime'] = ['between',[$start,$end]];
            }
            $sort = $req['sort'];
            $order = $req['order'];
            $offset = $req['offset'];
            $limit = $req['limit'];
            $total = Db::name("channel")->where('admin_id','=',$id)->count();
            $list = Db::name("channel")->where('admin_id','=',$id)->field('id,channel_name,channel_code')->order($sort, $order)->limit($offset, $limit)->select();
            $channelIds = array_column($list, 'channel_code');

            $regWhere['channel_code'] = ['in',$channelIds];
            $register = Db::name("user")->where($regWhere)->field('id,channel_code')->select();
            $userIds = array_column($register, 'id');
            $orderWhere['uid'] = ['in',$userIds];
            $orderWhere['type'] = ['=',1];
            $order = Db::name("order")->where($orderWhere)->field('uid,status')->select();

            foreach($list as $key=>$value){
                $regCount = 0;
                $applyCount = 0;
                $txCount = 0;
                foreach($register as $k=>$v){
                    if($value['channel_code'] == $v['channel_code']){
                        foreach($order as $ke=>$va){
                            if($v['id'] == $va['uid']){
                                $applyCount += 1;
                                if(in_array($va['status'],[8,9,10])){
                                    $txCount += 1;
                                }
                            }
                        }
                        $regCount += 1;
                    }
                }
                $list[$key]['regCount'] = $regCount;
                $list[$key]['applyCount'] = $applyCount;
                $list[$key]['txCount'] = $txCount;
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }
}

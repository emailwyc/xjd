<?php

namespace app\api\controller\client;

use app\common\controller\Api;
use think\Db;
use xjd\util\Credit;
use xjd\util\ExportExcel;
use xjd\util\Timeutil;

/**
 * 贷后管理/订单管理
 */
class Message extends Api {
    //如果$noNeedLogin为空表示所有接口都需要登录才能请求
    //如果$noNeedRight为空表示所有接口都需要验证权限才能请求
    //如果接口已经设置无需登录,那也就无需鉴权了
    // 无需登录的接口,*表示全部
    protected $noNeedLogin = ['*'];
    // 无需鉴权的接口,*表示全部
    protected $noNeedRight = ['*'];

    public function _initialize() {
        parent::_initialize();
    }

    public function index() {
        $params = $this->request->param();
        if (empty($params['uid'])) {
            $this->error('参数错误：uid');
        }
        $where['o.uid'] = $params['uid'];
        $page = isset($params['page']) && $params['page'] > 0 ? $params['page'] : 1;
        $offset = isset($params['offset']) && $params['offset'] > 0 ? $params['offset'] : 10;
        $re['list'] = Db::name('message')->alias('o')
                        ->field("o.*,d.nickname")
                        ->join('d_user d', ' o.uid = d.id ', 'LEFT')
                        ->where($where)
//                        ->page($page, $offset)
                        ->order('o.create_time desc')
                        ->select();
        foreach ($re['list'] as &$v) {
            $v["create_time"] = date("Y/m/d", $v["create_time"]);
        }
        $re['count'] = Db::name('message')->alias('o')
                         ->where($where)
                         ->count();
        $this->success('成功', $re);
    }

    public function read() {
        $params = $this->request->param();
        if (empty($params['id'])) {
            $this->error('参数错误：id');
        }
        Db::name('message')->where(array('id' => $params['id']))->update(array('status' => 1));
        $this->success('成功');
    }

    public function allRead() {
        Db::name('message')->where('1=1')->update(array('status' => 1));
        $this->success('成功');
    }

    public function getScrollMsg(){
        $data = array(
            ["content"=>"用户158***1111成功借款","money"=>"1000元"],
            ["content"=>"用户158***2222成功借款","money"=>"1000元"],
            ["content"=>"用户158***3333成功借款","money"=>"1000元"],
            ["content"=>"用户158***4444成功借款","money"=>"1000元"],
            ["content"=>"用户158***5555成功借款","money"=>"1000元"],
            ["content"=>"用户158***6666成功借款","money"=>"1000元"],
        );
        $this->success("成功",$data);
    }

    public function getBanners(){
        $data = array(
            ["url"=>"http://47.104.73.125:82/xjd_server/public/uploads/banner01_2x.png"],
            ["url"=>"http://47.104.73.125:82/xjd_server/public/uploads/banner02_2x.png"],
            ["url"=>"http://47.104.73.125:82/xjd_server/public/uploads/banner03_2x.png"],
        );
        $this->success("成功",$data);
    }
}

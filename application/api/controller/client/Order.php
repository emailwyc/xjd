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
class Order extends Api {
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
        $re['list'] = Db::name('order')->alias('o')
                        ->field("o.*,d.nickname,a1.nickname dcidname,a2.nickname allotfcidname")
                        ->join('d_user d', ' o.uid = d.id ', 'LEFT')
                        ->join('d_admin a1', ' o.dcid = a1.id ', 'LEFT')
                        ->join('d_admin a2', ' o.allotfcid = a2.id ', 'LEFT')
                        ->where($where)
                        ->page($page, $offset)
                        ->order('o.createtime desc')
                        ->select();
        $re['count'] = Db::name('order')->alias("o")
                         ->where($where)
                         ->count();
        $this->success('成功', $re);
    }
}

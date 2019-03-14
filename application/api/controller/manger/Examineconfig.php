<?php

namespace app\api\controller\manger;

use app\common\controller\Api;
use think\Db;
use xjd\util\Timeutil;

/**
 * 审核配置
 */
class Examineconfig extends Api {
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

    /**
     * 查询
     */
    public function index() {
        $re['examine'] = Db::name('examine_config')->find();
        $re['area'] = Db::name('china')->alias('c1')
                        ->where('EXISTS(SELECT 1 FROM d_china c2 WHERE Pid=0 and Id != 0 AND (c1.Pid = c2.Id))')
                        ->select();;
        $area_pis = $re['examine']['area'];
        $where['Pid'] = array('in', $area_pis);
        $areas = Db::name('china')->where($where)->select();
        $re['examine']['area_name'] = implode(",", $areas);
        $this->success('成功', $re);
    }

    /**
     * 设置
     */
    public function set() {
        $params = $this->request->param();
        $info = Db::name('examine_config')->find();
        if (empty($info)) {
            $params['create_time'] = time();
            $params['update_time'] = time();
            $re = Db::name('examine_config')->insert($params);
            if (!$re) {
                $this->error('设置数据失败');
            }
            $this->success('设置数据成功');
        }
        $params['update_time'] = time();
        $re = Db::name('examine_config')->where(array('id' => $info['id']))->update($params);
        if (!$re) {
            $this->error('设置数据失败');
        }
        $this->success('设置数据成功');
    }
}

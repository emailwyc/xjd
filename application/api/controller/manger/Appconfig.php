<?php

namespace app\api\controller\manger;

use app\common\controller\Api;
use think\Db;
use xjd\util\Timeutil;

/**
 * APP包 模板配置
 */
class Appconfig extends Api {
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
        $re = Db::name('app_config')->find();
        $this->success('成功', $re);
    }



    /**
     * 设置
     */
    public function set() {
        $params = $this->request->param();
        $info = Db::name('app_config')->find();
        if (empty($info)) {
            $params['create_time'] = time();
            $params['update_time'] = time();
            $re = Db::name('app_config')->insert($params);
            if (!$re) {
                $this->error('设置数据失败');
            }
            $this->success('设置数据成功');
        }
        $params['update_time'] = time();
        $re = Db::name('app_config')->where(array('id' => $info['id']))->update($params);
        if (!$re) {
            $this->error('设置数据失败');
        }
        $this->success('设置数据成功');
    }


}

<?php

namespace app\api\controller\manger;

use app\common\controller\Api;
use think\Db;
use xjd\util\Timeutil;

/**
 * APP 模板配置
 */
class Sysconfig extends Api {
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
        $re = Db::name('sys_config')->find();
        $this->success('成功', $re);
    }

    /**
     *添加
     */
    public function add() {
        $params = $this->request->param();
        $info = Db::name('sys_config')->find();
        if ($info) {
            $this->error('配置已存在');
        }
        $params['create_time'] = time();
        $params['update_time'] = time();
        $re = Db::name('sys_config')->insert($params);
        if (!$re) {
            $this->error('保存数据失败');
        }
        $this->success('保存数据成功');
    }

    /**
     * 修改
     */
    public function update() {
        $params = $this->request->param();
        $info = Db::name('sys_config')->find();
        if (empty($info)) {
            $params['create_time'] = time();
            $params['update_time'] = time();
            $re = Db::name('sys_config')->insert($params);
            if (!$re) {
                $this->error('保存数据失败');
            }
            $this->success('保存数据成功');
        }
        $params['update_time'] = time();
        $re = Db::name('sys_config')->where(array('id' => $info['id']))->update($params);
        if (!$re) {
            $this->error('更新数据失败');
        }
        $this->success('更新数据成功');
    }

    /**
     * 删除
     */
    public function del() {
        $re = Db::name('sys_config')->delete();
        if (!$re) {
            $this->error('删除数据失败');
        }
        $this->success('删除数据成功');
    }
}

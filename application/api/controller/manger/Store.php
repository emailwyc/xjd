<?php

namespace app\api\controller\manger;

use app\common\controller\Api;
use think\Db;
use xjd\util\Timeutil;

/**
 * APP 模板配置
 */
class Store extends Api {
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
        $params = $this->request->param();
        if (empty($params['end'])) {
            $params['end'] = date('Y-m-d');
        }
        if (empty($params['before'])) {
            //相对于结束时间一个月前
            $params['before'] = date('Y-m-d', strtotime('-1 month', strtotime($params['end'])));
        }
        $page = isset($params['page']) && $params['page'] > 0 ? $params['page'] : 1;
        $offset = isset($params['offset']) && $params['offset'] > 0 ? $params['offset'] : 10;
        $re['list'] = Db::name('store')
                        ->page($page, $offset)
                        ->select();
        $re['count'] = Db::name('store')->count();
        $this->success('成功', $re);
    }

    /**
     *添加
     */
    public function add() {
        $params = $this->request->param();
        $info = Db::name('store')->where(array('name' => $params['name']))->find();
        if ($info) {
            $this->error('该场景已存在');
        }
        $params['code'] = md5(time().$params['name']);
        $re = Db::name('store')->insert($params);
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
        $info = Db::name('store')->where(array('id' => $params['id']))->find();
        if (empty($info)) {
            $this->error('数据不存在');
        }
        $re = Db::name('store')->where(array('id' => $params['id']))->update($params);
        if (!$re) {
            $this->error('更新数据失败');
        }
        $this->success('更新数据成功');
    }

    /**
     * 删除
     */
    public function del() {
        $params = $this->request->param();
        $re = Db::name('store')->where(array('id' => $params['id']))->delete();
        if (!$re) {
            $this->error('删除数据失败');
        }
        $this->success('删除数据成功');
    }

    public function statistics() {
        $params = $this->request->param();
        if (empty($params['end'])) {
            $params['end'] = date('Y-m-d');
        }
        if (empty($params['before'])) {
            //相对于结束时间一个月前
            $params['before'] = date('Y-m-d', strtotime('-1 month', strtotime($params['end'])));
        }
        $page = isset($params['page']) && $params['page'] > 0 ? $params['page'] : 1;
        $offset = isset($params['offset']) && $params['offset'] > 0 ? $params['offset'] : 10;
        $re = Db::name('store_statistics')
                ->page($page, $offset)
                ->order('create_time desc')
                ->select();
        $this->success('成功', $re);
    }
}

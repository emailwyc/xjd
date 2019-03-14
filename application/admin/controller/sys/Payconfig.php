<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/1/6
 * Time: 14:52
 */

namespace app\admin\controller\sys;

use app\common\controller\Backend;
use think\Db;

class Payconfig extends Backend{
    /**
     * App模型对象
     * @var \app\admin\model\Store
     */
    protected $model = null;
    public function _initialize() {
        parent::_initialize();
        $this->model = new \app\admin\model\sys\Payconfig;
    }

    /**
     * 编辑
     */
    public function edit($ids = null){
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            $info = Db::name('pay_config')->find();
            $params['set_time'] = time();
            if (empty($info)) {
                $re = Db::name('pay_config')->insert($params);
                if (!$re) {
                    $this->error('设置数据失败');
                }
                $this->success('设置数据成功');
            }
            $re = Db::name('pay_config')->where(array('id' => $info['id']))->update($params);
            if (!$re) {
                $this->error('设置数据失败');
            }
            $this->success('设置数据成功');
        }
        $row = Db::name('pay_config')->find();
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }
}
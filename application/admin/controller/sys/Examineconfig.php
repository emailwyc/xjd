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

class Examineconfig extends Backend{
    /**
     * App模型对象
     * @var \app\admin\model\Store
     */
    protected $model = null;
    public function _initialize() {
        parent::_initialize();
        $this->model = new \app\admin\model\sys\Examineconfig;
    }

    /**
     * 编辑
     */
    public function edit($ids = null){
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            $info = Db::name('examine_config')->find();
            if (empty($info)) {
                $re = Db::name('examine_config')->insert($params);
                if (!$re) {
                    $this->error('设置数据失败');
                }
                $this->success('设置数据成功');
            }
            $re = Db::name('examine_config')->where(array('id' => $info['id']))->update($params);
            if (!$re) {
                $this->error('设置数据失败');
            }
            $this->success('设置数据成功');
        }
        $row = Db::name('examine_config')->find();
        $area = Db::name('china')->alias('c1')
                        ->where('EXISTS(SELECT 1 FROM d_china c2 WHERE Pid=0 and Id != 0 AND (c1.Pid = c2.Id))')
                        ->column('Id,Name');
//        if($row['area']){
//            $areas = Db::name('china')->where('Id','in',$row['area'])->column('Id,Name');
//        }else{
//            $areas = [];
//        }
        //$row['area_name'] = implode(",", $areas);
        $this->view->assign("area", $area);
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }
}
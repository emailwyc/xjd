<?php

namespace app\api\controller\client;

use app\common\controller\Api;
use think\Db;
use xjd\util\Credit;
use xjd\util\ExportExcel;
use xjd\util\Fypay;
use xjd\util\Timeutil;

/**
 * 测试
 */
class UserInfo extends Api {
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

    public function setNickName() {
        $params = $this->request->param();
        $need_params = array('uid', 'nickname');
        $check = $this->params_verify($params, $need_params);
        if (!empty($check)) {
            $this->error('参数错误：'.$check);
        }
        $user = Db::name('user')->where(array('id' => $params['uid']))->find();
        if (empty($user)) {
            $this->error('参数错误：uid');
        }
        if (!empty($user['nickname']) && $params['nickname'] == $user['nickname']) {
            $this->success('成功');
        }
        $re = Db::name('user')->where(array('id' => $params['uid']))->update(array('nickname' => $params['nickname']));
        if (!$re) {
            $this->error('昵称修改失败');
        }
        $this->success('成功');
    }

    public function getBankInfo() {
        $params = $this->request->param();
        $need_params = array('uid');
        $check = $this->params_verify($params, $need_params);
        if (!empty($check)) {
            $this->error('参数错误：'.$check);
        }
        $user = Db::name('user')->where(array('id' => $params['uid']))->find();
        if (empty($user)) {//校验用户
            $this->error('参数错误：uid');
        }
        $where['uid'] = $params['uid'];
        $where['fy_status'] = 4;
        $bank = Db::name('user_bankcard')->where($where)->find();
        $this->success('成功', $bank);
    }

    public function costControl(){
        $params = $this->request->param();
        $need_params = array('type','id','classify');
        $check = $this->params_verify($params, $need_params);
        if (!empty($check)) {
            $this->error('参数错误：'.$check);
        }
        coast($params['type'],$params['id'],$params['classify']);
        $this->success('成功');
    }



    public function facematch($facematch =null ,$uid = null ){
        $data = $this->request->param();
        if (!empty($data)){
            $facematch = $data['facematch'];
            $uid = $data['uid'];
        }else{
            $this->error();
        }
        $list = ['facematch'=>$facematch];
        $a = Db::name('user_info')->where(['uid'=>$uid])->update($list);
        if ($a){
            return 1;
        }else{

            return 0;
        }
    }

}

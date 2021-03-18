<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 */

namespace app\index\controller;

use app\common\controller\Frontend;
use xjd\util\Sms;
use app\common\model\User;
use think\Db;
use think\Log;
class Permit extends Frontend{
    protected $noNeedLogin = '*';
    protected $noNeedRight = '*';
    protected $layout = '';

    public function _initialize()
    {
        parent::_initialize();
    }

    public function index(){
        if($this->request->isPost()){
            $params = $this->request->post();
            $userinfo = User::getByMobile($params['mobile']);
            if ($userinfo) {
                $result = array('code'=>1,'message'=>'注册成功');
            }else{
                $res = $this->check($params['mobile'],$params['code']);
                if($res['code'] == 1){
                    $data['username'] = $params['mobile'];
                    $data['channel_code'] = isset($params['channel_code']) ? $params['channel_code'] : '';
                    $data['createtime'] = $data['jointime'] = time();
                    $data['status'] = 'normal';
                    $rst = model('user')->save($data);
                    if($rst){
                        $result = array('code'=>1,'message'=>'注册成功');
                    }else{
                        $result = array('code'=>0,'message'=>'注册失败');
                    }
                }else{
                    $result = $res;
                }
            }
            echo json_encode($result);
            exit;
        }
        $channel_code = $this->request->get('channelCode');
        if($channel_code){
            $templates = Db::name("channel")
                ->alias('c')
                ->join('d_channel_template t', 'c.template_id = t.id', 'LEFT')
                ->where('c.channel_code','=',$channel_code)
                ->field('t.template_preview_pc,t.template_preview_app')
                ->find();
        }else{
            $templates = '';
        }
        $this->view->assign('templates',$templates);
        return $this->view->fetch();
    }

    /**
     * 发送验证码
     *
     * @param string $mobile 手机号
     * @param string $event 事件名称
     */
    public function send()
    {
        $mobile = $this->request->request("mobile");
        if (!$mobile || !\think\Validate::regex($mobile, "^1\d{10}$")) {
            $result = array('code'=>0,'message'=>'手机号有误');
        }
        $sms = new  Sms();
        $re = $sms->send($mobile);
        Log::write("结果" . print_r($re, true), 'error');
        if (200 != $re) {
            $result = array('code'=>0,'message'=>'发送失败');
        }else{
            $result = array('code'=>1,'message'=>'发送成功');
        }
        echo json_encode($result);
    }

    /**
     * 检测验证码
     *
     * @param string $mobile 手机号
     * @param string $event 事件名称
     * @param string $captcha 验证码
     */
    private function check($mobile,$code)
    {
        $find_log = Db::name('mobile_code_log')->where(array('mobile' => $mobile))->find();
        if (empty($find_log)) {
            return array('code'=>0,'message'=>'验证码有误');
        } else {
            $expire_time = $find_log['expire_time'];
            if (time() > $expire_time) {
                return array('code'=>0,'message'=>'验证码已过期');
            } elseif ($code != $find_log['code']) {
                return array('code'=>0,'message'=>'验证码有误');
            }
        }
        return array('code'=>1,'message'=>'校验成功');
    }

    /**
     * app下载
     */
    public function download(){
        $download = Db::name('app_config')->find();
        $this->view->assign('download',$download);
        return $this->view->fetch();
    }

}
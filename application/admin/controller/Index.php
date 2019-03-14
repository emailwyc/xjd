<?php

namespace app\admin\controller;

use app\admin\model\AdminLog;
use app\common\controller\Backend;
use think\Config;
use think\Db;
use think\Hook;
use think\Log;
use think\Validate;
use xjd\util\Syt;

/**
 * 后台首页
 * @internal
 */
class Index extends Backend
{

    protected $noNeedLogin = ['login'];
    protected $noNeedRight = ['index', 'logout'];
    protected $layout = '';

    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 后台首页
     */
    public function index()
    {
        //左侧菜单
        list($menulist, $navlist, $fixedmenu, $referermenu) = $this->auth->getSidebar([], $this->view->site['fixedpage']);
        $action = $this->request->request('action');
        if ($this->request->isPost()) {
            if ($action == 'refreshmenu') {
                $this->success('', null, ['menulist' => $menulist, 'navlist' => $navlist]);
            }
        }
        $mem_id = $this->auth->id;
        Log::write(__FUNCTION__.': mem_id='.$mem_id,'error');
        $where['mem_id'] = $mem_id;
        $charges = Db::name('charge')->select();
        $account = Db::name('mem_account')->where($where)->value('balance');
        $account = empty($account)?0:$account;
        $this->assign("charges", $charges);
        $this->assign("balance", $account);
        $this->view->assign('menulist', $menulist);
        $this->view->assign('navlist', $navlist);
        $this->view->assign('fixedmenu', $fixedmenu);
        $this->view->assign('referermenu', $referermenu);
        $this->view->assign('title', __('Home'));
        return $this->view->fetch();
    }

    /**
     * 管理员登录
     */
    public function login()
    {
        $url = $this->request->get('url', 'index/index');
        if(strpos($url,'logout')){
            $url = 'index/index';
        }
        if ($this->auth->isLogin()) {
            $this->success(__("You've logged in, do not login again"), $url);
        }
        if ($this->request->isPost()) {
            $username = $this->request->post('username');
            $password = $this->request->post('password');
            $keeplogin = $this->request->post('keeplogin');
            $token = $this->request->post('__token__');
            $rule = [
                'username'  => 'require|length:3,30',
                'password'  => 'require|length:3,30',
                '__token__' => 'token',
            ];
            $data = [
                'username'  => $username,
                'password'  => $password,
                '__token__' => $token,
            ];
            if (Config::get('fastadmin.login_captcha')) {
                $rule['captcha'] = 'require|captcha';
                $data['captcha'] = $this->request->post('captcha');
            }
            $validate = new Validate($rule, [], ['username' => __('Username'), 'password' => __('Password'), 'captcha' => __('Captcha')]);
            $result = $validate->check($data);
            if (!$result) {
                $this->error($validate->getError(), $url, ['token' => $this->request->token()]);
            }
            AdminLog::setTitle(__('Login'));
            $result = $this->auth->login($username, $password, $keeplogin ? 86400 : 0);
            if ($result === true) {
                Hook::listen("admin_login_after", $this->request);
                $this->success(__('Login successful'), $url, ['url' => $url, 'id' => $this->auth->id, 'username' => $username, 'avatar' => $this->auth->avatar]);
            } else {
                $msg = $this->auth->getError();
                $msg = $msg ? $msg : __('Username or password is incorrect');
                $this->error($msg, $url, ['token' => $this->request->token()]);
            }
        }

        // 根据客户端的cookie,判断是否可以自动登录
        if ($this->auth->autologin()) {
            $this->redirect($url);
        }
        $background = Config::get('fastadmin.login_background');
        $background = stripos($background, 'http') === 0 ? $background : config('site.cdnurl') . $background;
        $this->view->assign('background', $background);
        $this->view->assign('title', __('Login'));
        Hook::listen("admin_login_init", $this->request);
        return $this->view->fetch();
    }

    /**
     * 注销登录
     */
    public function logout()
    {
        $this->auth->logout();
        Hook::listen("admin_logout_after", $this->request);
        return $this->view->fetch('index/login');
        //$this->success(__('Logout successful'), 'index/login');
    }

    public function charge(){
        //$mem_id = $this->auth->id;
        $mem_id = 1;
        Log::write(__FUNCTION__.': mem_id='.$mem_id,'error');
        $where['mem_id'] = $mem_id;
        $charges = Db::name('charge')->select();
        $account = Db::name('mem_account')->where($where)->value('balance');
        $account = empty($account)?0:$account;
        $this->assign("charges", $charges);
        $this->assign("balance", $account);

        return $this->view->fetch();
    }


    /**
     * 下单
     */
    public function apply() {
        // TODO:  防止重复点击
        $mem_id = $this->auth->id;
        if(1 != $mem_id){
            $this->error('请联系商户充值');
        }
        //商品名称 和商品描述 判断计费点是否异常
        $params = $this->request->param();
        if (!isset($params['amount']) || empty($params['amount'])) { //提现金额
            $this->error('参数错误：amount');
        }
        $point = array();
        $charges = Db::name('charge')->select();
        foreach ($charges as $charge){
            $point[] = $charge['charge_amount'];
        }
        if (!in_array($params['amount'],$point)) {
            $this->error('无效的充值金额');
        }
        $params['uid'] = $this->auth->id;
        if (empty($params['uid'])) {
            $this->error('参数错误：uid');
        }
        //Log::write(__FUNCTION__.print_r($params,true),'error');
        $account = '';
        $description = '充值：'.$params['amount'].'元';
        $_order_data['mem_id'] = $params['uid'];
        $_order_data['order_id'] = md5(time().$params['uid']);
        $_order_data['description'] = $description;
        $_order_data['account'] = $account;//支付账号或者微信的openid
        $_order_data['real_name'] = '';
        $_order_data['amount'] = $params['amount'];// TT:RMB = 100:1
        $_order_data['real_amount'] = $_order_data['amount'];
        $_order_data['status'] = 1;//1 待审核 2 成功 3 失败
        $_order_data['create_time'] = time();
        $_order_data['update_time'] = time();
        $_order_data['type'] = empty($params['type'])?2:$params['type'];//1 : wx  or  2:alipay
        $_order_data['product_name'] = empty($params['product_name'])?'':$params['product_name'];
        $_order_data['product_desc'] = empty($params['product_desc'])?'':$params['product_desc'];
        $_cash_id = Db::name('mem_cash')->insertGetid($_order_data);
        if (!$_cash_id) {
            $this->error('充值申请失败');
        }
        $order = Db::name('mem_cash')->where(array('id'=>$_cash_id))->find();
        if(empty($order)){
            $this->error('失败');
        }
        $syt = new Syt();
        $re = $syt->createOrder($order);
        //Log::write(__FUNCTION__.print_r($re,true),'error');
        if (!$re || empty($re['url'])) {
            $this->error('失败');
        }
        //Log::write(__FUNCTION__.': url='.$re['url']);
        $this->success('成功',null,$re);
    }

    public function order_status(){
        Log::write(__FUNCTION__.'：order_status','error');
        $params = $this->request->param();
        Log::write(__FUNCTION__.'：params='.print_r($params,true),'error');
        if(empty($params['order_id'])){
            $this->error('参数错误:order_id');
        }
        $where['order_id'] = $params['order_id'];
        $cash = Db::name('mem_cash')->where($where)->find();
        if(empty($cash) || 2 != $cash['status'] ){
            $this->error('充值未完成');
        }
        Log::write(__FUNCTION__.'：cash='.print_r($cash,true),'error');
        $this->success('充值成功',null,$cash);
    }


}

<?php

namespace app\common\controller;

use app\common\library\Auth;
use think\Config;
use think\Db;
use think\exception\HttpResponseException;
use think\exception\ValidateException;
use think\Hook;
use think\Lang;
use think\Loader;
use think\Log;
use think\Request;
use think\Response;

/**
 * API控制器基类
 */
class Api
{

    /**
     * @var Request Request 实例
     */
    protected $request;

    /**
     * @var bool 验证失败是否抛出异常
     */
    protected $failException = false;

    /**
     * @var bool 是否批量验证
     */
    protected $batchValidate = false;

    /**
     * @var array 前置操作方法列表
     */
    protected $beforeActionList = [];

    /**
     * 无需登录的方法,同时也就不需要鉴权了
     * @var array
     */
    protected $noNeedLogin = [];

    /**
     * 无需鉴权的方法,但需要登录
     * @var array
     */
    protected $noNeedRight = [];

    /**
     * 权限Auth
     * @var Auth 
     */
    protected $auth = null;

    /**
     * 默认响应输出类型,支持json/xml
     * @var string 
     */
    protected $responseType = 'json';

    public $url = '';

    /**
     * 构造方法
     * @access public
     * @param Request $request Request 对象
     */
    public function __construct(Request $request = null)
    {
        $this->request = is_null($request) ? Request::instance() : $request;

        // 控制器初始化
        $this->_initialize();

        // 前置操作方法
        if ($this->beforeActionList)
        {
            foreach ($this->beforeActionList as $method => $options)
            {
                is_numeric($method) ?
                                $this->beforeAction($options) :
                                $this->beforeAction($method, $options);
            }
        }
    }

    /**
     * 初始化操作
     * @access protected
     */
    protected function _initialize()
    {
        //移除HTML标签
        $this->request->filter('strip_tags');

        $this->auth = Auth::instance();

        $modulename = $this->request->module();
        $controllername = strtolower($this->request->controller());
        $actionname = strtolower($this->request->action());

        // token
        $token = $this->request->server('HTTP_TOKEN', $this->request->request('token', \think\Cookie::get('token')));

        $path = str_replace('.', '/', $controllername) . '/' . $actionname;
        // 设置当前请求的URI
        $this->auth->setRequestUri($path);
        // 检测是否需要验证登录
        if (!$this->auth->match($this->noNeedLogin))
        {
            //初始化
            $this->auth->init($token);
            //检测是否登录
            if (!$this->auth->isLogin())
            {
                $this->error(__('Please login first'), null, 401);
            }
            // 判断是否需要验证权限
            if (!$this->auth->match($this->noNeedRight))
            {
                // 判断控制器和方法判断是否有对应权限
                if (!$this->auth->check($path))
                {
                    $this->error(__('You have no permission'), null, 403);
                }
            }
        }
        else
        {
            // 如果有传递token才验证是否登录状态
            if ($token)
            {
                $this->auth->init($token);
            }
        }

        $upload = \app\common\model\Config::upload();

        // 上传信息配置后
        Hook::listen("upload_config_init", $upload);

        Config::set('upload', array_merge(Config::get('upload'), $upload));

        // 加载当前控制器语言包
        $this->loadlang($controllername);
    }

    /**
     * 加载语言文件
     * @param string $name
     */
    protected function loadlang($name)
    {
        Lang::load(APP_PATH . $this->request->module() . '/lang/' . $this->request->langset() . '/' . str_replace('.', '/', $name) . '.php');
    }

    /**
     * 操作成功返回的数据
     * @param string $msg   提示信息
     * @param mixed $data   要返回的数据
     * @param int   $code   错误码，默认为1
     * @param string $type  输出类型
     * @param array $header 发送的 Header 信息
     */
    protected function success($msg = '', $data = null, $code = 1, $type = null, array $header = [])
    {
        $this->result($msg, $data, $code, $type, $header);
    }

    /**
     * 操作失败返回的数据
     * @param string $msg   提示信息
     * @param mixed $data   要返回的数据
     * @param int   $code   错误码，默认为0
     * @param string $type  输出类型
     * @param array $header 发送的 Header 信息
     */
    protected function error($msg = '', $data = null, $code = 0, $type = null, array $header = [])
    {
        $this->result($msg, $data, $code, $type, $header);
    }

    /**
     * 返回封装后的 API 数据到客户端
     * @access protected
     * @param mixed  $msg    提示信息
     * @param mixed  $data   要返回的数据
     * @param int    $code   错误码，默认为0
     * @param string $type   输出类型，支持json/xml/jsonp
     * @param array  $header 发送的 Header 信息
     * @return void
     * @throws HttpResponseException
     */
    protected function result($msg, $data = null, $code = 0, $type = null, array $header = [])
    {
        $result = [
            'code' => $code,
            'msg'  => $msg,
            'time' => Request::instance()->server('REQUEST_TIME'),
            'data' => $data,
        ];
        // 如果未设置类型则自动判断
        $type = $type ? $type : ($this->request->param(config('var_jsonp_handler')) ? 'jsonp' : $this->responseType);

        if (isset($header['statuscode']))
        {
            $code = $header['statuscode'];
            unset($header['statuscode']);
        }
        else
        {
            //未设置状态码,根据code值判断
            $code = $code >= 1000 || $code < 200 ? 200 : $code;
        }
        $response = Response::create($result, $type, $code)->header($header);
        throw new HttpResponseException($response);
    }

    /**
     * 前置操作
     * @access protected
     * @param  string $method  前置操作方法名
     * @param  array  $options 调用参数 ['only'=>[...]] 或者 ['except'=>[...]]
     * @return void
     */
    protected function beforeAction($method, $options = [])
    {
        if (isset($options['only']))
        {
            if (is_string($options['only']))
            {
                $options['only'] = explode(',', $options['only']);
            }

            if (!in_array($this->request->action(), $options['only']))
            {
                return;
            }
        }
        elseif (isset($options['except']))
        {
            if (is_string($options['except']))
            {
                $options['except'] = explode(',', $options['except']);
            }

            if (in_array($this->request->action(), $options['except']))
            {
                return;
            }
        }

        call_user_func([$this, $method]);
    }

    /**
     * 设置验证失败后是否抛出异常
     * @access protected
     * @param bool $fail 是否抛出异常
     * @return $this
     */
    protected function validateFailException($fail = true)
    {
        $this->failException = $fail;

        return $this;
    }

    /**
     * 验证数据
     * @access protected
     * @param  array        $data     数据
     * @param  string|array $validate 验证器名或者验证规则数组
     * @param  array        $message  提示信息
     * @param  bool         $batch    是否批量验证
     * @param  mixed        $callback 回调方法（闭包）
     * @return array|string|true
     * @throws ValidateException
     */
    protected function validate($data, $validate, $message = [], $batch = false, $callback = null)
    {
        if (is_array($validate))
        {
            $v = Loader::validate();
            $v->rule($validate);
        }
        else
        {
            // 支持场景
            if (strpos($validate, '.'))
            {
                list($validate, $scene) = explode('.', $validate);
            }

            $v = Loader::validate($validate);

            !empty($scene) && $v->scene($scene);
        }

        // 批量验证
        if ($batch || $this->batchValidate)
            $v->batch(true);
        // 设置错误信息
        if (is_array($message))
            $v->message($message);
        // 使用回调验证
        if ($callback && is_callable($callback))
        {
            call_user_func_array($callback, [$v, &$data]);
        }

        if (!$v->check($data))
        {
            if ($this->failException)
            {
                throw new ValidateException($v->getError());
            }

            return $v->getError();
        }

        return true;
    }


    public function setCoast($type){
        return false;
        $data['type'] = $type;
        $data['create_time'] = time();
        Db::name('coast')->insert($data);
    }

    public function setMessage($uid,$des){
        Log::write('----------------------', 'error');
        $data['uid'] = $uid;
        $data['title'] = $this->titleDes($des);
        $data['content'] = $this->des($des);
        $data['status'] = 2;
        $data['create_time'] = time();
        $data['update_time'] = time();
        Log::write(__FUNCTION__ . ': data=' . print_r($data,true), 'error');
        Db::name('message')->insert($data);
    }


    //积分说明
    public function des($des){
        $re['mno'] = '运营商认证完成';
        $re['tb'] = '淘宝认证完成';
        $re['mno_f'] = '运营商认证失败';
        $re['tb_f'] = '淘宝认证失败';
        $re['zxy_f'] = '资信云认证失败';
        $re['zxy'] = '资信云认证成功';
        $re['duotou'] = '获取多头负债';
        $re['c_amount'] = '提现金额审核失败';
        $re['c_idcard'] = '提现身份证审核失败';
        $re['c_yhk'] = '提现银行卡审核失败';
        $re['c_yhk'] = '提现银行卡审核失败';
        $re['c_have'] = '提现已有订单未完成';
        $re['c_zx'] = '提现征信认证失败';
        $re['age_f'] = '提现审核年龄不达标';
        $re['zm_f'] = '提现审核芝麻积分不达标';
        $re['area_f'] = '提现审核所在地区不达标';
        $re['shy_f'] = '审核员失败';

        if(empty($re[$des])){
            $resulet = '消息内容获取失败：'.$des;
        }else{
            $resulet = $re[$des];
        }

        return $resulet;
    }

    public function titleDes($des){
        $re['mno'] = '征信认证';
        $re['tb'] = '征信认证成';
        $re['mno_f'] = '征信认证';
        $re['tb_f'] = '征信认证';
        $re['zxy_f'] = '征信认证';
        $re['zxy'] = '征信认证';
        $re['duotou'] = '征信认证';
        $re['c_amount'] = '提现审核';
        $re['c_idcard'] = '提现审核';
        $re['c_yhk'] = '提现审核';
        $re['c_yhk'] = '提现审核';
        $re['c_have'] = '提现审核';
        $re['c_zx'] = '提现审核';
        $re['age_f'] = '订单审核';
        $re['zm_f'] = '订单审核';
        $re['area_f'] = '订单审核';
        $re['shy_f'] = '订单审核';

        if(empty($re[$des])){
            $resulet = '消息内容获取失败：'.$des;
        }else{
            $resulet = $re[$des];
        }

        return $resulet;
    }

    public function params_verify($params, $names)
    {
        foreach ($names as $key => $value) {
            if (!isset($params[$value]) || empty($params[$value])) {
                return $value;
            }
        }
        return "";
    }

    public function user_account($amount,$user_id){
        $where['mem_id'] = $user_id;
        $user_account  = Db::name('mem_account')->where($where)->find();
        if(empty($user_account)){
            $data['createtime'] = time();
            $data['balance'] = $amount;
            $data['mem_id'] = $user_id;
            Db::name('mem_account')->insert($data);
        }else{
            $data['balance'] = $user_account['balance'] + $amount;
            Db::name('mem_account')->where($where)->update($data);
        }
    }
}

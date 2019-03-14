<?php

namespace app\api\controller\manger;

use app\common\controller\Api;
use fast\Random;
use think\Db;
use think\Log;
use xjd\util\Sms;
use xjd\util\Timeutil;

/**
 *  用户
 */
class User extends Api
{
    //如果$noNeedLogin为空表示所有接口都需要登录才能请求
    //如果$noNeedRight为空表示所有接口都需要验证权限才能请求
    //如果接口已经设置无需登录,那也就无需鉴权了
    // 无需登录的接口,*表示全部
    protected $noNeedLogin = ['*'];
    // 无需鉴权的接口,*表示全部
    protected $noNeedRight = ['*'];

    public function _initialize()
    {
        parent::_initialize();
    }

    public function send()
    {
        $params = $this->request->param();
        if (empty($params['mobile'])) {
            $this->error('参数错误：mobile');
        }
        $mobile = $params['mobile'];
        $sms = new  Sms();
        $re = $sms->send($mobile);
        Log::write("结果" . print_r($re, true), 'error');
//        $this->success('成功');
        if (200 != $re) {
            $this->error('发送验证码失败');
        }
        /*成本控制*/
        $user = Db::name('user')->where(array('mobile' => $params['mobile']))->find();
        //if (!empty($user)) {
        $user_id = empty($user['id'])?1000000:$user['id'];
        coast(2, $user_id, 1);
        //}
        /*成本控制*/
        $this->success('成功');
    }

    /**
     * 登录注册一体
     */
    public function login()
    {
        $params = $this->request->param();
        if (empty($params['mobile'])) {
            $this->error('参数错误：mobile');
        }
        if (empty($params['code'])) {
            $this->error('参数错误：code');
        }
        $name = $params['mobile'];
        $code = $params['code'];
        //判断验证码
        $find_log = Db::name('mobile_code_log')->where(array('mobile' => $name))->find();
        if (empty($find_log)) {
            $this->error('未发送验证码');
        } else {
            $expire_time = $find_log['expire_time'];
            if (time() > $expire_time) {
                $this->error('验证码已过期');
            } elseif ($code != $find_log['code']) {
                $this->error('验证码错误');
            }
        }
        //先登录
        $mem_id = $this->regAndLoginLogin($name);
        if ($mem_id) {
            $this->auth->direct($mem_id);
            $this->success('成功', $this->getUserinfo($mem_id));
        } else {//进行注册
            $store_id = !empty($params['store_id']) ? $params['store_id'] : 0;
            $mem_id = $this->regAndLoginReg($name, $store_id);
            if (!$mem_id) {
                $this->error('注册失败');
            }
            $this->auth->direct($mem_id);
        }
        $this->success('成功', $this->getUserinfo($mem_id));
    }

    public function info()
    {
        $params = $this->request->param();
        if (empty($params['uid'])) {
            $this->error('参数错误：uid');
        }
        $this->success('成功', $this->getUserinfo($params['uid']));
    }

    //通过UID 获得userinfo
    private function getUserinfo($uid)
    {
        $data["uid"] = 0;
        $data["username"] = "";
        $data["nickname"] = "";
        $data["avatar"] = "";
        $data["token"] = "";
        $data["quota"] = 0;
        $userinfo = Db::name("user")->where(["id" => $uid])->find();
        if ($userinfo) {
            $data["uid"] = $userinfo['id'];
            $data["username"] = $userinfo['username'];
            $data["nickname"] = $userinfo['nickname'];
            $data["avatar"] = $userinfo['avatar'];
            $data["token"] = $userinfo['token'];
            $data["quota"] = $userinfo['quota'];
            $data["realname"] = "";
            $data["cardid"] = "";
            $data["is_real"] = 0;
            //查询真实信息
            $userrealinfo = Db::name("user_info")->where(["uid" => $uid])->find();
            if ($userrealinfo) {
                $data["realname"] = $userrealinfo["realname"];
                $data["cardid"] = $userrealinfo["cardid"];
                $data["is_real"] = 1;
            }
            //当前待还的订单
            $data["pay_count"] = Db::name("order")->where(["uid" => $uid, "status" => 8])->count();
        }
        return $data;
    }


    private function regAndLoginLogin($name)
    {
        //先登录
        $_map['mobile'] = $name;
        $_mem_id = Db::name('user')->where($_map)->value('id');
        //账户不存在去注册
        if (empty($_mem_id)) {
            return null;
        }
        return $_mem_id;
    }

    public function regAndLoginReg($name, $store_id = 0)
    {
        $info = Db::name('user')->where(array('mobile' => $name))->find();
        if (!empty($info)) {
            return false;
        }
        $ip = request()->ip();
        $time = time();
        $password = '123456';
        $data = [
            'username' => $name,
            'password' => $password,
            'email' => 'cs@cs.com',
            'mobile' => $name,
            'level' => 1,
            'score' => 0,
            'avatar' => '',
        ];
        $params = array_merge(
            $data, [
                'nickname' => Random::alnum(),
                'salt' => Random::alnum(),
                'createtime' => $time,
                'jointime' => $time,
                'joinip' => $ip,
                'logintime' => $time,
                'loginip' => $ip,
                'prevtime' => $time,
                'status' => 'normal'
            ]
        );
        if (!empty($store_id)) {//商户id
            $params['store_id'] = $store_id;
        }
        $params['password'] = $this->getEncryptPassword($password, $params['salt']);
        $params = array_merge($params, []);
//        $params['token'] = Random::uuid();
        Log::write(__FUNCTION__.': params='.print_r($params,true),'error');
        if(empty($params['mobile'])){
            return false;
        }

        $t = Db::name('user')->where(['username'=>$name])->find();
        if ($t){
            $re = Db::name('user')->where(['id'=>$t['id']])->update($params);

        }else{

            $re = Db::name('user')->insertGetId($params);
        }
        $sql =  Db::name('user')->getLastSql();
        Log::write(__FUNCTION__.': sql ='.$sql,'error');
        if (!$re) {
            return false;
        }

        return $re;
    }

    /**
     * 获取密码加密后的字符串
     *
     * @param string $password 密码
     * @param string $salt 密码盐
     *
     * @return string
     */
    public function getEncryptPassword($password, $salt = '')
    {
        return md5(md5($password) . $salt);
    }

    public function loginOut()
    {
        $this->success('成功');
    }

    public function authzxy()
    {
        //更改状态
        $params = $this->request->param("params", "");
        $params = json_decode($params, true);
        if (empty($params['mobile'])) {
            $this->error("失败");
        }
        $extraParam = $params['extraParam'];

        $uid = $extraParam["uid"];
        $code = $extraParam["code"];

        if (Db::name("user_authinfo")->where(["uid" => $uid, "code" => $code])->find()) {
            Db::name("user_authinfo")->where(["uid" => $uid, "code" => $code])->update(["status" => 2, "updatetime" => time()]);
        } else {
            $data["uid"] = $uid;
            $data["code"] = $code;
            $data["createtime"] = time();
            $data["uid"] = $uid;
            $data["status"] = 2;
            Db::name("user_authinfo")->insertGetId($data);
        }
        echo "
         <!DOCTYPE html>
            <html lang=\"en\">
            <head>
                <meta charset=\"UTF - 8\">
                <title>运营商认证完成</title>
            </head>
            <body>
               <div style='width: 300px;margin: 0 auto;'>
                  <div style='width:200px;margin:0 auto;'><img src=\" ../../assets/img/success.png\" style='width:200px;margin-top:20px;'></div>
                  <div style='height: 50px;line-height: 50px;border: 1px solid #888888;width: 200px;text-align: center;margin:10px auto;cursor: pointer;' onclick='window.postMessage(1)'>" . ($code == "tb" ? "认证完成，立即申请" : "认证完成，下一步") . "</div>
               </div>
           
            </body>
          </html>
        ";

    }


    public function getAboutUsInfo()
    {
        $appinfo = Db::name("app_config")->field("mobile,qq,wechat")->where(["id" => 1])->find();
        $this->success('成功', $appinfo);
    }

}

<?php
/**
 * Created by PhpStorm.
 * User: Ksh
 * Date: 2018/12/31
 * Time: 22:24
 */

namespace xjd\util;

use think\Db;
use think\Log;

class Sms {
//    protected $url      = 'http://39.98.72.106:8088/sms.aspx';
//    protected $account  = 'GXZN-001';
//    protected $password = '1qaz@WSX';
    protected $sms_id   = '25';

    protected $url      = 'http://ah.jj-mob.com:8000/v2/sms/send';
    protected $account  = 'gzjryz';
    protected $password = '69P7f7YI';

    public function send($mobile,$store_name = '') {
        if ($this->check_mobile($mobile)) {
            $code = $this->get_mobile_code($mobile);
            Log::write(__FUNCTION__.': re1111='.print_r($code, true), 'error');
            if ($code) {
                $re = $this->sendMsg_jiujia($mobile, $code,$store_name);
                //$this->setCoast(1);
                Log::write(__FUNCTION__.': re='.print_r($re, true), 'error');
                if (200 == $re['code']) {
                    $expire_time = time() + 1 * 60;//30分钟后过期
                    //验证码入库
                    $this->sp_mobile_code_log($mobile, $code, $expire_time);
                    return 200;
                } else {
                    return -3;
                }
            } else {
                return -2;
            }
        } else {
            return -1;
        }
    }

    /**
     * 验证手机号
     *
     * @param $mobile
     *
     * @return bool
     */
    function check_mobile($mobile) {
        $pattern = '/^[1][3,4,5,6,7,8][0-9]{9}$/';
        if (empty($mobile)) {
            Return false;
        } else {
            if (preg_match($pattern, $mobile)) {
                Return true;
            } else {
                Return false;
            }
        }
    }

    /**
     * 获取code
     *
     * @param $mobile
     *
     * @return bool|int
     */
    function get_mobile_code($mobile) {
        if (empty($mobile)) {
            return false;
        }
        $find_log = Db::name('mobile_code_log')->where(array('mobile' => $mobile))->find();
        Log::write(__FUNCTION__.': find_log='.print_r($find_log,true),'error');
        $result = false;
        if (empty($find_log)) {
            $result = true;
        } else {
            $expire_time = $find_log['expire_time'];
            Log::write(__FUNCTION__.': expire_time='.$expire_time,'error');
            if (time() > $expire_time) {
                $result = true;
            }
        }
        Log::write(__FUNCTION__.': result='.$result,'error');
        if ($result) {
            $result = rand(1000, 9999);
        }

        return $result;
    }

    //发送短信验证码
    function sendMsg($mobile, $sms_code,$store_name = '') {
        $_rdata['sms_code'] = $sms_code;
        if(empty($store_name)){
            $store_name = '北京国造';
            $store = Db::name('store')->find();
            if (!empty($store['name'])){
                $store_name = $store['name'];
            }
        }
        $msg = "【".$store_name."】尊敬的用户您好，您的验证码是".$sms_code;
        $postArr = array(
            'action'   => 'send',
            'userid'   => $this->sms_id,
            'account'  => $this->account,
            'password' => $this->password,
            'mobile'   => $mobile,
            'content'  => $msg,
            'sendTime' => '',
            'extno'    => ''
        );
        $result = $this->curlPost($this->url, $postArr);
        Log::write(__FUNCTION__.': result='.$result.'error');
        if (empty($result)) {
            $_rdata['code'] = 0;
            $_rdata['msg'] = "短信发送获取数据失败";

            return $_rdata;
        }
        $postObj = simplexml_load_string($result);
        $returnstatus = $postObj->returnstatus;
        Log::write(__FUNCTION__.': returnstatus='.$returnstatus.'error');
        $message = $postObj->message;
        Log::write(__FUNCTION__.': message='.$message.'error');
        if (strtolower($returnstatus) == 'success') {
            $_rdata['code'] = '200';
            $_rdata['msg'] = '发送成功';

            return $_rdata;
        }
        $_rdata['code'] = 0;
        $_rdata['msg'] = "短信发送失败";

        return $_rdata;
    }

    //八一.久佳短信 （新对接）
    function sendMsg_jiujia($mobile, $sms_code,$store_name = '') {
        $_rdata['sms_code'] = $sms_code;
        if(empty($store_name)){
            $store_name = '北京国造';
            $store = Db::name('store')->find();
            if (!empty($store['name'])){
                $store_name = $store['name'];
            }
        }
        $time = date('YmdHis',time());

        $token = sha1($this->account . $this->password .$time);
        $ts = $time;

        $msg = "【".$store_name."】尊敬的用户您好，您的验证码是".$sms_code;
        $postArr = array(
            'account'  => $this->account,
            'token' => $token,
            'dest'   => $mobile,
            'content'  => $msg,
            'ts' =>$ts,
        );

        $result = $this->curlPost($this->url, $postArr);


        Log::write(__FUNCTION__.': result='.$result.'error');
        if (empty($result)) {
            $_rdata['code'] = 0;
            $_rdata['msg'] = "短信发送获取数据失败";

            return $_rdata;
        }

//        $postObj = simplexml_load_string($result);
//        $returnstatus = $postObj->returnstatus;


        $result_aray = json_decode($result,true);


        Log::write(__FUNCTION__.': send_phone='.$mobile.';'.'return'.$result);
        if ($result_aray['status'] == 0) {
            $_rdata['code'] = '200';
            $_rdata['msg'] = '发送成功';

            return $_rdata;
        }
        $_rdata['code'] = 0;
        $_rdata['msg'] = "短信发送失败";
        Log::write(__FUNCTION__.': errorMsdn='.$result_aray['errorMsdn'].'error');

        return $_rdata;
    }


    //post请求
    public function curlPost($url, $postFields) {
        $postFields = http_build_query($postFields);
        Log::write(__FUNCTION__.': postFields='.$postFields, 'error');
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        $result = curl_exec($ch);
        curl_close($ch);
        Log::write(__FUNCTION__.': fukuan='.$result, 'error');

        return $result;
    }

    //post
    public  function httpJsonpost($url, $params) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);//设置等待时间
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);//要求结果为字符串且输出到屏幕上
        //https 请求
        if (strlen($url) > 5 && strtolower(substr($url, 0, 5)) == "https") {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }
        curl_setopt(
            $ch, CURLOPT_HTTPHEADER, array(
                   'Content-Type: application/json; charset=utf-8',
                   'Content-Length: '.strlen($params))
        );
        //ob_start();
        $return_content = curl_exec($ch);
        //$return_content = ob_get_contents();
        //ob_end_clean();
        return $return_content;
    }

    //更新数据
    function sp_mobile_code_log($mobile, $code, $expire_time) {
        $find_log = Db::name('mobile_code_log')->where(array('mobile' => $mobile))->find();
        if ($find_log) {
            $count = $find_log['count'] + 1;
            $result = Db::name('mobile_code_log')->where(array('mobile' => $mobile))->update(
                array('send_time' => time(), 'expire_time' => $expire_time, 'code' => $code, 'count' => $count)
            );
        } else {
            $result = Db::name('mobile_code_log')->insert(
                array('mobile'      => $mobile, 'send_time' => time(), 'code' => $code, 'count' => 1,
                      'expire_time' => $expire_time)
            );
        }

        return $result;
    }
    public function setCoast($type){
        return false;//废弃
        $data['type'] = $type;
        $data['create_time'] = time();
        Db::name('coast')->insert($data);
    }
}
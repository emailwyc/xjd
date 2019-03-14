<?php
/**
 * Created by PhpStorm.
 * User: Ksh
 * Date: 2019/1/1
 * Time: 3:16
 */

namespace xjd\util;

use think\Db;
use think\Log;

class Credit {
    //private $server_url   = 'http://47.104.69.18/html/';
    private $server_url;
    private $partnerId    = 'bjgz';
    private $verifyKey    = '87614471c2c04e45a40bb24bea7662a0';
    private $partnerId_cs = 'bjgz';
    private $verifyKey_cs = '1d4691ba00c84110b6478144d3dd1ef3';
    //1 获取数据--淘宝单项报告数据 2 获取数据--运营商单项报告数据 3 资信云报告数据
    private $url_arr
                         = array(
            1 => "https://credit.baiqishi.com/clweb/api/tb/getreport",
            2 => "https://credit.baiqishi.com/clweb/api/mno/getreport",
            3 => "https://credit.baiqishi.com/clweb/api/common/getreport",
        );
    private $decisionUrl = 'https://api.baiqishi.com/services/decision';
    private $appId       = 'BJGZ';
    // html
    //1 获取数据--淘宝单项报告数据 2 获取数据--运营商单项报告数据 3 资信云报告数据
    private $url_html
        = array(
            1 => "https://credit.baiqishi.com/clweb/api/common/gettoken",
            2 => "https://credit.baiqishi.com/clweb/api/common/gettoken",
            3 => "https://credit.baiqishi.com/clweb/api/common/gettoken",
        );
    // html_get
    //1 获取数据--淘宝单项报告数据 2 获取数据--运营商单项报告数据 3 资信云报告数据
    private $url_html_get
        = array(
            1 => "https://credit.baiqishi.com/clweb/api/tb/getreportpage",
            2 => "https://credit.baiqishi.com/clweb/api/mno/getreportpage",
            3 => "https://credit.baiqishi.com/clweb/api/common/getreportpage",
        );

//    private $device_url = 'https://df.baiqishi.com/webdf/df/queryHistory';
    private $device_url = 'https://df.baiqishi.com/webdf/df/query';
    private $creditReportUrl = 'https://api.baiqishi.com/fraud/report/creditreport';

    private $callRecords = 'https://credit.baiqishi.com/clweb/api/mno/getoriginal';
    function __construct() {
        $this->server_url = 'http://'.$_SERVER['HTTP_HOST'].'/html/';
    }

    public function device($platform,$tokenKey){
        $url = $this->device_url;
        $data['partnerId'] = $this->partnerId;
        $data['verifyKey'] = $this->verifyKey;
        $data['platform'] = $platform;
        $data['tokenKey'] = $tokenKey;
        $params = json_encode($data);
        Log::write(__FUNCTION__.': params1='.$params, 'error');
        $sms = new Sms();
        $re = $sms->curlPost($url, $data);
        Log::write(__FUNCTION__.': re='.$re, 'error');
        $_re_data = json_decode($re, true);
        if ('BQS000' != $_re_data['resultCode'] || empty($_re_data['resultData'])) {
            return false;
        }

        return json_encode($_re_data);
    }

    public function get_html($type, $name, $certNo, $mobile, $uid) {
        if (empty($type)) {
            return false;
        }
        if (empty($this->url_html_get[$type])) {
            return false;
        }
        if (1 == $type) {//淘宝
            $where['code'] = 'tb';
        } elseif (2 == $type) {//运营商
            $where['code'] = 'mno';
        } else {//资讯云
            $where['code'] = 'zxy';
        }
        $where['uid'] = $uid;
        $authdata_josn = Db::name('user_authinfo')->where($where)->value('authdata');

        $sql = Db::name('user_authinfo')->getLastSql();
        Log::write(__FUNCTION__.': sql='.$sql, 'error');
        $authdata = json_decode($authdata_josn, true);

        Log::write(__FUNCTION__.': authdata='.print_r($authdata, true), 'error');
        if (empty($authdata) || empty($authdata['timeStamp']) || empty($authdata['data'])) {
            return false;
        }
        //$html = file_get_contents('https://credit.baiqishi.com/clweb/api/mno/getreportpage?partnerId=bjgz&name=%E5%88%98%E6%B5%B7%E4%BA%AE&certNo=12022419881019443X&mobile=15898873712&timeStamp=1547469260&token=1FA3C892C8C486117AD6BC2A1C5B591679EAF3E7');
        //$cache_file_path = ROOT_PATH . '/public/html/' . 1 . '.html';
        // file_put_contents($cache_file_path, $html, LOCK_EX);
        $cache_file_path = ROOT_PATH.'/public/html/'.$authdata['data'].'.html';
        if (!file_exists($cache_file_path)) {
            $data['partnerId'] = $this->partnerId;
            $data['name'] = $name;
            $data['certNo'] = $certNo;
            $data['mobile'] = $mobile;
            $data['timeStamp'] = $authdata['timeStamp'];
            $data['token'] = $authdata['data'];
            Log::write(__FUNCTION__.': data='.print_r($data, true), 'error');
            $param = http_build_query($data);
            Log::write(__FUNCTION__.': params='.$param, 'error');
            $url = $this->url_html_get[$type].'?'.$param;
            Log::write(__FUNCTION__.': url='.$url, 'error');
            $html = file_get_contents($url);
            file_put_contents($cache_file_path, $html, LOCK_EX);
        }
        $re_url = $this->server_url.$authdata['data'].'.html';

        return $re_url;
    }

    public function credit_html($type, $name, $certNo, $mobile) {
        if (empty($type)) {
            return false;
        }
        if (empty($this->url_html[$type])) {
            return false;
        }
        $url = $this->url_html[$type];
        $data['partnerId'] = $this->partnerId;
        $data['verifyKey'] = $this->verifyKey;
        // $data['name'] = $name;
        $data['certNo'] = $certNo;
        $data['timeStamp'] = time();
        //$data['mobile'] = $mobile;
        $params = json_encode($data);
        Log::write(__FUNCTION__.': params1='.$params, 'error');
        $params = urlencode($params);
        Log::write(__FUNCTION__.': params2='.$params, 'error');
        $sms = new Sms();
        $re = $sms->httpJsonpost($url, $params);
        Log::write(__FUNCTION__.': re='.$re, 'error');
        $_re_data = json_decode($re, true);
        if ('CCOM1000' != $_re_data['resultCode']) {
            return false;
        }
        $_re_data['timeStamp'] = $data['timeStamp'];

        return json_encode($_re_data);
    }

    public function credit($type, $name, $certNo, $mobile) {
        if (empty($type)) {
            return false;
        }
        if (empty($this->url_arr[$type])) {
            return false;
        }
        $url = $this->url_arr[$type];
        $data['partnerId'] = $this->partnerId;
        $data['verifyKey'] = $this->verifyKey;
        $data['name'] = $name;
        $data['certNo'] = $certNo;
        $data['mobile'] = $mobile;
        $params = json_encode($data);
        Log::write(__FUNCTION__.': params1='.$params, 'error');
        $params = urlencode($params);
        Log::write(__FUNCTION__.': params2='.$params, 'error');
        $sms = new Sms();
        $re = $sms->httpJsonpost($url, $params);
        Log::write(__FUNCTION__.': re='.$re, 'error');
        $_re_data = json_decode($re, true);
        if ('CCOM1000' != $_re_data['resultCode']) {
            return false;
        }

        return $re;
    }

    public function duotou($name, $certNo) {
        $array = array(
            "realName" => $name,
            "idCard"   => $certNo
        );
        $msgBody = base64_encode(json_encode($array));
        $pkgReq = "01|P2PY8G5NJLGD7EZLF|01|1003|01|01|".$msgBody."|||D47B702293364AEDA7A60FE22326EAF4";
        $result = $this->post("http://service.91zhengxin.com/jyzx/zxservice.do", $pkgReq);
        if (empty($result)) {
            return false;
        }
        Log::write(__FUNCTION__.': result='.$result,'error');
        $pkgRsp = explode("|", $result);
        $rspStr = array(
            "version"     => $pkgRsp[0],    //默认01
            "custNo"      => $pkgRsp[1],        //请求源
            "encode"      => $pkgRsp[2],     //01.UTF8 02.GBK
            "trxCode"     => $pkgRsp[3],    //报文编号 默认四位 例:0001
            "encryptType" => $pkgRsp[4],//加密类型 01.不加密 02.RSA
            "msgType"     => $pkgRsp[5],    //01.JSON 02.XML 03.Protobuf
            "msgBody"     => $pkgRsp[6],    //报文主体为Base64编码的字节数组
            "retCode"     => $pkgRsp[7],    //返回代码
            "retMsg"      => $pkgRsp[8],     //返回消息
            "sign"        => $pkgRsp[9]        //签名
        );
        $msgBody = base64_decode($rspStr["msgBody"]);
        $rspStr['deBody'] = json_decode($msgBody, true);

        return $rspStr;
    }

    function post($url, $data) {
        $header[] = "Content-Type: application/octet-stream";
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $response = curl_exec($ch);
        Log::write(__FUNCTION__.': response='.$response, 'error');
        if (curl_errno($ch)) {
            return false;
        }
        curl_close($ch);

        return $response;
    }

    //反欺诈云
    public function decision($info = array()) {
        $data['partnerId'] = $this->partnerId;
        $data['verifyKey'] = $this->verifyKey;
        $data['appId'] = $this->appId;
        $data['eventType'] = 'loan';
        if (!empty($info['mobile'])) {
            $data['mobile'] = $info['mobile'];
        }
        if (!empty($info['certNo'])) {//身份证
            $data['certNo'] = $info['certNo'];
        }
        if (!empty($info['name'])) {
            $data['name'] = $info['name'];
        }
        if (!empty($info['ip'])) {
            $data['ip'] = $info['ip'];
        }
        if (!empty($info['longitude'])) {//经度
            $data['longitude'] = $info['longitude'];
        }
        if (!empty($info['latitude'])) {//纬度
            $data['latitude'] = $info['latitude'];
        }
        if (!empty($info['tokenKey'])) {//指纹
            $data['tokenKey'] = $info['tokenKey'];
        }
        Log::write(__FUNCTION__.': data='.print_r($data, true), 'error');
        // $data['latitude'] = '26.4818790000';
        // $data['longitude'] = '111.3127270000';
        //-------
        $sms = new Sms();
        $re = $sms->httpJsonpost($this->decisionUrl, json_encode($data));
        Log::write(__FUNCTION__.': re='.$re, 'error');
        $re_arr = json_decode($re, true);

        return $re_arr;
    }

    public function creditReport($info = array()) {
        $data['partnerId'] = $this->partnerId;
        $data['verifyKey'] = $this->verifyKey;
        //$data['appId'] = $this->appId;
        //$data['eventType'] = 'loan';
        $data['productId'] = '107030';
        if (!empty($info['mobile'])) {
            $data['extParam']['mobile'] = $info['mobile'];
        }
        if (!empty($info['certNo'])) {//身份证
            $data['extParam']['certNo'] = $info['certNo'];
        }
        if (!empty($info['name'])) {
            $data['extParam']['name'] = $info['name'];
        }
        if (!empty($info['ip'])) {
            $data['extParam']['ip'] = $info['ip'];
        }
        if (!empty($info['longitude'])) {//经度
            $data['extParam']['longitude'] = $info['longitude'];
        }
        if (!empty($info['latitude'])) {//纬度
            $data['extParam']['latitude'] = $info['latitude'];
        }
        Log::write(__FUNCTION__.': data='.print_r($data, true), 'error');
        // $data['latitude'] = '26.4818790000';
        // $data['longitude'] = '111.3127270000';
        //-------
        $sms = new Sms();
        $re = $sms->httpJsonpost($this->creditReportUrl, json_encode($data));
        Log::write(__FUNCTION__.': re='.$re, 'error');
        $re_arr = json_decode($re, true);

        return $re_arr;
    }




    /**
     * 取用户的通话记录原型
     * @param $phone
     * @param $name
     * @param $certNo
     * @return array
     */
    public function callRecords($phone,$name,$certNo){

        $url = $this->callRecords;
        $data['partnerId'] = $this->partnerId;
        $data['verifyKey'] = $this->verifyKey;
        $data['name'] = $name;
        $data['certNo'] = $certNo;
        $data['mobile'] = $phone;

        $sms = new Sms();
        $data_json = json_encode($data);
        $list = $sms->httpJsonpost($this->callRecords,$data_json);

        $list = json_decode($list,true);



        if (!empty($list['data']['mnoDetail']['mnoCallRecords'])){
             $callrecords = $list['data']['mnoDetail']['mnoCallRecords'];
        }else{
            return [];
        }


        return $callrecords;

    }

}
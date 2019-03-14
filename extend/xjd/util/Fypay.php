<?php
/**
 * Created by PhpStorm.
 * User: Ksh
 * Date: 2018/12/15
 * Time: 12:11
 */

namespace xjd\util;

use think\Db;
use think\Log;

/**
 * Class Fypay
 *富友支付
 *
 * @package xjd\util
 */
class Fypay {

    private $bindMsgUrl = 'https://mpay.fuioupay.com/newpropay/bindMsg.pay';
    private $bindCommit = 'https://mpay.fuioupay.com/newpropay/bindCommit.pay';
    private $unbind     = 'https://mpay.fuioupay.com/newpropay/unbind.pay';
    private $orderUrl   = 'https://mpay.fuioupay.com/newpropay/order.pay';
    private $dbzfUrl    = 'https://fht-api.fuioupay.com/req.do';
    public  $msg        = 'mjrzpnqqfc7xxs5sl4m595tsk7iu5g3l';//商户密钥
    public  $MCHNTCD    = '0001000F2004430';//商户号
    public $mchntcd  = '0001000F2004430';
    public $mchntkey = 'qp3wy5ut73njhkzeak6cx1ygwc4s9v9u';

    //测试数据开始
    private $bindMsgUrl_cs = 'http://www-1.fuioupay.com:18670/mobile_pay/newpropay/bindMsg.pay';
    private $bindCommit_cs = 'http://www-1.fuioupay.com:18670/mobile_pay/newpropay/bindCommit.pay';
    private $unbind_cs    = 'http://www-1.fuioupay.com:18670/mobile_pay/newpropay/unbind.pay';
    private $orderUrl_cs   = 'http://www-1.fuioupay.com:18670/mobile_pay/newpropay/order.pay';
    private $dbzfUrl_cs    = 'https://fht-test.fuioupay.com/fuMer/req.do';
    public  $msg_cs        = '5old71wihg2tqjug9kkpxnhx9hiujoqj';//商户密钥
    public  $MCHNTCD_cs    = '0002900F0096235';//商户号
    //测试数据结束
    public  $VERSION    = '1.0';
    public  $backurl    = 'http://47.104.69.18/index.php/fypay/backurl';
    //测试数据
    private $USERID = 'M88888888';
    //private $TRADEDATE  = time();
    // private $MCHNTSSN = '20170630009';
    private $ACCOUNT  = '孙悟空';
    private $CARDNO   = '6225885845354179';
    private $IDTYPE   = 0;
    private $IDCARD   = '420116199001011234';
    private $MOBILENO = '13888888888';
    private $CVN      = '';
    //private $SIGN     = '8b5f59f2b10aa154874ae68225ad7d35';
    private $sms;
    private $mcrypt;
    private $mcrypt2;
    //单笔支付
    public $mchntcd_cs  = '0002900F0345178';
    public $mchntkey_cs = '123456';

    function __construct() {
        $this->sms = new Sms();
        // $this->mcrypt = new Mcrypt();
        $this->mcrypt2 = new Mcrypttwo();
        $pay_config = Db::name('pay_config')->find();//现在只有富友
        if (!empty($pay_config) && !empty($pay_config['mchntcd']) && !empty($pay_config['paykey'])
            && !empty($pay_config['serverurl'])
        ) {
            $this->MCHNTCD = $pay_config['mchntcd'];
            $this->msg = $pay_config['paykey'];
            $this->mchntcd = $pay_config['mchntcd'];
            $this->mchntkey = $pay_config['mchntkey'];
            $this->backurl = $pay_config['serverurl'];
        }
    }

    //发送短信验证码接口
    public function bindMsg($obj) {
        Log::write(__FUNCTION__.': obj='.print_r($obj, true), 'error');
        $key = $this->getKeyLength8($this->msg);
        Log::write(__FUNCTION__.': key='.$key, 'error');
        //$obj = $this->setObj();
        $data = $this->arrayToXml($obj);
        Log::write(__FUNCTION__.': data='.$data, 'error');
        // $encrypt = $this->mcrypt->encryptDesEcbPKCS5($data, $key);
        $encrypt = $this->mcrypt2->encryptForDES($data, $key);
        Log::write(__FUNCTION__.': encrypt='.$encrypt, 'error');
        //$decrypt = $this->mcrypt->decryptDesEcbPKCS5($encrypt,$key);
        // Log::write(__FUNCTION__.': decrypt='.$decrypt, 'error');
        $param['MCHNTCD'] = $this->MCHNTCD;
        $param['APIFMS'] = $encrypt;
        Log::write(__FUNCTION__.': param='.print_r($param, true), 'error');
        Log::write(__FUNCTION__.': url='.$this->bindMsgUrl, 'error');
        $re_fy = $this->sms->curlPost($this->bindMsgUrl, $param);
        Log::write(__FUNCTION__.': re='.$re_fy, 'error');
        if (empty($re_fy)) {
            return false;
        }
        $decrypt = $this->mcrypt2->decryptForDES($re_fy, $key);
        Log::write(__FUNCTION__.': decrypt='.$decrypt, 'error');
        $re = $this->xmlToArray($decrypt);
        Log::write(__FUNCTION__.': re='.print_r($re, true), 'error');

        return $re;
    }

    //协议卡绑定
    public function bindCommit($obj) {
        $key = $this->getKeyLength8($this->msg);
        Log::write(__FUNCTION__.': key='.$key, 'error');
        // $obj = $this->setObj();
        $data = $this->arrayToXml($obj);
        Log::write(__FUNCTION__.': data='.$data, 'error');
        // $encrypt = $this->mcrypt->encryptDesEcbPKCS5($data, $key);
        $encrypt = $this->mcrypt2->encryptForDES($data, $key);
        Log::write(__FUNCTION__.': encrypt='.$encrypt, 'error');
        //$decrypt = $this->mcrypt->decryptDesEcbPKCS5($encrypt,$key);
        // Log::write(__FUNCTION__.': decrypt='.$decrypt, 'error');
        $param['MCHNTCD'] = $this->MCHNTCD;
        $param['APIFMS'] = $encrypt;
        Log::write(__FUNCTION__.': param='.print_r($param, true), 'error');
        $re_fy = $this->sms->curlPost($this->bindCommit, $param);
        Log::write(__FUNCTION__.': re='.$re_fy, 'error');
        if (empty($re_fy)) {
            return false;
        }
        $decrypt = $this->mcrypt2->decryptForDES($re_fy, $key);
        Log::write(__FUNCTION__.': decrypt='.$decrypt, 'error');
        $re = $this->xmlToArray($decrypt);
        Log::write(__FUNCTION__.': re='.print_r($re, true), 'error');

        return $re;
    }

    //解绑
    public function unbind($obj) {
        $key = $this->getKeyLength8($this->msg);
        Log::write(__FUNCTION__.': key='.$key, 'error');
        //$obj = $this->setObj();
        $data = $this->arrayToXml($obj);
        Log::write(__FUNCTION__.': data='.$data, 'error');
        // $encrypt = $this->mcrypt->encryptDesEcbPKCS5($data, $key);
        $encrypt = $this->mcrypt2->encryptForDES($data, $key);
        Log::write(__FUNCTION__.': encrypt='.$encrypt, 'error');
        //$decrypt = $this->mcrypt->decryptDesEcbPKCS5($encrypt,$key);
        // Log::write(__FUNCTION__.': decrypt='.$decrypt, 'error');
        $param['MCHNTCD'] = $this->MCHNTCD;
        $param['APIFMS'] = $encrypt;
        Log::write(__FUNCTION__.': param='.print_r($param, true), 'error');
        $re_fy = $this->sms->curlPost($this->unbind, $param);
        Log::write(__FUNCTION__.': re='.$re_fy, 'error');
        if (empty($re_fy)) {
            return false;
        }
        $decrypt = $this->mcrypt2->decryptForDES($re_fy, $key);
        Log::write(__FUNCTION__.': decrypt='.$decrypt, 'error');
        $re = $this->xmlToArray($decrypt);
        Log::write(__FUNCTION__.': re='.print_r($re, true), 'error');

        return $re;
    }

    //协议支付
    public function orderPay($obj) {
        $key = $this->getKeyLength8($this->msg);
        Log::write(__FUNCTION__.': key='.$key, 'error');
        //$obj = $this->setObj();
        $data = $this->arrayToXml($obj);
        Log::write(__FUNCTION__.': data='.$data, 'error');
        // $encrypt = $this->mcrypt->encryptDesEcbPKCS5($data, $key);
        $encrypt = $this->mcrypt2->encryptForDES($data, $key);
        Log::write(__FUNCTION__.': encrypt='.$encrypt, 'error');
        //$decrypt = $this->mcrypt->decryptDesEcbPKCS5($encrypt,$key);
        // Log::write(__FUNCTION__.': decrypt='.$decrypt, 'error');
        $param['MCHNTCD'] = $this->MCHNTCD;
        $param['APIFMS'] = $encrypt;
        Log::write(__FUNCTION__.': param='.print_r($param, true), 'error');
        $re_fy = $this->sms->curlPost($this->orderUrl, $param);
        Log::write(__FUNCTION__.': re='.$re_fy, 'error');
        if (empty($re_fy)) {
            return false;
        }
        $decrypt = $this->mcrypt2->decryptForDES($re_fy, $key);
        Log::write(__FUNCTION__.': decrypt='.$decrypt, 'error');
        $re = $this->xmlToArray($decrypt);
        Log::write(__FUNCTION__.': re='.print_r($re, true), 'error');

        return $re;
    }

    public function setObj() {
        $obj = array();//8WGM0M100000029830GPTM
        $obj['VERSION'] = $this->VERSION;
        $obj['MCHNTCD'] = $this->MCHNTCD;
        $obj['USERID'] = $this->USERID;
        $obj['PROTOCOLNO'] = '8WGM0M100000029830GPTM';
        $sign = $this->setSign($obj);
        $obj['MCHNTSSN'] = '12312413124';
        /*
        $obj['ACCOUNT'] = $this->ACCOUNT;
        $obj['CARDNO'] = $this->CARDNO;
        $obj['IDTYPE'] = $this->IDTYPE;
        $obj['IDCARD'] = $this->IDCARD;
        $obj['MOBILENO'] = $this->MOBILENO;
       // $obj['CVN'] = $this->CVN;
        $obj['MSGCODE'] = '000000';*/
        $obj['SIGN'] = $sign;
        $obj['TRADEDATE'] = date("Ymd");

        return $obj;
    }

    //将XML转为array
    function xmlToArray($xml) {
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $values = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);

        return $values;
    }

    public function getKeyLength8($key) {
        $key = empty($key) ? '' : trim($key);
        $tt = strlen($key) % 64;
        $temp = '';
        for ($i = 0; $i < 64 - $tt; $i++) {
            $temp = $temp.'D';
        }

        return $key.$temp;
    }

    //数组转XML
    function arrayToXml($arr) {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="no"?><REQUEST>';
        foreach ($arr as $key => $val) {
            $xml .= "<".$key.">".$val."</".$key.">";
        }
        $xml .= '</REQUEST>';

        return $xml;
    }

    function arrayToXml2($arr) {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><payforreq>';
        foreach ($arr as $key => $val) {
            $xml .= "<".$key.">".$val."</".$key.">";
        }
        $xml .= '</payforreq>';

        return $xml;
    }

    public function setSign($data) {
        /* $info2 = $data['VERSION'].'|'.$data['MCHNTSSN'].'|'.$data['MCHNTCD'].'|'.$data['USERID'].'|'.$data['ACCOUNT']
                  .'|'
                  .$data['CARDNO'].'|'.$data['IDTYPE'].'|'.$data['IDCARD'].'|'.$data['MOBILENO'].'|'.$key;
        */
        $info = '';
        foreach ($data as $key => $value) {
            Log::write(__FUNCTION__.': '.$key.'='.$value, 'error');
            $info = $info.$value.'|';
        }
        $info = $info.$this->msg;
        Log::write(__FUNCTION__.': info='.$info, 'error');
        $re = md5($info);

        return $re;
    }

    //单笔支付
    public function dbzf($obj) {
        Log::write(__FUNCTION__.': obj='.print_r($obj, true), 'error');
        $data = $this->arrayToXml2($obj);
        $params['merid'] = $this->mchntcd;
        $params['reqtype'] = 'payforreq';
        $params['xml'] = $data;
        $mac = $params['merid'].'|'.$this->mchntkey.'|'.$params['reqtype'].'|'.$params['xml'];
        Log::write(__FUNCTION__.': mac='.$mac, 'error');
        $params['mac'] = strtoupper(md5($mac));
        Log::write(__FUNCTION__.': params='.print_r($params, true), 'error');
        //$re_fy = $this->sms->httpJsonpost($this->dbzfUrl, json_encode($params));
//        $re_fy = $this->sms->curlPost($this->dbzfUrl, $params);
        $re_fy = http($this->dbzfUrl, $params,'post');


        Log::write(__FUNCTION__.': re='.$re_fy, 'error');
        if (empty($re_fy)) {
            return false;
        }
        $re = $this->xmlToArray($re_fy);
        Log::write(__FUNCTION__.': re='.print_r($re, true), 'error');

        //付款接口实时返回 000000 或 AAAAAA，这两种应答码表示富友受理成功
        return $re;
    }
}
<?php
/**
 * Created by PhpStorm.
 * User: Ksh
 * Date: 2018/12/15
 * Time: 12:11
 */

namespace xjd\util;

use think\Log;

class Syt {
    private $merchant  = '201901161013496251';
    public  $key       = '45BF53493CDE6ACCC67FD29F95C02BBB';
    private $merchant_cs  = '201811230233441862';
    public  $key_cs       = '1D7B263E5A67C6103D743CE4E31F953C';
    private $payMethod = '2';
    private $payType   = '21';
    private $sms;
    private $url       = 'http://pay.sytpay.cn/index.php/Api/Index/createOrder';
    //private $returnurl = 'http://47.104.69.18/index.php/admin/dashboard?ref=addtabs';
    //private $notifyUrl = 'http://47.104.69.18/index.php/syt/notifyurl';
    private $notifyUrl;
    private $returnurl;

    function __construct() {
        $this->sms = new Sms();
        $this->returnurl = 'http://'.$_SERVER['HTTP_HOST'].'/index.php/admin/dashboard?ref=addtabs';
        $this->notifyUrl = 'http://'.$_SERVER['HTTP_HOST'].'/index.php/syt/notifyurl';
    }

    public function createOrder($order) {
        if (empty($order)) {
            return false;
        }
        $data = array(
            "orderAmount" => $order['real_amount'], //金额
            "orderId"     => $order['order_id'],//订单号
            "merchant"    => $this->merchant, //商户号
            'payMethod'   => $order['type'], //支付方式
            "payType"     => 2 == $order['type'] ? 21 : 11, //支付类型
            "signType"    => "MD5",
            "version"     => "1.0",
            //此参数作用为返回的数据的格式  ‘yes’时返回的是json格式数据，并且             没有支付页面，只返回支付链接  ‘no’时返回的是支付页面
            "outcome"     => "yes",
        );
        //组装参与签名的字段，生成一个数组$data;
        $key = $this->key; //商户号所对应的商户密钥
        ksort($data); //按照键名对关联数组进行升序排序
        //生成 URL-encode 之后的请求字符串
        $postString = http_build_query($data);
        //将$postString连接上商户密钥后再MD5加密
        $mdString = md5($postString.$key);
        //将MD5后的数据全部专为大写 生成签名
        $signMyself = strtoupper($mdString);
        //将签名加入数组
        $data["sign"] = $signMyself;
        $data['productName'] = $order['product_name'];
        $data['productDesc'] = $order['product_desc'];
        $data['createTime'] = time();//time()为当前时间戳 秒级
        //接收订单回调数据的URL
        $data['notifyUrl'] = $this->notifyUrl;
        //支付成功后跳转到的地址
        $data['returnUrl'] = $this->returnurl;
        //使用GET方式访问需要生成 URL-encode 之后的请求字符串
        // 这里可以用 GET 也可以用POST方式
        $postString = http_build_query($data);
        //$url = "http://pay.sytpay.cn/index.php/Api/Index/createOrder?".$postString;
        //header("Location: ".$url);
        Log::write(__FUNCTION__.': data='.print_r($data, true), 'error');
        $re = $this->sms->curlPost($this->url, $data);
        Log::write(__FUNCTION__.': re='.$re, 'error');
        $re_data = json_decode($re, true);
        Log::write(__FUNCTION__.': re_data='.print_r($re_data, true), 'error');
        $sign = md5($re_data['merchant'].$re_data['orderId']);
        Log::write(__FUNCTION__.': sign='.$sign, 'error');
        if ($sign != $re_data['sign']) {
            //签名失败
            return false;
        }
        if (0 === $re_data['code']) {
            //成功
            return $re_data;
        } else {
            return false;
        }
    }
}
<?php

namespace app\api\controller\manger;

use AlipayTradePrecreateRequest;
use AlipayTradeQueryRequest;
use AopClient;
use app\common\controller\Api;
use think\Db;
use xjd\util\Syt;
use xjd\util\Timeutil;

/**
 * 支付宝支付
 */
class Pay extends Api {
    //如果$noNeedLogin为空表示所有接口都需要登录才能请求
    //如果$noNeedRight为空表示所有接口都需要验证权限才能请求
    //如果接口已经设置无需登录,那也就无需鉴权了
    // 无需登录的接口,*表示全部
    protected $noNeedLogin = ['*'];
    // 无需鉴权的接口,*表示全部
    protected $noNeedRight = ['*'];
    public    $rate        = 0.1;
    // Alpha
    public $app_id       = "2017072407880532";// 付宝分配给开发者的应用ID
    public $method       = "alipay.fund.trans.toaccount.transfe";
    public $charset      = "UTF-8";
    public $privatKey    = "MIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQCPmkvu0bGN8K7QY3kAUEpunBK/mvQb9xzULlgDfjnFbMF235enOEB9qh7d8WRS/Ho4ZXBkMamr6yUm8JUCY9BDLui+WiUIYEIS4jiN4SuTb+Z14OIBMaKjFuJq4jFK58xoeY6Y1jx9z3pBTEE6jQl88I62XMOuPZIBV03OIqp7LpuZCSDCYXvK/w60aKngFdYvboYca/LSyXMVLBGVRPYsZOcwc9XBH2U+BOSFsnXwq6luGDHYjvhWFXOuS6OreAG9DDKpCfKmwpRC7Q3wf2rtHcyqVo5vD9ijOcGe+Mz1fZKb1QrhpQ+8WGB4dCL++/cxAs9SHNu2fXeKTucHq+RZAgMBAAECggEADYCCdzf8K85fgXR44hCqju/Zy240WZTIfyLEX7+0it2I6zZmufBOEAK42EFkH3FhxH+1K5aHz4RSf/DK8eiUolx8wbkUFk+GpuPiUWTEhh5VWuwEE/yowBDcxRPzcomM7J3RTImIMoQZiJ4029567+ZmZLsgIvfjDIE67ZDQ2rgI9mR7HoYf5eA9ydA64eFQe6ybg1ejFG6lQGadNd63SKY/3c+a4hTottSRZOwhny2MOpYBl2leO7+BWQqYAMoV7Ed7ZsVsgdTtj3OqOSMlHqgaxQ8zRVbx2PVsw6JqaBpcBSuorT7hkxx8SXqBee6iuBLDXpC1qIYncCDdAD0woQKBgQDQi8mFFnsFw9BdeQ5SOzR3HIqW9HJMDO1jOkVEagsJODGS60CCqDCVQIzbK1y3yzSouw8AC0rIXsbeN1gel/PjRL05LSmodYYSMNpSCcttsURKY9kRJQBerXakiGWd2+UIDetH1et47knjSeKX7L8EdqXY2zDfTwqsRUVxdQHePQKBgQCwR289KBFmqAnlvg10jgW/6mFZwzjyzBpPNY7okYNUQtKsl6XLLOIRD3I1/kqAxYfPwPw5ou8I81Rax8zW8gb0vmDE4KFBKpzVpK0Lolipp1Euo3gTKTsh44M6n8panhep5hW5CZU+pZbdUYQ1jbR8y0B8bvEg1E3SEkGqCjj8TQKBgEH9md0VSgR++/Y4EkIPHgD69Rfjbi+Gf6/Ahp6pd6iJMZat/pHZqtXvwSKxS/uUv6sM1ihLanSRGRjEJow3xSMLY10uX1pBJhWng84l70tcpqFriwqTzNQoy7wwtQcIyCaOUP9AP+zaamMTxDIS5jcBXGWMpqBjIJt2qAzI27h5AoGBAJiHRw5w1BWygtXiy/u1Q+5anJ+x113nE0DEhAJinnNTWr1yfke+b2H5roZfoRGpWal/P/+TJyfkfHIMfgbi5vfwnwTbpUwg1hHaas0tTXCG0Sz8z1ItE/hvx0X7q3kbYDhnWRaB3zyUxWR9O1unYKu2pvbEfCo+6ZNCrsS2EbblAoGAOZa2+MrXuigPKt7Q+zuk6/EUDbTfMaASWOKAsXf2OJsxDDLJ8YWtyD3yqKdEcfzwHulEVEkiqo9kz6Qb9G4NWX767SHwlJFE4OYE3zfY2LYToJ/+nP4ScpxGjEUt5UzGcq+rr1k/IUyaFNSChXajZ82NSAC7PAlvbmyt7ohIqK4=";
    public $alipublicKey = "MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAzYJRFgDl1DGObMwsWsghO09XCIn9dtwmnc6Gr1OLKXXnOXo7RKK+mnbqFqxR5UBXnR3Q0BbxSTebX288/e9Kon2FyWdkz3bSKbYKVRK201EkGpiEqqOqesffja+q8R6g/PPVTO3G6Tsm6wS+w94ig3JLsvsL8VdXAFx/RPbjjtKsUDf9EZrm/nkB8gpyw1WlxZ9Aoo75nhjWux85MdeowownEBhkwtOxipTSNTAPWt0fsbjHP404QiWTHFTzuj3n1xbEJGkLaScG0VqUmht+L8WjH731hqN4rJMtvOudoltZR5HBVTCxjZAyfs2peL41IwDeUqfqeJFMF/J8M3Hg9wIDAQAB";
    public $ali_url      = "https://openapi.alipay.com/gateway.do";

    public function _initialize() {
        parent::_initialize();
    }

    public function getPayInfo(){
        //获取计费点 和余额
        $params = $this->request->param();
        if (empty($params['mem_id'])) {
            $this->error('参数错误：mem_id');
        }
        $where['mem_id'] = $params['mem_id'];
        $re['charges'] = Db::name('charge')->select();
        $re['account'] = Db::name('mem_account')->where($where)->find();
        $this->success('成功',$re);
    }



    /**
     * 绑定支付宝账号
     *
     * @return $thistest
     */
    public function bindAlipay() {
        $params = $this->request->param();
        if (!isset($params['account']) || empty($params['account'])) {
            $this->error('参数错误：account');
        }
        $where['mem_id'] = $params['uid'];
        $aliInfo = Db::name('alipay_user')->where($where)->find();
        if (!empty($aliInfo)) {
            $this->error('该账号已绑定过支付宝账号');
        }
        $aliInfo_accout = Db::name('alipay_user')->where(array('account' => $params['account']))->find();
        if (!empty($aliInfo_accout)) {
            $this->error('支付宝账号已被绑定');
        }
        $data['mem_id'] = $params['uid'];
        $data['account'] = $params['account'];
        $data['create_time'] = time();
        $data['update_time'] = time();
        $re = Db::name('alipay_user')->insert($data);
        if (!$re) {
            $this->error('设置玩家支付宝账号失败');
        }
        $this->success('设置玩家支付宝账号成功');
    }

    /**
     * 下单
     */
    public function apply() {
        // TODO:  防止重复点击
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
        if (empty($params['uid'])) {
            $this->error('参数错误：uid');
        }
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
        $this->success('充值申请成功');
    }

    public function qrcode() {
        $params = $this->request->param();
        if (empty($params['order_id'])) {
            $this->error('参数错误：order_id');
        }
        if (empty($params['type']) || (1 != $params['type'] && 2 != $params['type'])) {//1：微信  2 支付宝
            $this->error('参数错误：type');
        }else{
            $re = Db::name('mem_cash')->where(array('id'=>$params['order_id']))->update(array('type'=>$params['type']));
            if($re === false){
                $this->error('失败');
            }
        }
        $order = Db::name('mem_cash')->where(array('id'=>$params['order_id']))->find();
        if(empty($order)){
            $this->error('失败');
        }
        $syt = new Syt();
        $re = $syt->createOrder($order);
        if (!$re) {
            $this->error('失败');
        }
        $this->success('成功', $re);
    }

    public function pay() {
        $params = $this->request->param();
        //require_once (SPAPP_PATH.'Core/Library/alipay-sdk-PHP-3.3.0/aop/AopClient.php');
        //require_once (SPAPP_PATH.'Core/Library/alipay-sdk-PHP-3.3.0/aop/request/AlipayFundTransToaccountTransferRequest.php');
        //require_once (SPAPP_PATH.'Core/Library/alipay-sdk-PHP-3.3.0/aop/SignData.php');
        if (!isset($params['order_id']) || empty($params['order_id'])) {
            $this->error('参数错误：order_id');
        }
        $order_id = $params['order_id'];
        $orderInfo = Db::name('mem_cash')->where(array('id' => $order_id))->find();
        $aop = new AopClient ();
        $aop->gatewayUrl = $this->ali_url;
        $aop->appId = $this->app_id;
        $aop->rsaPrivateKey = $this->privatKey;
        $aop->alipayrsaPublicKey = $this->alipublicKey;
        $aop->apiVersion = '1.0';
        $aop->signType = 'RSA2';
        $aop->postCharset = $this->charset;
        $aop->format = 'json';
        $request = new AlipayTradePrecreateRequest ();
        $request->setBizContent(
            "{".
            "\"out_trade_no\":\"".$orderInfo['order_id']."\",".
            // "\"seller_id\":\"\",".
            "\"total_amount\":".$orderInfo['real_amount'].",".
            // "\"discountable_amount\":8.88,".
            "\"subject\":\"感谢您的支持\",".
            // "      \"goods_detail\":[{".
            // "        \"goods_id\":\"apple-01\",".
            // "\"goods_name\":\"ipad\",".
            //"\"quantity\":1,".
            // "\"price\":2000,".
            // "\"goods_category\":\"34543238\",".
            //"\"categories_tree\":\"124868003|126232002|126252004\",".
            // "\"body\":\"特价手机\",".
            // "\"show_url\":\"http://www.alipay.com/xxx.jpg\"".
            // "        }],".
            //"\"body\":\"Iphone6 16G\",".
            //"\"operator_id\":\"yx_001\",".
            // "\"store_id\":\"NJ_001\",".
            // "\"disable_pay_channels\":\"pcredit,moneyFund,debitCardExpress\",".
            // "\"enable_pay_channels\":\"pcredit,moneyFund,debitCardExpress\",".
            //  "\"terminal_id\":\"NJ_T_001\",".
            // "\"extend_params\":{".
            // "\"sys_service_provider_id\":\"2088511833207846\",".
            //  "\"industry_reflux_info\":\"{\\\\\\\"scene_code\\\\\\\":\\\\\\\"metro_tradeorder\\\\\\\",\\\\\\\"channel\\\\\\\":\\\\\\\"xxxx\\\\\\\",\\\\\\\"scene_data\\\\\\\":{\\\\\\\"asset_name\\\\\\\":\\\\\\\"ALIPAY\\\\\\\"}}\","
            //  .
            //  "\"card_type\":\"S0JP0000\"".
            //  "    },".
            //  "\"timeout_express\":\"90m\",".
            //  "\"settle_info\":{".
            // "        \"settle_detail_infos\":[{".
            //  "          \"trans_in_type\":\"cardSerialNo\",".
            // "\"trans_in\":\"A0001\",".
            // "\"summary_dimension\":\"A0001\",".
            //"\"settle_entity_id\":\"2088xxxxx;ST_0001\",".
            //"\"settle_entity_type\":\"SecondMerchant、Store\",".
            // "\"amount\":0.1".
            //"          }]".
            // "    },".
            // "\"merchant_order_no\":\"20161008001\",".
            //"\"business_params\":\"{\\\"data\\\":\\\"123\\\"}\",".
            // "\"qr_code_timeout_express\":\"90m\"".
            "  }"
        );
        $result = $aop->execute($request);
        $responseNode = str_replace(".", "_", $request->getApiMethodName())."_response";
        $resultCode = $result->$responseNode->code;
        if (!empty($resultCode) && $resultCode == 10000) {
            $re['qr_code'] = $result->$responseNode->qr_code;
            $this->success('成功', $re);
        }
        $this->error("生成二维码失败");
    }

    public function notify() {
    }

    public function checkorder() {
        $params = $this->request->param();
        //require_once (SPAPP_PATH.'Core/Library/alipay-sdk-PHP-3.3.0/aop/AopClient.php');
        //require_once (SPAPP_PATH.'Core/Library/alipay-sdk-PHP-3.3.0/aop/request/AlipayFundTransToaccountTransferRequest.php');
        //require_once (SPAPP_PATH.'Core/Library/alipay-sdk-PHP-3.3.0/aop/SignData.php');
        if (!isset($params['order_id']) || empty($params['order_id'])) {
            $this->error('参数错误：order_id');
        }
        $order_id = $params['order_id'];
        $orderInfo = Db::name('mem_cash')->where(array('id' => $order_id))->find();
        $aop = new AopClient ();
        $aop->gatewayUrl = $this->ali_url;
        $aop->appId = $this->app_id;
        $aop->rsaPrivateKey = $this->privatKey;
        $aop->alipayrsaPublicKey = $this->alipublicKey;
        $aop->apiVersion = '1.0';
        $aop->signType = 'RSA2';
        $aop->postCharset = $this->charset;
        $aop->format = 'json';
        $request = new AlipayTradeQueryRequest();
        $request->setBizContent(
            "{".
            "\"out_trade_no\":\"".$orderInfo['order_id']."\",".
            // "\"trade_no\":\"2014112611001004680 073956707\"," .
            // "\"org_pid\":\"2088101117952222\"" .
            "  }"
        );
        $result = $aop->execute($request);
        $responseNode = str_replace(".", "_", $request->getApiMethodName())."_response";
        $resultCode = $result->$responseNode->code;
        if (!empty($resultCode) && $resultCode == 10000) {
            //交易状态：WAIT_BUYER_PAY（交易创建，等待买家付款）、
            //TRADE_CLOSED（未付款交易超时关闭，或支付完成后全额退款）、
            //TRADE_SUCCESS（交易支付成功）、TRADE_FINISHED（交易结束，不可退款）
            $amt = $result->$responseNode->total_amount;
            $re['status'] = 1;//结束轮询
            if ($amt != $orderInfo['real_amount']) {
                $data['status'] = 3;//
                $data['memo'] = '订单异常，金额不一致';
                Db::name('mem_cash')->where(array('id' => $order_id))->update($data);
                $this->success('金额不一致', $re);
            }
            $resultCode = $result->$responseNode->trade_status;
            if ('TRADE_CLOSED' == $resultCode) {
                $data['status'] = 3;//
                $data['memo'] = '未付款交易超时关闭，或支付完成后全额退款';
                Db::name('mem_cash')->where(array('id' => $order_id))->update($data);
                $this->success('超时或者退款', $re);
            } elseif ('TRADE_SUCCESS' == $resultCode || 'TRADE_FINISHED' == $resultCode) {
                $data['status'] = 2;
                $data['memo'] = '';
                $data['update_time'] = time();
                Db::name('mem_cash')->where(array('id' => $order_id))->update($data);
                $this->success('支付成功', $re);
            } else {
                $re['status'] = 2;//继续结束轮询
                $this->success("成功", $re);
            }
        }
        $re['status'] = 2;//继续结束轮询
        $this->success("成功", $re);
    }

    /**
     * 查询
     */
    public function index() {
        $re = Db::name('pay_config')->find();
        $this->success('成功', $re);
    }

    /**
     * 设置
     */
    public function set() {
        $params = $this->request->param();
        $info = Db::name('pay_config')->find();
        if (empty($info)) {
            $params['set_time'] = time();
            $re = Db::name('pay_config')->insert($params);
            if (!$re) {
                $this->error('设置数据失败');
            }
            $this->success('设置数据成功');
        }
        $params['set_time'] = time();
        $re = Db::name('pay_config')->where(array('id' => $info['id']))->update($params);
        if (!$re) {
            $this->error('设置数据失败');
        }
        $this->success('设置数据成功');
    }
}

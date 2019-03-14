<?php

namespace app\api\controller\client;


use app\common\controller\Api;
use think\Db;
use xjd\util\Credit;
use xjd\util\ExportExcel;
use xjd\util\Fypay;
use xjd\util\Sms;
use xjd\util\Syt;
use xjd\util\Timeutil;

/**
 * 测试
 */
class Test extends Api {
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

    public function index() {
        $credit = new Credit();
        $re = array();
        $hj = QueryList::Query('https://portal.baiqishi.com/spage/control/creditreport?flowNo=201901291600253270000000209212&token=21c08c69b1f44c8593afa4e6ade21082&partnerId=bjgz',array());
        var_dump($hj);
        die;
        /*
         *征信
        $credit->credit(1,'张三','451234198701234567','13512345678');
       */
        /*
         * 多头负债
         * $re = $credit->duotou('谭小女','430224198502132723 ');
         * */
        //富友发送短信验证码接口
        $fy = new  Fypay();
        //$re = $fy->bindMsg();
        //$re =$fy->bindCommit();
        //$re =$fy->unbind();
        //$syt = new Syt();
        //$re = $syt->createOrder();
        // if(!$re){
        //    $this->error('失败');
        // }

        $data['ver'] = '1.0';
        $data['merdt'] = date("Ymd", time());
        $data['orderno'] = time();
        //$data['bankno'] = '0104';
        //$data['cityno'] = '1000';
        //$data['branchnm'] = '中国银行股份有限公司北京西单支行';
        $data['accntno'] = '1111111111111111111';
        $data['accntnm'] = '张三';
        $data['amt'] = '100000';
        $data['mobile'] = '18500504384';
        $data['addDes'] = '1';
        //$re = $fy->dbzf($data);
       // $re = $credit->credit(3, '王艺锦', '370685198811060610', '18500504384');
       // $re = $credit->decision();
        //$html = file_get_contents('https://credit.baiqishi.com/clweb/api/mno/getreportpage?partnerId=bjgz&name=%E5%88%98%E6%B5%B7%E4%BA%AE&certNo=12022419881019443X&mobile=15898873712&timeStamp=1547469260&token=1FA3C892C8C486117AD6BC2A1C5B591679EAF3E7');
        //$cache_file_path = ROOT_PATH . '/public/html/' . 1 . '.html';
       // file_put_contents($cache_file_path, $html, LOCK_EX);
        $this->success('成功', $re);
       //coast(1,100,2);
    }


    public function creditreport() {
        //身份证实名校验
        $uid = 4;
        $where['uid'] = $uid;
        $user = Db::name('user')->where(array('id' => $uid))->find();
        $userInfo = Db::name('user_info')->where($where)->find();
        $deviceInfo = Db::name('device')->where($where)->order('updatetime desc')->find();
        $tokenKey = !empty($deviceInfo['tokenKey']) ? $deviceInfo['tokenKey'] : '';
        $info = array();
        $info['mobile'] = !empty($user['mobile']) ? $user['mobile'] : '';
        $info['certNo'] = !empty($userInfo['cardid']) ? $userInfo['cardid'] : '';
        $info['name'] = !empty($userInfo['realname']) ? $userInfo['realname'] : '';
        $info['ip'] = Timeutil::getIp();
        $info['longitude'] = !empty($userInfo['longitude']) ? $userInfo['longitude'] : '';//经度
        $info['latitude'] = !empty($userInfo['latitude']) ? $userInfo['latitude'] : '';//纬度
        $info['tokenKey'] = $tokenKey;
        $credit = new Credit();
        $re = $credit->creditReport($info);
        if ('BQS000' != $re['resultCode']) {
            $orderStatus['status'] = 12;
            $this->error('获取风险监测报告异常', null, 400);
        }
        $this->success('成功',$re);
    }


}

<?php
/**
 * Created by PhpStorm.
 * User: L
 * Date: 2019/1/9
 * Time: 4:57
 * 认证详情
 */

namespace app\admin\controller;

use app\admin\model\AuthGroup;
use app\common\controller\Backend;
use think\Db;
use think\Log;
use xjd\util\Credit;
use xjd\util\Timeutil;
use xjd\util\Util;
use fast\Http;

/**
 * 管理员日志
 *
 * @icon   fa fa-users
 * @remark 管理员可以查看自己所拥有的权限的管理员日志
 */
class Authinfo extends Backend
{
    /**
     * @var \app\admin\model\AdminLog
     */
    protected $model = null;
    protected $childrenGroupIds = [];
    protected $childrenAdminIds = [];
    //private $server_url = 'http://47.104.69.18/html/';
    private $server_url;
    public $finalDecision
        = array(
            "Accept" => '通过',
            "Reject" => '拒绝',
            "Review" => '审核',
        );
    private $str;

    public function _initialize()
    {
        parent::_initialize();
        $this->server_url = 'http://'.$_SERVER['HTTP_HOST'].'/html/';
        $this->model = model('AdminLog');
        $this->childrenAdminIds = $this->auth->getChildrenAdminIds(true);
        $this->childrenGroupIds = $this->auth->getChildrenGroupIds($this->auth->isSuperAdmin() ? true : false);
        $groupName = AuthGroup::where('id', 'in', $this->childrenGroupIds)
            ->column('id,name');
        $this->view->assign('groupdata', $groupName);
    }

    /**
     * 查看
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->where($where)
                ->where('admin_id', 'in', $this->childrenAdminIds)
                ->order($sort, $order)
                ->count();
            $list = $this->model
                ->where($where)
                ->where('admin_id', 'in', $this->childrenAdminIds)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }

        return $this->view->fetch();
    }

    /**
     * 详情
     */
    public function detail($ids)
    {
        //找到订单详情
        $order = Db::name("order")->where(["id" => $ids])->find();
        $userinfo = [];
        if ($order) {
            $uid = $order['uid'];
            $userinfo = Db::name("user")->alias("u1")
                ->join("d_user_info u2", "u2.uid = u1.id", 'left')
                ->join("d_user_address u3", "u3.uid = u1.id", 'left')
                ->join("d_user_cardinfo u4", "u4.uid = u1.id", 'left')
                ->where(["u1.id" => $uid])->find();
            $userinfo['age'] = $this->getAgeByID($userinfo['cardid']);
            if ($userinfo) {
                $phoneinfo = json_decode($userinfo["phone"], true);
                $userinfo["phone"] = $phoneinfo["Brand"] . " " . $phoneinfo["Model"];
//                $area = $this->get_area($userinfo["joinip"] ? $userinfo["joinip"] : $userinfo["loginip"]);

                $userinfo["dw_address"] = $this->get_area_detail($userinfo['latitude'], $userinfo['longitude']);
            }
            //$longitude = $userinfo['longitude'];//经度
            //$latitude = $userinfo['latitude'];//维度
            //if(!empty($userinfo['longitude']) && !empty($userinfo['latitude'])){
            //}
        }
        $this->assign("ids", $ids);
        $this->assign("userinfo", $userinfo);

        return $this->view->fetch();
    }

    private function get_area_detail($latitude, $longitude)
    {
        if (!$latitude || !$longitude)
            return "暂无";
        $place_url = "http://api.map.baidu.com/geocoder/v2/?location=" . $latitude . "," . $longitude . "&output=json&ak=nTBFc6pNNRyyEwppRnjHM6TEzGyG9aGv&pois=0";
        $json_place = file_get_contents($place_url);
        $place_arr = json_decode($json_place, true);
        return !empty($place_arr['result']['formatted_address']) ? $place_arr['result']['formatted_address'] : "暂无";
    }

    private function get_area($ip = '')
    {
        if ($ip == '') {
            $ip = GetIp();
        }
        $url = "http://ip.taobao.com/service/getIpInfo.php?ip={$ip}";
        $ret = $this->https_request($url);
        $arr = json_decode($ret, true);
        return $arr;
    }

    private function https_request($url, $data = null)
    {
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);

        if (!empty($data)) {//如果有数据传入数据
            curl_setopt($curl, CURLOPT_POST, 1);//CURLOPT_POST 模拟post请求
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);//传入数据
        }

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        curl_close($curl);

        return $output;
    }

    public function txl($ids)
    {
        //找到订单详情
        $order = Db::name("order")->where(["id" => $ids])->find();
        $term = $this->request->param('term');
        $where = array();
        if (!empty($term)) {
            $checkMoblie = $this->checkMoblie($term);
            if ($checkMoblie) {
                $where['phone'] = array("like", "%$term%");
            } else {
                $where['linkname'] = array("like", "%$term%");
            }
        }
        $txlinfo = [];
        if ($order) {
            $uid = $order['uid'];
            $txlinfo = Db::name("user_telebook")->where(["uid" => $uid])->where($where)->select();
            $count = Db::name("user_telebook")->where(["uid" => $uid])->where($where)->count();
        }
        $this->assign("term", $term);
        $this->assign("count", $count);
        $this->assign("txlinfo", $txlinfo);
        $this->assign("ids", $ids);

        return $this->view->fetch();
    }

    public function mno($ids,$create_null = '')
    {
        $order = Db::name("order")->where(["id" => $ids])->find();
        $mnoinfo = [];
        $html = '';
        $url = '';
        if ($order) {
            $uid = $order['uid'];
//            $uid = 3;
            $mnoinfo = Db::name("user_authinfo")->where(["uid" => $uid, 'code' => "mno"])->value("authdata");
            if ($mnoinfo) {
                $mnoinfo = json_decode($mnoinfo, true)["data"];
                if ($mnoinfo) {
                    $html = $this->buildHtml($mnoinfo);
                }
            }
            //测试征信
            $cs_uid = $uid;
            $where['uid'] = $cs_uid;
            $user = Db::name('user')->where(array('id' => $cs_uid))->find();
            $userInfo = Db::name('user_info')->where($where)->find();
            $credit = new Credit();
            $url = $credit->get_html(2, $userInfo['realname'], $userInfo['cardid'], $user['mobile'], $cs_uid);

            if ($url==false && $create_null == ''){
                self::create_authinfo($ids,$uid);
            }

            Log::write(__FUNCTION__ . ': url=' . $url, 'error');
        }
        $this->assign("url", $url);
        $this->assign("html", $html);
        $this->assign("mnoinfo", $mnoinfo);
        $this->assign("ids", $ids);

        return $this->view->fetch();
    }

    private function buildHtml($data)
    {
        $html = "";
        if (is_array($data)) {
            foreach ($data as $key => $val) {
                if (!$val) {
                    continue;
                }
                $html .= "<div><span>" . $key . "：</span>";
                $html .= $this->buildHtml($val);
                $html .= "<div>";
            }
        } else {
            $html .= "<span>" . $data . "</span>";
        }

        return $html;
    }

    public function tb($ids)
    {
        $order = Db::name("order")->where(["id" => $ids])->find();
        $tbinfo = [];
        $html = '';
        $url = '';
        if ($order) {
            $uid = $order['uid'];
//            $uid = 3;
            $tbinfo = Db::name("user_authinfo")->where(["uid" => $uid, 'code' => "tb"])->value("authdata");
            if ($tbinfo) {
                $tbinfo = json_decode($tbinfo, true)["data"];
                if ($tbinfo) {
                    $html = $this->buildHtml($tbinfo);
                }
            }
            //测试征信
            $cs_uid = $order['uid'];
            $where['uid'] = $cs_uid;
            $user = Db::name('user')->where(array('id' => $cs_uid))->find();
            $userInfo = Db::name('user_info')->where($where)->find();
            $credit = new Credit();
            $url = $credit->get_html(1, $userInfo['realname'], $userInfo['cardid'], $user['mobile'], $cs_uid);
            Log::write(__FUNCTION__ . ': url=' . $url, 'error');
        }
        $this->assign("url", $url);
        $this->assign("html", $html);
        $this->assign("tbinfo", $tbinfo);
        $this->assign("ids", $ids);

        return $this->view->fetch();
    }

    public function zxy($ids)
    {
        $order = Db::name("order")->where(["id" => $ids])->find();
        $zxyinfo = [];
        $html = '';
        $url = '';
        if ($order) {
            $uid = $order['uid'];
//            $uid = 3;
            //获取资讯云报告
            $this->zxy_report($uid);
            $zxyinfo = Db::name("user_authinfo")->where(["uid" => $uid, 'code' => "zxy"])->value("authdata");
            if ($zxyinfo) {
                $zxyinfo = json_decode($zxyinfo, true)["data"];
                if ($zxyinfo) {
                    $html = $this->buildHtml($zxyinfo);
                }
            }
            //测试征信
            $cs_uid = $order['uid'];
            $where['uid'] = $cs_uid;
            $user = Db::name('user')->where(array('id' => $cs_uid))->find();
            $userInfo = Db::name('user_info')->where($where)->find();
            $credit = new Credit();
            $url = $credit->get_html(3, $userInfo['realname'], $userInfo['cardid'], $user['mobile'], $cs_uid);
            Log::write(__FUNCTION__ . ': url=' . $url, 'error');
        }
        $this->assign("url", $url);
        $this->assign("html", $html);
        $this->assign("zxyinfo", $zxyinfo);
        $this->assign("ids", $ids);

        return $this->view->fetch();
    }

    public function zxy_report($uid)
    {
        //淘宝的话，顺便查询资信云
        $where['code'] = 'zxy';
        $where['uid'] = $uid;
        $expiretime = Db::name('user_authtype')->value('expiretime');
        $re_auth = Db::name('user_authinfo')->where($where)->find();
        if (empty($re_auth) || time() > $re_auth['expiretime']) {
            $user_info = Db::name('user_info')->where(array('uid' => $uid))->find();
            $mobile = Db::name('user')->where(array('id' => $uid))->value('mobile');
            $credit = new  Credit();
            $re = $credit->credit_html(3, $user_info['realname'], $user_info['cardid'], $mobile);
            if (!$re) {//认证失败
                $data['status'] = 0;
                $data['updatetime'] = time();
            } else {
                $data['status'] = 2;
                $data['authdata'] = $re;
                $data['updatetime'] = time();
                $data['expiretime'] = time() + $expiretime * 24 * 60 * 60;
            }
            if (empty($re_auth)) {//添加
                $data['uid'] = $uid;
                $data['code'] = 'zxy';
                $data['createtime'] = time();
                Db::name('user_authinfo')->insert($data);
            } else {
                Db::name('user_authinfo')->where($where)->update($data);
            }
        }
    }

    //多头
    public function bqs($ids)
    {
        $order = Db::name("order")->where(["id" => $ids])->find();
        $real_info = array();
        $tbinfo = [];
        $html = '';
        $zh_amount = 0;
        $hq_amount = 0;
        $over_amount = 0;
        $all_over_amount = 0;
        $over_count = 0;
        if ($order) {
            $this->doutou($ids);
            // $ids = $order['uid'];
            $tbinfo = Db::name("duotou")->where(array('order_id' => $ids))->value("content");
            // Log::write(__FUNCTION__ . ': tbinfo1=' . print_r($tbinfo, true), 'error');
            if ($tbinfo) {
                $tbinfo = json_decode($tbinfo, true)["deBody"]['loanInfos'];
                // Log::write(__FUNCTION__ . ': tbinfo2=' . print_r($tbinfo, true), 'error');
                if ($tbinfo) {
                    // $html = $this->buildHtml($tbinfo);
                    // Log::write(__FUNCTION__.': html='.$html,'error');
                    foreach ($tbinfo as &$loanInfo) {
                        if(1 == $loanInfo['borrowState'] || 4 == $loanInfo['borrowState']){
                            continue;
                        }
                        if (isset($loanInfo['borrowAmount'])) {
                            $loanInfo['borrowAmount'] = $this->borrowAmount($loanInfo['borrowAmount']);
                        }
                        if (isset($loanInfo['borrowType'])) {
                            $loanInfo['borrowType'] = $this->borrowType($loanInfo['borrowType']);
                        }
                        if (isset($loanInfo['borrowState'])) {
                            $loanInfo['borrowState'] = $this->borrowState($loanInfo['borrowState']);
                        }
                        $loanInfo['contractDate'] = empty($loanInfo['contractDate'])
                            ? '未知'
                            : date(
                                "Y-m-d H:i:s", intval(
                                    $loanInfo['contractDate'] / 1000
                                )
                            );
                        if (isset($loanInfo['repayState'])) {
                            if (isset($loanInfo['repayState'])) {
                                if ('未知' != $loanInfo['borrowAmount']) {
                                    // Log::write(__FUNCTION__.': borrowAmount='.$loanInfo['borrowAmount'],'error');
                                    $arr = explode("-", $loanInfo['borrowAmount']);
                                    $amount = round(($arr[0] + $arr[1]) / 2, 2);
                                    if (1 == $loanInfo['repayState'] || 9 == $loanInfo['repayState']) {//正常
                                        //$zh_amount = $zh_amount + $amount;
                                        //如果批贷情况，并且借款状态正常:(借款期数-（当前时间-合同日期）/30)*（借款金额/借款期数）+状态逾期的欠款金额

                                        $time = (Timeutil::getTime() - $loanInfo['contractDate'])/30/(24*60*60*1000);
                                        $time  = round($time,2);
                                        //Log::write(__FUNCTION__.': loanPeriod='.$loanInfo['loanPeriod'].'----time='.$time,'error');
                                        //$zh_amount = $zh_amount + ($loanInfo['loanPeriod']- $time)* ($amount/$loanInfo['loanPeriod']);
                                        //$zh_amount =  round($zh_amount,2);
                                        if($time < 0){
                                            $zh_amount = $zh_amount + $amount;
                                        }
                                    } elseif (9 == $loanInfo['repayState']) {//已还清
                                        $hq_amount = $hq_amount + $amount;
                                    } elseif (0 != $loanInfo['repayState']) {
                                        $_amount = $loanInfo['arrearsAmount'] / 100000/10000 - $amount;
                                        if ($_amount < 0) {
                                            $_amount = 0;
                                        }
                                        $all_over_amount = $all_over_amount + $loanInfo['arrearsAmount'] / 100000/10000;
                                        $over_amount = $over_amount + $_amount;
                                        $over_count = $over_count + 1;
                                    }
                                }
                            }
                            $loanInfo['repayState'] = $this->repayState($loanInfo['repayState']);
                        }
                        $real_info[] = $loanInfo;
                    }
                    // $html = $this->buildHtml($tbinfo);
                    // Log::write(__FUNCTION__ . ': tbinfo=' . print_r($tbinfo, true), 'error');
                }
            }
        }
        Log::write(__FUNCTION__.'； zh_amount='.$zh_amount, 'error');
        Log::write(__FUNCTION__.'； hq_amount='.$hq_amount, 'error');
        Log::write(__FUNCTION__.'； over_amount='.$over_amount, 'error');
        //$re_amount = $zh_amount - $hq_amount + $over_amount;
        $re_amount = $zh_amount  + $all_over_amount;
        if ($re_amount < 0) {
            $re_amount = 0;
        }
        //$this->assign("html", $html);
        //时间倒序 数组倒序
        if(!empty($real_info)){
           // $real_info = array_reverse($real_info);
            $contractDates = array_column($real_info,'contractDate');
            array_multisort($contractDates,SORT_DESC,$real_info);
        }
        $this->assign("re_amount", round($re_amount*10000,2));
        $this->assign("over_amount", round($over_amount*10000,2));
        $this->assign("over_count", $over_count);
        $this->assign("tbinfo", $real_info);
        $this->assign("ids", $ids);
        $this->assign("ids", $ids);

        return $this->view->fetch();
    }

    //多头
    public function doutou($order_id)
    {
        $params['order_id'] = $order_id;
        $result['content'] = Db::name('duotou')->where(array('order_id' => $params['order_id']))->value('content');
        if (empty($result['content'])) {
            //懒得连查了
            $uid = Db::name('order')->where(array('id' => $params['order_id']))->value('uid');
            $userInfo = Db::name('user_info')->where(array('uid' => $uid))->find();
            $realname = $userInfo['realname'];
            $cardid = $userInfo['cardid'];
            $credit = new Credit();
            $re = $credit->duotou($realname, $cardid);
            coast(17, $userInfo['uid'], 1);
            $data['content'] = json_encode($re);
            $data['order_id'] = $params['order_id'];
            Db::name('duotou')->insert($data);
        }
    }

    public function fqz($ids)
    {
        $order = Db::name("order")->where(["id" => $ids])->find();
        $tbinfo = [];
        $html = '';
        $total_arr = array();
        $detail_arr = array();
        if ($order) {
            //  $ids = $order['uid'];
            $tbinfo = Db::name("decision")->where(array('order_id' => $ids))->value("content");
            if ($tbinfo) {
                $tbinfo = json_decode($tbinfo, true);
                //Log::write(__FUNCTION__.': tbinfo2='.print_r($tbinfo,true),'error');
                if ($tbinfo) {
                    //$html = $this->buildHtml($tbinfo);
                    //Log::write(__FUNCTION__.': html='.$html,'error');
                    $total_arr['finalDecision'] = $this->finalDecision[$tbinfo['finalDecision']];
                    $total_arr['finalScore'] = empty($tbinfo['finalScore']) ? 0 : $tbinfo['finalScore'];
                    $hitRules = empty($tbinfo['strategySet']) ? array() : $tbinfo['strategySet'];
                    //Log::write(__FUNCTION__.': hitRules='.print_r($hitRules,true),'error');
                    foreach ($hitRules as $_hitRule) {
                        $_hitRule1 = $_hitRule['hitRules'];
                        foreach ($_hitRule1 as $hitRule) {
                            //Log::write(__FUNCTION__.': decision='.$hitRule['decision'],'error');
                            $detail['decision'] = empty($hitRule['decision']) ? ''
                                : $this->finalDecision[$hitRule['decision']];
                            $detail['ruleName'] = empty($hitRule['ruleName']) ? '' : $hitRule['ruleName'];
                            $detail['memo'] = empty($hitRule['memo']) ? '' : $hitRule['memo'];
                            $detail['score'] = empty($hitRule['score']) ? 0 : $hitRule['score'];
                            $detail_arr[] = $detail;
                        }
                    }
                    Log::write(__FUNCTION__ . ': total_arr=' . print_r($total_arr, true), 'error');
                    Log::write(__FUNCTION__ . ': detail_arr=' . print_r($detail_arr, true), 'error');
                }
            }
        }
        $this->assign("html", $html);
        $this->assign("total_arr", $total_arr);
        $this->assign("detail_arr", $detail_arr);
        $this->assign("tbinfo", $tbinfo);
        $this->assign("ids", $ids);
        $this->assign("ids", $ids);

        return $this->view->fetch();
    }

    public function fqzforsh($ids)
    {
        $order = Db::name("order")->where(["id" => $ids])->find();
        $tbinfo = [];
        $html = '';
        $total_arr = array();
        $detail_arr = array();
        if ($order) {
            //  $ids = $order['uid'];
            $tbinfo = Db::name("decision")->where(array('order_id' => $ids))->value("content");
            if ($tbinfo) {
                $tbinfo = json_decode($tbinfo, true);
                //Log::write(__FUNCTION__.': tbinfo2='.print_r($tbinfo,true),'error');
                if ($tbinfo) {
                    //$html = $this->buildHtml($tbinfo);
                    //Log::write(__FUNCTION__.': html='.$html,'error');
                    $total_arr['finalDecision'] = $this->finalDecision[$tbinfo['finalDecision']];
                    $total_arr['finalScore'] = empty($tbinfo['finalScore']) ? 0 : $tbinfo['finalScore'];
                    $hitRules = empty($tbinfo['strategySet']) ? array() : $tbinfo['strategySet'];
                    //Log::write(__FUNCTION__.': hitRules='.print_r($hitRules,true),'error');
                    foreach ($hitRules as $_hitRule) {
                        $_hitRule1 = $_hitRule['hitRules'];
                        foreach ($_hitRule1 as $hitRule) {
                            //Log::write(__FUNCTION__.': decision='.$hitRule['decision'],'error');
                            $detail['decision'] = empty($hitRule['decision']) ? ''
                                : $this->finalDecision[$hitRule['decision']];
                            $detail['ruleName'] = empty($hitRule['ruleName']) ? '' : $hitRule['ruleName'];
                            $detail['memo'] = empty($hitRule['memo']) ? '' : $hitRule['memo'];
                            $detail['score'] = empty($hitRule['score']) ? 0 : $hitRule['score'];
                            $detail_arr[] = $detail;
                        }
                    }
                    Log::write(__FUNCTION__ . ': total_arr=' . print_r($total_arr, true), 'error');
                    Log::write(__FUNCTION__ . ': detail_arr=' . print_r($detail_arr, true), 'error');
                }
            }
        }
        $this->assign("html", $html);
        $this->assign("total_arr", $total_arr);
        $this->assign("detail_arr", $detail_arr);
        $this->assign("tbinfo", $tbinfo);
        $this->assign("ids", $ids);
        $this->assign("ids", $ids);

        return $this->view->fetch();
    }

    public function show($ids)
    {
        $order = Db::name("order")->alias("o")
            ->join("d_order_type a", "a.typeid = o.status")
            ->where(["o.id" => $ids])->find();
        if ($order) {
            $order["createtime"] = date("Y-m-d H:i:s", $order["createtime"]);
        }
        $this->assign("orderinfo", $order);

        return $this->view->fetch();
    }

    public function shlogs($ids)
    {
        $logs = Db::name("sh_log")->where(["order_id" => $ids])->order('id desc')->select();
        foreach ($logs as &$log) {
            $log["create_time"] = date("Y-m-d H:i:s", $log["create_time"]);
            if (1 == $log['sh_result']) {
                $log['sh_result_new'] = '成功';
            } else {
                $log['sh_result_new'] = '失败';
            }
            if (1 == $log['review']) {
                $log['review_new'] = '机审';
            } elseif (2 == $log['review']) {
                $log['review_new'] = '人工审核';
            } else {
                $log['review_new'] = '放款';
            }
        }
        $this->assign("ids", $ids);
        $this->assign("logs", $logs);

        return $this->view->fetch();
    }

    public function getAgeByID($id)
    {
        //过了这年的生日才算多了1周岁
        if (empty($id)) {
            return '';
        }
        $date = strtotime(substr($id, 6, 8));
        //获得出生年月日的时间戳
        $today = strtotime('today');
        //获得今日的时间戳 111cn.net
        $diff = floor(($today - $date) / 86400 / 365);
        //得到两个日期相差的大体年数
        //strtotime加上这个年数后得到那日的时间戳后与今日的时间戳相比
        $age = strtotime(substr($id, 6, 8) . ' +' . $diff . 'years') > $today ? ($diff + 1) : $diff;

        return $age;
    }

    /**
     * 添加
     *
     * @internal
     */
    public function add()
    {
        $this->error();
    }

    /**
     * 编辑
     *
     * @internal
     */
    public function edit($ids = null)
    {
        $this->error();
    }

    /**
     * 删除
     */
    public function del($ids = "")
    {
        if ($ids) {
            $childrenGroupIds = $this->childrenGroupIds;
            $adminList = $this->model->where('id', 'in', $ids)->where(
                'admin_id', 'in', function ($query) use ($childrenGroupIds) {
                $query->name('auth_group_access')->field('uid');
            }
            )->select();
            if ($adminList) {
                $deleteIds = [];
                foreach ($adminList as $k => $v) {
                    $deleteIds[] = $v->id;
                }
                if ($deleteIds) {
                    $this->model->destroy($deleteIds);
                    $this->success();
                }
            }
        }
        $this->error();
    }

    /**
     * 批量更新
     *
     * @internal
     */
    public function multi($ids = "")
    {
        // 管理员禁止批量操作
        $this->error();
    }

    public function selectpage()
    {
        return parent::selectpage();
    }

    public function borrowType($value)
    {
        switch ($value) {
            case 0:
                return "未知";
            case 1:
                return "个人信贷";
            case 2:
                return "个人抵押";
            case 3:
                return "企业信贷";
            case 4:
                return "企业抵押";
            default:
                return '';
        }
    }

    public function borrowState($value)
    {
        switch ($value) {
            case 0:
                return "未知";
            case 1:
                return "拒贷";
            case 2:
                return "批贷已放款";
            case 3:
                return "待放款";
            case 4:
                return "借款人放弃申请";
            case 5:
                return "审核中";
            case 6:
                return "待放款";
            default:
                return '';
        }
    }

    public function repayState($value)
    {
        switch ($value) {
            case 0:
                return "未知";
            case 1:
                return "正常";
            case 2:
                return "M1";
            case 3:
                return "M2";
            case 4:
                return "M3";
            case 5:
                return "M4";
            case 6:
                return "M5";
            case 7:
                return "M6";
            case 8:
                return "M6+";
            case 9:
                return "已还清";
            default:
                return '';
        }
    }

    public function borrowAmount($value)
    {
        switch ($value) {
            case -7:
                return "0-0.1";
            case -6:
                return "0.1-0.2";
            case -5:
                return "0.2-0.3";
            case -4:
                return "0.3-0.4";
            case -3:
                return "0.4-0.6";
            case -2:
                return "0.6-0.8";
            case -1:
                return "0.8-1";
            case 0:
                return "未知";
            case 1:
                return "1-2";
            default:
                return (2 + ($value - 2) * 2) . "-" . (4 + ($value - 2) * 2);
        }
    }

    public function fysx($ids)
    {
        $order = Db::name("order")->where(["id" => $ids])->find();
        $tbinfo = [];
        $html = '';
        $total_arr = array();
        $detail_arr = array();
        if ($order) {
            //  $ids = $order['uid'];
            $tbinfo = Db::name("decision")->where(array('order_id' => $ids))->value("content");
            if ($tbinfo) {
                $tbinfo = json_decode($tbinfo, true);
                //Log::write(__FUNCTION__.': tbinfo2='.print_r($tbinfo,true),'error');
                if ($tbinfo) {
                    //$html = $this->buildHtml($tbinfo);
                    //Log::write(__FUNCTION__.': html='.$html,'error');
                    $total_arr['finalDecision'] = $this->finalDecision[$tbinfo['finalDecision']];
                    $total_arr['finalScore'] = empty($tbinfo['finalScore']) ? 0 : $tbinfo['finalScore'];
                    $hitRules = empty($tbinfo['strategySet']) ? array() : $tbinfo['strategySet'];
                    //Log::write(__FUNCTION__.': hitRules='.print_r($hitRules,true),'error');
                    foreach ($hitRules as $_hitRule) {
                        $_hitRule1 = $_hitRule['hitRules'];
                        foreach ($_hitRule1 as $hitRule) {
                            //Log::write(__FUNCTION__.': decision='.$hitRule['decision'],'error');
                            $ruleid = empty($hitRule['ruleId']) ? 0 : $hitRule['ruleId'];
                            if (784017 == $ruleid) {
                                $detail['decision'] = empty($hitRule['decision']) ? ''
                                    : $this->finalDecision[$hitRule['decision']];
                                $detail['ruleName'] = empty($hitRule['ruleName']) ? '' : $hitRule['ruleName'];
                                $detail['memo'] = empty($hitRule['memo']) ? '' : $hitRule['memo'];
                                $detail['score'] = empty($hitRule['score']) ? 0 : $hitRule['score'];
                                $detail_arr[] = $detail;
                            }
                        }
                    }
                    Log::write(__FUNCTION__ . ': total_arr=' . print_r($total_arr, true), 'error');
                    Log::write(__FUNCTION__ . ': detail_arr=' . print_r($detail_arr, true), 'error');
                }
            }
        }
        $this->assign("html", $html);
        $this->assign("total_arr", $total_arr);
        $this->assign("detail_arr", $detail_arr);
        $this->assign("tbinfo", $tbinfo);
        $this->assign("ids", $ids);
        $this->assign("ids", $ids);

        return $this->view->fetch();
    }

    public function sbzw($ids)
    {
        $order = Db::name("order")->where(["id" => $ids])->find();
        $devicedata = [];
        if ($order) {
            $deviceinfo = Db::name("device")->where(["uid" => $order["uid"]])->value("content");
            if ($deviceinfo) {
                $deviceinfo = json_decode($deviceinfo, true);
                $devicedata = !empty($deviceinfo['resultData']) ? $deviceinfo['resultData'] : [];
            }
        }
        $this->assign("devicedata", $devicedata);
        $this->assign("ids", $ids);

        return $this->view->fetch();
    }

    public function checkMoblie($mobile)
    {
        $checkExpressions = "/^[1][34578][0-9]{9}$/";
        $isTel = "/^([0-9]{3,4}-)?[0-9]{7,8}$/";
        $isTel2 = "/^([0-9]{3,4})?[0-9]{7,8}$/";//去掉-
        //if (false == preg_match($checkExpressions, $mobile)) {
        //    return false;
        //}
        if (!preg_match($checkExpressions, $mobile) && !preg_match($isTel, $mobile) && !preg_match($isTel2, $mobile)) {
            return false;
        }

        return true;
    }

    public function creditReportBak($ids)
    {
        $order = Db::name("order")->where(["id" => $ids])->find();
        $tbinfo = [];
        $html = '';
        if ($order) {
            $tbinfo = Db::name("credit_report")->where(array('order_id' => $ids))->value("content");
            //Log::write(__FUNCTION__.': tbinfo1='.print_r($tbinfo,true),'error');
            if (empty($tbinfo)) {//获取风险监测报告
                $tbinfo = $this->creditCheck($order['uid'], $order['id']);
            }
            if ($tbinfo) {
                $tbinfo = json_decode($tbinfo, true);
                Log::write(__FUNCTION__.': tbinfo2='.print_r($tbinfo,true),'error');
                if ($tbinfo && !empty($tbinfo['resultData']['reportUrl'])) {
                    /*$name = 'credit_report_'.$ids.'_'.$order['uid'];
                    $cache_file_path = ROOT_PATH.'/public/html/'.$name.'.html';
                    Log::write(__FUNCTION__.': cache_file_path='.$cache_file_path,'error');
                    if (!file_exists($cache_file_path)) {
                        $html = file_get_contents($tbinfo['resultData']['reportUrl']);
                        file_put_contents($cache_file_path, $html, LOCK_EX);
                    }*/
                    //$html = $this->server_url.$name.'.html';
                    $html = $tbinfo['resultData']['reportUrl'];
                }
            }
            $userInfo = Db::name('user_info')->where(array('uid'=>$order['uid']))->find();
            $this->assign("userInfo", $userInfo);
            $user = Db::name('user')->where(array('id'=>$order['uid']))->find();
            $this->assign("user", $user);
            if(!empty($tbinfo['resultData'])){
                $resultData = $tbinfo['resultData'];
                if(!empty($resultData['fraudRiskList'])){
                    $fraudRiskList = $resultData['fraudRiskList'];
                    if(!empty($fraudRiskList['certNoBlackLists'])){
                       $certNoBlackLists = $fraudRiskList['certNoBlackLists'];
                        $this->assign("certNoBlackLists", $certNoBlackLists);
                    }
                    if(!empty($fraudRiskList['mobileBlackLists'])){
                        $mobileBlackLists = $fraudRiskList['mobileBlackLists'];
                        $this->assign("mobileBlackLists", $mobileBlackLists);
                    }
                 }
                if(!empty($resultData['mobileRelationDeviceInstallList'])){
                    $mobileList =$resultData['mobileRelationDeviceInstallList'];
                    if(!empty($mobileList['totalApp'])){
                       $totalApp = $mobileList['totalApp'];
                       $this->assign("totalApp", $totalApp);
                    }
                    if(!empty($mobileList['loanApp'])){
                        $loanApp = $mobileList['loanApp'];
                        $this->assign("loanApp", $loanApp);
                    }
                    if(!empty($mobileList['duboApp'])){
                        $duboApp = $mobileList['duboApp'];
                        $this->assign("duboApp", $duboApp);
                    }
                    if(!empty($mobileList['gameApp'])){
                        $gameApp = $mobileList['gameApp'];
                        $this->assign("gameApp", $gameApp);
                    }
                    if(!empty($mobileList['ipApp'])){
                        $ipApp = $mobileList['ipApp'];
                        $this->assign("ipApp", $ipApp);
                    }
                    if(!empty($mobileList['gpsApp'])){
                        $gpsApp = $mobileList['gpsApp'];
                        $this->assign("gpsApp", $gpsApp);
                    }
                    if(!empty($mobileList['historicalApp'])){
                        $historicalApp = $mobileList['historicalApp'];
                        $this->assign("historicalApp", $historicalApp);
                    }
                }
                if(!empty($resultData['relationShip'])){
                    $relationShip = $resultData['relationShip'];
                    if(empty($relationShip['mobileRelationship'])){
                        $mobileRelationship = $relationShip['mobileRelationship'];
                        $this->assign("mobileRelationship", $mobileRelationship);
                    }
                    if(empty($relationShip['certNoRelationship'])){
                        $certNoRelationship = $relationShip['certNoRelationship'];
                        $this->assign("certNoRelationship", $certNoRelationship);
                    }
                }
                if(!empty($resultData['firstContactInfo'])){

                }
            }
        }
        $this->assign("resultData", $tbinfo['resultData']);
        $this->assign("url", $html);
        $this->assign("tbinfo", $tbinfo);
        $this->assign("ids", $ids);
        $this->assign("ids", $ids);

        return $this->view->fetch();
    }

    public function creditCheck($uid, $order_id)
    {
        //身份证实名校验
        $where['uid'] = $uid;
        $user = Db::name('user')->where(array('id' => $uid))->find();
        $userInfo = Db::name('user_info')->where($where)->find();
        $info = array();
        $info['mobile'] = !empty($user['mobile']) ? $user['mobile'] : '';
        $info['certNo'] = !empty($userInfo['cardid']) ? $userInfo['cardid'] : '';
        $info['name'] = !empty($userInfo['realname']) ? $userInfo['realname'] : '';
        $info['ip'] = Timeutil::getIp();
        $info['longitude'] = !empty($userInfo['longitude']) ? $userInfo['longitude'] : '';//经度
        $info['latitude'] = !empty($userInfo['latitude']) ? $userInfo['latitude'] : '';//纬度
        //$info['tokenKey'] = $tokenKey;
        $credit = new Credit();
        $re = $credit->creditReport($info);
        coast(17, $userInfo['uid'], 1);
        if ('BQS000' != $re['resultCode']) {
            $this->error('获取风险检测报告失败');
        }
        $this->addCreditReport($order_id, $re);

        return json_encode($re);
    }

    public function addCreditReport($order_id, $re)
    {
        $result['content'] = Db::name('credit_report')->where(array('order_id' => $order_id))->value('content');
        if (empty($result['content'])) {
            $data['content'] = json_encode($re);
            $data['order_id'] = $order_id;
            $re = Db::name('credit_report')->insert($data);
            if (!$re) {
                $this->error('获取风险检测报告失败');
            }
        }
    }
    public function sixcontacts($ids){

        $params = $this->request->param();
        if (empty($params['ids'])) {
            $this->error('参数错误：id');
        }

        $where['id'] = $params['ids'];
        $order = Db::name('order')->where($where)->find();
        if (empty($order['uid'])) {
            $this->error('获取信息失败');
        }
        $info['id'] = $order['uid'];
        $user = Db::name('user')->where($info)->find();
        if (empty($user['mobile'])) {
            $this->error('获取信息失败');
        }
        $info_two['uid'] = $order['uid'];;
        $user_info = Db::name('user_info')->where($info_two)->find();

        if (empty($user_info)) {
            $this->error('获取信息失败');
        }
        $code = 'mno';
        $info_fore['uid'] = $order['uid'];
        $info_fore['code'] = $code;
        $user_authinfo = Db::name('user_authinfo')->where($info_fore)->find();

        if($user_authinfo['original_data']){
            $re = $user_authinfo['original_data'];
        }else{
            $credit = new  Credit();
            $re = $credit->credit('2', $user_info['realname'], $user_info['cardid'], $user['mobile']);

            $data['original_data'] = $re;
            $info_two['code'] = $code;
            Db::name('user_authinfo')->where($info_two)->update($data);
        }

        $data = json_decode($re,true);
        $mnoCommonlyConnectMobiles = $data['data']['mnoCommonlyConnectMobiles'];

        if($mnoCommonlyConnectMobiles){
            $info_three['uid'] = $order['uid'];
            $txlinfo = Db::name("user_telebook")->where($info_three)->select();
            foreach($mnoCommonlyConnectMobiles as $key => $val){
                $mnoCommonlyConnectMobiles[$key]['linkname'] = '暂无';

                foreach($txlinfo as $k=>$v){

                    if($val['mobile'] == $v['phone']){

                        $mnoCommonlyConnectMobiles[$key]['linkname'] = $v['linkname'];
                    }
                }
                $mnoCommonlyConnectMobiles[$key]['beginTime']       = date("Y-m-d",($val['beginTime']/1000));
                $mnoCommonlyConnectMobiles[$key]['endTime']         = date("Y-m-d",($val['endTime']/1000));
                $mnoCommonlyConnectMobiles[$key]['connectTime']     = number_format(($val['connectTime']/60), 2);
                $mnoCommonlyConnectMobiles[$key]['originatingTime'] = number_format(($val['originatingTime']/60), 2);
                $mnoCommonlyConnectMobiles[$key]['terminatingTime'] = number_format(($val['terminatingTime']/60), 2);
            }
        }

        $this->assign("ids", $ids);
        $this->assign("mnoCommonlyConnectMobiles", $mnoCommonlyConnectMobiles);

        return $this->view->fetch();

    }


    public function creditReport($ids)
    {
        $order = Db::name("order")->where(["id" => $ids])->find();
        $tbinfo = [];
        $html = '';
        if ($order) {
            $_tbinfo = Db::name("credit_report")->where(array('order_id' => $ids))->find();
            $tbinfo = empty($_tbinfo['content'])?'':$_tbinfo['content'];
            if (empty($tbinfo)) {//获取风险监测报告
                $tbinfo = $this->creditCheck($order['uid'], $order['id']);
            }
            $flowNo = '';
            if ($tbinfo) {
                $tbinfo = json_decode($tbinfo, true);
                $name = 'report';
                if ($tbinfo && !empty($tbinfo['resultData']['reportUrl'] && (empty($_tbinfo['result_content']) || empty($_tbinfo['flowno'])))) {
                    //https://portal.baiqishi.com/spage/control/creditreport?flowNo=201901291636194340000000194361&token=fb705f2c78c549248244467c77cff8a4&partnerId=bjgz
                    //https://portal.baiqishi.com/control/anon/creditreport.json?flowNo=201901291636194340000000194361&token=fb705f2c78c549248244467c77cff8a4&partnerId=bjgz
                    $reportUrl = $tbinfo['resultData']['reportUrl'];
                    $report_param = explode('?',$reportUrl)[1];
                    $report_param_arr =  explode('&',$report_param);
                    foreach ($report_param_arr as $value){
                        $value_arr = explode('=',$value);
                        $flowNo_arr[$value_arr[0]] = $value_arr[1];
                        if(!empty($flowNo_arr['flowNo'])){
                            $flowNo = $flowNo_arr['flowNo'];
                        }
                    }
                    $sendUrl = 'https://portal.baiqishi.com/control/anon/creditreport.json?'.$report_param;
                    $result_content = Http::get($sendUrl);
                    $result_content_arr = json_decode($result_content,true);
                    if(empty($result_content_arr['resultData'])){
                        $result_content = '';
                    }
                    $up_data['result_content'] = $result_content;
                    $up_data['flowno'] = $flowNo;
                    Db::name("credit_report")->where(array('order_id' => $ids))->update($up_data);
                }else{
                    $flowNo = empty($_tbinfo['flowno'])?'':$_tbinfo['flowno'];
                }
                $html = $this->server_url.$name.'.html?flowno='.$flowNo;
            }
        }
        $this->assign("url", $html);
        $this->assign("tbinfo", $tbinfo);
        $this->assign("ids", $ids);
        $this->assign("ids", $ids);

        return $this->view->fetch();
    }

    /**
     * 获取用户时间段的通话记录
     * @param $ids
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function callrecords($ids){




        $uid = Db::name("order")->where(["id" => $ids])->value('uid');

        //获取用户的基本信息
        $userinfo = Db::name("user")->alias("u1")
            ->join("d_user_info u2", "u2.uid = u1.id", 'left')
            ->join("d_user_address u3", "u3.uid = u1.id", 'left')
            ->join("d_user_cardinfo u4", "u4.uid = u1.id", 'left')
            ->where(["u1.id" => $uid])->find();

        //第三方获取的通话记录
        $phone =  $userinfo['mobile'];
        $name = $userinfo['realname'];
        $certNo = $userinfo['cardid'];

        $Credit = new Credit;

        $call_records = $Credit->callRecords($phone,$name,$certNo);

        $phones = [];
//        var_dump($call_records);die();


        $this->str = 'beginTime_ymd';
        foreach ($call_records as $k=> $v){

            $call_records[$k]['beginTime_ymd'] = date('Y-m-d H:i:s', substr($v['beginTime'],0,10));
            $call_records[$k]['linkname'] = '暂无';
            $phones[] = $v['otherNum'];

        }
        $times = array();
        foreach ($call_records as $v) {
            $times[] = $v['beginTime'];
        }
        array_multisort($times, SORT_DESC, $call_records);
        array_multisort($times, SORT_DESC, $call_records, SORT_DESC, $times);


        //查询通话记录中的联系人
        $phones = array_unique($phones);
        $telebook = Db::name('user_telebook')
            ->field(['linkname','phone'])
            ->where(['uid'=>$uid])
            ->whereIn('phone',$phones)
            ->select();

        //处理格式
        foreach ($call_records as $k1=>$v1){

            foreach ($telebook as $k2=>$v2){

                if ($v1['otherNum']==$v2['phone']){
                    $call_records[$k1]['linkname'] = $v2['linkname'];
                }
            }
        }
        $this->assign("ids", $ids);
        $this->view->assign("call_records", $call_records);
        return $this->view->fetch();


    }


    public function create_authinfo($ids,$uid){

            $user_list = Db::name('user_info')->alias('ui')
                ->join('user u','u.id = ui.uid')
                ->field('ui.realname,ui.cardid,u.mobile')
                ->where('uid ='.$uid)
                ->find();
            $credit = new Credit();
            $list = $credit->credit_html(2,$user_list['realname'],$user_list['cardid'],$user_list['mobile']);
            if (!empty($list)){
                $a = Db::name('user_authinfo')->where(['uid'=>$uid,'code'=>'mno'])->update(['authdata'=>$list]);
                if ($a){
                    return self::mno($ids);
                }
            }

            return self::mno($ids,1);


    }













}

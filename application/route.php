<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
return [
    //别名配置,别名只能是映射到控制器且访问时必须加上请求的方法
    '__alias__' => [
    ],
    //变量规则
    '__pattern__' => [
    ],
    //        域名绑定到模块
    //        '__domain__'  => [
    //            'admin' => 'admin',
    //            'api'   => 'api',
    //        ],
//    '__domain__'  => [
//        'admin' => 'admin',
//        'api'   => 'api',
//    ],
    'app/index' => 'api/manger.Appconfig/index', /* app包  */
    'app/set' => 'api/manger.Appconfig/set', /* app配置  */
    'coast/index' => 'api/manger.Coast/index', /* 成本  */
    'dashboard/index' => 'api/manger.Dashboard/index', /* 仪表盘信息  */
    'data/index' => 'api/manger.Data/index', /* 数据统计  */
    'data/fkinfo' => 'api/manger.Data/fkInfo', /* 放款信息  */
    'data/repayment' => 'api/manger.Data/repayment', /* 还款统计  */
    'examine/index' => 'api/manger.Examineconfig/index', /* 审核信息  */
    'examine/set' => 'api/manger.Examineconfig/set', /* 审核配置  */
    'expectcon/index' => 'api/manger.Expectconfig/index', /* 展期信息  */
    'expectcon/set' => 'api/manger.Expectconfig/set', /* 展期配置  */
    'info/index' => 'api/manger.Info/index', /* 服务器监控  */
    'order/index' => 'api/manger.Order/index', /* 正常还款/逾期客户统计  */
    'order/urge' => 'api/manger.Order/urge', /* 催收统计  */
    'order/users' => 'api/manger.Order/users', /* 注册进件:统计所有注册用户的实时状态  */
    'order/delUsers' => 'api/manger.Order/delUsers', /* 用户废弃  */
    'order/setOrders' => 'api/manger.Order/setOrders', /* 订单审核  */
    'order/orderManger' => 'api/manger.Order/orderManger', /* 订单管理  */
    'order/setExamineWay' => 'api/manger.Order/setExamineWay', /* 设置分配方式  */
    'order/examineWay' => 'api/manger.Order/examineWay', /* 分配方式  */
    'order/sd_allot' => 'api/manger.Order/sd_allot', /* 手动分配  */
    'pay/sd_allot' => 'api/manger.Pay/bindAlipay', /* 绑定支付宝  */
    'pay/apply' => 'api/manger.Pay/apply', /* 预下单  */
    'pay/qrcode' => 'api/manger.Pay/qrcode', /* 支付宝支付返回二维码  */
    'pay/checkorder' => 'api/manger.Pay/checkorder', /* 轮询支付结果支付  */
    'pay/index' => 'api/manger.Pay/index', /* 支付宝配置  */
    'pay/set' => 'api/manger.Pay/set', /* 支付宝配置  */
    'shlog/index' => 'api/manger.Shlog/index', /* 审核日志  */
    'sms/index' => 'api/manger.Smsscene/index', /* 短信配置信息  */
    'sms/add' => 'api/manger.Smsscene/add', /* 添加短信配置信息  */
    'sms/update' => 'api/manger.Smsscene/update', /* 修改短信配置信息  */
    'sms/del' => 'api/manger.Smsscene/del', /* 删除短信配置信息  */
    'store/index' => 'api/manger.Store/index', /*  商户信息  */
    'store/add' => 'api/manger.Store/add', /* 添加商户信息  */
    'store/update' => 'api/manger.Store/update', /* 修改商户信息  */
    'store/del' => 'api/manger.Store/del', /* 删除商户信息 */
    'store/statistics' => 'api/manger.Store/statistics', /* 商户统计  */
    'sysconfig/index' => 'api/manger.Sysconfig/index', /* APP 模板配置信息  */
    'sysconfig/add' => 'api/manger.Sysconfig/add', /* 添加APP 模板配置信息  */
    'sysconfig/update' => 'api/manger.Sysconfig/update', /* 修改APP 模板配置信息  */
    'sysconfig/del' => 'api/manger.Sysconfig/del', /* 删除APP 模板配置信息  */
    'user/send' => 'api/manger.User/send', /* 删除APP 模板配置信息  */
    'user/login' => 'api/manger.User/login', /* 删除APP 模板配置信息  */
    'user/info' => 'api/manger.User/info', /* 删除APP 模板配置信息  */
    'order/loginOut' => 'api/manger.User/loginOut', /* 登出  */
    'order/credit' => 'api/manger.Order/credit', /* 白骑士征信  传用户订单号（废弃）*/
    'order/phone' => 'api/manger.Order/phone', /* 征信手机号 传用户订单号 */
    'order/baseInfo' => 'api/manger.Order/baseInfo', /* 征信基本信息 传用户订单号 */
    'order/credit_user' => 'api/manger.Order/credit_user', /* 白骑士征信 传用户id （废弃）*/
    'order/phone_user' => 'api/manger.Order/phone_user', /* 征信手机号 传用户id */
    'order/baseInfo_user' => 'api/manger.Order/baseInfo_user', /* 征信基本信息 传用户id  */
    'order/repayDetail' => 'api/manger.Order/orderRepayDetail', /* 订单还款详情  */
    'order/userorderRepayDetail' => 'api/manger.Order/userorderRepayDetail', /* 订单还款详情  */
    'order/userorderlist' => 'api/manger.Order/userorderlist', /* 订单还款详情  */
    'pay/getPayInfo' => 'api/manger.Pay/getPayInfo', /* 订单还款详情  */


    //客户端
    'withdraw/trial' => 'api/client.Withdraw/trial', /*借款试算*/
    'auth/updatetelbook' => 'api/client.Auth/updateTelBook', /*更新通讯录*/
    'order/client_index' => 'api/client.Order/index', /*客户端历史订单*/
    'withdraw/draw' => 'api/client.Withdraw/draw', /*用户申请提现*/
    'withdraw/point' => 'api/client.Withdraw/point', /*用户提现计费点*/
    'repay/repayment' => 'api/client.Repay/repay', /*还款*/
    'repay/renewal' => 'api/client.Repay/renewal', /*续期*/
    'message/index' => 'api/client.Message/index', /*信息*/
    'message/read' => 'api/client.Message/read', /*已读*/
    'message/allread' => 'api/client.Message/allRead', /*全部表位已读*/
    'identifice/back' => 'api/client.Identifice/back', /*白骑士回调*/
    'identifice/databack' => 'api/client.Identifice/databack', /*白骑士回调*/
    'identifice/credit' => 'api/client.Identifice/credit', /*获取白骑士征信*/
    'identifice/credit_user' => 'api/client.Identifice/credit_user', /*获取白骑士征信*/
    'identifice/doutou' => 'api/client.Identifice/doutou', /*多头负债*/
    'fypay/bindMsg' => 'api/client.Fuypay/bindMsg',//发送短信验证码接口
    'fypay/bindCommit' => 'api/client.Fuypay/bindCommit',//协议绑定
    'fypay/unbind' => 'api/client.Fuypay/unbind',//协议解绑
    'fypay/backurl' => 'api/client.Fuypay/backurl',//富友绑卡回调
    'fypay/notify' => 'api/client.Fuypay/notify_success',//富友单笔支付成功回调
    'fypay/refund' => 'api/client.Fuypay/notify_fail',//富友单笔支付退款回调
    'user/setnickname' => 'api/client.UserInfo/setNickName',//修改昵称
    'user/bank' => 'api/client.UserInfo/getBankInfo',//银行卡信息
    'syt/notifyurl' => 'api/client.Sytapi/notifyurl',//收银台回调
    'identifice/device' => 'api/client.Identifice/device', /*白骑士设备信息*/
    'user/costcontrol' => 'api/client.UserInfo/costControl', /* 用户成功控制  */
    'control/anon/creditreport.json' => 'api/client.Identifice/reprot', /*风险报告*/

    'identifice/sixcontacts' => 'api/client.Identifice/sixcontacts',

    'auth/getauthlist' => 'api/client.Auth/getAuthList',

    'auth/saveData' => 'api/client.Auth/saveData',

    'test/index' => 'api/client.Test/index', /*测试接口*/
    'test/creditreport' => 'api/client.Test/creditReport', /*测试风险监测报告*/

    'common/getscrollmsg' => 'api/client.Message/getScrollMsg',
    'common/getbanners' => 'api/client.Message/getBanners',

    'auth/savePic' => 'api/client.Auth/savePic',
    'bank/getbankname' => 'api/client.Bank/getbankname',

    'auth/updatetelbookstate' => 'api/client.Auth/updatetelbookstate',
    'auth/zxyauth' => 'api/manger.User/authzxy',


    "auth/getauthstatus" => 'api/client.Auth/getAuthState',


    //落地页
    'register/index' => 'index/register/index',
    'register/send' => 'index/register/send',
    'register/download' => 'index/register/download',
    'sms/send' => 'admin/sms.business/send',


    'user/aboutus' => 'api/manger.User/getAboutUsInfo',



    'user/facematch' => 'api/client.UserInfo/facematch',



    'permit/index' => 'index/permit/index',

];

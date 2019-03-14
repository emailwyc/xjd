<?php

namespace app\api\controller\client;

use app\common\controller\Api;
use app\common\model\Area;
use app\common\model\Version;
use fast\Random;
use think\Config;
use think\Log;
use think\Db;
use fast\Http;

/**
 * 公共接口
 */
class Bank extends Api
{

    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    public function _initialize()
    {
        parent::_initialize();
    }

    //根据银行卡号获取银行名
    public function getbankname()
    {
        $params = $this->request->param();
        if (empty($params['bankid'])) {
            $this->error('参数错误：bankid');
        }
        $url = "https://ccdcapi.alipay.com/validateAndCacheCardInfo.json?_input_charset=utf-8&cardNo=" . $params["bankid"] . "&cardBinCheck=true";
        $rs = Http::get($url);
        //{"cardType":"DC","bank":"BOC","key":"6217856000016683087","messages":[],"validated":true,"stat":"ok"}
        $rs = json_decode($rs,true);
        $name = "";
        Log::write("测试".print_r($rs,true),"error");
        if($rs["stat"] == "ok" && !empty($rs['bank'])){
            $name = Db::name("banklist")->where(["id"=>$rs['bank']])->value("name");
        }
        $this->success("成功",["name"=>$name]);
    }

    //获取银行名称
    public function getbanknamelist(){



    }

    //获取银行logo
    private function getbanklogo($bankname){
        return "https://apimg.alipay.com/combo.png?d=cashier&t=".$bankname;
    }

}

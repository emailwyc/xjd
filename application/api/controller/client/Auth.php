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
class Auth extends Api
{

    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    public function _initialize()
    {
        parent::_initialize();
    }

    //更新通讯录信息
    public function updateTelBook()
    {
//        if ($this->auth->isLogin()){
//
//            $this->error("未登录");
//
//        }

        $uid = $this->request->param("uid", 0);
        if (!$uid) {
            $this->error("未登录");
        }
        $telbook = json_decode($this->request->post("telbook"), true);
        if ($telbook) {
            foreach ($telbook as $key => $value) {
                $name = $value['familyName'] . $value['givenName'];
                foreach ($value['phoneNumbers'] as $k => $v) {
                    $number = $v['number'];
                    if (!Db::name("user_telebook")->where(["uid" => $uid, "linkname" => $name, "phone" => $number])->find()) {
                        $data["uid"] = $uid;
                        $data["linkname"] = $name;
                        $data["phone"] = $number;
                        $data["callnum"] = 0;
                        $data["relation"] = 0;
                        $data["createtime"] = time();
                        $data["status"] = 1;
                        Db::name("user_telebook")->insertGetId($data);
                    }
                }
            }
            if (Db::name("user_authinfo")->where(["uid" => $uid, "code" => "txl"])->find()) {
                Db::name("user_authinfo")->where(["uid" => $uid, "code" => "txl"])->update(["status" => 2, "updatetime" => time()]);
            } else {
                $taldata["uid"] = $uid;
                $taldata["code"] = "txl";
                $taldata["createtime"] = time();
                $taldata["uid"] = $uid;
                $taldata["status"] = 2;
                Db::name("user_authinfo")->insertGetId($taldata);
            }
            $this->result("更新成功", "", 200);
        } else {
            $this->result("更新失败", "", 301);
        }

    }

    public function getAuthList()
    {
        $uid = $this->request->param("uid", 0);
        if (!$uid) {
            $this->error("未登录");
        }
        $where["a.status"] = 1;
        $where["a.code"] = array("in", ["jcxx", "yhk", "mno", "tb"]);
        $data = Db::name("user_authtype")->alias("a")
            ->field("a.code,a.name,a.jumpurl,a.icon,a.color,ifnull(b.status,0) as status")
            ->join("user_authinfo b", "b.code = a.code and b.uid =" . $uid, "left")
            ->where($where)
            ->order("id asc")
            ->select();
        //查询认证到第几步
        $step = "";
        if ($data) {
            foreach ($data as &$v) {
                $step = empty($step) && $v["status"] == 0 ? $v['code'] : $step;
                //$v["icon"] = $v["icon"] ? "http://47.104.69.18" . $v["icon"] : $v["icon"];
                $v["icon"] = $v["icon"] ? "http://" . $_SERVER['HTTP_HOST'] . $v["icon"] : $v["icon"];
            }
            $step = !$step ? "success" : $step;
        }
        $rs["step"] = $step;
        $rs["list"] = $data;
        $quota = Db::name("user")->where(["id" => $uid])->value("quota");
        //更新计费点
        if (empty($quota)) {
            $sys = Db::name('sys_config')->find();
            $sql = Db::name('sys_config')->getLastSql();
            Log::write(__FUNCTION__ . ': sql=' . $sql, 'error');
            Log::write(__FUNCTION__ . ': sys=' . print_r($sys, true), 'error');
            if (empty($sys)) {
                $this->error('配置异常');
            }
            $sjqb = $sys['sjqb'];
            $sjdz = $sys['sjdz'];
            $xjzd = $sys['xjzd'];
            $count = Db::name('order')->where(array('uid' => $uid, 'status' => 9))->count();
            $quota = ($sjqb + $sjdz * $count) > $xjzd ? $xjzd : $sjqb + $sjdz * $count;
            Log::write(__FUNCTION__ . ': quota=' . $quota, 'error');
            $quota = round($quota, 2);
            Log::write(__FUNCTION__ . ': quota2=' . $quota, 'error');
            $re_up = Db::name('user')->where(array('id' => $uid))->update(array('quota' => $quota));
            if (!$re_up) {
                $this->error('更新用户额度失败');
            }
        }
        $rs["quota"] = $quota;
        $rs["cycle"] = 7;
        Log::write("数据" . print_r($rs, true), 'error');
        $this->success("查询成功", $rs);
    }

    public function saveData()
    {
        Log::write("result" . request()->param("uid", 0), "error");
        $uid = $this->request->param("uid", 0);
        if (!$uid) {
            $this->error("未登录");
        }
        $idname = $this->request->param("idname", "");

        $idaddress = $this->request->param("idaddress", "");
        $idnum = $this->request->param("idnum", "");
        if (!$idname || !$idnum) {
            $this->error("身份证正面未认证");
        }

        $idnation = $this->request->param("idnation", "");
        $idsex = $this->request->param("idsex", "");
        $idbirthday = $this->request->param("idbirthday", "");
        $idexpiryDate = $this->request->param("idexpiryDate", "");
        $idsignDate = $this->request->param("idsignDate", "");
//        if (!$idexpiryDate || !$idsignDate) {
//            $this->error("身份证反面未认证");
//        }

        $idsignUnit = $this->request->param("idsignUnit", "");
        if (!$idsignUnit) {
            $this->error("身份证反面未认证");
        }

        $education = $this->request->param("education", "");
//        if (!$education) {
//            $this->error("未选择学历");
//        }
        $marriage = $this->request->param("marriage", "");
//        if (!$marriage) {
//            $this->error("未选择婚姻状况");
//        }
        $address = $this->request->param("address", "");
        $addressinfo = $this->request->param("addressinfo", "");
//        if (!$address || !$addressinfo) {
//            $this->error("居住地址错误");
//        }
        $workaddress = $this->request->param("workaddress", "");
        $workaddressinfo = $this->request->param("workaddressinfo", "");
//        if (!$workaddress || !$workaddressinfo) {
//            $this->error("公司地址错误");
//        }
        $weixin = $this->request->param("weixin", "");
        $qq = $this->request->param("qq", "");
        $company = $this->request->param("company", "");
//        if (!$company) {
//            $this->error("公司错误");
//        }


        $longitude = $this->request->param("longitude", "");
        $latitude = $this->request->param("latitude", "");
        $deviceInfo = $this->request->param("deviceInfo", "");

        $faceimg = $this->request->param("faceimg", ""); //request()->file('faceimg');
        $frontfile = request()->file('frontfile');
        $backfile = request()->file('backfile');
        $path = "/var/www/html/xjd_server/public/uploads/" . $uid . "/";
        if (!is_dir($path)) {
            mkdir($path, 0777);
        }
//        $rs1 = $frontfile->move($path, "front.png");
//        $rs2 = $backfile->move($path, "back.png");
//        $rs3 = $backfile->move($path, "faceimg.png");
        $frontfile = $this->request->param("frontfile", ""); //request()->file('faceimg');
        $backfile = $this->request->param("backfile", ""); //request()->file('faceimg');
        $rs1 = $this->base64_image_content($frontfile, $path . "front.png");
        $rs2 = $this->base64_image_content($backfile, $path . "back.png");
        $rs3 = $this->base64_image_content($faceimg, $path . "faceimg.png");

        if ($rs1 && $rs2 && $rs3) {
            //获取百度token
            $access_token = json_decode($this->getBaiduToken(), true);
            if (empty($access_token["access_token"])) {
                $this->error("人脸对比失败");
            }
            $base64img1 = $this->base64EncodeImage($path . "front.png");
            $base64img2 = $this->base64EncodeImage($path . "faceimg.png");
            $baiducom_rs = json_decode($this->getBaiduImgCom($access_token["access_token"], $base64img1, $base64img2), true);

            $facescore = 0;

            if ($baiducom_rs["error_code"] == 0 && !empty($baiducom_rs["result"])) {
                $facescore = !empty($baiducom_rs["result"]["score"]) ? $baiducom_rs["result"]["score"] : 0;
            }
            $facematch = $facescore >= 80 ? 1 : 0;


            //更新生日数据
            $birthday = date("Y-m-d", strtotime($idbirthday));

            $rs = Db::name("user")->where(["id" => $uid])->update(["birthday" => $birthday]);


            //保存数据
            $personinfo = array(
                "realname" => $idname,
                "cardid" => $idnum,
                "idfrontpic" => "/" . $uid . "/front.png",
                "idbackpic" => "/" . $uid . "/back.png",
                "facematch" => 1,
                "card_address" => $idaddress,
                "education" => $education,
                "marriage" => $marriage,
                "longitude" => $longitude,
                "latitude" => $latitude,
                "phone" => $deviceInfo,
                "facematch" => $facematch,
                "facescore" => $facescore,
                "faceimg" => "/" . $uid . "/faceimg.png"
            );

            Log::write("数据." . print_r($personinfo, true), "error");

            $this->updatePersonalInfo($uid, $personinfo);

            //身份证详情
            $cardinfo = array(
                "address" => $idaddress,
                "nation" => $idnation,
                "sex" => $idsex,
                "birthday" => $idbirthday,
                "expiryDate" => $idexpiryDate,
                "signDate" => $idsignDate,
                "signUnit" => $idsignUnit
            );
            $this->updateIDCardInfo($uid, $cardinfo);
            //地址详情
//            $addressdata = explode("/", $address);
//            $address = array(
//                "province" => $addressdata[0],
//                "city" => $addressdata[1],
//                "area" => $addressdata[2],
//                "address" => $addressinfo
//            );
//
//            $this->updateAddressInfo($uid, $address);

            //工作详情
//            $workaddressdata = explode("/", $workaddress);
//            $workinfo = array(
//                "company" => $company,
//                "province" => $workaddressdata[0],
//                "city" => $workaddressdata[1],
//                "area" => $workaddressdata[2],
//                "address" => $workaddressinfo
//            );
//            $this->updateWorkInfo($uid, $workinfo);

            if (Db::name("user_authinfo")->where(["uid" => $uid, "code" => "jcxx"])->find()) {
                Db::name("user_authinfo")->where(["uid" => $uid, "code" => "jcxx"])->update(["status" => 2, "updatetime" => time()]);
            } else {
                $data["uid"] = $uid;
                $data["code"] = "jcxx";
                $data["createtime"] = time();
                $data["uid"] = $uid;
                $data["status"] = 2;
                Db::name("user_authinfo")->insertGetId($data);
            }
            $this->success("认证成功");
        } else {
            $this->error("身份证保存失败");
        }
    }


    //更新个人信息
    private function updatePersonalInfo($uid, $params)
    {
        $rs = 0;
        if (Db::name("user_info")->where(["uid" => $uid])->find()) {
            $params["updatetime"] = time();
            $rs = Db::name("user_info")->where(["uid" => $uid])->update($params);
        } else {
            $params["uid"] = $uid;
            $params["createtime"] = time();
            $rs = Db::name("user_info")->insertGetId($params);
        }
        return $rs;
    }

    //更新身份证信息
    private function updateIDCardInfo($uid, $params)
    {
        $rs = 0;
        if (Db::name("user_cardinfo")->where(["uid" => $uid])->find()) {
            $params["updatetime"] = time();
            $rs = Db::name("user_cardinfo")->where(["uid" => $uid])->update($params);
        } else {
            $params["uid"] = $uid;
            $params["createtime"] = time();
            $rs = Db::name("user_cardinfo")->insertGetId($params);
        }
        return $rs;
    }

    //更新住址
    private function updateAddressInfo($uid, $params)
    {
        $rs = 0;
        if (Db::name("user_address")->where(["uid" => $uid])->find()) {
            $params["updatetime"] = time();
            $rs = Db::name("user_address")->where(["uid" => $uid])->update($params);
        } else {
            $params["uid"] = $uid;
            $params["createtime"] = time();
            $rs = Db::name("user_address")->insertGetId($params);
        }
        return $rs;
    }

    //更新工作地址
    private function updateWorkInfo($uid, $params)
    {
        $rs = 0;
        if (Db::name("user_work")->where(["uid" => $uid])->find()) {
            $params["updatetime"] = time();
            $rs = Db::name("user_work")->where(["uid" => $uid])->update($params);
        } else {
            $params["uid"] = $uid;
            $params["createtime"] = time();
            $rs = Db::name("user_work")->insertGetId($params);
        }
        return $rs;
    }

    public function savePic()
    {
        $file = request()->file('file');
        $file->move("/var/www/html/xjd_server/public/uploads/", "front.png");
        Log::write("返回值" . print_r($file, true), "error");
        Log::write("返回值" . print_r($this->request->param(), true), "error");
        $this->success("成功");
    }

    public function updatetelbookstate()
    {
        $uid = $this->request->param("uid", 0);
        if (!$uid) {
            $this->error("未登录");
        }
        if (Db::name("user_authinfo")->where(["uid" => $uid, "code" => "txl"])->find()) {
            Db::name("user_authinfo")->where(["uid" => $uid, "code" => "txl"])->update(["status" => 1, "updatetime" => time()]);
        } else {
            $data["uid"] = $uid;
            $data["code"] = "txl";
            $data["createtime"] = time();
            $data["status"] = 1;
            Db::name("user_authinfo")->insertGetId($data);
        }
        $this->result("更新成功", "", 200);
    }


    //查看认证状态
    public function getAuthState()
    {
        $uid = $this->request->param("uid", 0);
        if (!$uid) {
            $this->error("未登录");
        }
        $code = $this->request->param("code", "");

        $status = Db::name("user_authinfo")->where(["uid" => $uid, "code" => $code])->value("status");


        $this->result("查看成功", $status, 200);
    }

    private function getBaiduToken()
    {
        $url = 'https://aip.baidubce.com/oauth/2.0/token';
        $post_data['grant_type'] = 'client_credentials';
        $post_data['client_id'] = 'FN525T9gkGPitk4BuB6GGl0c';
        $post_data['client_secret'] = '30OGD4TapfGHSBAOVqZ0YRLk3eGB1aPM';
        $o = "";
        foreach ($post_data as $k => $v) {
            $o .= "$k=" . urlencode($v) . "&";
        }
        $post_data = substr($o, 0, -1);

        $res = Http::post($url, $post_data);

        return $res;
    }

    private function getBaiduImgCom($access_token, $img1, $img2)
    {
        $url = 'https://aip.baidubce.com/rest/2.0/face/v3/match?access_token=' . $access_token;
        $post_data = [];
        $post_data[0] = array(
            "image" => $img1,
            "image_type" => "BASE64",
            "face_type" => "CERT",
            "quality_control" => "LOW",
            "liveness_control" => "NONE"
        );
        $post_data[1] = array(
            "image" => $img2,
            "image_type" => "BASE64",
            "face_type" => "LIVE",
            "quality_control" => "LOW",
            "liveness_control" => "NONE"
        );
        $res = $this->request_post($url, json_encode($post_data));
        return $res;
    }

    /**
     * 发起http post请求(REST API), 并获取REST请求的结果
     * @param string $url
     * @param string $param
     * @return - http response body if succeeds, else false.
     */
    private function request_post($url = '', $param = '')
    {
        if (empty($url) || empty($param)) {
            return false;
        }

        $postUrl = $url;
        $curlPost = $param;
        // 初始化curl
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $postUrl);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        // 要求结果为字符串且输出到屏幕上
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        // post提交方式
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $curlPost);
        // 运行curl
        $data = curl_exec($curl);
        curl_close($curl);

        return $data;
    }

    private function base64EncodeImage($image_file)
    {
        $base64_image = '';
        $image_info = getimagesize($image_file);
        $image_data = fread(fopen($image_file, 'r'), filesize($image_file));

        return chunk_split(base64_encode($image_data));
        $base64_image = 'data:' . $image_info['mime'] . ';base64,' . chunk_split(base64_encode($image_data));
        return $base64_image;
    }

    /**
     * [将Base64图片转换为本地图片并保存]
     * @param  [Base64] $base64_image_content [要保存的Base64]
     * @param  [目录] $path [要保存的路径]
     */
    private function base64_image_content($base64_image_content, $file)
    {
        Log::write("写入文件-123132---------------------", 'error');
        //匹配出图片的格式
        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64_image_content, $result)) {
            Log::write("写入文件----------------------", 'error');
            $new_file = $file;
            if (file_put_contents($new_file, base64_decode(str_replace($result[1], '', $base64_image_content)))) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

}

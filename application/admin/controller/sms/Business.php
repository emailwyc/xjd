<?php

namespace app\admin\controller\sms;

use app\common\controller\Backend;

/**
 * 会员管理
 *
 * @icon fa fa-user
 */

use think\Db;
use think\Session;

class Business extends Backend
{

    protected $relationSearch = true;

    /**
     * @var \app\admin\model\User
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\sms\Business;
    }
    
    public function index(){
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            $req = $this->request->request();
            //list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $sort = $req['sort'];
            $order = $req['order'];
            $offset = $req['offset'];
            $limit = $req['limit'];
            $total = Db::name("business_sms_rules")->count();
            $list = Db::name("business_sms_rules")
                ->field("id,business_type,business_node,start_date,end_date,is_use,desc")
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }
    
    public function edit($ids=''){
        if ($this->request->isPost()){
            $row = $this->model->get(['id' => $ids]);
            if(!$row){
                $this->error('该业务节点不存在');
            }
            $params = $this->request->post("row/a");
            if ($params)
            {
                $params['start_date'] = $params['end_date'];
                $params['end_date'] = $params['end_date'];
                $params['is_use'] = $params['is_use'];
                $result = $row->save($params);
                if ($result === false)
                {
                    $this->error($row->getError());
                }
                $this->success();
            }
            $this->error();
        }
        $row = Db::name("business_sms_rules")->where('id','=',$ids)->find();
        $this->view->assign('row',$row);
        return $this->view->fetch();
    }

    public function send(){
       $info =  Db::name("business_sms_rules")->where('is_use','=',1)->select();
        foreach($info as $k=>$v){
            $business_sql = strtr($v['business_sql'], array("@start_date" =>$v['start_date'], "@end_date" => $v['end_date']));
            $result = Db::query($business_sql);
            $mobile = implode(',',array_column($result,'mobile'));
            if($v['business_type'] == 1){//营销
                $this->marketing($mobile,$v['programm_id']);
            }else{//催收
                $this->collection($mobile,$v['programm_id']);
            }
        }
    }

    //发送短信（催收）
    private function collection($mobile,$programmId){
        $url = "";
        $params = array(
            'appId'=>'d0c6495175c1406084119441a188a3',
            'phones'=>$mobile,
            'programmId'=>$programmId
        );
    }

    //发送短信(营销)
    private function marketing($mobile, $type) {
        if($type == 1){
            $msg = "尊敬的用户您好，您只差一步就可活动2000-10000元周转资金，点击http://www.baidu.com可继续填写资料，祝您生活愉快！";
        }else{
            $msg = "尊敬的用户您好，您在我司被定为优质客户，现对您进行额度提升，提现秒到账，祝您生活愉快！";
        }
        $postArr = array(
            'action'   => 'send',
            'userid'   => '29',
            'account'  => 'GXZN-002',
            'password' => '1qaz@WSX',
            'mobile'   => $mobile,
            'content'  => $msg,
            'sendTime' => '',
            'extno'    => ''
        );
        $url = 'http://39.98.72.106:8088/sms.aspx';
        $result = $this->curlPost($url, $postArr);
    }

    //post请求
    private function curlPost($url, $postFields) {
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
        return $result;
    }
}

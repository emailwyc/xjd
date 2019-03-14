<?php
/**
 * Created by PhpStorm.
 * User: ggx
 * Date: 2019/3/8
 * Time: 14:44
 */
namespace app\admin\controller\crontab;

use app\common\controller\Backend;
use think\Controller;
use think\Db;
use think\Log;
use think\Exception;



class Pastdueloans extends Controller
{

    //将逾期没有付款的项目改为逾期
    public function PastDueLoans(){
        set_time_limit(0);
        $data =  Db::name('order')->field('id,uid,status,overday,cycle,fk_time_detail')->whereIn('status',[8,10])->select();

        $time = strtotime(date('Y-m-d',time()));


        foreach ($data as $k=>$v){
            //贷款下发时期到今日时间间隔(天数)
            $fk_time = ($time - strtotime(date('Y-m-d',$v['fk_time_detail'])))/86400;
            //逾期天数
            $overday = $fk_time - $v['cycle'];
            //修改逾期状态

            if ($v['status']==10){
                //更新逾期天数
                $type = '';
                try{
                    $type = Db::name('order')->where(['id'=>$v['id']])->update(['overday'=>$overday]);

                }catch (Exception $e){
                    Log::write(__FUNCTION__ . '用户:'.$v['uid'] . '逾期状态更新失败');
                }
                if ($type){
                    continue;
                }
            }
            if ($fk_time > $v['overday']){

                try{
                    Db::startTrans();
                    $a = Db::name('order')->where(['id'=>$v['id']])->update(['status'=>10,'overday'=>$overday]);
                    $b = Db::name('order_repay')->where(['order_id'=>$v['id']])->update(['status'=>3]);
                    if ($a && $b){
                        Db::commit();
                    }
                }catch (Exception $e){
                    Db::rollback();
                    Log::write(__FUNCTION__ . '用户:'.$v['uid'] . '逾期状态更新失败');
                }
            }

        }

        Log::write(__FUNCTION__ . '日期:'.$time.',逾期更新完成');

        return 1;

    }
}
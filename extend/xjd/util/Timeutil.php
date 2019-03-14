<?php
/**
 * Created by PhpStorm.
 * User: Ksh
 * Date: 2018/12/15
 * Time: 12:11
 */

namespace xjd\util;
class Timeutil {
    /**
     * 获取今天的开始时间和结束时间
     *
     * @return array
     */
    public function getTodayTime() {
        $beginToday = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
        $endToday = mktime(0, 0, 0, date('m'), date('d') + 1, date('Y')) - 1;

        return array($beginToday, $endToday);
    }

    public static function todayTime() {
        $beginToday = mktime(0, 0, 0, date('m'), date('d'), date('Y'));

        return $beginToday;
    }

    public static function interval($before, $end) {
        $days = round(($end - $before) / 3600 / 24);

        return $days;
    }

    public static function getIp($type = 0, $adv = false) {
        $type = $type ? 1 : 0;
        $ip = null;
        if ($adv) {
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
                $pos = array_search('unknown', $arr);
                if (false !== $pos) {
                    unset($arr[$pos]);
                }
                $ip = trim(current($arr));
            } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
                $ip = $_SERVER['HTTP_CLIENT_IP'];
            } elseif (isset($_SERVER['REMOTE_ADDR'])) {
                $ip = $_SERVER['REMOTE_ADDR'];
            }
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        // IP地址合法验证
        $long = sprintf("%u", ip2long($ip));
        $ip = $long
            ? array(
                $ip,
                $long
            )
            : array(
                '0.0.0.0',
                0
            );

        return $ip[$type];
    }

    public static function msectime() {
        list($msec, $sec) = explode(' ', microtime());
        $msectime = (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);

        return $msectime;
    }

    /**
     * 获取当前时间到毫秒
     * @return float
     */
    public static function getTime() {
        list($msec, $sec) = explode(' ', microtime());
        $msectime = (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);

        return $msectime;
    }


    public static function todayTime_1() {
        $beginToday = mktime(23, 59, 59, date('m'), date('d'), date('Y'));

        return $beginToday;
    }
}
<?php
/**
 * Created by PhpStorm.
 * User: Ksh
 * Date: 2019/1/24
 * Time: 23:49
 */

namespace xjd\util;
class Util {
    public static function getVal($data, $val, $defalt = '') {
        if (empty($val) || empty($data) || !isset($data[$val])) {
            return $defalt;
        }

        return $data[$val];
    }
}
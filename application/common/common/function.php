<?php
/**
 * Created by PhpStorm.
 * User: Ksh
 * Date: 2018/7/4
 * Time: 16:56
 */

function getVal($data, $val, $defalt = '') {
    if (empty($val) || empty($data) || !isset($data[$val])) {
        return $defalt;
    }

    return $data[$val];
}
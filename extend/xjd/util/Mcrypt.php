<?php
/**
 * Created by PhpStorm.
 * User: Ksh
 * Date: 2019/1/5
 * Time: 12:26
 */

namespace xjd\util;
/**
 * DES加密解密
 */
class Mcrypt {
    public function __construct() {
    }

    function getSKey($msg) {
        if (!$msg) {
            die('请输入参数值');
        }
        /* 打开加密算法和模式 */
        $td = mcrypt_module_open('des', '', 'ecb', '');
        /* 创建初始向量，并且检测密钥长度。 Windows 平台请使用 MCRYPT_RAND。 */
       // $iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_DEV_RANDOM);
        $iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
        $ks = mcrypt_enc_get_key_size($td);
        /* 创建密钥 */
        $key = substr(md5($msg), 0, $ks);
        /* 并且关闭模块 */
        mcrypt_module_close($td);

        return $key;
    }

    /**
     *
     * 加密函数
     * 算法：des
     * 加密模式：ecb
     * 补齐方法：PKCS5
     *
     * @param unknown_type $input
     */
    public function encryptDesEcbPKCS5($input, $key) {
        $size = mcrypt_get_block_size('des', 'ecb');
        $input = $this->pkcs5_pad($input, $size);
        $td = mcrypt_module_open('des', '', 'ecb', '');
        //获取密钥的最大长度
        $ks = mcrypt_enc_get_key_size($td);
        $key = substr($key, 0, $ks);
        //加密向量值
        $iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
        //$iv =0;
        $tmp = mcrypt_generic_init($td, $key, $iv);
        $data = mcrypt_generic($td, $input);
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);

        return $data;
    }

    /**
     * 解密函数
     * 算法：des
     * 加密模式：ecb
     * 补齐方法：PKCS5
     *
     * @param unknown_type $input
     */
    public function decryptDesEcbPKCS5($input, $key) {
        $size = mcrypt_get_block_size('des', 'ecb');
        $td = mcrypt_module_open('des', '', 'ecb', '');
        /*获取密钥的最大长度*/
        $ks = mcrypt_enc_get_key_size($td);
        $key = substr($key, 0, $ks);
        $iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
        mcrypt_generic_init($td, $key, $iv);
        $data = mdecrypt_generic($td, $input);
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
        $data = $this->pkcs5_unpad($data, $size);

        return $data;
    }

    private function pkcs5_pad($text, $blocksize) {
        $pad = $blocksize - (strlen($text) % $blocksize);

        return $text.str_repeat(chr($pad), $pad);
    }

    private function pkcs5_unpad($text) {
        $pad = ord($text{strlen($text) - 1});
        if ($pad > strlen($text)) {
            return false;
        }
        if (strspn($text, chr($pad), strlen($text) - $pad) != $pad) {
            return false;
        }

        return substr($text, 0, -1 * $pad);
    }
}
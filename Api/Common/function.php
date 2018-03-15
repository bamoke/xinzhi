<?php
/**
 * Created by PhpStorm.
 * User: joy.wangxiangyin
 */

/***创建验证码***/
function createValidateCode(){
    return mt_rand(100000,999999);
}

function wxParse($str){

}

/**
 * 创建随机数
 */

    function createRandom($length)
    {
        $str = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';//62个字符
        $strlen = 64;
        while ($length > $strlen) {
            $str .= $str;
            $strlen += 64;
        }
        $str = str_shuffle($str);
        return substr($str, 0, $length);
    }

        /** 
     * 微信支付数据签名
     */
    function wxSign($param,$mch_key)
    {

        ksort($param);
        $signString = '';
        foreach ($param as $k => $v) {
            if ($v != '') {
                $signString .= $k . "=" . $v . "&";
            }
        }
        $signString .= "key=" . $mch_key;
        $signString = strtoupper(md5($signString));
        return $signString;
    }
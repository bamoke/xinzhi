<?php
/**
 * Created by PhpStorm.
 * User: wetz1
 * Date: 2018/1/16
 * Time: 0:40
 */

error_reporting(0);
function sendHttpRequest($url, $method = "", $data = '', $header = '')
{
    if (function_exists("curl_init")) {
        $opt = array(
            CURLOPT_URL => $url,
            CURLOPT_HEADER => 0,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        );
        if ($method == 'post' || $method == 2) {
            $opt[CURLOPT_POST] = 1;
        }
        if (($method == "post" || $method == 2) && $data != '') {
            $opt[CURLOPT_POSTFIELDS] = $data;
        }
        if ($header != "") {
            $opt[CURLOPT_HTTPHEADER] = $header;
        }
        $ch = curl_init();
        curl_setopt_array($ch, $opt);
        $resp = curl_exec($ch);
        curl_close($ch);
        if(!$resp){
            trigger_error(curl_error($ch));
        }
//        var_dump($resp);
        return $resp;
    }

}


$response = file_get_contents('php://input');
/*$my_file = 'log.txt';
$handle = fopen($my_file, 'a+') or die('Cannot open file: '.$my_file);
fwrite($handle, $_SERVER['HTTP_REFERER']);
fclose($handle);*/
$url='http://localhost/api.php/Wxpay/index';
$payResult = sendHttpRequest($url,'post',$response);

if($payResult['status']){
    // 返回给微信的响应

    echo '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';

}






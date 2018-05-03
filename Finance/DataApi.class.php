<?php
/**
 * Created by PhpStorm.
 * User: joy.wangxiangyin
 * Date: 2018/4/28
 * Time: 17:14
 */
session_start();
class Http {
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
            return $resp;
        }else {
            var_dump("noexists curl");
        }

    }
}

/*
 * 云表API接口
 * 初始通过$_SESSION['apitoken']判断是否登陆
 * 通过type实例化两个对象，type为out,logout实例；单独针对logout方法
 * */

class DataApi
{
    protected $apiUrl;
    protected $appKey = 'fff4bc3f-8e0c-41bd-b00d-f1a6e9c46de4';
    protected $appName = 'd599c57a-469b-4f59-b63c-c3d2f4688169';
    protected  $token;
    protected $serverNum;
    public function __construct($serverNum){
        $this->apiUrl = "http://www.iyunbiao.cn/".$serverNum;
        if(isset($_SESSION['apitoken'])){
            $this->token = $_SESSION['apitoken'];
        }else {
            $this->token = $this->login();
        }

    }

    public function test(){
        var_dump($_SESSION);
    }
    /*
     * 用户登录
     * 登陆成功保存session apitoken;
     * */
    public function login()
    {
        if(isset($_SESSION['apitoken'])) return $_SESSION['apitoken'];
        $headerParam = $this->createHeader();
        $url = $this->apiUrl.'/openapi/1.0/login';
        $data = array("loginJson"=>'{"account":"wechatadmin","password":"'.strtoupper(MD5("we9876***")).'"}');
        $curHttp = new Http();
        $resp = json_decode($curHttp->sendHttpRequest($url,"post",$data,$headerParam));
        if(isset($resp->token)) {
            $_SESSION['apitoken'] = $resp->token;
            return $_SESSION['apitoken'];
        }else {
            return "";
        }
    }

    /*
     * 用户退出登录
     * 检测session是否存在，防止重复退出发送接口请求
     * */
    public function logout(){
        if(isset($_SESSION['apitoken'])){
            $url = $this->apiUrl.'/openapi/1.0/logout';
            $headerParam = $this->createHeader($this->token);
            $curHttp = new Http;
            $resp = json_decode($curHttp->sendHttpRequest($url,"get","",$headerParam));

            //----///
            var_dump($resp);
            unset($_SESSION['apitoken']);
        }
    }

    /**
     * 获取主表内容
     * @param str 模板名称
    */
    public function getMainData($tplname,$postData=null){

    }


    /*
     * 生成header parameter
     * @param string token,登陆时token默认为空，其他请求传入token值
     * @return array
     * */
    protected function createHeader($token=''){
        $timestamp = $this->getMicroTime();
        $sign = $this->setSign($timestamp);
        $header = array(
            "x-eversheet-request-sign"=>$sign.",".$timestamp,
            "x-eversheet-application-name"=>$this->appName,
            "x-eversheet-session-token"=>$token
        );
        $headerArr = array();
        foreach ($header as $k=>$v){
            $headerArr[] = $k.":".$v;
        }
        return $headerArr;
    }

    /**获取毫秒时间戳**/
    protected function getMicroTime(){
        $timeArr = explode(" ",microtime());
        $m = intval($timeArr[0] * 1000);
        return $timeArr[1].$m;
    }
    /*
     * 生产签名
     * @param int 毫秒时间戳
     * */
    protected function setSign($timestamp){
        $sign = strtoupper(md5($timestamp.$this->appKey));
        return $sign;
    }
}


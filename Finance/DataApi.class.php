<?php
/**
 * Created by PhpStorm.
 * User: joy.wangxiangyin
 * Date: 2016/8/4
 * Time: 17:14
 */

namespace Org\Api;

/*
 * 云表API接口
 * 初始通过$_SESSION['apitoken']判断是否登陆
 * 通过type实例化两个对象，type为out,logout实例；单独针对logout方法
 * */

class DataApi
{
    protected $appKey = 'fff4bc3f-8e0c-41bd-b00d-f1a6e9c46de4';
    // protected $appKey = 'd599c57a-469b-4f59-b63c-c3d2f4688169';
    protected $appName = 'd599c57a-469b-4f59-b63c-c3d2f4688169';
    protected  $token;
    public function __construct($type='in'){
        if($type != "in") return;
        if(isset($_SESSION['apitoken'])){
            $this->token = $_SESSION['apitoken'];
        }else {
            $this->token = $this->login();
        }

    }

    /*
     * 用户登录
     * 登陆成功保存session apitoken;
     * */
    public function login()
    {
        if(isset($_SESSION['apitoken'])) return $_SESSION['apitoken'];
        $headerParam = $this->createHeader();
        $url = 'http://www.iyunbiao.cn/18005/openapi/1.0/login';
        // $url = 'https://s10.iyunbiao.cn/openapi/1.0/login';
        $data = array("loginJson"=>'{"account":"wechatadmin","password":"we9876***"}');
        $curHttp = new \Org\Net\Http();
        $resp = json_decode($curHttp->sendHttpRequest($url,"post",$data,$headerParam));
        var_dump($resp);
        if($resp->error == 0) {
            $loginInfo = $resp->info;
            $_SESSION['apitoken'] = $loginInfo->token;
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
            $url = 'https://s10.iyunbiao.cn/openapi/1.0/logout';
            $headerParam = $this->createHeader($this->token);
            $curHttp = new \Org\Net\Http();
            $resp = json_decode($curHttp->sendHttpRequest($url,"get","",$headerParam));
            if($resp->error == 0){
                unset($_SESSION['apitoken']);
                $backdata=array("error"=>0);
            }else {
                $backdata=array("error"=>1,"msg"=>"错误");
            }
        }else {
            $backdata=array("error"=>1,"msg"=>"用户已退出登录");
        }
        return json_encode($backdata);
    }

    /**
     * 获取主表内容
     * @param str 模板名称
    */
    public function getMainData($tplname,$postData=null){
        $url = 'https://s10.iyunbiao.cn/openapi/1.0/'.urlencode($tplname);
        $headerParam = $this->createHeader($this->token);
        $curHttp = new \Org\Net\Http();
        if(!is_null($postData)){
            $resp = json_decode($curHttp->sendHttpRequest($url,"post",$postData,$headerParam));
        }else {
            $resp = json_decode($curHttp->sendHttpRequest($url,"get","",$headerParam));
        }
        if($resp->error == 0){
            return $resp->info->results;
        }
        return null;
    }

    public function ssa(){
        $data=array(
            "formJson"=>'{"pageInfo": {
                            "isUseRowIndex": false,
                            "pageCount": 1,
                            "pageIndex": 0,
                            "pageSize": 1,
                            "rowIndex": 0,
                            "total":1
                            },
                            "filter": [
                            {"expressionList": [{"operator": "$e","isAnd": true,"value": "珠海方盈电器销售有限公司"}],"filterField": "企业全称"}
                            ]
                            }'
        );

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
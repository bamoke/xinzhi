<?php
/*
 * @Author: joy.wangxiangyin 
 * @Date: 2018-08-27 13:59:40 
 * @Last Modified by: joy.wangxiangyin
 * @Last Modified time: 2018-10-06 11:51:58
 * 200 success
 * 10* access error 01 invalid request ;02 params error
 * 11*  login error
 * 12*  weixin error
 * 1300*  business logic error
 * 13001  params error
 */

namespace Api\Common\Controller;
use Think\Controller;
class AuthController extends Controller
{
    public $uid;
    public function _initialize(){
        $this->_checklogin();
        $this->uid = $this->fetchUid();
    }

    protected function _checklogin(){
        if(empty($_SERVER["HTTP_X_ACCESS_TOKEN"])) {
            $backData = array(
                "code"  => 11001,
                "msg"   => "请登录后再操作"
            );
            $this->ajaxReturn($backData);
        }
        $sessionModel = M("Mysession");
        $where = array(
            "token" =>$_SERVER["HTTP_X_ACCESS_TOKEN"]
        );
        $res = $sessionModel->where($where)->find();
        if(!$res) {
            $backData = array(
                "code"  => 11002,
                "msg"   => "登陆态已过期，请重新登录"
            );
            $this->ajaxReturn($backData);          
        }
        
    }

    protected function fetchUid (){
        $token = $_SERVER["HTTP_X_ACCESS_TOKEN"];
        $sessionResult = M("Mysession")->field("uid")->where(array("token"=>$token))->find();
        return intval($sessionResult["uid"]);
    }
}
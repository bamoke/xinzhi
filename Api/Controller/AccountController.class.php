<?php
namespace Api\Controller;
use Think\Controller;
class AccountController extends Controller {

    protected function createSessionId(){
        $session_id=`head -n 80 /dev/urandom | tr -dc A-Za-z0-9 | head -c 64`;
        return $session_id;
    }

    function test (){
        echo "s";
    }
    /**
     * login
     */
    public function login($code){
/*        if(empty($_POST['code'])) {
            $backData = array(
                "errorCode" =>10001,
                "errorMsg"  =>'code参数错误',
            );
            return $this->ajaxReturn($backData);
        }*/
        // 1. 登录，
//        $code = I('post.code');
        $Http = new \Org\Net\Http();
        $url = 'https://api.weixin.qq.com/sns/jscode2session?appid='.APP_ID.'&secret='.APP_KEY.'&js_code='.$code.'&grant_type=authorization_code';
        $result = json_decode($Http->sendHttpRequest($url),true);
        if(isset($result['openid'])){
            $openid = $result['openid'];
            $sessionkey = $result['session_key'];
            $sessionId = $this->createSessionId();

            //1.2 session manage
            $sessionModel = M("Mysession");
            $sessionInfo = $sessionModel->where(array("openid"=>$openid))->find();
            if($sessionInfo){
                $updateData = array(
                    "sessionid" =>$sessionId,
                    "sessionkey" =>$sessionkey
                );
                $updateSession = $sessionModel->where(array("openid"=>$openid))->save($updateData);
                if($updateSession !== false){
                    $backData = array(
                        "errorCode" =>10000,
                        "errorMsg"  =>'ok',
                        "sessionid" =>$sessionId,
                        "info"      =>$updateSession
                    );
                }else {
                    $backData = array(
                        "errorCode" =>10002,
                        "errorMsg"  =>'登陆态更新失败'
                    );
                }

            }else {
                $model = M();
                $model->startTrans();
                // 1.3 创建用户
                $memberModel = M("Member");
                $insertMemberData = array(
                    "openid"    =>$openid,
                    "reg_time"  =>time()
                );
                $insertMember = $memberModel->add($insertMemberData);

                //1.4 创建session
                $insertSessionData = array(
                    "sessionid" =>$sessionId,
                    "openid"    =>$openid,
                    "sessionkey"       =>$sessionkey
                );
                $insertSession = $sessionModel->add($insertSessionData);

                // 1.5 数据提交;
                if($insertSession && $insertMember){
                    $backData = array(
                        "errorCode" =>10000,
                        "errorMsg"  =>'ok',
                        "sessionid" =>$sessionId,
                        "info"      =>$insertSessionData
                    );
                    $model->commit();
                }else {
                    $model->rollback();
                    $backData = array(
                        "errorCode" =>10003,
                        "errorMsg"  =>'登陆态创建失败'
                    );
                }
            }
        }else {
            $backData = array(
                "errorCode" =>10001,
                "errorMsg"  =>'微信登陆连接失败',
                "info"      =>$result
            );
        }
        $this->ajaxReturn($backData);
    }


    public function getopenid(){
        $sessionid = $_SERVER["HTTP_SESSION_ID"];
        $sessionInfo = M("Mysession")->field('openid')->where(array("sessionid"=>$sessionid))->find();
        return $sessionInfo ? $sessionInfo['openid'] : null;
    }

    /**
     * 获取用户账号信息
    */
    public function getMemberId(){
        $openid = $this->getopenid();
        $memberId = null;
        $memberInfo = M("Member")->field("id")->where(array("openid"=>$openid))->find();
        if($memberInfo){
            $memberId = $memberInfo['id'];
        }
        return (int)$memberId;
    }
    /**
     * 通过openid检测用户状态
     * @param   $openid    string
     * @return  array
     */
    public function checkMember($openid=null){
        if(is_null($openid)){
            $openid =  $this->getopenid();
        }
        $memberInfo = M("Member")->field("id,verify")->where(array("openid"=>$openid))->find();
        if($memberInfo){
            if(0 == $memberInfo['verify']){
                $backData = array(
                    "errorCode" =>10002,
                    "errorMsg"  =>"用户认证待审中…"
                );
            }elseif(3 == $memberInfo['verify']){
                $backData = array(
                    "errorCode" =>10003,
                    "errorMsg"  =>"未通过认证不能购买"
                );
            }else{
                $backData = array(
                    "errorCode" =>10000,
                    "errorMsg"  =>"ok",
                    "memberid"  =>$memberInfo['id']
                );
            }
        }else {
            $backData = array(
                "errorCode" =>10001,
                "errorMsg"  =>"请先注册",
                "openid"    =>$openid
            );
        }
        return $backData;
    }

    /**
     *  新增&更新用户头像和昵称
     *
    */
    public function updateUserinfo(){
        if(IS_POST){
            $memberId = $this->getMemberId();
            $model = M("MemberInfo");
            $data = array(
                "nickname"      =>I("post.nickname",''),
                "avatar"        =>I("post.avatar",''),
                "realname"      =>I("post.realname"),
                "province"      =>I("post.province"),
                "city"          =>I("post.city"),
                "area"          =>I("post.area"),
                "birthday"      =>I("post.birthday"),
                "edu"           =>I("post.edu")
            );
            $userInfo = $model->where(array('member_id'=>$memberId))->find();
            if($userInfo){
                $result = $model->where(array('id'=>$userInfo['id']))->data($data)->fetchSql(false)->save();
            }else {
                $data['member_id'] = $memberId;
                $result = $model->data($data)->add();
            }

            if($result !== false){
                $backData = array(
                    "errorCode" =>10000,
                    "errorMsg"  =>"ok",

                );
            }else {
                $backData = array(
                    "errorCode" =>10001,
                    "errorMsg"  =>"更新资料错误",
                );
            }
            $this->ajaxReturn($backData);
        }
    }

    /**
     * 获取手机验证码 
     */
    public function mpcode($phone){
        //1.1 检测手机号码
        if($this->check_phone($phone)){
            $backData = array(
                "errorCode"     =>10001,
                "errorMsg"      =>"手机号码已经存在"
            );
            $this->ajaxReturn($backData);
        }


        //1.2 检测时效，发送间隔必须在60秒
        $codeMode = M("VerifyCode");
        $prevTime = $codeMode->field("id,create_time")->where("phone=$phone")->fetchSql(false)->find();
        $curTime = time();
        if($prevTime && $curTime - $prevTime['create_time'] < 60){
            $backData = array(
                "errorCode"     =>10002,
                "errorMsg"      =>"发送过于频繁"
            );
            $this->ajaxReturn($backData);
        }

        //1.3 创建验证码
        $code = mt_rand(100000,999999);
        
        //1.4 发送验证码
        $params = array ();
        $accessKeyId = "LTAIctKwqQCrAOxA";
        $accessKeySecret = "2od2OCeEiFHCnCJy9e1y8D0Ubh0EQt";
        $params["PhoneNumbers"] = $phone;
        $params["SignName"] = "会计职业精英汇";
        $params["TemplateCode"] = "SMS_126940077";
        $params['TemplateParam'] = Array (
            "code" => $code
            // "product" => "阿里通信"
        );

            // *** 需用户填写部分结束, 以下代码若无必要无需更改 ***
        if(!empty($params["TemplateParam"]) && is_array($params["TemplateParam"])) {
            $params["TemplateParam"] = json_encode($params["TemplateParam"], JSON_UNESCAPED_UNICODE);
        }
        // 初始化SignatureHelper实例用于设置参数，签名以及发送请求
        $helper = new \Vendor\AliSmsApi\SignatureHelper();
        try {
            $content = $helper->request(
                $accessKeyId,
                $accessKeySecret,
                "dysmsapi.aliyuncs.com",
                array_merge($params, array(
                    "RegionId" => "cn-hangzhou",
                    "Action" => "SendSms",
                    "Version" => "2017-05-25",
                ))
            );
            
        } catch( \Exception $e) {
            $backData = array(
                "errorCode"     =>10004,
                "errorMsg"      =>$e->getMessage()
            );
            $this->ajaxReturn($backData);
        }

        //1.5 写入code表
        if($content->Message == "OK"){
            $codeResult = false;
            $dataArr = array(
                "code"          =>$code,
                "create_time"   =>time()
            );
            if($prevTime){
                $codeResult = $codeMode->where("id=".$prevTime['id'])->data($dataArr)->save();
            }else{
                $dataArr['phone'] = $phone;
                $codeResult = $codeMode->data($dataArr)->add();
            }
            if($codeResult){
                $backData = array(
                    "errorCode"     =>10000,
                    "errorMsg"      =>"OK"
                );
            }else {
                $backData = array(
                    "errorCode"     =>10005,
                    "errorMsg"      =>"insert error"
                );
            }
            $this->ajaxReturn($backData);

        }else{
            $backData = array(
                "errorCode"     =>10004,
                "errorMsg"      =>$content->Message
            );
            $this->ajaxReturn($backData);
        }

    }

    /**
     * 检测手机号码
      */
    
    public function check_phone($phone){
        $where = array(
            "phone" =>$phone
        );
        $result = M("Member")->where($where)->fetchSql(false)->find();
        if($result){
            return true;
        }else {
            return false;
        }
    }


    /** 
     * 手机号绑定
     */
    public function bindphone(){
        //1.1检测验证码
        $phone = I("post.phone");
        $code = I("post.code");
        $verifyCondition = array(
            "phone" =>$phone,
            "code"  =>$code
        );
        $vertifyNum = M("VerifyCode")->where($verifyCondition)->count();
        if(0 == $vertifyNum){
            $backData = array(
                "errorCode"     =>10001,
                "errorMsg"      =>"验证码不正确"
            );
            $this->ajaxReturn($backData);
        }

        //1.2 更新用户表
        $memberId = $this->getMemberId();
        $updateData =array(
            "phone"             =>$phone,
            "mp_identification" =>1
        );
        $updateMember = M("Member")->where("id=".$memberId)->fetchSql(false)->save($updateData);
        if($updateMember !==false){
            $backData = array(
                "errorCode"     =>10000,
                "errorMsg"      =>"OK"
            ); 
        }else {
            $backData = array(
                "errorCode"     =>10002,
                "errorMsg"      =>"UPDATE FAILD"
            );
        }
        $this->ajaxReturn($backData);

    }


}
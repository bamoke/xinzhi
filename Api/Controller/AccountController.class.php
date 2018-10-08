<?php
namespace Api\Controller;
use Think\Controller;
class AccountController extends Controller {

    protected function createSessionId(){
        $session_id=`head -n 80 /dev/urandom | tr -dc A-Za-z0-9 | head -c 32`;
        return $session_id;
    }

    public function regist(){
        if(!IS_POST){
            $backData = array(
                "code" =>10001,
                "msg"  =>'请求错误',
            );
            return $this->ajaxReturn($backData);       
        }
        // 1.1 check data
        $phone = I("post.phone");
        $password = I("post.password"); 
        $password2 = I("post.password2"); 
        $code = I("post.code");
        // 1.1.1 validate phone 
        if(empty($_POST['phone'])){
            $backData = array(
                "code" =>13001,
                "msg"  =>'手机号不能为空'
            );
            return $this->ajaxReturn($backData);   
        }
        // 1.1.2 validate password
        if($password !== $password2){
            $backData = array(
                "code" =>13001,
                "msg"  =>'两次密码不一致'
            );
            return $this->ajaxReturn($backData);   
        }
        // 1.1.3 validate phone
        $isExist = M("Member")->where(array("phone"=>$phone))->find();
        if($isExist) {
            $backData = array(
                "code" =>13001,
                "msg"  =>'用户已存在'
            );
            return $this->ajaxReturn($backData);              
        }
        // 1.1.4 validate code
        $codeInfo = M("SmsCode")->where(array("phone"=>$phone))->find();
        if(!$codeInfo || $code != $codeInfo['code']) {
            $backData = array(
                "code" =>13001,
                "msg"  =>'验证码不正确'
            );
            return $this->ajaxReturn($backData);  
        }
        if(($codeInfo['send_time'] + 600) < time()) {
            $backData = array(
                "code" =>13001,
                "msg"  =>'验证码已过期'
            );
            return $this->ajaxReturn($backData);            
        }

        // 1.2 insert
        $memberData = array(
            "phone"         => $phone,
            "password"      => md5($password."xz"),
            "reg_time"      => time()
        );
        
        $model = M();
        $model->startTrans();
        $insertMemberId = M("Member")->data($memberData)->add();
        $sessionData = array(
            "uid"           => $insertMemberId,
            "token"         => $this->createSessionId(),
            "expires_time"  => time() + (3600 * 24 * 7)
        );
        $insertSession = M("Mysession")->data($sessionData)->add();
        if(!$insertMemberId || !$insertSession) {
            $model->rollback();
            $backData = array(
                "code" =>13002,
                "msg"  =>'登录失败'
            );
            $this->ajaxReturn($backData); 
        }

        $model->commit();
        $backData = array(
            "code" =>200,
            "msg"  =>'注册成功'
        );
        $this->ajaxReturn($backData); 
    }
    /**
     *  Account login
     */
    public function login(){
        if(!IS_POST){
            $backData = array(
                "code" =>10001,
                "msg"  =>'请求错误',
            );
            return $this->ajaxReturn($backData);       
        }
        $memberModel = M("Member");
        $phone  = I("post.phone");
        $password = md5(I("post.password")."xz");
        $condition = array(
            "phone"     => $phone
        );
        $memberInfo = $memberModel->field("id,error_time,error_limit")->where($condition)->find();
        if(!$memberInfo){
            $backData = array(
                "code" =>13001,
                "msg"  =>'账号不存在',
            );
            return $this->ajaxReturn($backData);      
        }
        if($memberInfo['error_time'] >= 5 && $memberInfo['error_limit'] > time()) {
            $backData = array(
                "code" =>13002,
                "msg"  =>"密码错误超过五次，账号锁定2小时",
            );
            return $this->ajaxReturn($backData);            
        }
        $error_time = $memberInfo["error_time"];
        $condition['password'] = $password;
        $memberInfo = $memberModel->field("id")->where($condition)->find();
        if(!$memberInfo){
            // update error info
            $updateData =array(
                "error_time"    => $error_time + 1
            );
            if($error_time >= 4){
                $updateData['error_limit'] = time() + 7200;
            }
            $updateError = $memberModel->where(array("phone"=>$phone))->save($updateData);
            $backData = array(
                "code" =>13003,
                "msg"  =>'密码错误',
            );
            return $this->ajaxReturn($backData);      
        }

        $model = M();
        $model->startTrans();
        // update session
        $accessToken = $this->createSessionId();
        $sessionData = array(
            "token" =>$accessToken
        );
        $updateSession = M("Mysession")->where(array("uid"=>$memberInfo['id']))->data($sessionData)->fetchSql(false)->save();
        // update member
        $memberData = array(
            "error_time"    => 0,
            "error_limit"   => null

        );
        $updateMemer = M("Member")->where(array("id"=>$memberInfo['id']))->data($memberData)->fetchSql(false)->save();
        if(!$updateSession || $updateMemer === false) {
            $model->rollback();
            $backData = array(
                "code" =>13004,
                "msg"  =>'数据更新错误',
            );
            return $this->ajaxReturn($backData);              
        }
        $model->commit();
        $backData = array(
            "code" =>200,
            "msg"  =>'登录成功',
            "info"  => array(
                "token" => $accessToken,
                "user" => array(
                    "uid"   => $memberInfo["id"],
                    "phone" => $phone
                )
            )
        );
        return $this->ajaxReturn($backData);    
        
    }
    /**
     * login
     */
    public function wxlogin($code){
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
        $accessToken = $_SERVER["HTTP_X_ACCESS_TOKEN"];
        $sessionInfo = M("Mysession")->field('openid')->where(array("token"=>$accessToken))->find();
        return $sessionInfo ? $sessionInfo['openid'] : null;
    }

    /**
     * 获取用户账号信息
    */
    public function getMemberId(){
        $accessToken = $_SERVER["HTTP_X_ACCESS_TOKEN"];
        $memberId = null;
        $memberInfo = M("Member")->field("id")->where(array("token"=>$accessToken))->find();
        if($memberInfo){
            $memberId = intval($memberInfo['id']);
        }
        return $memberId;
    }

    /**
     * 获取用户账号余额
    */
    public function fetchBalance($memberId=null){
        if(!$memberId) {
            $memberId = $this->getMemberId();
        }
        $memberInfo = M("Member")->field("balance")->where(array("id"=>$memberId))->find();
        if($memberInfo){
            $balance = $memberInfo['balance'];
        }
        return $balance;
    }

    /**
     * 添加账户余额
     * 应用在事务逻辑中
     */
    public function addBalance($val,$reson,$memberId=null){
        if($memberId === null){
            $memberId = $this->getMemberId();
        }
        $val = (float)$val;
        $updateSql = "update __MEMBER__ set balance = balance + $val where id=$memberId";
        $update = M()->execute($updateSql);

        //插入日志
        $logData = array(
            "member_id"     =>$memberId,
            "amount"        =>$val,
            "description"   =>$reson
        );
        $insertLog = M("BalanceLog")->data($logData)->add();
        return $update && $insertLog;
    }

    /**
     * subBalance
     */
    public function subBalance($val,$reson,$memberId = null){
        if($memberId === null){
            $memberId = $this->getMemberId();
        }
        $val = (float)$val;
        $updateSql = "update __MEMBER__ set balance = balance - $val where id=$memberId";
        $update = M()->execute($updateSql);

        //插入日志
        $logData = array(
            "member_id"     =>$memberId,
            "amount"        => 0 - $val,
            "description"   =>$reson
        );
        $insertLog = M("BalanceLog")->data($logData)->add();
        return $update && $insertLog;
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
     * Fetch member info
     */
    public function getUserinfo(){
        $memberId = $this->getMemberId();
        $info = M("MemberInfo")->where("member_id=$memberId")->find();
        if($info){
            $backData = array(
                "errorCode" =>10000,
                "errorMsg"  =>"ok",
                "info"  =>$info
            );
        }else {
            $backData = array(
                "errorCode" =>10001,
                "errorMsg"  =>"系统繁忙"
            );
        }
        $this->ajaxReturn($backData);
    }

    /**
     *  新增&更新用户头像和昵称
     *
    */
    public function updateUserinfo(){
        if(IS_POST){
            $memberId = $this->getMemberId();
            $model = M("MemberInfo");
/*             $data = array(
                "nickname"      =>I("post.nickname",''),
                "avatar"        =>I("post.avatar",''),
                "realname"      =>I("post.realname"),
                "province"      =>I("post.province"),
                "city"          =>I("post.city"),
                "area"          =>I("post.area"),
                "birthday"      =>I("post.birthday"),
                "edu"           =>I("post.edu")
            ); */
            $data = I("post.");
            $userInfo = $model->where(array('member_id'=>$memberId))->find();
            if($userInfo){
                $result = $model->where(array('id'=>$userInfo['id']))->data($data)->fetchSql(false)->save();
            }else {
                $data['member_id'] = $memberId;
                $result = $model->data($data)->fetchSql(false)->add();
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
                "code"     =>13001,
                "msg"      =>"手机号码已经存在"
            );
            $this->ajaxReturn($backData);
        }


        //1.2 检测时效，发送间隔必须在60秒
        $codeMode = M("VerifyCode");
        $prevTime = $codeMode->field("id,create_time")->where("phone=$phone")->fetchSql(false)->find();
        $curTime = time();
        if($prevTime && $curTime - $prevTime['create_time'] < 60){
            $backData = array(
                "code"     =>13002,
                "msg"      =>"发送过于频繁"
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
                "code"     =>13004,
                "msg"      =>$e->getMessage()
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
                    "code"     =>200,
                    "msg"      =>"OK"
                );
            }else {
                $backData = array(
                    "code"     =>13005,
                    "msg"      =>"insert error"
                );
            }
            $this->ajaxReturn($backData);

        }else{
            $backData = array(
                "code"     =>13004,
                "msg"      =>$content->Message
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
        $memberId = $this->getMemberId();
        //1.1检测验证码
        $acType = I("post.actype");
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

        // 1.1.1 检测新旧号码是否一样
        if($acType == 2){
            $memberInfo = M("Member")->field("phone")->where("id=$memberId")->find();
            if($phone == $memberInfo['phone']){
                $backData = array(
                    "errorCode"     =>10011,
                    "errorMsg"      =>"新号码与原号码相同"
                );
                $this->ajaxReturn($backData); 
            }
        }

        $model = M();
        $model->startTrans();

        //1.2 更新用户表
        $addBalanceVal = 10;
        $updateData =array(
            "phone"             =>$phone,
            "mp_identification" =>1
        );
        $updateMember = M("Member")->where("id=".$memberId)->fetchSql(false)->save($updateData);


        //1.3 增加余额
        $addBalanceResult = true;
        if($acType == 1){
            $addBalanceResult = $this->addBalance($addBalanceVal,"手机号绑定奖励",$memberId);
        }
        
        if($updateMember && $addBalanceResult){
            $backData = array(
                "errorCode"     =>10000,
                "errorMsg"      =>"OK"
            ); 
            $backData['errorMsg'] = $acType==1? "恭喜您完成手机号绑定，并获得".(int)$addBalanceVal."元现金奖励" : "已绑定为新手机号码";
            $model->commit();
        }else {
            $backData = array(
                "errorCode"     =>10001,
                "errorMsg"      =>"系统繁忙"
            );
            $model->rollback();
        }
        $this->ajaxReturn($backData);

    }


}
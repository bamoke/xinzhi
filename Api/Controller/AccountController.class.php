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
                if($updateSession){
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

                // 1.5 创建个人资料;
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
        $memberInfo = M("Member")->field("id,capital")->where(array("openid"=>$openid))->find();
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
     *  更新用户资料
     *
    */
    public function updateUserinfo(){
        if(IS_POST){
            $memberId = $this->getMemberId();
            $model = M("MemberInfo");
            $data = array(
                "nickname"  =>$_POST['nickname'],
                "avatar"  =>$_POST['avatar']
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



}
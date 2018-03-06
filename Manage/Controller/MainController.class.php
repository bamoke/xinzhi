<?php
/**
 * Created by PhpStorm.
 * User: joy.wangxiangyin
 */

namespace Manage\Controller;


use Think\Controller;

class MainController extends Controller
{
    function login() {
        if(IS_POST){
            $this->verifyLogin($_POST);
        }else {
            layout(false);
            $output = array(
                "title"     =>"用户登录",
                "script"    =>CONTROLLER_NAME."/login"
            );
            $this->assign("output",$output);
            $this->display();
        }

    }

    public function logout(){
        session(null);
        $this->redirect("Main/login");
    }

    /*
     * 验证码
     * */
    public function code()
    {
        $config = array(
            'expire' => 600,//验证码过期时间
            'useImgBg' => false,//是否使用背景图,默认false;
            'useCurve' => false,//是否使用混淆曲线,默认true;
            'useNoise' => false,//是否添加杂点；默认true;
            'fontSize' => '16px',//设置字体大小，默认25px;
            'imageW' => '120px',//验证码宽度;
            'imageH' => '34px',//验证码高度;
            'length' => 4,//验证码字符数
            'bg' => array(190, 190, 190)
        );
        $verify = new \Think\Verify($config);
        $verify->entry();
    }

    /*
     * 用户登录操作
     * @param array
     * @return json
     * */
    protected function verifyLogin($data){
        $verify = new \Think\Verify();
        if($verify->check(I("post.code")) === false){
            $backData=array(
                'status'    =>false,
                'msg'       =>"验证码错误",
            );
        }else {
            $user_model=M('User');

            $user=$user_model
                ->where(array("username"=>I("post.username"),"password"=>md5(I("post.password"))))
                ->find();
            if($user){
                session("uid",intval($user['id']));
                session("username",I("post.username"));
                session("realname",$user['realname']);
                session("issuper",$user['issuper']);
                $backData=array(
                    'status'    =>true,
                    'jump'       =>U("Sys/index"),
                );
            }else {
                $backData=array(
                    'status'    =>false,
                    'msg'       =>"用户名或密码错误",
                );
            }
        }
        $this->ajaxReturn($backData);

    }
}
<?php
/**
 * Created by PhpStorm.
 * User: joy.wangxiangyin
 * Date: 2017/06/12
 * Time: 10:27
 */

namespace Manage\Controller;


use Manage\Common\Controller\AuthController;

class UserController extends AuthController
{

    /***View  Index**/
    public function index(){
        $model = M('User');
        $result = $model->field('id,username,realname,status')->page($_GET['p'].',20')->select();

        // paging set
        $count = $model->count();
        $page = new \Think\Page($count,20);
        $page->setConfig('next','下一页');
        $page->setConfig('prev','上一页');
        $paging = $page->show();

        $outData = array(
            'list'      => $result,
            'paging'    => $paging
        );
        $this->assign('output',$outData);
        $this->display();
    }

    /***View  Add user page**/
    public function add(){
        $outData['script']= CONTROLLER_NAME."/add";
        $this->assign('output',$outData);
        $this->display();
    }

    /***View  edit user page**/
    public function edit($id){
        $outData['script']= CONTROLLER_NAME."/add";
        $model = M('user');
        $result = $model->field('id,username,email,realname')->where('id='.$id)->find();
        $outData['info'] = $result;
        $this->assign("pageName",'编辑用户');
        $this->assign('output',$outData);
        $this->display();
    }


    /***action***/
    public function save(){
        if(IS_POST){
            $model = D('User');
            $data = array(
                "username"  =>I('post.username'),
                "password"  =>md5(I('post.username')),
                "realname"  =>I('post.realname'),
                "email"  =>I('post.email'),
            );
            if($model->create($data,1) && $model->add()){
                $this->ajaxReturn(array('status'=>1,'info'=>'ok'));
            }else {
                $this->ajaxReturn(array('status'=>0,'info'=>$model->getError()));
            }
        }

    }

    public function update(){
        $backData = array();
        if(IS_POST){
            $model = D('user');
            if($model->create($_POST,2)){
                $result = $model->save();
                if($result === false){
                    $backData['status'] = 0;
                    $backData['info'] = $model->getError();
                }elseif ($result === 0){
                    $backData['status'] = 1;
                    $backData['info'] = "没有变动";
                }else {
                    $backData['status'] = 1;
                    $backData['info'] = 'ok';
                }
                $this->ajaxReturn($backData);
            }
        }

    }

    /**密码重置**/
    public function reset($id){
        $model = M('user');
        $data=array('password'=>md5('123456'));
        $result = $model->where('id = '.$id)->save($data);
        if($result){
            $this->ajaxReturn(array('status'=>1,'info'=>'success'));
        }else {
            $this->ajaxReturn(array('status'=>0,'info'=>$model->getError()));
        }
    }

    /***禁用***/
    public function disable($id,$v){
        $model = M('user');
        $data = array('status'=>$v);
        $result = $model->where('id = '.$id)->save($data);
        if($result){
            $this->ajaxReturn(array('status'=>1,'info'=>'success'));
        }else {
            $this->ajaxReturn(array('status'=>0,'info'=>$model->getError()));
        }
    }



}
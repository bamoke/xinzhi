<?php
/**
 * Created by PhpStorm.
 * User: wetz1
 * Date: 2017/10/5
 * Time: 3:24
 */

namespace Manage\Controller;
use Manage\Common\Controller\AuthController;


class OrganizationController extends AuthController
{
    function index(){
        $curModel = M();

        $list = M("Org")->select();
        $outData = array(
            "script"=>CONTROLLER_NAME."/index",
            "list"  =>$list
        );
        $this->assign('output',$outData);
        $this->display();
    }

    function add(){
        $outData = array(
            "script"=>CONTROLLER_NAME."/main"
        );
        $this->assign('pageName',"添加机构");
        $this->assign('output',$outData);
        $this->display();
    }

    function a_add(){
        $backData = array();
        if(IS_POST){
            $curModel = D('Org');
            $uploadDir = ROOT_DIR.'/Upload/';
            $result = $curModel->create();
            if($result){
                if($_FILES['thumb']['tmp_name']){
                    //图片处理
                    $upload_conf=array(
                        'maxSize' => 3145728,
                        'rootPath' => $uploadDir,
                        'savePath' => 'thumb/',
                        'saveName' => md5(time().I("session.uid")),
                        'exts' => array('jpg', 'gif', 'png', 'jpeg'),
                        'autoSub' => false,
                        'subName' => date("Ym",time())
                    );
                    $upload = new \Think\Upload($upload_conf);
                    $uploadResult = $upload->uploadOne($_FILES["thumb"]);
                    if(!$uploadResult){
                        $backData['status'] = 0;
                        $backData['msg'] = $upload->getError();
                        return $this->ajaxReturn(json_encode($backData));
                    }else{
                        $curModel->logo = $uploadResult['savename'];
                    }
                }

                $add = $curModel->fetchSql(false)->add();

                if($add){
                    $backData['status'] = 1;
                    $backData['msg'] = "添加成功";
                    $backData['jump'] = U('index');

                }else {
                    $backData['status'] = 0;
                    $backData['msg'] = $curModel->getError();
                }

            }else {
                $backData['status'] = 0;
                $backData['msg'] = $curModel->getError();
            }
            $this->ajaxReturn($backData);
        }
    }

    public function edit($id){
        $curModel = M("Org");
        $result =  $curModel->where(array("id"=>$id))->find();
        $outData = array(
            "script"=>CONTROLLER_NAME."/main",
            "data"  =>$result
        );
        $this->assign('output',$outData);
        $this->assign('pageName',"编辑机构资料");
        $this->display();
    }

    public function a_update($id){
        if(IS_POST){
            $curModel = D('Org');
            $uploadDir = ROOT_DIR.'/Upload/';
            $result = $curModel->create();
            if($result){
                if($_FILES['thumb']['tmp_name']){
                    //图片处理
                    $upload_conf=array(
                        'maxSize' => 3145728,
                        'rootPath' => $uploadDir,
                        'savePath' => 'thumb/',
                        'saveName' => md5(time().I("session.uid")),
                        'exts' => array('jpg', 'gif', 'png', 'jpeg'),
                        'autoSub' => false,
                        'subName' => date("Ym",time())
                    );
                    $upload = new \Think\Upload($upload_conf);
                    $uploadResult = $upload->uploadOne($_FILES["thumb"]);
                    if(!$uploadResult){
                        $backData['status'] = 0;
                        $backData['msg'] = $upload->getError();
                        return $this->ajaxReturn(json_encode($backData));
                    }else{
                        $curModel->thumb = $uploadResult['savename'];
                        $this->del_thumb($id);
                    }
                }

                $save = $curModel->fetchSql(false)->where("id = ".$id)->save();

                if($save){
                    $backData['status'] = 1;
                    $backData['msg'] = "修改成功";
                    $backData['jump'] = U('index');

                }else {
                    $backData['status'] = 0;
                    $backData['msg'] = $curModel->getError();
                }

            }else {
                $backData['status'] = 0;
                $backData['msg'] = $curModel->getError();
            }

            $this->ajaxReturn($backData);
        }
    }


    protected function del_thumb($id){
        $dir = ROOT_DIR."/Upload/thumb/";
        $result = M("Org")->field("logo")->where('id='.$id)->find();
        @unlink($dir.$result['logo']);
    }

}
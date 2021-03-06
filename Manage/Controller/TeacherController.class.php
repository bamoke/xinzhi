<?php
/**
 * Created by PhpStorm.
 * User: wetz1
 * Date: 2017/10/5
 * Time: 3:24
 */

namespace Manage\Controller;
use Manage\Common\Controller\AuthController;


class TeacherController extends AuthController
{
    function index(){
        $curModel = M();
        $sql = '
        select T.*,
        (select count(*) from __COLUMNIST__ where teacher_id = T.id) as columnist_num,
        (select count(*) from __COURSE__ where teacher_id = T.id) as course_num,O.name as org_name 
        from __TEACHER__ as T 
        join __ORG__ as O on T.org_id = O.id
        ';
//        $list = $curModel->where(array('status'=>1))->select();
        $list = $curModel->query($sql);

        $outData = array(
            "list"  =>$list
        );
        $this->assign('output',$outData);
        $this->display();
    }

    function add(){
        $outData = array(
            "script"=>CONTROLLER_NAME."/main"
        );
        $orgList = M("Org")->field("id,name")->where(array('status'=>1))->select();
        $this->assign('output',$outData);
        $this->assign('orgList',$orgList);
        $this->display();
    }

    function a_add(){
        $backData = array();
        if(IS_POST){
            $curModel = D('Teacher');
            $uploadDir = ROOT_DIR.'/Upload/';
            $result = $curModel->create();
            if($result){
                if($_FILES['avatar']['tmp_name']){
                    //图片处理
                    $upload_conf=array(
                        'maxSize' => 3145728,
                        'rootPath' => $uploadDir,
                        'savePath' => 'avatar/',
                        'saveName' => md5(time().I("session.uid")),
                        'exts' => array('jpg', 'gif', 'png', 'jpeg'),
                        'autoSub' => false,
                        'subName' => date("Ym",time())
                    );
                    $upload = new \Think\Upload($upload_conf);
                    $uploadResult = $upload->uploadOne($_FILES["avatar"]);
                    if(!$uploadResult){
                        $backData['status'] = 0;
                        $backData['msg'] = $upload->getError();
                        return $this->ajaxReturn(json_encode($backData));
                    }else{
                        $curModel->avatar = $uploadResult['savename'];
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
        $curModel = M("Teacher");
        $result =  $curModel->where(array("id"=>$id))->find();
        $orgList = M("Org")->field("id,name")->where(array('status'=>1))->select();
        $outData = array(
            "script"=>CONTROLLER_NAME."/main",
            "data"  =>$result
        );
        $this->assign('output',$outData);
        $this->assign('orgList',$orgList);
        $this->display();
    }

    public function a_update($id){
        if(IS_POST){
            $curModel = D('Teacher');
            $uploadDir = ROOT_DIR.'/Upload/';
            $result = $curModel->create();
            if($result){
                if($_FILES['avatar']['tmp_name']){
                    //图片处理
                    $upload_conf=array(
                        'maxSize' => 3145728,
                        'rootPath' => $uploadDir,
                        'savePath' => 'avatar/',
                        'saveName' => md5(time().I("session.uid")),
                        'exts' => array('jpg', 'gif', 'png', 'jpeg'),
                        'autoSub' => false,
                        'subName' => date("Ym",time())
                    );
                    $upload = new \Think\Upload($upload_conf);
                    $uploadResult = $upload->uploadOne($_FILES["avatar"]);
                    if(!$uploadResult){
                        $backData['status'] = 0;
                        $backData['msg'] = $upload->getError();
                        return $this->ajaxReturn(json_encode($backData));
                    }else{
                        $curModel->avatar = $uploadResult['savename'];
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
        $dir = ROOT_DIR."/Upload/avatar/";
        $result = M("Teacher")->field("avatar")->where('id='.$id)->find();
        @unlink($dir.$result['avatar']);
    }

}
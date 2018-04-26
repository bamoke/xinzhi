<?php
/**
 * Created by PhpStorm.
 * User: wetz1
 * Date: 2017/10/5
 * Time: 3:24
 */

namespace Manage\Controller;
use Manage\Common\Controller\AuthController;


class DownloadController extends AuthController
{
    function index(){
        $curModel = M("Download");
        // paging set
        $count = $curModel->count();
        $page = new \Think\Page($count,20);
        $page->setConfig('next','下一页');
        $page->setConfig('prev','上一页');
        $paging = $page->show();
        $list = $curModel->order('sort,id desc')->limit($page->firstRow.",".$page->listRows)->select();;
        $outData = array(
            'list'      => $list,
            'paging'    => $paging,
            'script'    =>CONTROLLER_NAME."/index"
        );
        $this->assign('output',$outData);
        $this->display();
    }

    function add(){
        $outData = array(
            "script"=>CONTROLLER_NAME."/main"
        );
        $this->assign('output',$outData);
        $this->assign('pageName',"添加资料");
        $this->display();
    }

    /**
     * Change sort
     * @param int id
     * @param int sort number
     */
    function changeorder($id,$order_val){
        $result = M("Download")->where("id=$id")->data("sort=".$order_val)->save();
        $backData =array();
        if($result){
            $backData['status'] = 1;
            $backData['msg'] = "修改成功";
        }else {
            $backData['status'] = 0;
            $backData['msg'] = $curModel->getError();
        }
        $this->ajaxReturn();
    }


    function a_add(){
        $backData = array();
        if(IS_POST){
            $curModel = M("Download");
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

                $result->content= $_POST["content"];//避免标签自动转换过滤
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
        $curModel = M("Download");
        $result =  $curModel->where(array("id"=>$id))->find();
        $outData = array(
            "script"=>CONTROLLER_NAME."/main",
            "info"  =>$result
        );
        $this->assign('pageName',"修改下载资料");
        $this->assign('output',$outData);
        $this->display();
    }

    public function a_update($id){
        if(IS_POST){
            $curModel = M('Download');
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

                $result->content= $_POST["content"];//避免标签自动转换过滤
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
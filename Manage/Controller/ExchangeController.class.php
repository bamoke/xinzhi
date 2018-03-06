<?php
/**
 * Created by PhpStorm.
 * User: joy.wangxiangyin
 * Date: 2017/6/26
 * Time: 16:59
 */

namespace Manage\Controller;
use Manage\Common\Controller\AuthController;

class ExchangeController extends AuthController
{

    public function index(){
        $modelPro = M("Exchange");
        // paging set
        $count = $modelPro->count();
        $page = new \Think\Page($count,2);
        $page->setConfig('next','下一页');
        $page->setConfig('prev','上一页');
        $paging = $page->show();

        $fields =  '
        id,name,price,thumb,recommend,create_time,status
        ';
        $proList = $modelPro->field( $fields)->order('recommend desc,id desc')->limit($page->firstRow.",".$page->listRows)->select();
        $outData = array(
            'list'      => $proList,
            'paging'    => $paging
        );
        $this->assign('output',$outData);
        $this->display();
    }

    /***编辑 View**/
    public function edit($id){
        $proInfo = M("Exchange")->where('id ='.$id)->find();
        $outData = array(
            'proInfo'      => $proInfo,
            "script"=>CONTROLLER_NAME."/main"
        );
        $this->assign('output',$outData);
        $this->display();

    }

    /***View Cate**/
    public function add(){
        $outData = array(
            "script"=>CONTROLLER_NAME."/main"
        );
        $this->assign('output',$outData);
        $this->display();
    }



    /***action ****/
    public function a_update($id){
        if(IS_POST){
            $backData = array();
            $model = D("Product");
            $uploadDir = ROOT_DIR.'/Upload/';
            $result = $model->create($_POST);
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
                        $backData['imgErr'] = $upload->getError();
                    }else {
                        $model->thumb = $thumb = $uploadResult['savename'];
                        $this->del_thumb($id);
                    }
                }else {
                    // 删除旧图片
                    if(isset($_POST['oldthumb']) && $_POST['oldthumb'] == ''){
                        $model->thumb = '';
                        $this->del_thumb($id);
                    }
                }

                $model->sell_start_date = strtotime($_POST['sell_start_date']);
                $model->sell_end_date = strtotime($_POST['sell_end_date']);
                $update = $model->where('id='.$id)->fetchSql(false)->save();
                if($update !== false){
                    $backData['status'] = 1;
                    $backData['msg'] = $update === 0 ? "数据没有变动":"修改成功";
                    $backData['jump'] = U('index');
//                    $backData['data'] = $result;

                }else {
                    $backData['status'] = 0;
                    $backData['msg'] = $model->getError();
                }

            }else {
                $backData['status'] = 0;
                $backData['msg'] = $model->getError();
            }


            $this->ajaxReturn($backData);

        }
    }


    public function a_add(){
        if(IS_POST){
            $backData = array();
            $model = D("Product");
            $uploadDir = ROOT_DIR.'/Upload/';
            $result = $model->create($_POST);

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
                        $backData['imgErr'] = $upload->getError();
                    }else{
                        $model->thumb = $uploadResult['savename'];
                    }
                }
                $add = $model->add();
                if($add){
                    $backData['status'] = 1;
                    $backData['msg'] = "添加成功";
                    $backData['jump'] = U('index');

                }else {
                    $backData['status'] = 0;
                    $backData['msg'] = $model->getError();
                }

            }else {
                $backData['status'] = 0;
                $backData['msg'] = $model->getError();
            }


            $this->ajaxReturn($backData);

        }
    }


    /**
     * Delete old thumbnail
     * @param $pid int product ID
    */
    protected function del_thumb($pid){
        $dir = ROOT_DIR."/Upload/thumb/";
        $result = M("Product")->field("thumb")->where('id='.$pid)->find();
        @unlink($dir.$result['thumb']);
    }






    /*=============================*/
}
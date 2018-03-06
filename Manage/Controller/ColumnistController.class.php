<?php
/**
 * Created by PhpStorm.
 * User: joy.wangxiangyin
 * Date: 2017/6/26
 * Time: 16:59
 */

namespace Manage\Controller;
use Manage\Common\Controller\AuthController;

class ColumnistController extends AuthController
{


    public function index(){
        $modelColumnist = new \Manage\Model\ColumnistModel();
        // paging set
        $count = $modelColumnist->count();
        $page = new \Think\Page($count,10);
        $page->setConfig('next','下一页');
        $page->setConfig('prev','上一页');
        $paging = $page->show();

        $fields =  '
        id,name,price,thumb,recommend,create_time,status,group_min,
        from_unixtime(sell_start_date) as sell_start_date,
        from_unixtime(sell_end_date) as sell_end_date
        ';
        $proList = $modelColumnist->order('recommend desc,id desc')->limit($page->firstRow.",".$page->listRows)->select();
        $outData = array(
            'list'      => $proList,
            'paging'    => $paging
        );
        $this->assign('output',$outData);
        $this->display();
    }

    /***编辑 View**/
    public function edit($id){
        $cateModel = D('Category');
        $cateList = $cateModel->getCate('columnist');
        $teacherList = M("Teacher")->field('id,name')->where(array("status"=>1))->select();
        $columnistInfo = M("Columnist")->where(array('id'=>$id))->find();
        $outData = array(
            'columnistInfo'      => $columnistInfo,
            "script"=>CONTROLLER_NAME."/main",
            "cateList" =>$cateList,
            "teacherList"  =>$teacherList
        );
        $this->assign('output',$outData);
        $this->assign('pageName',"编辑专栏");
        $this->display();

    }

    /***View Cate**/
    public function add(){
        $cateModel = D('Category');
        $cateList = $cateModel->getCate('columnist');
        $teacherList = M("Teacher")->field('id,name')->where(array("status"=>1))->select();
        $outData = array(
            "script"=>CONTROLLER_NAME."/main",
            "cateList"=>$cateList,
            "teacherList"=>$teacherList
        );
        $this->assign('output',$outData);
        $this->display();
    }



    /***action ****/
    public function a_update($id){
        if(IS_POST){
            $backData = array();
            $model = D("Columnist");
            $result = $model->create($_POST);
            if($result){
                if($_FILES['thumb']['tmp_name']){
                    //图片处理
                    $upload_conf=$this->_upload_conf();
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
            if($_POST['isfree'] == 0 && !is_numeric($_POST['price'])){
                return  $this->ajaxReturn(array("status"=>0,"msg"=>"请填写合法的价格数值"));
            }
            $model = M("Columnist");
            $result = $model->create($_POST);

            if($result){
                if($_FILES['thumb']['tmp_name']){
                    //图片处理
                    $upload_conf=$this->_upload_conf();
                    $upload = new \Think\Upload($upload_conf);
                    $uploadResult = $upload->uploadOne($_FILES["thumb"]);
                    if(!$uploadResult){
                        $backData['imgErr'] = $upload->getError();
                    }else{
                        $model->thumb = $uploadResult['savename'];
                    }
                }
                $add = $model->fetchSql(false)->add();
//                var_dump($add);
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



    /***专栏详情***/
    function detail($id){
        $columnistInfo = M("Columnist")->field("id,thumb,title,description,price,status")->where(array("id"=>$id))->find();
        $articleList = M("ColumnistArticle")->field()->where(array("columnist_id"=>$id))->order("id desc")->select();
        $outData = array(
            "script"=>CONTROLLER_NAME."/main"
        );
        $this->assign('output',$outData);
        $this->assign('columnistInfo',$columnistInfo);
        $this->assign('articleList',$articleList);
        $this->assign('pageName',"专栏详情");
        $this->display();

    }

    /***选择内容类型***/
    function choose_type(){
        $this->assign('pageName',"选择内容类型");
        $this->display();
    }

    /***添加专栏内容***/
    function add_article($clid,$type){
        $outData = array(
            "script"=>CONTROLLER_NAME."/main"
        );
        $this->assign('output',$outData);
        $this->assign('pageName',"添加内容");
        $this->display();
    }

    /***保存内容***/
    public function a_add_article(){
        if(IS_POST){
            $backData = array();
            $articleModel = M("ColumnistArticle");
            $result = $articleModel->create($_POST);

            if($result){
                if($_FILES['thumb']['tmp_name']){
                    //图片处理
                    $upload_conf=$this->_upload_conf();
                    $upload = new \Think\Upload($upload_conf);
                    $uploadResult = $upload->uploadOne($_FILES["thumb"]);
                    if(!$uploadResult){
                        $backData['imgErr'] = $upload->getError();
                    }else{
                        $articleModel->thumb = $uploadResult['savename'];
                    }
                }
                $model = M();
                $model->startTrans();
                $add = $articleModel->fetchSql(false)->add();
                $updateSql = "update __COLUMNIST__ set `article_num` = article_num+1 where id=".I('post.columnist_id');
                $update = $model->execute($updateSql);

                if($add && $update){
                    $model->commit();
                    $backData['status'] = 1;
                    $backData['msg'] = "添加成功";
                    $backData['jump'] = U('index');

                }else {
                    $model->rollback();
                    //删除已上传得图片
                    if($uploadResult){
                        $dir = ROOT_DIR."/Upload/thumb/";
                        @unlink($dir.$uploadResult['thumb']);
                    }


                    $backData['status'] = 0;
                    $backData['msg'] = "数据保存错误";
                }

            }else {
                $backData['status'] = 0;
                $backData['msg'] = $articleModel->getError();
            }


            $this->ajaxReturn($backData);

        }
    }

    /***修改专栏内容***/
    function edit_article($id){
        $curModel = M("ColumnistArticle");
        $articleInfo = $curModel->where(array('id'=>$id))->find();
        $outData = array(
            "script"=>CONTROLLER_NAME."/main"
        );
        $this->assign('output',$outData);
        $this->assign('articleInfo',$articleInfo);
        $this->assign('pageName',"编辑文章内容");
        $this->display();
    }

    /***保存文章修改***/
    public function a_update_article($id){
        if(IS_POST){
            $backData = array();
            $model = D("ColumnistArticle");
            $result = $model->create($_POST);
            if($result){
                if($_FILES['thumb']['tmp_name']){
                    //图片处理
                    $upload_conf=$this->_upload_conf();
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
                        $this->del_thumb($id,'ColumnistArticle');
                    }
                }

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

    /****修改专栏状态***/
    public function change_columnist_status($id,$status){
        $model = M("Columnist");
        $newData = array(
            "status"    =>$status
        );
        $result = $model->where(array('id'=>$id))->save($newData);
        if($result !== false ){
            $backData['status'] = 1;
            $backData['msg'] = "修改成功";
        }else {
            $backData['status'] = 0;
            $backData['msg'] = $model->getError();
        }
        $this->ajaxReturn($backData);
    }

    /****修改内容状态***/
    public function change_article_status($id,$status){
        $model = M("ColumnistArticle");
        $newData = array(
            "status"    =>$status
        );
        $result = $model->where(array('id'=>$id))->save($newData);
        if($result !== false ){
            $backData['status'] = 1;
            $backData['msg'] = "修改成功";
        }else {
            $backData['status'] = 0;
            $backData['msg'] = $model->getError();
        }
        $this->ajaxReturn($backData);
    }


    /**
     * Delete old thumbnail
     * @param   $id     int product ID
     * @param   $table  string
    */
    protected function del_thumb($id,$table='Columnist'){
        $dir = ROOT_DIR."/Upload/thumb/";
        $result = M($table)->field("thumb")->where('id='.$id)->find();
        @unlink($dir.$result['thumb']);
    }


    /**
     * set default upload config
    */
    private function _upload_conf (){
        return array(
            'maxSize' => 3145728,
            'rootPath' => ROOT_DIR.'/Upload/',
            'savePath' => 'thumb/',
            'saveName' => md5(time().I("session.uid")),
            'exts' => array('jpg', 'gif', 'png', 'jpeg'),
            'autoSub' => false,
            'subName' => date("Ym",time())
        );
    }



    /*=============================*/
}
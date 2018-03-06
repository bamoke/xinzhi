<?php
/**
 * Created by PhpStorm.
 * User: joy.wangxiangyin
 * Date: 2017/6/26
 * Time: 16:59
 */

namespace Manage\Controller;
use Manage\Common\Controller\AuthController;

class CourseController extends AuthController
{


    public function index(){
        $courseModel = M("Course");
        // paging set
        $count = $courseModel->count();
        $page = new \Think\Page($count,10);
        $page->setConfig('next','下一页');
        $page->setConfig('prev','上一页');
        $paging = $page->show();

        $where = "1 = 1";
        $courseList = $courseModel
            ->alias("c")
            ->field("c.id,c.title,c.thumb,c.isfree,c.price,c.buy_num,c.comment_num,c.recommend,c.order_no,c.status,t.name as teacher_name,mc.name as cate_name")
            ->join("__TEACHER__ as t on c.teacher_id = t.id")
            ->join("__MAIN_CATE__ as mc on c.cate_id = mc.id")
            ->where($where)
            ->order('c.recommend desc,c.order_no,c.id desc')->limit($page->firstRow.",".$page->listRows)->select();
        $outData = array(
            'list'      => $courseList,
            'paging'    => $paging,
            "script"    =>CONTROLLER_NAME.'/index'
        );
        $this->assign('output',$outData);
        $this->display();
    }

    /**
     * 修改课程排序
    */
    public function changeorder($id,$order_val){
        $updateData = array(
            'order_no'  =>$order_val
        );
        $update = M("Course")->where("id=$id")->data($updateData)->save();
    }


    /***编辑 View**/
    public function edit($id){
        $cateModel = D('Category');
        $cateList = $cateModel->getCate('course');
        $teacherList = M("Teacher")->field('id,name')->where(array("status"=>1))->select();
        $courseInfo = M("Course")->where(array('id'=>$id))->find();
        $outData = array(
            'courseInfo'      => $courseInfo,
            "script"=>CONTROLLER_NAME."/main",
            "cateList" =>$cateList,
            "teacherList"  =>$teacherList
        );
        $this->assign('output',$outData);
        $this->assign('pageName',"编辑课程");
        $this->display();

    }

    /***View add**/
    public function add(){
        $cateModel = D('Category');
        $cateList = $cateModel->getCate('course');
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
            $model = D("Course");
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
                        $this->del_thumb($id,'Course');
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
            $model = M("Course");
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

    /***章节列表***/
    function section($cid){
        $typeArr = array('',"音频","视频");
        $sectionModel = M("CourseSection");
        $sectionList = $sectionModel->field('id,title,type,view_num')->where(array('course_id'=>$cid))->select();
        $courseInfo = M("Course")->field('id,title,price,status,buy_num,comment_num,description')->where(array('id'=>$cid))->find();
        $outData = array(
            "script"=>CONTROLLER_NAME."/main"
        );
        $this->assign('output',$outData);
        $this->assign('sectionList',$sectionList);
        $this->assign('courseInfo',$courseInfo);
        $this->assign('typeArr',$typeArr);
        $this->assign('pageName',"课程章节");
        $this->display();
    }

    /***添加课程章节***/
    public function addsection($cid){
        $outData = array(
            "script"=>CONTROLLER_NAME."/main"
        );
        $this->assign('output',$outData);
        $this->assign('pageName',"添加课程章节");
        $this->display();
    }

    /***修改课程章节***/
    public function updatesection($id){
        $sectionInfo = M("CourseSection")->where(array('id'=>$id))->find();
        $outData = array(
            "info"  =>$sectionInfo,
            "script"=>CONTROLLER_NAME."/main"
        );
        $this->assign('output',$outData);
        $this->assign('pageName',"编辑课程章节");
        $this->display();
    }



    /***保存课程章节内容***/
    function a_add_section(){
        if(IS_POST){
            $curModel = M("CourseSection");
            $result = $curModel->create($_POST);
            if($result){
                $model =M();
                $model->startTrans();
                $add = $curModel->add();
                $updateSql = "update __COURSE__ set `period` = period+1 where id=".I('post.course_id');
                $update = $model->execute($updateSql);
                if($add && $update){
                    $model->commit();
                    $backData['status'] = 1;
                    $backData['msg'] = "保存成功";
                    $backData['jump'] = U('section',array('cid'=>I('post.cid')));
                }else {
                    $model->rollback();
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




    /***保存章节修改***/
    public function a_update_section(){
        if(IS_POST){
            $backData = array();
            $model = M("CourseSection");
            $result = $model->create($_POST);
            if($result){

                $update = $model->where('id='.$_POST['id'])->fetchSql(false)->save();
                if($update !== false){
                    $backData['status'] = 1;
                    $backData['msg'] = $update === 0 ? "数据没有变动":"修改成功";

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

    /****修改课程状态***/
    public function change_course_status($id,$status){
        $model = M("Course");
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
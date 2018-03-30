<?php
/**
 * Created by PhpStorm.
 * User: joy.wangxiangyin
 * Date: 2017/6/26
 * Time: 16:59
 */

namespace Manage\Controller;
use Manage\Common\Controller\AuthController;

class SurveyController extends AuthController
{


    public function index(){
        $mainModel = M("Survey");
        // paging set
        $count = $mainModel->count();
        $page = new \Think\Page($count,10);
        $page->setConfig('next','下一页');
        $page->setConfig('prev','上一页');
        $paging = $page->show();

        $where = "1 = 1";
        $list = $mainModel->limit($page->firstRow.",".$page->listRows)->order("id desc")->select();
        $outData = array(
            'list'      => $list,
            'paging'    => $paging,
            // "script"    =>CONTROLLER_NAME.'/index'
        );
        $this->assign('output',$outData);
        $this->display();
    }

    /**
     * 修改课程排序
    */
    public function changeorder($id,$order_val){
        $updateData = array(
            'sort'  =>$order_val
        );
        $update = M("Booking")->where("id=$id")->data($updateData)->save();
    }


    /***编辑 View**/
    public function edit($id){
        $surveyInfo = M("Survey")->where(array('id'=>$id))->find();
        $outData = array(
            'info'      => $surveyInfo,
            "script"=>CONTROLLER_NAME."/main"
        );
        $this->assign('output',$outData);
        $this->assign('pageName',"编辑问卷");
        $this->display();

    }

    /***View add**/
    public function add(){
        $outData = array(
            "script"=>CONTROLLER_NAME."/main"
        );
        $this->assign('output',$outData);
        $this->assign('pageName','添加调查问卷');
        $this->display();
    }


   /***Action update survey ****/
   public function a_update($id){
    if(IS_POST){
        $backData = array();
        $model = M("Survey");
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
                }
            }else {
                // 删除旧图片
                if(isset($_POST['oldthumb']) && $_POST['oldthumb'] == ''){
                    $model->thumb = '';
                    // $this->del_thumb($img);
                }
            }

            if($_POST['give_balance'] == ''){
                $model->give_balance = NULL;
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
            if($_POST['give_balance'] != '' && !is_numeric($_POST['give_balance'])){
                return  $this->ajaxReturn(array("status"=>0,"msg"=>"请填写合法的价格数值"));
            }
            $model = M("Survey");
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
                if($_POST['give_balance'] == ''){
                    $model->give_balance = NULL;
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



    /**
     * View question list page
     */
    function question($sid){
        $surveyInfo = M("Survey")->where("id=$sid")->find();
        $questionList = M("SurveyQuestion")->where("s_id = $sid")->limit(50)->order("sort,id")->select();
        $outData = array(
            "script"=>CONTROLLER_NAME."/main"
        );
        $this->assign('output',$outData);
        $this->assign('surveyInfo',$surveyInfo);
        $this->assign('questionList',$questionList);
        $this->assign('pageName','问卷内容');
        $this->display();
    }

    /**
     * View page of add question
     */
    public function add_question($sid){
        $outData = array(
            "script"=>CONTROLLER_NAME."/main"
        );
        $this->assign('pageName',"添加提问");
        $this->assign('output',$outData);
        $this->display();
    }

    /**
     * View page of modify question and answer
     */
    public function edit_question($id){
        $questionId = $id;
        $questionInfo = M("SurveyQuestion")->where("id=$questionId")->find();
        $answerList = M("SurveyAnswer")->where("q_id=$questionId")->limit(10)->select();
        $outData = array(
            "questionInfo"  =>$questionInfo,
            "answerList"    =>$answerList,
            "script"=>CONTROLLER_NAME."/main"
        );
        $this->assign("output",$outData);
        $this->assign('pageName',"修改提问内容");
        $this->display();
    }

    /**
     * Action add question
     */
    public function a_add_question(){
        if(IS_POST){
            $model = M();
            $model->startTrans();
            $questionData = array(
                "s_id"      =>I("post.sid"),
                "type"      =>I("post.type"),
                "question"  =>I("post.question")
            );
            $questionInsert = M("SurveyQuestion")->data($questionData)->add();
            if($questionInsert){
                $answerData = array();
                foreach($_POST['answer'] as $v){
                    $answerData[] = array(
                        "q_id"      =>$questionInsert,
                        "name"      =>$v
                    );
                }
                $answerInsert = M("SurveyAnswer")->addAll($answerData);
                $backData =array();
                if($answerInsert){
                    $backData['status'] = 1;
                    $backData['msg'] = "操作成功";
                    $backData['jump'] = U('question',array("sid"=>I("post.sid")));
                    $model->commit();
                }else {
                    $backData['status'] = 0;
                    $backData['msg'] = "添加失败";
                    $model->rollback();
                }
            }else {
                $backData['status'] = 0;
                $backData['msg'] = "添加失败";
            }
            $this->ajaxReturn($backData); 
        }
    }

    /**
     * Action update question
     */
    public function a_update_question(){
        if(IS_POST){
            $questionId = I("post.qid");
            $surveyId = I("post.sid");
            $model = M();
            $model->startTrans();
            $questionData = array(
                "type"      =>I("post.type"),
                "question"  =>I("post.question"),
                "sort"      =>I("post.sort")
            );
            $questionUpdate = M("SurveyQuestion")->where("id=$questionId")->data($questionData)->save();

            $backData =array();
            //判断是否有新插入选项
            if(isset($_POST['answer'])){
                $answerData = array();
                foreach($_POST['answer'] as $v){
                    $answerData[] = array(
                        "q_id"      =>$questionId,
                        "name"      =>$v
                    );
                }
                $answerInsert = M("SurveyAnswer")->addAll($answerData);
                if($questionUpdate!==false && $answerInsert){
                    $backData['status'] = 1;
                    $backData['msg'] = "操作成功";
                    // $backData['jump'] = U('question',array("sid"=>I("post.sid")));
                    $model->commit();
                }else {
                    $backData['status'] = 0;
                    $backData['msg'] = "操作失败";
                    $model->rollback();
                }
            }else {
                if($questionUpdate !== false){
                    $backData['status'] = 1;
                    $backData['msg'] = "操作成功";
                    $model->commit();
                }else {
                    $backData['status'] = 0;
                    $backData['msg'] = "操作失败";
                    $model->rollback();
                }
            }
            $this->ajaxReturn($backData);
        }
    }

    /**
     * Action answer oparetion
     * Action delete answer one log
     */
    public function a_del_answer($id){
        $deleteResult = M("SurveyAnswer")->where("id=$id")->delete();
        if($deleteResult){
            $backData = array(
                "status"    =>1,
                "msg"       =>"已删除"
            );
        }else {
            $backData = array(
                "status"    =>0,
                "msg"       =>"删除失败"
            );
        }

        $this->ajaxReturn($backData);
    }

    /**
     * Action update answer
     */
    public function a_update_answer(){

        
        $id = I("get.id");
        $updateData = array(
            "name"  =>I("get.name")
        );
        $update = M("SurveyAnswer")->where("id=$id")->data($updateData)->save();
        if($update !==false){
            $backData = array(
                "status"    =>1,
                "msg"       =>"已删除"
            );
        }else {
            $backData = array(
                "status"    =>0,
                "msg"       =>"服务器错误"
            );
        }
        $this->ajaxReturn($backData);
    }


    /**
     * Delete old thumbnail
     * @param   $id     int product ID
     * @param   $table  string
    */
    protected function del_thumb($id,$table='Survey'){
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
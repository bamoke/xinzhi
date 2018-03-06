<?php
/**
 * Created by PhpStorm.
 * User: joy.wangxiangyin
 * Date: 2017/6/26
 * Time: 16:59
 */

namespace Manage\Controller;
use Manage\Common\Controller\AuthController;

class BookingController extends AuthController
{


    public function index(){
        $mainModel = M("Booking");
        // paging set
        $count = $mainModel->count();
        $page = new \Think\Page($count,10);
        $page->setConfig('next','下一页');
        $page->setConfig('prev','上一页');
        $paging = $page->show();

        $where = "1 = 1";
        $list = $mainModel
            ->alias('B')
            ->field("B.id,B.thumb,B.title,B.description,B.sort,B.price,B.status,O.name as org_name")
            ->join("__ORG__ as O on O.id=B.org_id")
            ->where($where)
            ->order('B.sort desc,B.id desc')->limit($page->firstRow.",".$page->listRows)->select();
        $outData = array(
            'list'      => $list,
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
            'sort'  =>$order_val
        );
        $update = M("Booking")->where("id=$id")->data($updateData)->save();
    }


    /***编辑 View**/
    public function edit($id){
        $bookingInfo = M("Booking")->where(array('id'=>$id))->find();
        $orgList = M('Org')->where('status=1')->limit(50)->select();
        $outData = array(
            'bookingInfo'      => $bookingInfo,
            'orgList'           =>$orgList,
            "script"=>CONTROLLER_NAME."/main"
        );
        $this->assign('output',$outData);
        $this->assign('pageName',"编辑课程");
        $this->display();

    }

    /***View add**/
    public function add(){
        $orgModel = M('Org');
        $orgList = $orgModel->where('status=1')->limit(50)->select();
        $outData = array(
            "script"=>CONTROLLER_NAME."/main",
            "orgList"=>$orgList
        );
        $this->assign('output',$outData);
        $this->assign('pageName','添加预约课程');
        $this->display();
    }



    /***action ****/
    public function a_update(){
        if(IS_POST){
            $id = I("post.id");
            $backData = array();
            $model = D("Booking");
            $result = $model->create($_POST);
            if($result){
                $model->content = $_POST['content'];

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
                        $this->del_thumb($id,'Booking');
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
            if(!is_numeric($_POST['price'])){
                return  $this->ajaxReturn(array("status"=>0,"msg"=>"请填写合法的价格数值"));
            }
            $model = M("Booking");
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

    /***课程表***/
    function lesson($bid){
        $PhaseModel = M("BookingPhase");
        $lessonList = $PhaseModel->field('id,title,lesson_month,lesson_day,buy_num,status')->where(array('booking_id'=>$bid))->limit(0,50)->select();
        $bookingInfo = M("Booking")->field('id,title,thumb,price,status,description')->where(array('id'=>$bid))->find();
        $outData = array(
            "script"=>CONTROLLER_NAME."/main"
        );
        $this->assign('output',$outData);
        $this->assign('lessonList',$lessonList);
        $this->assign('bookingInfo',$bookingInfo);
        $this->assign('pageName',"课程表");
        $this->display();
    }

    /***添加课程周期表***/
    public function addlesson($bid){
        $outData = array(
            "script"=>CONTROLLER_NAME."/main",
            "startMonth"    =>idate("m")
        );
        $this->assign('output',$outData);
        $this->assign('pageName',"添加课程周期");
        $this->display();
    }

    /***修改课程表***/
    public function editlesson($id){
        $lessonInfo = M("BookingPhase")->where(array('id'=>$id))->find();
        $lessonInfo['day'] = explode(',',$lessonInfo['lesson_day']);
        $lessonInfo['month'] = date_parse($lessonInfo['lesson_month'])['month'];
        $outData = array(
            "info"  =>$lessonInfo,
            "script"=>CONTROLLER_NAME."/main"
        );
//        var_dump($lessonInfo);
        $this->assign('output',$outData);
        $this->assign('pageName',"编辑课程周期表");
        $this->display();
    }



    /***保存课程章节内容***/
    function a_add_lesson(){
        if(IS_POST){
            $curModel = M("BookingPhase");
            $month = I("post.month")>9?I("post.month"):'0'.I("post.month");
            $month = idate("Y").'-'.$month;
            $dayArr = explode(',',I('post.day'));
            sort($dayArr);
            $lastDay = (int)$dayArr[count($dayArr)-1];
            $insertData=array(
                'title'         =>I("post.title"),
                "booking_id"    =>I("post.bid"),
                "lesson_month"  =>$month,
                "lesson_day"    =>implode(',',$dayArr),
                "deadline"      =>$month . '-' . ($lastDay>9 ? $lastDay : '0'.$lastDay),
                "status"        =>I("post.status")
            );
            $backData =array();
            $add = $curModel->data($insertData)->add();
            if($add){
                $backData['status'] = 1;
                $backData['msg'] = "保存成功";
                $backData['jump'] = U('lesson',array('bid'=>I('post.bid')));
            }else {
                $backData['status'] = 0;
                $backData['msg'] = $curModel->getError();
            }

            $this->ajaxReturn($backData);
        }

    }




    /***保存课程周期表修改***/
    public function a_update_lesson(){
        if(IS_POST){
            $backData = array();
            $curModel = M("BookingPhase");
            $month = I("post.month")>9?I("post.month"):'0'.I("post.month");
            $month = idate("Y").'-'.$month;
            $dayArr = explode(',',I('post.day'));
            sort($dayArr);
            $lastDay = (int)$dayArr[count($dayArr)-1];
            $updateData=array(
                'title'         =>I("post.title"),
                "lesson_month"  =>$month,
                "lesson_day"    =>implode(',',$dayArr),
                "deadline"      =>$month . '-' . ($lastDay>9 ? $lastDay : '0'.$lastDay),
                "status"        =>I("post.status")
            );

            $update = $curModel->data($updateData)->where("id=".I('post.id'))->save();
            if($update !== false){
                $backData['status'] = 1;
                $backData['msg'] = "修改成功";
                $backData['jump'] = U('lesson',array('bid'=>I('post.bid')));
            }else {
                $backData['status'] = 0;
                $backData['msg'] = $curModel->getError();
            }
            $this->ajaxReturn($backData);

        }
    }

    /****修改课程状态***/
    public function change_course_status($id,$status){
        $model = M("Booking");
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


    /***预约记录**/
    public function logs(){
        $where = "1";
        if(isset($_GET['bid'])){
            $where .=' and L.booking_id = '.$_GET['bid'];
        }
        if(isset($_GET['phase_id']) && $_GET['phase_id'] != ''){
            $where .=' and L.phase_id = '.$_GET['phase_id'];
        }

        $curModel = M("BookingLog");
        $count = $curModel->alias("L")->where($where)->count();
        $page = new \Think\Page($count,10);
        $page->setConfig('next','下一页');
        $page->setConfig('prev','上一页');
        $paging = $page->show();

        $list = $curModel
            ->alias("L")
            ->where($where)
            ->field("L.*,concat(B.title,'-',BP.title) as title")
            ->join("__BOOKING__ as B on L.booking_id = B.id")
            ->join("__BOOKING_PHASE__ as BP on L.phase_id = BP.id")
            ->order("id desc")
            ->limit($page->firstRow.",".$page->listRows)
            ->select();
        $outData = array(
            'list'      => $list,
            'paging'    => $paging
        );
        $this->assign('output',$outData);
        $this->display();
    }


    /**
     * Delete old thumbnail
     * @param   $id     int product ID
     * @param   $table  string
    */
    protected function del_thumb($id,$table='Booking'){
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
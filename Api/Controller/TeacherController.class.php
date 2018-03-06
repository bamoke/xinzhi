<?php
namespace Api\Controller;
use Think\Controller;
class TeacherController extends Controller {

    public function index($id){
//        $banner= M('Banner')->where('position_key=0')->select();
        $teacherInfo = M("Teacher")->alias("T")->field("*,(select count(*) from x_course where teacher_id = T.id) as course_total")->where("T.id=$id")->fetchSql(false)->find();
        $courseList = M("Course")->field("id,title,description,thumb,isfree,price,study_num,comment_num")->where('status=1')->order('id desc,recommend desc')->limit('10')->select();
        if($teacherInfo){
            $teacherInfo['introduce'] = str_replace('src="/Upload/images','src="http://www.xinzhinetwork.com/Upload/images',$teacherInfo['introduce']);
        }
        $backData = array(
            "errorCode" =>10000,
            "errorMsg"  =>"success",
            "teacherInfo"     =>$teacherInfo,
            "courseList"    =>$courseList
        );
        $this->ajaxReturn($backData);
    }

}
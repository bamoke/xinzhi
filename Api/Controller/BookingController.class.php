<?php
namespace Api\Controller;
use Think\Controller;
class BookingController extends Controller {
    //booking list
    public function vlist($page=1){
        $curModel = M("Booking");
        $condition = array(
            'status'    =>1
        );
        $list = $curModel->field('id,title,thumb,price')->where($condition)->page($page,10)->order("sort,id desc")->select();
        $backData = array(
            "errorCode" =>10000,
            "errorMsg"  =>"success",
            "list"    =>$list,
        );
        $this->ajaxReturn($backData);
    }

    //booking detail
    public function detail($id){
        $curModel = M("Booking");
        $info = $curModel->where("id=$id")->find();
        $listCondition = array(
            "booking_id"    =>$id,
            "deadline"      =>array('gt',date("Y-m-d",time())),
            "status"    =>1
        );
        $phaseList = M("BookingPhase")->where($listCondition)->order("lesson_month desc")->page(1,10)->select();
        $backData = array(
            "errorCode" =>10000,
            "errorMsg"  =>"success",
            "info"      =>$info,
            "phaseList" =>$phaseList
        );
        $this->ajaxReturn($backData);
    }

    //预约课程阶段详情
    public function phase($id){
        $info = M("BookingPhase")->field("id,lesson_month,lesson_day")->where("id=$id")->find();

        if($info){
            $lessonDay = explode(",",$info['lesson_day']);
            $curDay = idate("d");
            $curMonth = date("Y-m",time());
            $newDay = array();
            foreach($lessonDay as $val){
                if($info['lesson_month'] > $curMonth){
                    $newDay[] = $info['lesson_month']."-".($val>9?$val:"0".$val);
                }elseif($info['lesson_month'] == $curMonth){
                    if($val > $curDay){
                        $newDay[] = $info['lesson_month']."-".($val>9?$val:"0".$val);
                    }
                }
            }
            $info['lesson_day'] = $newDay;
            $backData = array(
                "errorCode" =>10000,
                "errorMsg"  =>"success",
                "info"      =>$info
            );
        }else {
            $backData = array(
                "errorCode" =>10000,
                "errorMsg"  =>"success",
                "info"      =>$info
            );
        }
        $this->ajaxReturn($backData);
    }

    public function index(){
        $banner= M('Banner')->field('id,img as image,url')->where('position_key=1 and status=1')->order('id desc')->limit(4)->select();
        $columnList = M("Columnist")->field("id,title,description,thumb,isfree,price,subscribers,article_num")->where('status=1')->order('recommend desc,id desc')->limit('4')->select();
        $courseList = M("Course")->field("id,title,description,thumb,isfree,price,period,comment_num")->where('status=1')->order('recommend desc,order_no,id desc')->limit('4')->select();
        //字符截取
        $String = new \Org\Util\String();
        foreach($columnList as $k=>$v){
            $columnList[$k]['title'] = $String->msubstr($v['title'],0,15,$charset="utf-8", $suffix=false);
            $columnList[$k]['description'] = $String->msubstr($v['description'],0,15,$charset="utf-8", $suffix=false);
        }
        foreach($courseList as $k=>$v){
            $courseList[$k]['title'] = $String->msubstr($v['title'],0,15,$charset="utf-8", $suffix=false);
            $courseList[$k]['description'] = $String->msubstr($v['description'],0,15,$charset="utf-8", $suffix=false);
        }

        $backData = array(
            "errorCode" =>10000,
            "errorMsg"  =>"success",

            "banner"    =>$banner,
            "columnist"     =>$columnList,
            "courseList"    =>$courseList
        );
        $this->ajaxReturn($backData);
    }

    /***预约报名***/
    public function enroll(){
        if(IS_POST){
            $Account = A("Account");
            $memberId = $Account->getMemberId();
            $insertData = array(
                "member_id"     =>$memberId,
                "booking_id"    =>I("post.bid"),
                "phase_id"      =>I("post.id"),
                "realname"      =>I("post.realname"),
                "gender"        =>I("post.gender"),
                "phone"         =>I("post.phone"),
                "day"           =>I("post.day"),
                "notes"         =>I("post.notes")
            );
            $result = M("BookingLog")->data($insertData)->fetchSql(false)->add();
            if($result){
                $backData = array(
                    "errorCode" => 10000,
                    "errorMsg" => "success"
                );
            }else {
                $backData = array(
                    "errorCode" => 10001,
                    "errorMsg" => "保存错误"
                );
            }
            $this->ajaxReturn($backData);
        }
    }

}
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
        $info = $curModel
        ->where("id=$id")
        ->find();

        // 重新计算优惠信息
        if($info['has_yh'] == 1 && strtotime($info['yh_limit']) <= time()){
            $info['has_yh']=0;
        }
        // 设置缩略图URL
        $info['thumb'] = XZSS_BASE_URL."/thumb/".$info['thumb'];

        // 计算报名是否截至
        $enroll_end = 0;
        if(strtotime($info['enroll_limit']) <= time()){
            $enroll_end = 1;
        }

        // 重新计算已报名人数
        $enrollNum = $info['enroll_person'];
        $recruitNum =$info['person_limit'];
        if($enrollNum == 0) {
            $info['enroll_person'] = ceil($recruitNum/2);
        }elseif($enrollNum <= floor($recruitNum/2)) {
            $info['enroll_person'] = ceil($recruitNum/2) + $info['enroll_person'];
        }

        // 获取机构信息
        $orgInfo = M("Org")
        ->field("id,name,logo")
        ->where(array('id'=>$info['org_id']))
        ->find();
        $orgInfo['logo'] = XZSS_BASE_URL."/thumb/".$orgInfo['logo'];

        // 查看是否已经报名
        $Account = A("Account");
        $memberId = $Account->getMemberId();
        $enrollLog = M("BookingLog")
        ->where(array('booking_id'=>$id,'member_id'=>$memberId))
        ->count();

        // 查看收藏记录
        $isCollected = M("Collection")
        ->where(array("type"=>3,"pro_id"=>$id,"member_id"=>$memberId))
        ->count();

        $backData = array(
            "code" =>200,
            "msg"  =>"success",
            "data"=>array(
                "info"      =>$info,
                "orgInfo"   =>$orgInfo,
                "enrollEnd" =>!!$enroll_end,
                "isHas" =>!!$enrollLog,
                "isCollected"=>!!$isCollected
            )
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
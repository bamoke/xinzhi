<?php
namespace Api\Controller;
use Think\Controller;
class IndexController extends Controller {

    public function index(){
        $thumbUrl = XZSS_BASE_URL .'/thumb/';
        $bannerUrl = XZSS_BASE_URL .'/banner/';
        $banner= M('Banner')->field("id,title as name,concat('$bannerUrl',img) as img,url")->where('position_key=1 and status=1')->order('id desc')->limit(4)->select();
        $columnList = M("Columnist")->field("id,title,description,concat('$thumbUrl',thumb) as thumb,isfree,price,subscribers,article_num")->where('status=1')->order('recommend desc,id desc')->limit('4')->select();

        $courseList = M("Course")->field("id,title,description,concat('$thumbUrl',thumb) as thumb,isfree,price,period,study_num,has_yh,yh_limit,yh_price")->where('status=1')->order('recommend desc,sort,id desc')->limit('4')->select();
        $article = M("News")->field("id,title,concat('$thumbUrl',img) as thumb")->where("status=1")->limit("4")->select();
        $booking = M("Booking")
        ->field("id,title,concat('$thumbUrl',thumb) as thumb,LEFT(description,20)as description,price,enroll_person,person_limit")
        ->where("status=1")
        ->limit("4")
        ->select();
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

        // 重新输出线下课程已报名人数
        if(count($booking)){
            foreach($booking as $key=>$val){
                $tempLimitNum = ceil($val['person_limit']/2);
                if($val['enroll_person'] < $tempLimitNum){
                    $booking[$key]['enroll_person'] = $tempLimitNum + $val['enroll_person'];
                }
            }
        }

        // 重新计算课程价格
        if(count($courseList)) {
            foreach($courseList as $key=>$val) {
                if($val['has_yh'] == 1){
                    $oldYhLimit = strtotime($courseInfo['yh_limit']);
                    if($oldYhLimit <= time()){
                        $val['has_yh'] = 0;
                    }
                }
            }
        }

        $backData = array(
            "code" =>200,
            "msg"  =>"success",
            "data" =>array(
                "banner"        =>$banner,
                "column"     =>$columnList,
                "course"    =>$courseList,
                "article"      =>$article,
                "booking"        =>$booking,
                "showBooking"   =>true
                )
        );
        $this->ajaxReturn($backData);
    }

}
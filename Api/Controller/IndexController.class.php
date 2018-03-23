<?php
namespace Api\Controller;
use Think\Controller;
class IndexController extends Controller {

    public function index(){
        $banner= M('Banner')->field('id,img as image,url')->where('position_key=1 and status=1')->order('id desc')->limit(4)->select();
        $columnList = M("Columnist")->field("id,title,description,thumb,isfree,price,subscribers,article_num")->where('status=1')->order('recommend desc,id desc')->limit('4')->select();
        $courseList = M("Course")->field("id,title,description,thumb,isfree,price,period,comment_num")->where('status=1')->order('recommend desc,order_no,id desc')->limit('4')->select();
        $survey = M("Survey")->field("id,thumb")->where("status=1")->find();
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
            "courseList"    =>$courseList,
            "survey"        =>$survey
        );
        $this->ajaxReturn($backData);
    }

}
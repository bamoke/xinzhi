<?php
namespace Api\Controller;
use Think\Controller;
class CourseController extends Controller {

    /**
     * 课程列表
    */
    public function vlist($page=1,$cid=0){
        $cateList = M('MainCate')->where('pid !=0 and identification = "course"')->fetchSql(false)->select();
        $courseWhere = array(
            "status"    =>1
        );
        if($cid){
            $courseWhere['cate_id'] = intval($cid);
        }

        $courseList = M("Course")
            ->field('id,title,thumb,description,isfree,price,period,comment_num')
            ->where($courseWhere)
            ->page($page,15)
            ->order('recommend desc,order_no,id desc')
            ->select();
        //字符截取
        $String = new \Org\Util\String();
        foreach($courseList as $k=>$v){
            $courseList[$k]['title'] = $String->msubstr($v['title'],0,15,$charset="utf-8", $suffix=false);
            $courseList[$k]['description'] = $String->msubstr($v['description'],0,15,$charset="utf-8", $suffix=false);
        }
        if($cateList && $courseList !==false){
            $backData = array(
                "errorCode"     =>10000,
                "errorMsg"      =>"success",
                "cateList"      =>$cateList,
                "courseList" =>$courseList
            );
        }else {
            $backData = array(
                "errorCode"     =>10001,
                "errorMsg"      =>"数据读取错误"
            );
        }
        $this->ajaxReturn($backData);

    }

    /**
     * 课程详情
    */
    public function detail($id){
        // 1. 获取数据
        $dataInfo =array();
        $dataInfo = M('Course')
            ->alias("C")
            ->field('C.*,T.name as tc_name,T.position as tc_position,T.avatar as tc_avatar')
            ->join("__TEACHER__ as T on C.teacher_id = T.id")
            ->where("C.id = $id")
            ->fetchSql(false)
            ->find();
        if($dataInfo){
            $dataInfo['content'] = str_replace('src="/Upload/images','src="http://www.xinzhinetwork.com/Upload/images',$dataInfo['content']);
        }
        $backData = array(
            "errorCode" =>10000,
            "errorMsg"  =>"success",
            "courseDetail"  =>$dataInfo
        );
        // 2. 查看是否已购买
        $Account = A("Account");
        $memberId = $Account->getMemberId();
        $where = array(
            'member_id' =>$memberId,
            "type"      =>2,
            "pro_id"    =>$id
        );
        $myCourse = M("MyGoods")->where($where)->find();
        if($myCourse){
            $backData['hasCourse'] = !! 1;
        }else {
            $backData['hasCourse'] = !! 0;
        }

        $this->ajaxReturn($backData);
    }

    /**
     * 课程目录
     * @param int   $cid    课程ID
     * @param int   $page   页码
    */
    public function section($cid,$page=1){
        $sectionList = M('CourseSection')->where(array('course_id'=>$cid))->order('id')->page($page,20)->select();
        if($sectionList !==false){
            $backData = array(
                "errorCode"     =>10000,
                "errorMsg"      =>"success",
                "sectionList" =>$sectionList
            );
        }else {
            $backData = array(
                "errorCode"     =>10001,
                "errorMsg"      =>"数据读取错误"
            );
        }
        $this->ajaxReturn($backData);
    }

    /**
     * 课程章节详情
    */
    public function sectiondetail($id){
        //更新阅读量
        $updateSql = "update __COURSE_SECTION__ set `view_num`=view_num+1 where id=".$id;
        $updateResult = M()->execute($updateSql);

        //获取章节内容
        $result = M("CourseSection")
        ->where(array('id'=>$id))
        ->find();
        $nextInfo = M("CourseSection")->field("id,type")->where("id>$id and course_id = ".$result['course_id'])->order("id asc")->find();
        $prevInfo = M("CourseSection")->field("id,type")->where("id<$id and course_id = ".$result['course_id'])->order("id desc")->find();
        if($result && $updateResult){
            $result['content'] = str_replace('src="/Upload/images','src="http://www.xinzhinetwork.com/Upload/images',$result['content']);
            $backData = array(
                "errorCode"     =>10000,
                "errorMsg"      =>"success",
                "next"          =>$nextInfo,
                "prev"          =>$prevInfo,
                "sectioninfo"   =>$result
            );
        }else{
            $backData = array(
                "errorCode"     =>10001,
                "errorMsg"      =>"数据读取错误"
            );
        }
        $this->ajaxReturn($backData);

    }

}
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
                "code"     =>200,
                "msg"      =>"success",
                "data"  =>array(
                    "cateList"   =>$cateList,
                    "courseList" =>$courseList
                )
            );
        }else {
            $backData = array(
                "code"     =>13001,
                "msg"      =>"数据读取错误"
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


        $hasCourse = false;
        $isCollection = false;
        $Account = A("Account");
        $memberId = $Account->getMemberId();
        if($memberId) {
            // 2.1. 查看是否已购买
            $where = array(
                'member_id' =>$memberId,
                "type"      =>2,
                "pro_id"    =>$id
            );
            $myCourse = M("MyGoods")->where($where)->find();
            if($myCourse) {
                $hasCourse = true;
            }
            // 2.2 查看是否已收藏
            $where = array(
                'member_id' =>$memberId,
                "type"      =>2,
                "pro_id"    =>$id
            );
            $collectionInfo = M("Collection")->where($where)->find();
            if($collectionInfo) {
                $isCollection = true;
            }
        }


        $backData = array(
            "code" =>200,
            "msg"  =>"success",
            "data" =>array(
                "courseDetail"  =>$dataInfo,
                "hasCourse"     =>$hasCourse,
                "isCollection"  =>$isCollection
            )
        );
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
                "code"     =>200,
                "msg"      =>"success",
                "data"      =>array(
                    "sectionList" =>$sectionList
                )
            );
        }else {
            $backData = array(
                "code"     =>13001,
                "msg"      =>"数据读取错误"
            );
        }
        $this->ajaxReturn($backData);
    }

    /**
     * 课程章节详情
    */
    public function sectiondetail($id){
        $memberId = A("Account")->getMemberId();
        //更新阅读量
        $updateSql = "update __COURSE_SECTION__ set `view_num`=view_num+1 where id=".$id;
        $updateResult = M()->execute($updateSql);

        //获取章节内容
        $result = M("CourseSection")
        ->where(array('id'=>$id))
        ->find();
        $nextInfo = M("CourseSection")->field("id,type")->where("id>$id and course_id = ".$result['course_id'])->order("id asc")->find();
        $prevInfo = M("CourseSection")->field("id,type")->where("id<$id and course_id = ".$result['course_id'])->order("id desc")->find();
        $courseInfo = M("Course")->field("thumb")->where("id=".$result['course_id'])->find();
        //查询是否已经收藏
        $isCollection = M("Collection")->where("type=2 and member_id=$memberId and pro_id=$id and status=1")->count();
        if($result && $updateResult){
            $result['content'] = str_replace('src="/Upload/images','src="http://www.xinzhinetwork.com/Upload/images',$result['content']);
            $result['source'] = urlencode($result['source']);
            $backData = array(
                "code"     =>200,
                "msg"      =>"success",
                "data"     =>array(
                    "next"          =>$nextInfo,
                    "prev"          =>$prevInfo,
                    "sectioninfo"   =>$result,
                    "thumb"         =>$courseInfo['thumb'],
                    "isCollection"  =>!!$isCollection
                )
            );
        }else{
            $backData = array(
                "code"     =>13001,
                "msg"      =>"数据读取错误"
            );
        }
        $this->ajaxReturn($backData);

    }

}
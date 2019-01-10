<?php
namespace Api\Controller;
use Think\Controller;
class CourseController extends Controller {

    /**
     * 课程列表
    */
    public function vlist(){
        $thumbUrl = XZSS_BASE_URL .'/thumb/';
        $cateList = M('MainCate')->where('pid !=0 and identification = "course"')->fetchSql(false)->select();
        $orderBy = "C.recommend desc,C.sort,C.id desc";
        $courseCondition = array(
            "C.status"=>1
        );
        if($cid){
            $courseCondition['C.cate_id'] = intval($cid);
        }
        if(!empty($_GET['tag'])){
            $tag = I("get.tag");
            if($tag == 'hot'){
                $orderBy = 'C.study_num desc,C.recommend desc,C.sort,C.id desc';
            }elseif($tag== 'free'){
                $courseCondition['isfree'] = 1;
            }elseif($tag=='latest'){
                $orderBy = 'C.id desc';
            }
        }
        $page = I("get.page/d",1);
        $pageSize = 10;
        $total = M("Course")->alias("C")->where($courseCondition)->count();        
        $courseList = M("Course")
        ->alias("C")
            ->field("C.id,C.title,concat('$thumbUrl',C.thumb) as thumb,C.description,C.isfree,C.price,C.period,C.study_num,CATE.name as cate_name")
            ->join("__MAIN_CATE__ as CATE on C.cate_id=CATE.id")
            ->where($courseCondition)
            ->page($page,$pageSize)
            ->order($orderBy)
            ->fetchSql(false)
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
                    "list" =>$courseList,
                    "page"=>$page,
                    "hasMore"=>$total>$page*$pageSize
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
    public function detail(){
        if(empty($_GET['id'])) {
            $backData = array(
                "code" => 10001,
                "msg" => "参数错误"
            );
            return $this->ajaxReturn($backData);
        }
        $thumbUrl = XZSS_BASE_URL .'/thumb/';
        $courseId = I("get.id");
        // 1. 获取数据
        $courseInfo = M('Course')
            ->alias("C")
            ->field("C.*,CONCAT('$thumbUrl',C.thumb) as thumb,CATE.name as cate_name")
            ->join("__MAIN_CATE__ as CATE on C.cate_id = CATE.id")
            ->where("C.id = $courseId")
            ->fetchSql(false)
            ->find();
        if($dataInfo){
            $courseInfo['content'] = str_replace('src="/Upload/images','src='.XZSS_BASE_URL.'"/images',$courseInfo['content']);
        }

        // 1.1 计算优惠是否过期
        if($courseInfo['has_yh'] == 1){
            $oldYhLimit = strtotime($courseInfo['yh_limit']);
            if($oldYhLimit <= time()){
                $courseInfo['has_yh'] = 0;
            }
        }
        $orgInfo = M("Org")->field("*,concat('$thumbUrl',logo) as logo")->where(array("id"=>$courseInfo['org_id']))->find();

        $hasCourse = false;
        $isCollected = false;
        $Account = A("Account");
        $memberId = $Account->getMemberId();
        if($memberId) {
            // 2.1. 查看是否已购买
            $where = array(
                'member_id' =>$memberId,
                "type"      =>2,
                "pro_id"    =>$courseId
            );
            $myCourse = M("MyGoods")->where($where)->fetchSql(false)->find();
            if($myCourse) {
                $hasCourse = true;
            }
            // 2.2 查看是否已收藏
            $collectionInfo = M("Collection")->where($where)->find();
            if($collectionInfo) {
                $isCollected = true;
            }
        }
        $backData = array(
            "code" =>200,
            "msg"  =>"success",
            "data" =>array(
                "info"  =>$courseInfo,
                "orgInfo"=>$orgInfo,
                "isHas"     =>$hasCourse,
                "isCollected"  =>$isCollected
            )
        );
        $this->ajaxReturn($backData);
    }

    /**
     * 课程目录
     * @param int   $courseid    课程ID
     * @param int   $page   页码
    */
    public function section(){
        if(empty($_GET['courseid'])){
            $backData = array(
                "code" => 10001,
                "msg" => "参数错误"
            );
            return $this->ajaxReturn($backData);
        }
        $videoBaseUrl = OSS_BASE_URL . "/video/";
        $page = I("get.page/d",1);
        $courseId = I("get.courseid");
        $sectionList = M('CourseSection')
        ->field("*,CONCAT('$videoBaseUrl',source) as video")
        ->where(array('course_id'=>$courseId))
        ->order('id')
        ->page($page,50)
        ->select();
        if($sectionList !==false){
            $backData = array(
                "code"     =>200,
                "msg"      =>"success",
                "data"      =>array(
                    "list" =>$sectionList
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
     * 课程学习人次
     */
    public function study(){
        if(empty($_GET['courseid'])){
            $backData = array(
                "code" => 10001,
                "msg" => "参数错误"
            );
            return $this->ajaxReturn($backData);  
        }
        $courseId = I("get.courseid/d");
        // 判断是否已经是学员
        $Account = A("Account");
        $memberId = $Account->getMemberId();
        $dataArr = array(
            "course_id"=>$courseId,
            "member_id"=>$memberId
        );
        $isStudent = M("CourseStudent")->where($dataArr)->count();
        if(!$isStudent) {
            $insert = M("CourseStudent")->data($dataArr)->add();
            $update = M("Course")->where(array('id'=>$courseId))->setInc('study_num');
        }
        $backData = array(
            "code"     =>200,
            "msg"      =>"success"
        );
    $this->ajaxReturn($backData);
    }

}
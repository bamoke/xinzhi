<?php
namespace Api\Controller;
use Api\Common\Controller\AuthController;
class HomeController extends AuthController {


    public function index(){
        $memberId = $this->uid;
        $userInfo = M("Member")
        ->alias("M")
        ->field("M.id,MI.nickname,MI.avatar")
        ->join("__MEMBER_INFO__ as MI on M.id=MI.member_id","LEFT")
        ->where("M.id = $memberId")
        ->fetchSql(false)
        ->find();

        // 数据统计
        $totalCondition = array(
            "member_id" =>$memberId
        );
        $columnTotal = M("ColumnistStudent")->where($totalCondition)->count();
        $courseTotal = M("Course")->where($totalCondition)->count();
        $bookingTotal = M("BookingLog")->where($totalCondition)->count();
        $totalInfo = array(
            "column"    =>$columnTotal,
            "course"    =>$courseTotal,
            "booking"   =>$bookingTotal
        );


        $backData = array(
            "code" =>200,
            "msg"  =>"success",
            "data"      =>array(
                "userInfo"  =>$userInfo,
                "totalInfo" =>$totalInfo
            )
        );
        $this->ajaxReturn($backData);
    }

    /**
     * 我的专栏
    **/
    function mycolumn(){
        $thumbUrl = XZSS_BASE_URL .'/thumb/';
        $memberId = $this->uid;
        $page = I("get.page/d",1);
        $pageSize=10;
        $condition = array(
            "CS.member_id" =>$memberId
        );
        $total = M("ColumnistStudent")->alias("CS")->where($condition)->count();
        $list = M("ColumnistStudent")
        ->field("CS.deadline,C.id,C.title,CONCAT('$thumbUrl',C.thumb) as thumb")
        ->alias("CS")
        ->join("__COLUMNIST__ as C on CS.column_id = C.id")
        ->page($page,$pageSize)
        ->order("CS.id desc")
        ->select();

        $backData = array(
            "code"=>200,
            "msg"   =>"success",
            "data"  =>array(
                "page"  =>$page,
                "total" =>$total,
                "hasMore"   =>$total > $page*$pageSize,
                "list"  =>$list
            )
        );
        $this->ajaxReturn($backData);
    }

    /**
     * 我的课程
     **/
    function mycourse($page=1){
        $thumbUrl = XZSS_BASE_URL .'/thumb/';
        $memberId = $this->uid;
        $page = I("get.page/d",1);
        $pageSize=10;
        $condition = array(
            "CS.member_id" =>$memberId
        );
        $total = M("CourseStudent")->alias("CS")->where($condition)->count();
        $list = M("CourseStudent")
        ->field("C.id,C.title,CONCAT('$thumbUrl',C.thumb) as thumb,C.study_num")
        ->alias("CS")
        ->join("__COURSE__ as C on CS.course_id = C.id")
        ->page($page,$pageSize)
        ->order("CS.id desc")
        ->select();

        $backData = array(
            "code"=>200,
            "msg"   =>"success",
            "data"  =>array(
                "page"  =>$page,
                "total" =>$total,
                "hasMore"   =>$total > $page*$pageSize,
                "list"  =>$list
            )
        );
        $this->ajaxReturn($backData);

    }

    /**
     * 我的预约课程
     **/
    function mybooking(){
        $thumbUrl = XZSS_BASE_URL .'/thumb/';
        $memberId = $this->uid;
        $page = I("get.page/d",1);
        $pageSize=10;
        $condition = array(
            "BL.member_id" =>$memberId
        );
        $total = M("BookingLog")->alias("BL")->where($condition)->count();
        $list = M("BookingLog")
        ->field("B.id,B.title,CONCAT('$thumbUrl',B.thumb) as thumb,BL.create_time")
        ->alias("BL")
        ->join("__BOOKING__ as B on BL.booking_id = B.id")
        ->page($page,$pageSize)
        ->order("BL.id desc")
        ->select();

        $backData = array(
            "code"=>200,
            "msg"   =>"success",
            "data"  =>array(
                "page"  =>$page,
                "total" =>$total,
                "hasMore"   =>$total > $page*$pageSize,
                "list"  =>$list
            )
        );
        $this->ajaxReturn($backData);
    }



    /**
     * 我的评论
    **/

    public function mycomment($page=1){
        $memberId = A("Account")->getMemberId();
        $list = M("Comment")
            ->alias("C")
            ->field("C.*,M.nickname,M.avatar,R.content as reply,R.create_time as reply_time")
            ->join("__MEMBER_INFO__ as M on M.member_id=C.member_id")
            ->join("LEFT JOIN __REPLY__ as R on R.comment_id=C.id")
            ->where(" C.member_id=$memberId")
            ->page($page,15)
            ->order('C.id desc')
            ->select();
        if($list !== false){
            $backData = array(
                "errorCode" =>10000,
                "errorMsg"  =>"success",
                "list"      =>$list
            );
        }else {
            $backData = array(
                "errorCode" =>10001,
                "errorMsg"  =>"数据错误"
            );
        }
        $this->ajaxReturn($backData);
    }
   

    /**
     * 我的余额记录
     */
    public function mybalance($page=1){
        $Account  = A("Account");
        $memberId = $Account->getMemberId();
        $list = M("BalanceLog")->where("member_id=$memberId")->page($page,21)->order("id desc")->select();
        $balance = $Account->fetchBalance($memberId);
        $backData = array(
            "errorCode" =>10000,
            "errorMsg"  =>"success",
            "list"      =>$list,
            "balance"   =>$balance
        );
        $this->ajaxReturn($backData);
    }

    /**
     * 建议
     */
    public function feedback(){
        if(IS_POST){
            $model = M("Feedback");
            $insertData = array(
                "member_id"     =>$this->uid,
                "content"       =>I("post.content"),
                "contact"       =>I("post.contact")
            );
            $insertResult = $model->data($insertData)->add();
            if(!$insertResult){
                $backData = array(
                    "code" =>13001,
                    "msg"  =>"系统错误请稍后再试"
                );
                $this->ajaxReturn($backData);
            }else {
                $backData = array(
                    "code" =>200,
                    "msg"  =>"success"
                );
                $this->ajaxReturn($backData);
            }
        }
    }



}
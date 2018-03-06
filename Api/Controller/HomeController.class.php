<?php
namespace Api\Controller;
use Think\Controller;
class HomeController extends Controller {

    public function index(){
//        $banner= M('Banner')->where('position_key=0')->select();
        $columnList = M("Columnist")->field("id,title,description,thumb,isfree,price,subscribers")->where('status=1')->order('id desc,recommend desc')->limit('4')->select();
        $courseList = M("Course")->field("id,title,description,thumb,isfree,price,study_num")->where('status=1')->order('id desc,recommend desc')->limit('4')->select();
        $backData = array(
            "errorCode" =>10000,
            "errorMsg"  =>"success",

//            "banner"    =>$banner,
            "columnist"     =>$columnList,
            "courseList"    =>$courseList
        );
        $this->ajaxReturn($backData);
    }

    /**
     * 我的专栏
    **/
    function mycolumnist($page=1){
        $account = A("Account");
        //检测用户注册以及认证状态
        $memberId = $account->getMemberId();
        $where = 'MG.member_id = '.$memberId.' and MG.type = 1';
        $columnistList = M("MyGoods")
            ->alias("MG")
            ->field('MG.pro_id as id,from_unixtime(MG.start_time,"%Y-%m-%d") as start_time,from_unixtime(MG.end_time,"%Y-%m-%d") as end_time,C.thumb,C.title,C.subscribers,(select count(*) from x_columnist_article where columnist_id = MG.pro_id and status =1) as article_num')
            ->where($where)
            ->join('__COLUMNIST__ as C on MG.pro_id=C.id')
            ->page($page,10)
            ->fetchSql(false)
            ->select();
        if($columnistList !==false){
            $backData = array(
                "errorCode"     =>10000,
                "errorMsg"      =>'success',
                "list"          =>$columnistList
            );
        }else {
            $backData = array(
                "errorCode"     =>'100001',
                "errorMsg"      =>'数据读取错误'
            );
        }
        $this->ajaxReturn($backData);
    }

    /**
     * 我的课程
     **/
    function mycourse($page=1){
        $account = A("Account");
        $memberId = $account->getMemberId();
        $where = 'MG.member_id = '.$memberId.' and MG.type = 2';
        $courseList = M("MyGoods")
            ->alias("MG")
            ->field('MG.pro_id as id,C.thumb,C.title')
            ->where($where)
            ->join('__COURSE__ as C on MG.pro_id=C.id')
            ->page($page,10)
            ->select();
        if($courseList !== false){
            $backData = array(
                "errorCode"     =>10000,
                "errorMsg"      =>'success',
                "list"          =>$courseList
            );
        }else {
            $backData = array(
                "errorCode"     =>'100001',
                "errorMsg"      =>'数据读取错误'
            );
        }
        $this->ajaxReturn($backData);
    }

    /**
     * 我的课程
     **/
    function mybooking($page=1){
        $account = A("Account");
        $memberId = $account->getMemberId();
        $where = 'MG.member_id = '.$memberId.' and MG.type = 4';
        $list = M("MyGoods")
            ->alias("MG")
            ->field('MG.pro_id as id,BP.booking_id as bid,B.thumb,CONCAT(B.title,"-",BP.title) as title,BL.day,BL.time')
            ->where($where)
            ->join('__BOOKING_PHASE__ as BP on MG.pro_id=BP.id')
            ->join("__BOOKING__ as B on BP.booking_id = B.id")
            ->join("__BOOKING_LOG__ as BL on BP.id=BL.phase_id","LEFT")
            ->order("MG.id desc")
            ->page($page,10)
            ->select();
        if($list !== false){
            $backData = array(
                "errorCode"     =>10000,
                "errorMsg"      =>'success',
                "list"          =>$list
            );
        }else {
            $backData = array(
                "errorCode"     =>'100001',
                "errorMsg"      =>'数据读取错误'
            );
        }
        $this->ajaxReturn($backData);
    }

    /**
     * 我的订单
     * @param int type 0:所有;1:未支付;2:已支付
     * @param int page
    */
    public function myorders($type=0,$page=1){
        $account = A("Account");
        $memberId = $account->getMemberId();
        $curModel =M("Orders");
        $orderWhere = array(
            "member_id" =>$memberId
        );
        if($type > 0 ){
            $orderWhere['status'] = $type;
        }else {
            $orderWhere['status'] = array('GT',0);
        }
        $ordersList = $curModel->where($orderWhere)->order('id desc')->page($page,15)->fetchSql(false)->select();
        $total = $curModel->where($orderWhere)->count();
        if($ordersList !==false){
            $list = array();
            foreach($ordersList as $v){
                $list[] = array(
                    "id"            =>$v['id'],
                    "order_num"     =>$v['order_num'],
                    "status"        =>$v['status'],
                    "amount"        =>$v['amount'],
                    "prolist"       =>unserialize($v['goods'])
                );
            }
            $backData = array(
                "errorCode" =>10000,
                "errorMsg"  =>"success",
                "total"     =>$total,
                "list"      =>$list
            );
        }else {
            $backData = array(
                "errorCode" =>10001,
                "errorMsg"  =>"数据获取错误"
            );
        }
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
     * 我的测试首页
    **/
    public function mytest(){
        $memberId = A("Account")->getMemberId();
        $curModel = M("TestsLogs");
        $testList = $curModel->field('score')->where(array("member_id"=>$memberId))->select();
        $backData = array(
            "errorCode" =>10000,
            "errorMsg"  =>"success",
            "total" =>count($testList),
            "level_a"   =>0,
            "level_b"   =>0,
            "level_c"   =>0,
            "level_d"   =>0
        );
        foreach ($testList as $k=>$v){
            if($v['score'] >= 99){
                $backData['level_a'] += 1;
            }elseif($v['score'] >= 80){
                $backData['level_b'] += 1;
            }elseif($v['score'] >= 60){
                $backData['level_c'] += 1;
            }else {
                $backData['level_d'] += 1;
            }
        }
        $this->ajaxReturn($backData);
    }

    /**
     * 测试记录
     **/
    public function testlogs($page=1){
        $memberId = A("Account")->getMemberId();
        $curModel = M("TestsLogs");
        $logList = $curModel
            ->alias("Log")
            ->field('Log.id,T.title,Log.score,Log.create_time as time')
            ->join('__TESTS_LIST__ as T on T.id = Log.tests_id')
            ->where(array("Log.member_id"=>$memberId))
            ->page($page,20)
            ->order("Log.id desc")
            ->select();
        $backData = array(
            "errorCode" =>10000,
            "errorMsg"  =>"success",
            "log_list" =>$logList,
        );
        $this->ajaxReturn($backData);
    }

    /**
     * 错误题
     **/
    public function testerror($page=1){
        $memberId = A("Account")->getMemberId();
        $curModel = M("TestsError");
        $errorList = $curModel
            ->alias("E")
            ->field('E.id,E.selected,Q.ask,Q.correct,Q.answer')
            ->join('__TESTS_QUESTION__ as Q on Q.id = E.question_id')
            ->where(array("E.member_id"=>$memberId))
            ->page($page,10)
            ->order("E.id desc")
            ->select();
        if($errorList !== false){
            //将数据转换为前端可用格式
            foreach($errorList as $k=>$v){
                $errorList[$k]['selected'] = explode(",",$v['selected']);
                $errorList[$k]['correct'] = explode(",",$v['correct']);
                $errorList[$k]['answer'] = unserialize($v['answer']);
            }
            $backData = array(
                "errorCode" =>10000,
                "errorMsg"  =>"success",
                "question" =>$errorList,
            );
        }

        $this->ajaxReturn($backData);
    }



}
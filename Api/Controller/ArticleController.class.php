<?php
namespace Api\Controller;
use Think\Controller;
class ArticleController extends Controller {

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
        $videoUrl = OSS_BASE_URL .'/video/';
        $articleId = I("get.id");
        // 1. 获取数据
        $articleInfo = M('News')
            ->alias("C")
            ->field("C.*,CONCAT('$videoUrl',C.source) as video,CATE.name as cate_name")
            ->join("__MAIN_CATE__ as CATE on C.cid = CATE.id")
            ->where("C.id = $articleId")
            ->fetchSql(false)
            ->find();
        if($articleInfo){
            $articleInfo['content'] = str_replace('src="/Upload/images','src='.XZSS_BASE_URL.'"/images',$articleInfo['content']);
        }

        $courseList = M("Course")
        ->field("id,title,CONCAT('$thumbUrl',thumb) as thumb,isfree,price,has_yh,yh_limit,yh_price,study_num")
        ->limit("4")
        ->select();

        // 1.1 计算优惠是否过期
        if(count($courseList)) {
            foreach($courseList as $key=>$val) {
                if($val['has_yh'] == 1){
                    $oldYhLimit = strtotime($val['yh_limit']);
                    if($oldYhLimit <= time()){
                        $courseList[$key]['has_yh'] = 0;
                    }
                }
            }
        }



        $backData = array(
            "code" =>200,
            "msg"  =>"success",
            "data" =>array(
                "info"  =>$articleInfo,
                "course"=>$courseList
            )
        );
        $this->ajaxReturn($backData);
    }



}
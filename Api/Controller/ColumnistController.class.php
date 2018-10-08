<?php
namespace Api\Controller;
use Think\Controller;
class ColumnistController extends Controller {

    /**
     * 专栏列表
    */
    public function columnistlist($page=1,$cid=0){
        $cateList = M('MainCate')->where('pid !=0 and identification = "columnist"')->fetchSql(false)->select();
        $columnistWhere = array(
            "status"    =>1
        );
        if($cid){
            $columnistWhere['cate_id'] = intval($cid);
        }

        $colunistList = M("Columnist")->field('id,title,thumb,description,isfree,price,subscribers,article_num')->where($columnistWhere)->page($page,15)->select();
        //字符截取
        $String = new \Org\Util\String();
        foreach($colunistList as $k=>$v){
            $colunistList[$k]['title'] = $String->msubstr($v['title'],0,15,$charset="utf-8", $suffix=false);
            $colunistList[$k]['description'] = $String->msubstr($v['description'],0,15,$charset="utf-8", $suffix=false);
        }
        if($cateList && $colunistList !==false){
            $backData = array(
                "code"     =>200,
                "msg"      =>"success",
                "data"  =>array(
                "cateList"      =>$cateList,
                "columnistList" =>$colunistList
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
     * 专栏详情
    */
    public function detail(){
        if(empty($_GET["id"])){
            $backData = array(
              "code"  => 10002,
              "msg"   => "访问参数错误"
            );  
            $this->ajaxReturn($backData); 
        }
        $id=I("get.id");
        $columnistInfo = M("Columnist")
            ->alias("C")
            ->field("C.*,(select count(*) from x_columnist_article where columnist_id = $id and status = 1) as article_num")
            ->fetchSql(false)
            ->where("C.id=$id")
            ->find();
        $articleList = M('ColumnistArticle')->field("id,title,thumb,Date(create_time) as time,view_num,comment_num")->where(array('columnist_id'=>$id))->order('id desc')->limit('5')->select();
        $teacherInfo = M("Teacher")->where(array('id'=>$columnistInfo['teacher_id']))->find();
        if($columnistInfo){
            $columnistInfo['content'] = str_replace('src="/Upload/images','src="http://www.xinzhinetwork.com/Upload/images',$columnistInfo['content']);
        }
        if($teacherInfo){
            $teacherInfo['introduce'] = str_replace('src="/Upload/images','src="http://www.xinzhinetwork.com/Upload/images',$teacherInfo['introduce']);
        }
        $backData = array(
            "code" =>200,
            "msg"  =>"success",
            "data"=>array(
                "columnist"     =>$columnistInfo,
                "articleList"    =>$articleList,
                "teacherInfo"     =>$teacherInfo
            )
        );

        // 2. 查看是否已购买
        $backData['data']['hasColumnistStatus'] = 0;//未购买
        if(!empty($_SERVER["HTTP_X_ACCESS_TOKEN"])) {
            $memberId = $Account->getMemberId();
            if(!is_null($memberId)){
                $where = array(
                    'member_id' =>$memberId,
                    "type"      =>1,
                    "pro_id"    =>$id
                );
                $myColumnist = M("MyGoods")->where($where)->find();
                if($myColumnist){
                    if($myColumnist['end_time'] <= time()){
                        $backData['data']['hasColumnistStatus'] = 2;//购买已到期
                    }else {
                        $backData['data']['hasColumnistStatus'] = 1;//已经购买
                    }
        
                }
            }

        }
        $this->ajaxReturn($backData);
    }

    /**
     * 文章列表
    */
    public function articlelist($cid,$page){
        $columnistInfo = M("Columnist")->field("title,thumb,description,subscribers,article_num")->where(array("id"=>$cid))->find();
        $articleList = M('ColumnistArticle')->field("id,title,type,thumb,Date(create_time) as time,view_num,comment_num")->where(array('columnist_id'=>$cid,'status'=>1))->order('id desc')->page($page,10)->select();
        if($columnistInfo && $articleList !==false){
            $backData = array(
                "code"     =>200,
                "msg"      =>"success",
                "data"  =>array(
                    "columnistInfo"      =>$columnistInfo,
                    "articleList" =>$articleList
                )
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
     * 文章详情
    */
    public function articledetail($id){
        //更新阅读量
        $updateSql = "update __COLUMNIST_ARTICLE__ set `view_num`=view_num+1 where id=".$id;
        $updateResult = M()->execute($updateSql);

        //获取文章内容
        $articleInfo = M("ColumnistArticle")
        ->alias("CA")
        ->field("CA.*,C.thumb as c_thumb")
        ->join("__COLUMNIST__ C on CA.columnist_id = C.id")
        ->where("CA.id=$id")
        ->find();

        //获取评论
        $commentList = M("Comment")
            ->alias("C")
            ->field("C.*,M.nickname,M.avatar,R.content as reply,R.create_time as reply_time")
            ->join("__MEMBER_INFO__ as M on M.member_id=C.member_id")
            ->join("LEFT JOIN __REPLY__ as R on R.comment_id=C.id")
            ->where("C.pro_id=$id and C.type=1 and C.status=1")
            ->page(1,15)
            ->order('C.id desc')
            ->select();

        if($updateResult && $articleInfo){
            $articleInfo['content'] = str_replace('src="/Upload/images','src="http://www.xinzhinetwork.com/Upload/images',$articleInfo['content']);;
            $backData = array(
                "errorCode"     =>10000,
                "errorMsg"      =>"success",
                "articleInfo" =>$articleInfo,
                "commentList" =>$commentList
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
     * 订阅专栏
     * 默认免费时长3个月
     * 1. update columnist
     * 2. insert my_goods
     */
     public function subscriber($proid,$type=0,$memberId=null){
        if($memberId == null){
           $memberId =  A("Account")->getMemberId();
        }
         $model =M();
         $model->startTrans();
         $updateSql = 'update __COLUMNIST__ set `subscribers` = subscribers+1 where id='.$proid;
         $updateColunist =  M()->execute($updateSql);

         $myGoodsData = array(
             "type"         => 1,
             "pro_id"       => $proid,
             "member_id"    => $memberId,
             "start_time"   =>time()
         );
         if ($type == 1) {
             $myGoodsData['end_time'] = strtotime("+1 year");
         }else {
             $myGoodsData['end_time'] = strtotime("+3 month");
         }
         $myGoodsInsert = M("MyGoods")->data($myGoodsData)->add();
         if($updateColunist && $myGoodsInsert){
             $backData = array(
                 "errorCode" => 10000,
                 "errorMsg" => "success",
             );
             $model->commit();
         }else {
             $backData = array(
                 "errorCode" => 10001,
                 "errorMsg" => "数据保存错误"
             );
             $model->rollback();
         }
         $this->ajaxReturn($backData);
     }

}
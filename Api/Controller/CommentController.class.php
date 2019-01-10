<?php
namespace Api\Controller;
use Api\Common\Controller\AuthController;
class CommentController extends AuthController {

    public function doadd(){
        if(IS_POST){
            $model = M();
            $model->startTrans();
            $curModel = M("Comment");
            $memberId = $this->uid;
            $insertData = I('post.');
            $insertData['member_id'] = $memberId;
            $result = $curModel->data($insertData)->add();

            //update
            $tableName = '';
            $type=I("post.type");
            $porid = I("post.pro_id");
            if($type == 1){
                $tableName="__COLUMNIST_ARTICLE__";
            }elseif($type == 2){
                $tableName="__COURSE__";
            }
            $updateSql = "update ".$tableName." set `comment_num`=comment_num+1 where id=".$porid;
            $update = M()->execute($updateSql);

            if($result && $update !==false){
                $model->commit();
                $backData = array(
                    "code" =>200,
                    "msg"  =>"success"
                );
            }else {
                $model->rollback();
                $backData = array(
                    "code" =>13001,
                    "msg"  =>"提交错误请稍后再试"
                );
            }
            $this->ajaxReturn($backData);
        }

    }

    /***获取评论列表**/
    public function vlist(){
        $proid = I("get.proid");
        $type = I("get.type");
        $page=I("get.page/d",1);
        $pageSize = 15;
        $condition = array(
            "C.pro_id" => $proid,
            "C.type"=>$type,
            "C.status"=>1
        );
        $total = M("Comment")->alias("C")->where($condition)->count();
        $list = M("Comment")
            ->alias("C")
            ->field("C.*,M.nickname,M.avatar,R.content as reply,R.create_time as reply_time")
            ->join("__MEMBER_INFO__ as M on M.member_id=C.member_id","LEFT")
            ->join("LEFT JOIN __REPLY__ as R on R.comment_id=C.id")
            ->where($condition)
            ->page($page,$pageSize)
            ->order('C.id desc')
            ->fetchSql(false)
            ->select();
            $backData = array(
                "code" =>200,
                "msg"  =>"success",
                "data"  =>array(
                    "list"      =>$list,
                    "total"     =>$total,
                    "page"  =>$page,
                    "hasMore"=>$total > $page*$pageSize
                )
            );
        $this->ajaxReturn($backData);
    }

}
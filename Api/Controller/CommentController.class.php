<?php
namespace Api\Controller;
use Think\Controller;
class CommentController extends Controller {

    public function index(){
        if(IS_POST){
            $model = M();
            $model->startTrans();
            $curModel = M("Comment");
            $Account = A("Account");
            $memberId = $Account->getMemberId();
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
                    "errorCode" =>10000,
                    "errorMsg"  =>"success"
                );
            }else {
                $model->rollback();
                $backData = array(
                    "errorCode" =>10001,
                    "errorMsg"  =>"提交错误请稍后再试"
                );
            }
            $this->ajaxReturn($backData);
        }

    }

    /***获取评论列表**/
    public function getlist(){
        $proid = I("get.proid");
        $type = I("get.type");
        $page=I("get.page",1);

        $list = M("Comment")
            ->alias("C")
            ->field("C.*,M.nickname,M.avatar,R.content as reply,R.create_time as reply_time")
            ->join("__MEMBER_INFO__ as M on M.member_id=C.member_id")
            ->join("LEFT JOIN __REPLY__ as R on R.comment_id=C.id")
            ->where("C.pro_id=$proid and C.type=$type and C.status=1")
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

}
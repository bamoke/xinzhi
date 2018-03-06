<?php
/**
 * Created by PhpStorm.
 * User: wetz1
 * Date: 2017/10/5
 * Time: 3:24
 */

namespace Manage\Controller;
use Manage\Common\Controller\AuthController;


class CommentController extends AuthController
{
    function index(){
        $curModel = M("Comment");
        $count = $curModel->count();
        $page = new \Think\Page($count,20);
        $page->setConfig('next','下一页');
        $page->setConfig('prev','上一页');
        $paging = $page->show();

        $where = "1 = 1";
//        $list = $curModel->where(array('status'=>1))->select();
        $list = $curModel
            ->alias("C")
            ->field("C.id,C.pro_title,C.content,C.create_time,if(type=1,'专栏','课程') as type,C.status,M.nickname,R.content as reply")
            ->join("__MEMBER_INFO__ as M on M.member_id=C.member_id")
            ->join("LEFT JOIN __REPLY__ as R on R.comment_id=C.id")
            ->order("C.id desc")
            ->limit($page->firstRow.','.$page->listRows)
            ->select();

        $outData = array(
            "list"      =>$list,
            "script"    =>CONTROLLER_NAME."/main"
        );
        $this->assign('output',$outData);
        $this->display();
    }


    /**回复**/
    public function reply(){
        if(IS_POST){
            $replyNum = M("Reply")->where("comment_id=".I("post.comment_id"))->count();
            if($replyNum){
                $backData = array(
                    "status"    =>!!0,
                    "msg"       =>"已经有回复了"
                );
                $this->ajaxReturn($backData);
                return;
            }
            $insertData = array(
                "comment_id"    =>I("post.comment_id"),
                "content"       =>I("post.content"),
                "admin_id"      =>session("uid")
            );
            $insertResult = M("Reply")->add($insertData);
            if($insertResult){
                $backData = array(
                    "status"    =>!!1,
                    "msg"       =>"操作成功"
                );
            }else {
                $backData = array(
                    "status"    =>!!0,
                    "msg"       =>"数据保存出错"
                );
            }
            $this->ajaxReturn($backData);
        }

    }


    /***评论审核***/
    public function changestatus($id,$t){
        $condition = "id=$id";
        $updateData = array(
            "status"    =>$t
        );
        $update = M("Comment")->where($condition)->data($updateData)->save();
        if($update !== false){
            $backData = array(
                "status"    =>!!1,
                "msg"       =>"操作成功"
            );
        }else {
            $backData = array(
                "status"    =>!!0,
                "msg"       =>"数据保存出错"
            );
        }
        $this->ajaxReturn($backData);
    }







}
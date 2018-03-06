<?php
/**
 * Created by PhpStorm.
 * User: joy.wangxiangyin
 * Date: 2017/06/12
 * Time: 10:27
 */

namespace Manage\Controller;


use Manage\Common\Controller\AuthController;

class MemberController extends AuthController
{

    /***View  Index**/
    public function index(){
        $model = M('Member');
        // paging set
        $count = $model->count();
        $page = new \Think\Page($count,20);
        $page->setConfig('next','下一页');
        $page->setConfig('prev','上一页');
        $paging = $page->show();

        $result = $model
            ->alias("M")
            ->field("M.*,MI.nickname,MI.avatar")
            ->join("__MEMBER_INFO__ as MI on MI.member_id = M.id")
            ->page(I('get.p').',20')->select();



        $outData = array(
            'list'      => $result,
            'paging'    => $paging
        );
        $this->assign('output',$outData);
        $this->display();
    }


    /***View  detail page**/
    public function detail($id){
        $model = M('Member');
        $result = $model
            ->alias("a")
            ->field("a.*,b.*")
            ->join("__MEMBER_INFO__ as b ON b.member_id = a.id","LEFT")
            ->where('a.id='.$id)
            ->find();
        $outData['info'] = $result;
        $this->assign('output',$outData);
        $this->display();
    }


    /***Operation 禁用***/
    public function disable($id,$v){
        $model = M('Member');
        $data = array('status'=>$v);
        $result = $model->where('id = '.$id)->save($data);
        if($result){
            $this->ajaxReturn(array('status'=>1,'info'=>'success'));
        }else {
            $this->ajaxReturn(array('status'=>0,'info'=>$model->getError()));
        }
    }

    public function cash(){
        $model = M("Cash");
        $where = array();
        if(!empty($_GET['keyword'])){
            $where['member_name'] = I('get.keyword');
        }
        if(isset($_GET['status']) && $_GET['status'] !== '') {
            $where['status'] = I('get.status');
        }

        $count = $model->where($where)->count();
        $Page = new \Think\Page($count,15);
        $Page->setConfig("next","下一页");
        $Page->setConfig("prev","上一页");
        $show = $Page->show();

        $list = $model
            ->where($where)
            ->field("id,member_name,amount,account_num,bank,pay_way,date(pay_time) as pay_time,date(create_time) as create_time,status")
            ->order("create_time desc")
            ->limit($Page->firstRow.",".$Page->listRows)
            ->select();
        $output['script'] = CONTROLLER_NAME."/main";
        $output['page'] = $show;
        $accountType = array(
            "1"     =>"支付宝",
            "2"     =>"微信",
            "3"     =>"银行卡"
        );
        $this->assign('output',$output);
        $this->assign('cashData',$list);
        $this->assign('accountType',$accountType);
        $this->display();
    }


    public function confirm_cash ($id){
        $cashModel = M('Cash');
        $where = array(
            "id"    =>$id,
            "status"    =>0
        );
        $cashUpdateData = array(
            "status"    =>1,
            "pay_time"  =>date("Y-m-d H:i:s")
        );
        $cashInfo = $cashModel->where($where)->find();
        $cashModel->startTrans();
        $cashUpdate = $cashModel->where($where)->fetchSql(false)->save($cashUpdateData);
        $memberUpdate = M()->execute("update __MEMBER__ set `capital` = (`capital` - ".$cashInfo['amount'].") where id = ".$cashInfo['member_id']);
        if($cashUpdate && $memberUpdate){
            $cashModel->commit();
                $backData['status'] = 1;
            $backData['msg'] = "操纵成功";
            $backData['cashsql'] = $cashUpdate;
            $backData['sql'] = $memberUpdate;
        }else {
            $cashModel->rollback();
            $backData['status'] = 0;
            $backData['msg'] = "数据错误";
        }
        $this->ajaxReturn($backData);

    }


    public function recharge(){
        $model = M("Recharge");
        $where = array();
        if(!empty($_GET['keyword'])){
            $where['member_name'] = I('get.keyword');
        }
        if(isset($_GET['status']) && $_GET['status'] !== '') {
            $where['status'] = I('get.status');
        }

        $count = $model->where($where)->count();
        $Page = new \Think\Page($count,15);
        $Page->setConfig("next","下一页");
        $Page->setConfig("prev","上一页");
        $show = $Page->show();

        $list = $model
            ->where($where)
            ->field("id,member_name,amount,date(create_time) as create_time,status")
            ->order("create_time desc")
            ->limit($Page->firstRow.",".$Page->listRows)
            ->select();
        $output['script'] = CONTROLLER_NAME."/main";
        $output['page'] = $show;
        $this->assign('output',$output);
        $this->assign('rechargeData',$list);
        $this->display();
    }

    public function confirm_recharge($id){
        $reModel = M("Recharge");
        $reUpdateData = array('status'=>1);
        $reWhere = array('id'=>$id,'status'=>0);
        $reInfo = $reModel->where($reWhere)->find();
        if(!$reInfo){
            $backData = array('status'=>0,"msg"=>"已确认支付的记录");
            return $this->ajaxReturn($backData);
        }
        $reModel->startTrans();
        $reUpdate = $reModel->where($reWhere)->save($reUpdateData);
        $membUpdate = M()->execute("Update __MEMBER__ set `capital` = (`capital` + ".$reInfo['amount'].") where id =".$reInfo['member_id']);
        if($reUpdate && $membUpdate){
            $backData = array(
                "status"    =>1,
                "msg"       =>"操作成功"
            );
            $reModel->commit();
        }else {
            $backData = array(
                "status"    =>0,
                "msg"       =>"数据错误"
            );
            $reModel->rollback();
        }
        $this->ajaxReturn($backData);
    }

}
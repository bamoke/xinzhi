<?php
/**
 * Created by PhpStorm.
 * User: wetz1
 * Date: 2017/7/1
 * Time: 7:26
 */

namespace Manage\Controller;
use Manage\Common\Controller\AuthController;

class OrdersController extends AuthController
{

    public function index(){
        $where = array();
        $whereStr = '1=1';
        if(I('get.status') != ''){
            $whereStr .= ' and O.status ="'.I('get.status').'"';
        }
        if(!empty($_GET['s_date'])){
            $whereStr .= ' and Date(O.create_time) > '.I('get.s_date');
        }
        if(!empty($_GET['e_date'])){
            $whereStr .= ' and Date(O.create_time) < '.I('get.e_date');;
        }
        if(!empty($_GET['keyword'])){
            $whereStr .= ' and (O.order_num ="'.I("get.keyword").'")';
        }
        $where['_string'] =$whereStr;
        $modelOrder = M('Orders');
        $count = $modelOrder->alias("O")->where($where)->count();
        $Page = new \Think\Page($count,15);
        $Page->setConfig("next","下一页");
        $Page->setConfig("prev","上一页");
        $show = $Page->show();

        $orderList = $modelOrder
            ->alias("O")
            ->field("O.*,M.nickname,M.avatar")
            ->join('__MEMBER_INFO__ as M on O.member_id = M.member_id')
            ->where($where)
            ->limit($Page->firstRow.','.$Page->listRows)
            ->order('id desc')
            ->fetchSql(false)
            ->select();
//        echo $orderList;
        $output['script'] = CONTROLLER_NAME."/index";
        $output['page'] = $show;
        $output['search'] = $whereStr;
        $this->assign('output',$output);
        $this->assign('orderData',$orderList);
        $this->display();
    }

    public function detail($id){
        $orderInfo = M('Orders')->where('id = '.$id)->find();
        $output['script'] = CONTROLLER_NAME."/add";
        $output['orderInfo'] = $orderInfo;
        $output['goodsInfo'] = unserialize($orderInfo['goods']);

        $memberInfo = M('MemberInfo')->field("nickname")->where(array('member_id'=>$orderInfo['member_id']))->find();
        $output['memberInfo'] = $memberInfo;
        $this->assign('output',$output);
        $this->assign('pageName','订单详情');
        $this->display();
    }

    public function operation($id,$status){
        $result = M("Orders")->where(array("id"=>$id))->data(array('status'=>$status))->save();
        if($result){
            $backData=array(
                "status"    =>1,
                "msg"       =>"操纵成功"
            );
        }else {
            $backData=array(
                "status"    =>0,
                "msg"       =>"操作失败"
            );
        }
        $this->ajaxReturn($backData);
    }








}
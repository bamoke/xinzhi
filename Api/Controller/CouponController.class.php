<?php
namespace Api\Controller;
use Api\Common\Controller\AuthController;
class CouponController extends AuthController {

    /**
     * 领取优惠券
     */
    public function fetch(){

    }

 

    /**
     * My 
     */
    public function vlist(){
        $page = I("get.page/d",1);
        $pageSize = 20;
        $condition = array(
            "CL.member_id"   =>$this->uid
        );
        $total = M("CouponLog")->alias("CL")->where($condition)->count();
        $list = M("CouponLog")
        ->alias("CL")
        ->field("C.*")
        ->join("__COUPON__ as C on C.id = CL.coupon_id")
        ->where($condition)
        ->page($page,$pageSize)
        ->order("CL.id desc")
        ->select();

        if($list !== false){
            if(count($list)){
                foreach($list as $key=>$val){
                    $list[$key]["isOverdue"] = 0;
                    if(strtotime($val['deadline']) <= time()){
                        $list[$key]["isOverdue"] = 1;
                    }
                }
            }
            $backData = array(
                "code" =>200,
                "msg"  =>"success",
                "data"  =>array(
                    "page"      =>$page,
                    "total"     =>$total,
                    "list"      =>$list,
                    "hasMore"   =>$total > $page*$pageSize
                )
            );   
        }else {
            $backData = array(
                "code" =>13001,
                "msg"  =>"系统繁忙",
            );
        }
        $this->ajaxReturn($backData);
    }

}
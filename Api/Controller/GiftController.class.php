<?php
namespace Api\Controller;
use Think\Controller;
class GiftController extends Controller {

    /** 
     * 礼品领取
     * 
     */
    public function index($key){
        $giftInfo = M("Present")->field("id,value,pro_type")->where("secret = '$key'")->find();
        $backData = array(
            "errorCode" =>10000,
            "errorMsg"  =>"success",
            "info"     =>$giftInfo
        );
        $this->ajaxReturn($backData);
    }

    /** 
     * 收下礼包
     * 逻辑判断,确认是否已经拥有，
     */
    public function acceptance($id){
        $giftInfo = M("Present")->field("pro_id,pro_type,status")->where("id = $id")->find();
        //1.1 礼包是否已经被领取
        if ($giftInfo['status'] == 1) {
            $backData = array(
                "errorCode" => 10001,
                "errorMsg" => "对不起，礼包已经被领取了！"
            );
            return $this->ajaxReturn($backData);
        }

        //1.2 是否已经登录
        $account = A("Account");
        $memberId = $account->getMemberId();
        if (!$memberId) {
            $backData = array(
                "errorCode" => 10002,
                "errorMsg" => "请重新登录"
            );
            return $this->ajaxReturn($backData);
        }

        //1.3 是否已经拥有
        $goodsWhere =array(
            "member_id"     =>$memberId,
            "type"          =>$giftInfo['pro_type'],
            "pro_id"        =>$giftInfo['pro_id']
        );
        $hasNum = M("MyGoods")->where($goodsWhere)->count();
        if($hasNum > 0){
            $backData = array(
                "errorCode" => 10003,
                "errorMsg" => "您已经购买过此产品，无需重复获取"
            );
            return $this->ajaxReturn($backData);
        }

        //1.4 领取
        $updateData = array(
            "status"    =>1,
            "sccept_id" =>$memberId
        );
        $result = M("Present")->data($updateData)->where("id=$id")->save();
        if(!$result){
            $backData = array(
                "errorCode" => 10004,
                "errorMsg" => "操作错误请稍后再试"
            );
        }else {
            $backData = array(
                "errorCode" => 10000,
                "errorMsg" => "OK"
            );
        }
        return $this->ajaxReturn($backData);
    }

}
<?php
namespace Api\Controller;
use Think\Controller;
class GiftController extends Controller {

    /** 
     * 礼品领取
     * 逻辑判断,确认是否已经拥有，
     */
    public function index($key){
        $giftInfo = M("Present")->field("id,value,type")->where("secret = '$key'")->find();
        var_dump($giftInfo);
        $backData = array(
            "errorCode" =>10000,
            "errorMsg"  =>"success",
            "info"     =>$giftInfo
        );
        $this->ajaxReturn($backData);
    }

}
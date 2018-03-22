<?php
namespace Api\Controller;
use Think\Controller;
class SurveyController extends Controller {

    /** 
     * 调查问卷
     * View
     * 
     */
    public function index($key){
        $giftInfo = M("Present")->field("id,pro_type,content")->where("secret = '$key'")->find();
        $backData = array(
            "errorCode" =>10000,
            "errorMsg"  =>"success",
            "info"     =>array(
                "id"        =>$giftInfo['id'],
                "proType"   =>$giftInfo["pro_type"],
                "content"   =>unserialize($giftInfo["content"])
            )
        );
        $this->ajaxReturn($backData);
    }

    /** 
     * Action
     */
    public function dopoll(){
        var_dump($_POST);


/*         if($updateResult && $myGoodsInsert){
            $backData = array(
                "errorCode" => 10000,
                "errorMsg" => "OK"
            );
            $model->commit();
        }else {
            $backData = array(
                "errorCode" => 10004,
                "errorMsg" => "系统繁忙,请稍后再试"
            );
            $model->rollback();
        }
        return $this->ajaxReturn($backData); */
    }

}
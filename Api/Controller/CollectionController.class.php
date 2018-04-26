<?php
namespace Api\Controller;
use Think\Controller;
class CollectionController extends Controller {
    private $memberId;
    public function _initialize(){
        $this->memberId = A("Account")->getMemberId();
        if(!$this->memberId){
            $backData = array(
                "errorCode" =>11000,
                "errorMsg"  =>"请重新登陆"
            );
            $this->ajaxReturn($backData);
        }
    }

    /**
     * collection operation
     */
    public function index(){
        $model = M("Collection");
        $queryData = array(
            "member_id" =>$this->memberId,
            "type"      =>I("get.type"),
            "pro_id"    =>I("get.proid")
        );
        if(I("get.actype") == 1){
            $isExist = $model->field("id")->where($queryData)->find();
            if($isExist){
                $result = $model->where("id=".$isExist['id'])->data(array("status"=>1))->save();
            }else {
                $result = $model->data($queryData)->add();
            }
        }else {
            $result = $model->where($queryData)->data(array("status"=>0))->save();
        }
        if($result){
            $backData = array(
                "errorCode" =>10000,
                "errorMsg"  =>"success",
            );
        }else {
            $backData = array(
                "errorCode" =>10001,
                "errorMsg"  =>"系统繁忙",
            );
        }

        $this->ajaxReturn($backData);
    }

    /**
     * My collection
     */
    public function mycollection($type,$page=1){
        $modelName = $type == 1? "__COLUMNIST_ARTICLE__":"__COURSE_SECTION__";
        $list = M("Collection")
        ->alias("C")
        ->field("C.pro_id,Pro.title")
        ->join($modelName." as Pro on Pro.id = C.pro_id")
        ->where("C.member_id = ".$this->memberId." and C.type=".$type." and C.status=1")
        ->page($page,20)
        ->select();
        if($list !== false){
            $backData = array(
                "errorCode" =>10000,
                "errorMsg"  =>"success",
                "list"      =>$list
            );   
            if(count($list) < 20 ){
                $backData['hasMore'] = !!0;
            }else {
                $count = M("Collection")->alias("C")->where("C.member_id = ".$this->memberId." and C.type=".$type." and C.status=1")->count();
                if($count > $page*20){
                    $backData['hasMore'] = !!1;
                }else {
                    $backData['hasMore'] = !!0;
                }
            }
        }else {
            $backData = array(
                "errorCode" =>10001,
                "errorMsg"  =>"系统繁忙",
            );
        }
        $this->ajaxReturn($backData);
    }

}
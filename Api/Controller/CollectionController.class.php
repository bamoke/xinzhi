<?php
namespace Api\Controller;
use Api\Common\Controller\AuthController;
class CollectionController extends AuthController {
    public function docollect () {
        $model = M("Collection");
        $data = array(
            "member_id" =>$this->uid,
            "type"      =>I("get.type"),
            "pro_id"    =>I("get.proid")
        );
        $collectionInfo = $model->where($data)->find();
        if($collectionInfo) {
            $status = $collectionInfo['status'] == 1?0:1;
            $result = $model->where(array('id'=>$collectionInfo['id']))->data(array("status"=>$status))->fetchSql(false)->save();
        }else {
            $result = $model->data($data)->fetchSql(false)->add();
        }
        if($result ===false) {
            $backData = array(
                "code" =>13001,
                "msg"  =>"操作失败",
            );
            $this->ajaxReturn($backData);
        }
        $backData = array(
            "code" =>200,
            "msg"  =>"success",
        );
        $this->ajaxReturn($backData);

    }

 

    /**
     * My collection
     */
    public function vlist(){
        $thumbUrl = XZSS_BASE_URL .'/thumb/';
        $type=I("get.type/d",1);
        $page = I("get.page/d",1);
        $pageSize = 20;
        $modelName = $type == 1? "__COLUMNIST__":"__COURSE__";
        $condition = array(
            "C.member_id"   =>$this->uid,
            "C.type"        =>$type
        );
        $total = M("Collection")->alias("C")->where($condition)->count();
        $field = "Pro.id,Pro.title,CONCAT('$thumbUrl',Pro.thumb) as thumb,Pro.has_yh,Pro.yh_limit,Pro.yh_price,Pro.price,Pro.isfree";
        if($type ==1) {
            $field .= "";
        }elseif($type==2){
            $field .= ",Pro.study_num";
        }
        $list = M("Collection")
        ->alias("C")
        ->field($field)
        ->join($modelName." as Pro on Pro.id = C.pro_id")
        ->where($condition)
        ->page($page,$pageSize)
        ->select();
        if($list !== false){

            // 1.1 计算优惠是否过期
            if(count($list)) {
                foreach($list as $key=>$val) {
                    if($val['has_yh'] == 1){
                        $oldYhLimit = strtotime($val['yh_limit']);
                        if($oldYhLimit <= time()){
                            $list[$key]['has_yh'] = 0;
                        }
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
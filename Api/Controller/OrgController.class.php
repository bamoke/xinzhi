<?php
namespace Api\Controller;
use Think\Controller;
class OrgController extends Controller {

    public function detail($id){
        if(empty($_GET['id'])) {
            $backData = array(
                "code" => 10001,
                "msg" => "参数错误"
            );
            return $this->ajaxReturn($backData);
        }
        $thumbUrl = XZSS_BASE_URL .'/thumb/';
        $orgId = I("get.id");
        $orgInfo = M("Org")
        ->field("*,CONCAT('$thumbUrl',logo) as logo")
        ->where(array("id"=>$orgId))
        ->find();
        if($orgInfo){
            $orgInfo['introduce'] = str_replace('src="/gongfu/Upload/images','src="'.XZSS_BASE_URL.'/images',$orgInfo['introduce']);
        }

        $mainCondition= array(
            "org_id"    =>$orgId,
            "status"    =>1
        );
        $page = 1;
        $courseTotal = M("Course")->where($mainCondition)->count();
        $courseList = M("Course")
        ->field("id,title,CONCAT('$thumbUrl',thumb) as thumb,has_yh,yh_limit,yh_price")
        ->where($mainCondition)
        ->page($page,10)
        ->order("id desc")
        ->select();

       // 1.1 计算优惠是否过期
       if(count($courseList)) {
            foreach($courseList as $key=>$val) {
                if($val['has_yh'] == 1){
                    $oldYhLimit = strtotime($val['yh_limit']);
                    if($oldYhLimit <= time()){
                        $courseList[$key]['has_yh'] = 0;
                    }
                }
            }
        }

        // booking list
        $bookingTotal = M("Booking")->where($mainCondition)->count();
        $bookingList = M("Booking")
        ->field("id,title,CONCAT('$thumbUrl',thumb) as thumb,has_yh,yh_limit,yh_price")
        ->where($mainCondition)
        ->page($page,10)
        ->order("id desc")
        ->select();

        // 1.1 计算优惠是否过期
       if(count($bookingList)) {
            foreach($bookingList as $key=>$val) {
                if($val['has_yh'] == 1){
                    $oldYhLimit = strtotime($val['yh_limit']);
                    if($oldYhLimit <= time()){
                        $bookingList[$key]['has_yh'] = 0;
                    }
                }
            }
        }

        $backData = array(
            "code"=>200,
            "msg"=>"success",
            "data"      =>array(
                "baseInfo"  =>$orgInfo,
                "courseInfo"    =>array(
                    "list"  =>$courseList,
                    "total" =>$courseTotal,
                    "page"  =>$page,
                    "hasMore"   =>$courseTotal > $page*10
                ),
                "bookingInfo"    =>array(
                    "list"  =>$bookingList,
                    "total" =>$bookingTotal,
                    "page"  =>$page,
                    "hasMore"   =>$bookingTotal > $page*10
                )
            )
        );
        $this->ajaxReturn($backData);
    }

}
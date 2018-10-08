<?php
namespace Api\Controller;
use Think\Controller;
class DownloadController extends Controller {

    /** 
     * 下载
     * 
     */
    public function index($id=null){
        // $info = M("Download")->field("id,title,status,download_count,Date(create_time) as time,content,source")->where("id = $id")->find();
        if($info){
/*             if(!$info['status']){
                $backData = array(
                    "errorCode" =>10002,
                    "errorMsg"  =>"资料已下架"
                );
            }else {
                $backData = array(
                    "errorCode" =>10000,
                    "errorMsg"  =>"success",
                    "info"      =>$info
                );
            }

        }else {
            $backData = array(
                "errorCode" =>10001,
                "errorMsg"  =>"系统繁忙"
            );
        } */

        // $this->ajaxReturn($backData);
        // $this->assign('info',$info);
       
    }
    $this->display();
    }

 

}
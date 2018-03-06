<?php
/**
 * Created by PhpStorm.
 * User: joy.wangxiangyin
 */

namespace Manage\Controller;


use Manage\Common\Controller\AuthController;

class SysController extends AuthController
{


    public function index()
    {
        $sysInfo = array(
            "php_version" => PHP_VERSION,
            "os" => $_SERVER['SERVER_SOFTWARE'],
            "mysql_version" => $this->_get_mysql_version(),
            "time" => date("Y-m-d H:i:s", time()),
            "pc" => $_SERVER['SERVER_NAME'],
            "port" => $_SERVER['SERVER_PORT'],
            "language" => $_SERVER['HTTP_ACCEPT_LANGUAGE'],
            "osname" => php_uname()
        );
        $this->assign("output", $this->output);
        $this->assign("sysInfo", $sysInfo);
        $this->display();
    }

    public function conf()
    {
        $config = M("Site_config")->where('id=1')->find();
        $output['config'] = $config;
        $output['script'] = CONTROLLER_NAME . "/conf";
        $this->assign("output", $output);
        $this->display();
    }

    public function save_conf()
    {
        if (IS_POST) {
            $model = D('Site_config');
            if ($model->create() && ($model->where('id=1')->save()) !== false) {
                $backData = array(
                    "status" => 1,
                    "msg" => "修改成功"
                );
            } else {
                $backData = array(
                    "status" => 0,
                    "msg" => $model->getError()
                );
            }
            $this->ajaxReturn($backData);
        }
    }

    /**
     * 分类管理
     */
    public function cate()
    {
        $curModel = D('Category');
        $result = $curModel->select();
        $tree = $this->_createTree($result);
        $outData = array(
            "script" => CONTROLLER_NAME . "/cate",
            "tree" => $tree
        );
        $this->assign('output', $outData);
        $this->display();
    }

    public function save_cate()
    {
        $curModel = D('Category');
        $result = $curModel->create();
        $backData = array();
        if ($result) {
            $curModel->add();
            $backData['status'] = 1;
            $backData['msg'] = "保存成功";
        } else {
            $backData['status'] = 0;
            $backData['msg'] = $curModel->getError();
        }
        $this->ajaxReturn($backData);
    }

    public function del_cate($id){
        $curModel = D('Category');
        $list = $curModel->where(array('pid'=>$id))->count();
        if($list > 0){
            $backData = array(
                "status"=>0,
                "msg"   =>"请先删除子类"
            );
        }else {
            $result = $curModel->where(array('id'=>$id))->delete();
            if($result){
                $backData = array(
                    "status"=>1,
                    "msg"   =>"删除成功"
                );
            }else {
                $backData = array(
                    "status"=>0,
                    "msg"   =>$curModel->getError()
                );
            }
        }
        $this->ajaxReturn($backData);
    }

    public function menu()
    {
        $this->display();
    }

    /**
     * 获取Mysql版本
     * @return string
     */
    private function _get_mysql_version()
    {
        $model = M();
        $version = $model->query("select version() as ver");
        return $version[0]['ver'];
    }

    /**
     * 创建分类树
     * @param   $list   array
     * @param   $pid    number
     * @param   number
     * @param   number
     * @return  string
     */
    private function _createTree($list,$pid=0,$level=1,$max=3)
    {
        $tree = '';
        if (is_array($list) && $level <= $max) {
            foreach ($list as $k => $v) {
                if ($v['pid'] == $pid) {
                    if($level == 1 ){
                        $tree .='
                            <div class="list">
                                <div class="parent">
                                    <div class="status">+</div>
                                    <div class="item" data-id="' . $v["id"] . '" data-key="' . $v['identification'] . '">
                                        <div class="caption">' . $v['name'] . '</div>
                                        <div class="operation">
                                            <a class="js-add-child" href="javascript:void(0)">添加子类</a><a href="javascript:void(0)" class="js-del-parent">删除</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="child">'.$this->_createTree($list,$v['id'],$level+1).'</div>
                            </div>';
                    }elseif($level == 2){
                        $tree .= '
                        <div class="item" data-id="' . $v["id"] . '" data-key="' . $v['identification'] . '">
                        <div class="caption"><span>——</span>'.$v["name"].'</div>
                            <div class="operation">
                                <a href="javascript:void(0)" class="js-del-child">删除</a>
                            </div>
                        </div>
                        ';
                    }
                }

            }
        }
        return $tree;
    }

    /***banner**/
    public function banner(){
        if(I('get.t') == 'add'){
            return $this->banner_add();
        }elseif(I('get.t') == 'edit'){
            return $this->banner_edit(I('get.id'));
        }else {
            $bannerList = M("Banner")->page(1,20)->select();
            $outData = array(
                "script" => CONTROLLER_NAME . "/banner"
            );
            $this->assign('output',$outData);
            $this->assign("bannerList",$bannerList);
            $this->assign("pageTitle","Banner列表");
            $this->display('banner');
        }

    }

    public function savebanner(){
        $type = I('get.t');
        $saveData = I('post.');
        if($type=='add' && !$_FILES['img']['tmp_name']){
            $backData['status'] = 0;
            $backData['msg'] = "请上传图片";
            return $this->ajaxReturn($backData);
        }
        if($_FILES['img']['tmp_name']){
            //图片处理
            $upload_conf=array(
                'maxSize' => 3145728,
                'rootPath' => ROOT_DIR.'/Upload/',
                'savePath' => 'banner/',
                'saveName' => md5(time().I("session.uid")),
                'exts' => array('jpg', 'gif', 'png', 'jpeg'),
                'autoSub' => false,
                'subName' => date("Ym",time())
            );
            $upload = new \Think\Upload($upload_conf);
            $uploadResult = $upload->uploadOne($_FILES['img']);
            if(!$uploadResult){
                $backData['status'] = 0;
                $backData['msg'] = $upload->getError();
                return $this->ajaxReturn($backData);
            }
            $saveData['img']=$uploadResult['savename'];
        }

        $model = M('Banner');
        if($type == 'add'){
            $result = $model->data($saveData)->add();
        }else {
            if(!$_FILES['img']['tmp_name']){
                unset($saveData['img']);
            }
            $result = $model->where("id=".I('post.id'))->data($saveData)->save();
        }
        if($result !== false){
            $oldImg = '';
            if($type != 'add' && $_FILES['img']['tmp_name']){
                $oldImg = $upload_conf['rootPath'].$upload_conf['savePath'].$saveData['old_img'];
                unlink($oldImg);
            }
            $backData['status'] = 1;
            $backData['msg'] = "操作成功";
            $backData['jump'] = U('banner');
            $backData['test'] = $oldImg;

        }else {
            $backData['status'] = 0;
            $backData['msg'] = "操作失败!请稍后再试或反馈给开发人员";
        }
        $this->ajaxReturn($backData);
    }



    protected function banner_add(){
        $this->assign("pageTitle","添加banner");
        $outData = array(
            "script" => CONTROLLER_NAME . "/banner"
        );
        $this->assign('output',$outData);
        $this->display('banner-add');
    }

    protected function banner_edit($id){
        $this->assign("pageTitle","编辑banner");
        $bannerInfo = M("Banner")->where("id=$id")->find();
        $outData = array(
            "script" => CONTROLLER_NAME . "/banner",
            "info"      =>$bannerInfo
        );
        $this->assign('output',$outData);
        $this->display('banner-edit');
    }

    public function banner_del($id){
        $bannerInfo = M("Banner")->where("id=$id")->find();
        $result = M("Banner")->where("id=$id")->delete();
        if($result !== false ){
            $backData['status'] = 1;
            $backData['msg'] = "操作成功";
            $oldImg = ROOT_DIR.'/Upload/banner/'.$bannerInfo['img'];
            @unlink($oldImg);
        }else {
            $backData['status'] = 0;
            $backData['msg'] = "操作失败";
        }
        $this->ajaxReturn($backData);
    }

}
<?php
namespace Manage\Controller;
use Manage\Common\Controller\AuthController;

class IndexController extends AuthController {
    public function index(){
        $output['title'] = "Wiki 测试版";
        $this->assign("output",$output);
        $this->display();
    }

    /**/
    public function test(){
        $output['title'] = "Wiki Pjax";
        $output['script'] = CONTROLLER_NAME."/test";
        $this->assign("output",$output);
        $this->display();
    }
}
<?php
namespace Web\Common\Controller;
use Think\Controller;
class BaseController extends Controller
{
    protected function _initialize(){
        $this->set_site_config();
        $this->assign('curPage',strtolower(CONTROLLER_NAME));
    }

    public function set_site_config(){
        $siteInfo = M("SiteConfig")->where('id=1')->find();
        $this->assign('siteConfig',$siteInfo);
    }
}
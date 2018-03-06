<?php
/**
 * Created by PhpStorm.
 * User: joy.wangxiangyin
 */

namespace Manage\Controller;

use Manage\Common\Controller\AuthController;

class RbacController extends AuthController
{
    function index(){
        $this->display();
    }
}
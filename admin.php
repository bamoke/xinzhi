<?php
/**
 * Created by PhpStorm.
 * User: joy.wangxiangyin
 * Date: 2015/10/27
 * Time: 17:21
 */
//php环境检测
if(version_compare(PHP_VERSION,'5.3.0','<')) die('require PHP > 5.3.0 !');

//设置开发模式；当前开启调试模式
define("APP_DEBUG",true);
define('STATIC_DEBUG',time());

//定义应用名称,3.2可以不用定义
define("APP_NAME","欣志小程序后台管理系统V1.0.1");

//定义应用目录
define("APP_PATH","./");
define("ROOT","/xinzhi");

//设置是否生成目录安全文件
define("BUILD_DIR_SECURE",false);

//生成模块并绑定,默认生成Home模块； (另有$_GET['m']方法绑定)
//define("BIND_MODULE","Admin");
$_GET['m']='Manage';

//生成控制器并绑定，默认生成Index控制器； (另有$_GET['c']方法绑定)
//define("BIND_CONTROLLER","Index");

define("ROOT_DIR", dirname(__FILE__));
define("UPLOAD", ROOT . "/Upload");
//引入PHP框架文件
require("../ThinkPHP/ThinkPHP.php");
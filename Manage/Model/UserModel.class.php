<?php
/**
 * Created by PhpStorm.
 * User: joy.wangxiangyin
 * Date: 2017/6/14
 * Time: 16:53
 */

namespace Admin\Model;
use Think\Model;

class UserModel extends Model
{

    protected $_validate = array(
        array("username", '/[A-Za-z0-9-]{4,12}/', "用户名格式不正确", 0, 'regex'),
        array("username", '', "用户已存在", 0, 'unique'),
        array("email", 'email', "邮箱格式不正确", 2, 'regex'),
    );
}

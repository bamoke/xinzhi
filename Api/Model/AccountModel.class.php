<?php
/**
 * Created by PhpStorm.
 * User: wetz1
 * Date: 2017/7/6
 * Time: 21:59
 */

namespace Web\Model;
use Think\Model;


class AccountModel extends Model
{
    protected $tableName = 'member';

    protected $_validate = array(
        array('username','require','用户名不能为空',0,'regex',1),
        array('username','','用户名已经存在',0,'unique',1)
    );

}
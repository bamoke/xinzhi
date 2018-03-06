<?php

/**
 * Created by PhpStorm.
 * User: wetz1
 * Date: 2017/7/6
 * Time: 3:37
 */
namespace Web\Model;
use Think\Model;
class CashModel extends Model
{
    protected $_validate =array(
        array('amount','currency','请输入合法数值',0,'regex',1)
    );
}
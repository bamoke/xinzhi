<?php
/**
 * Created by PhpStorm.
 * User: joy.wangxiangyin
 * Date: 2016/9/27
 * Time: 9:34
 */

namespace Manage\Model;


use Think\Model;

class CategoryModel extends Model
{
    protected $tableName ="main_cate";

    public function getCate($type){
        $where= array(
            "identification"=>$type,
            "pid"   =>array('NEQ',0)
        );
        return $this->where($where)->select();
    }

}